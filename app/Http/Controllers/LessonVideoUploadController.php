<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizeMediaUpload;
use App\Models\Course;
use App\Models\LessonVideoUpload;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LessonVideoUploadController extends Controller
{
    public function start(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'video_duration_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
        ]);

        $upload = LessonVideoUpload::create([
            'token' => (string) Str::uuid(),
            'teacher_id' => $request->user()->id,
            'course_id' => $course->id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'lesson_order' => $validated['order'] ?? ((int) $course->lessons()->max('order')) + 1,
            'video_duration_seconds' => $validated['video_duration_seconds'],
            'total_chunks' => 0,
            'status' => 'uploading',
        ]);

        return response()->json(['upload_id' => $upload->token], 201);
    }

    public function storeChunk(Request $request, Course $course, string $uploadToken)
    {
        $this->authorize('manage', $course);

        $upload = $this->findUpload($request, $course, $uploadToken);
        abort_unless($upload->status === 'uploading', 409, 'رفع الفيديو غير متاح.');

        $validated = $request->validate([
            'video_chunk' => ['required', 'file', 'max:10240'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1'],
        ]);

        abort_unless($validated['chunk_index'] < $validated['total_chunks'], 422);

        if ($upload->total_chunks !== 0 && $upload->total_chunks !== (int) $validated['total_chunks']) {
            abort(409, 'عدد أجزاء الفيديو غير متطابق.');
        }

        $upload->total_chunks = (int) $validated['total_chunks'];
        $upload->save();

        $directory = $this->chunksDirectory($upload->token);
        $chunkPath = Storage::disk('private')->putFileAs(
            $directory,
            $validated['video_chunk'],
            sprintf('%08d.part', $validated['chunk_index']),
        );

        if ($chunkPath === false) {
            throw new RuntimeException('Unable to store a lesson video chunk.');
        }

        $upload->touch();

        return response()->json([
            'chunk_index' => (int) $validated['chunk_index'],
            'received' => true,
        ]);
    }

    public function finish(Request $request, Course $course, string $uploadToken)
    {
        $this->authorize('manage', $course);

        $upload = $this->findUpload($request, $course, $uploadToken);
        $lock = Cache::lock('lesson-video-upload:' . $upload->token, 86400);

        if (!$lock->get()) {
            return response()->json(['message' => 'جارٍ تجهيز هذا الفيديو بالفعل.'], 409);
        }

        try {
            $upload->refresh();

            if ($upload->status === 'completed' && $upload->lesson_id) {
                return response()->json([
                    'redirect' => route('lessons.manage', $course),
                ]);
            }

            abort_unless($upload->status === 'uploading', 409, 'حالة رفع الفيديو غير صالحة.');
            abort_if($upload->total_chunks < 1, 422, 'لم يتم استلام أجزاء الفيديو.');

            $disk = Storage::disk('private');
            $chunksDirectory = $this->chunksDirectory($upload->token);

            for ($index = 0; $index < $upload->total_chunks; $index++) {
                abort_unless(
                    $disk->exists($chunksDirectory . '/' . sprintf('%08d.part', $index)),
                    422,
                    'لم يكتمل رفع الفيديو. أعد المحاولة.'
                );
            }

            $assembledPath = $this->assembledPath($upload->token);
            $this->assembleChunks($disk, $chunksDirectory, $assembledPath, $upload->total_chunks);

            if (!$this->hasMp4FileType($disk->path($assembledPath))) {
                $disk->delete($assembledPath);

                throw ValidationException::withMessages([
                    'video' => 'الملف المرفوع ليس فيديو MP4 صالحًا.',
                ]);
            }

            $lesson = DB::transaction(function () use ($course, $upload, $assembledPath) {
                $lesson = $course->lessons()->create([
                    'title' => $upload->title,
                    'content' => $upload->content,
                    'order' => $upload->lesson_order,
                    'video' => $assembledPath,
                    'video_duration_seconds' => $upload->video_duration_seconds,
                ]);

                $upload->forceFill([
                    'lesson_id' => $lesson->id,
                    'status' => 'completed',
                ])->save();

                FinalizeMediaUpload::dispatch(
                    'lesson_video',
                    $lesson->id,
                    $assembledPath,
                    null,
                    true,
                )->afterCommit();

                return $lesson;
            });

            $disk->deleteDirectory($chunksDirectory);

            return response()->json([
                'lesson_id' => $lesson->id,
                'redirect' => route('lessons.manage', $course),
            ], 201);
        } finally {
            $lock->release();
        }
    }

    private function findUpload(Request $request, Course $course, string $uploadToken): LessonVideoUpload
    {
        return LessonVideoUpload::query()
            ->where('token', $uploadToken)
            ->where('teacher_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    private function chunksDirectory(string $token): string
    {
        return 'pending/lesson-uploads/' . $token . '/chunks';
    }

    private function assembledPath(string $token): string
    {
        return 'pending/videos/' . $token . '.mp4';
    }

    private function assembleChunks(
        FilesystemAdapter $disk,
        string $chunksDirectory,
        string $assembledPath,
        int $totalChunks,
    ): void {
        $disk->makeDirectory(dirname($assembledPath));
        $destination = fopen($disk->path($assembledPath), 'wb');

        if ($destination === false) {
            throw new RuntimeException('Unable to create the assembled lesson video.');
        }

        try {
            for ($index = 0; $index < $totalChunks; $index++) {
                $chunkPath = $chunksDirectory . '/' . sprintf('%08d.part', $index);
                $chunk = fopen($disk->path($chunkPath), 'rb');

                if ($chunk === false) {
                    throw new RuntimeException('Unable to read a lesson video chunk.');
                }

                try {
                    if (stream_copy_to_stream($chunk, $destination) === false) {
                        throw new RuntimeException('Unable to assemble the lesson video.');
                    }
                } finally {
                    fclose($chunk);
                }
            }

            if (!fflush($destination)) {
                throw new RuntimeException('Unable to flush the assembled lesson video.');
            }
        } finally {
            fclose($destination);
        }
    }

    private function hasMp4FileType(string $path): bool
    {
        $file = fopen($path, 'rb');

        if ($file === false) {
            throw new RuntimeException('Unable to inspect the assembled lesson video.');
        }

        try {
            $header = fread($file, 12);
        } finally {
            fclose($file);
        }

        return is_string($header)
            && strlen($header) >= 8
            && substr($header, 4, 4) === 'ftyp';
    }
}
