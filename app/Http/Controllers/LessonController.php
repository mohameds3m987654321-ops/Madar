<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lessons\LessonFormRequest;
use App\Http\Requests\Lessons\SaveLessonProgressRequest;
use App\Http\Requests\Lessons\StoreLessonRequest;
use App\Jobs\FinalizeMediaUpload;
use App\Models\Course;
use App\Models\LessonAttachment;
use App\Models\Lesson;
use App\Notifications\CourseActivityNotification;
use App\Services\CourseCertificateService;
use App\Services\CourseNotificationService;
use App\Services\UploadedMediaStager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonController extends Controller
{
    public function create()
    {
        $courses = Course::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('Lessons.create', compact('courses'));
    }

    public function manage(Course $course)
    {
        $this->authorize('manage', $course);
        $course->load(['lessons' => fn ($query) => $query->orderBy('order')->orderBy('id')]);

        return view('Lessons.manage', compact('course'));
    }

    public function createForCourse(Course $course)
    {
        $this->authorize('manage', $course);

        return view('Lessons.form', [
            'course' => $course,
            'lesson' => new Lesson,
        ]);
    }

    public function storeForCourse(
        LessonFormRequest $request,
        Course $course,
        CourseNotificationService $notifications,
        UploadedMediaStager $mediaStager,
    ) {
        $this->authorize('manage', $course);
        $validated = $request->validated();
        $validated['order'] ??= ((int) $course->lessons()->max('order')) + 1;
        $temporaryVideoPath = $request->hasFile('video')
            ? $mediaStager->stage($request->file('video'), 'videos', 'private')
            : null;
        if ($temporaryVideoPath) {
            $validated['video'] = $temporaryVideoPath;
        }

        $lesson = $course->lessons()->create($validated);
        if ($temporaryVideoPath) {
            FinalizeMediaUpload::dispatch(
                'lesson_video',
                $lesson->id,
                $temporaryVideoPath,
                null,
                true,
            )->afterCommit();
        }
        if (! $temporaryVideoPath) {
            $notifications->notifyPaidStudents($course, new CourseActivityNotification(
                'lesson_added',
                'محاضرة جديدة',
                'تمت إضافة محاضرة جديدة "'.$lesson->title.'" إلى كورس '.$course->title.'.',
                route('lessons.show', $lesson->id, false),
            ));
        }

        return redirect()
            ->route('lessons.edit', [$course, $lesson])
            ->with(
                'success',
                $temporaryVideoPath
                    ? 'تم استلام المحاضرة وحفظ الفيديو في مساحة آمنة. سيظهر بعد تجهيز الفيديو وتحديث الصفحة، شكرًا لصبرك.'
                    : 'تمت إضافة المحاضرة بنجاح.'
            );
    }

    public function editForCourse(Course $course, Lesson $lesson)
    {
        $this->authorize('manage', $course);
        abort_unless($lesson->course_id === $course->id, 404);
        $this->authorize('update', $lesson);

        return view('Lessons.form', compact('course', 'lesson'));
    }

    public function updateForCourse(
        LessonFormRequest $request,
        Course $course,
        Lesson $lesson,
        UploadedMediaStager $mediaStager,
    ) {
        $this->authorize('manage', $course);
        abort_unless($lesson->course_id === $course->id, 404);
        $this->authorize('update', $lesson);

        $validated = $request->validated();
        $temporaryVideoPath = $request->hasFile('video')
            ? $mediaStager->stage($request->file('video'), 'videos', 'private')
            : null;

        if ($temporaryVideoPath) {
            $validated['video'] = $temporaryVideoPath;
        }
        $previousVideoPath = $lesson->video;
        $lesson->update($validated);

        if ($temporaryVideoPath) {
            FinalizeMediaUpload::dispatch(
                'lesson_video',
                $lesson->id,
                $temporaryVideoPath,
                $previousVideoPath,
            )->afterCommit();
        }

        return redirect()
            ->route('lessons.manage', $course)
            ->with('success', 'تم حفظ تعديلات المحاضرة.');
    }

    public function destroyForCourse(Course $course, Lesson $lesson)
    {
        $this->authorize('manage', $course);
        abort_unless($lesson->course_id === $course->id, 404);
        $this->authorize('delete', $lesson);

        $videoPath = $lesson->video;
        $attachmentPaths = $lesson->attachments()->pluck('path');
        $lesson->delete();

        if ($videoPath) {
            Storage::disk('private')->delete($videoPath);
            Storage::disk('public')->delete($videoPath);
        }
        foreach ($attachmentPaths as $attachmentPath) {
            Storage::disk('private')->delete($attachmentPath);
        }

        return redirect()
            ->route('lessons.manage', $course)
            ->with('success', 'تم حذف المحاضرة.');
    }

    public function storeAttachment(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', $course);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
        $this->authorize('update', $lesson);

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:10'],
            'attachments.*' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,ppt,pptx,xls,xlsx,txt',
                'max:51200',
            ],
        ]);

        foreach ($validated['attachments'] as $file) {
            $path = $file->store("lessons/{$lesson->id}/attachments", 'private');
            abort_if($path === false, 500, 'تعذر حفظ الملف المرفق.');

            $lesson->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }

        return redirect()
            ->route('lessons.edit', [$course, $lesson])
            ->with('success', 'تم رفع الملفات المرفقة للمحاضرة.');
    }

    public function destroyAttachment(Course $course, Lesson $lesson, LessonAttachment $attachment)
    {
        $this->authorize('manage', $course);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
        abort_unless((int) $attachment->lesson_id === (int) $lesson->id, 404);
        $this->authorize('update', $lesson);

        $path = $attachment->path;
        $attachment->delete();
        Storage::disk('private')->delete($path);

        return redirect()
            ->route('lessons.edit', [$course, $lesson])
            ->with('success', 'تم حذف الملف المرفق.');
    }

    public function downloadAttachment(Lesson $lesson, LessonAttachment $attachment): BinaryFileResponse
    {
        abort_unless((int) $attachment->lesson_id === (int) $lesson->id, 404);
        $this->authorize('view', $lesson);
        $this->ensureCourseAccess($lesson->course);

        $path = $attachment->path;
        abort_unless(
            is_string($path)
                && preg_match(
                    '/\Alessons\/'.preg_quote((string) $lesson->id, '/').'\/attachments\/[A-Za-z0-9._-]+\z/',
                    $path
                ) === 1,
            404
        );

        $disk = Storage::disk('private');
        abort_unless($disk->exists($path), 404);

        $response = response()->download($disk->path($path), $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function store(
        StoreLessonRequest $request,
        CourseNotificationService $notifications,
        UploadedMediaStager $mediaStager,
    ) {
        $validatedData = $request->validated();

        $course = Course::findOrFail($validatedData['course_id']);
        $this->authorize('manage', $course);

        $temporaryVideoPath = $request->hasFile('video')
            ? $mediaStager->stage($request->file('video'), 'videos', 'private')
            : null;
        if ($temporaryVideoPath) {
            $validatedData['video'] = $temporaryVideoPath;
        }

        $validatedData['order'] ??= ((int) $course->lessons()->max('order')) + 1;
        unset($validatedData['course_id']);
        $lesson = $course->lessons()->create($validatedData);
        if ($temporaryVideoPath) {
            FinalizeMediaUpload::dispatch(
                'lesson_video',
                $lesson->id,
                $temporaryVideoPath,
                null,
                true,
            )->afterCommit();
        }
        if (! $temporaryVideoPath) {
            $notifications->notifyPaidStudents($course, new CourseActivityNotification(
                'lesson_added',
                'محاضرة جديدة',
                'تمت إضافة محاضرة جديدة "'.$lesson->title.'" إلى كورس '.$course->title.'.',
                route('lessons.show', $lesson->id, false),
            ));
        }

        return redirect()
            ->route('lessons.manage', $course)
            ->with(
                'success',
                $temporaryVideoPath
                    ? 'تم استلام المحاضرة وحفظ الفيديو في مساحة آمنة. سيظهر بعد تجهيز الفيديو وتحديث الصفحة، شكرًا لصبرك.'
                    : 'تمت إضافة المحاضرة بنجاح.'
            );
    }

    public function show($id)
    {
        $lesson = Lesson::findOrFail($id);
        $course = $lesson->course;

        if (auth()->user()->role === 'student' && $course->status !== 'published') {
            abort(404);
        }

        if (auth()->user()->role === 'student'
            && ! $course->enrollments()
                ->where('user_id', auth()->id())
                ->where('status', 'paid')
                ->exists()) {
            return redirect()
                ->route('courses.show', $course)
                ->with('access_notice', 'المحاضرة دي متاحة للمشتركين فقط. اشترك في الكورس علشان تقدر تشوفها.');
        }

        $this->authorize('view', $lesson);
        $this->ensureCourseAccess($course);

        if (auth()->user()->role === 'student') {
            $progress = $lesson->progresses()->firstOrCreate(
                ['user_id' => auth()->id()],
                ['started_at' => now()]
            );

            if ($progress->started_at === null) {
                $progress->update(['started_at' => now()]);
            }
        } else {
            $progress = $lesson->progresses()
                ->where('user_id', auth()->id())
                ->first();
        }

        $courseLessons = $course->lessons()
            ->with(['progresses' => fn ($query) => $query->where('user_id', auth()->id())])
            ->orderBy('order')
            ->get();
        $lesson->load('attachments');

        $courseProgress = $course->progressForUser(auth()->id());
        $certificate = auth()->user()->role === 'student'
            ? $course->certificates()->where('user_id', auth()->id())->first()
            : null;
        $comments = $lesson->comments()
            ->whereNull('parent_id')
            ->with([
                'user:id,name',
                'replies' => fn ($query) => $query->with('user:id,name'),
            ])
            ->latest()
            ->paginate(20);
        $canRespondToQuestions = auth()->user()->role === 'teacher'
            && $course->teacher_id === auth()->id();

        return view('Lessons.show', compact(
            'lesson',
            'course',
            'courseLessons',
            'progress',
            'courseProgress',
            'certificate',
            'comments',
            'canRespondToQuestions'
        ));
    }

    public function video(Lesson $lesson): BinaryFileResponse
    {
        $this->authorize('stream', $lesson);
        $this->ensureVideoIsRequestedFromLessonPage($lesson);

        $videoPath = $lesson->video;
        abort_unless(
            is_string($videoPath)
                && preg_match('/\Avideos\/[A-Za-z0-9][A-Za-z0-9._-]*\z/', $videoPath) === 1,
            404
        );

        $disk = Storage::disk('private');
        abort_unless($disk->exists($videoPath), 404);

        $response = response()->file($disk->path($videoPath), [
            'Content-Type' => $disk->mimeType($videoPath) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function ensureVideoIsRequestedFromLessonPage(Lesson $lesson): void
    {
        $request = request();
        $destination = $request->headers->get('Sec-Fetch-Dest');
        $site = $request->headers->get('Sec-Fetch-Site');

        if ($destination !== null || $site !== null) {
            abort_unless($destination === 'video' && $site === 'same-origin', 404);

            return;
        }

        $referer = $request->headers->get('referer');
        $refererParts = is_string($referer) ? parse_url($referer) : false;
        $expectedPath = parse_url(route('lessons.show', $lesson), PHP_URL_PATH);
        $refererOrigin = is_array($refererParts)
            ? strtolower((string) ($refererParts['scheme'] ?? '')).'://'.strtolower((string) ($refererParts['host'] ?? ''))
                .(isset($refererParts['port']) ? ':'.$refererParts['port'] : '')
            : null;

        abort_unless(
            is_array($refererParts)
                && $refererOrigin === strtolower($request->getSchemeAndHttpHost())
                && ($refererParts['path'] ?? null) === $expectedPath,
            404
        );
    }

    public function complete(Lesson $lesson, CourseCertificateService $certificates)
    {
        $this->authorize('view', $lesson);
        $this->ensureCourseAccess($lesson->course);
        abort_unless(auth()->user()->role === 'student', 403);

        if ($lesson->video) {
            $watchProgress = $lesson->progresses()
                ->where('user_id', auth()->id())
                ->first();
            abort_unless(
                $watchProgress
                    && $lesson->video_duration_seconds > 0
                    && $watchProgress->watched_seconds >= (int) ceil($lesson->video_duration_seconds * 0.95),
                422,
                'شاهد 95% على الأقل من فيديو المحاضرة قبل إكمالها.'
            );
        }

        $progress = $lesson->progresses()->firstOrCreate(
            [
                'user_id' => auth()->id(),
            ],
            [
                'started_at' => now(),
            ]
        );

        $progress->update([
            'progress' => 100,
            'completed' => true,
            'completed_at' => now(),
        ]);
        $certificates->issueIfEligible($lesson->course, auth()->user());

        return redirect()
            ->route('lessons.show', $lesson)
            ->with('success', 'تم إكمال المحاضرة بنجاح.');
    }

    public function saveProgress(SaveLessonProgressRequest $request, Lesson $lesson)
    {
        abort_unless(auth()->user()->role === 'student', 403);
        $this->authorize('view', $lesson);
        $this->ensureCourseAccess($lesson->course);

        $validated = $request->validated();
        abort_unless(
            $lesson->video && $lesson->video_duration_seconds > 0,
            422,
            'مدة فيديو المحاضرة غير محددة. اطلب من المدرّس تحديث بيانات المحاضرة.'
        );

        if ($validated['position'] > $lesson->video_duration_seconds) {
            throw ValidationException::withMessages([
                'position' => 'موضع الفيديو لا يمكن أن يتجاوز مدته.',
            ]);
        }

        $progress = $lesson->progresses()->firstOrNew([
            'user_id' => auth()->id(),
        ]);
        $now = now();
        $elapsedSeconds = $progress->exists && $progress->updated_at
            ? min(30, max(0, $now->getTimestamp() - $progress->updated_at->getTimestamp()))
            : 0;
        $previousPosition = (int) $progress->last_position;
        $acceptedPosition = max(
            $previousPosition,
            min($validated['position'], $previousPosition + ($elapsedSeconds * 2))
        );
        $forwardSeconds = max(0, $acceptedPosition - $previousPosition);
        $watchedSeconds = min($elapsedSeconds, $forwardSeconds);

        $progress->fill([
            'last_position' => $acceptedPosition,
            'duration' => $lesson->video_duration_seconds,
            'watched_seconds' => (int) $progress->watched_seconds
                + $watchedSeconds,
            'progress' => min(100, (int) floor(
                ($acceptedPosition / $lesson->video_duration_seconds) * 100
            )),
            'started_at' => $progress->started_at ?? $now,
        ])->save();

        return response()->json([
            'position' => $progress->last_position,
            'duration' => $lesson->video_duration_seconds,
            'watched_seconds' => $progress->watched_seconds,
            'progress' => $progress->progress,
            'completed' => $progress->completed,
        ]);
    }

    public function index()
    {
        return redirect()->route('teacher.dashboard');
    }

    private function ensureCourseAccess(Course $course): void
    {
        if (auth()->user()->role !== 'student') {
            return;
        }

        abort_unless($course->status === 'published', 404);

        abort_unless(
            $course->enrollments()
                ->where('user_id', auth()->id())
                ->where('status', 'paid')
                ->exists(),
            403,
            'اشترك في الكورس أولًا لمشاهدة المحاضرات.'
        );
    }

    private function ensureCourseOwnership(Course $course): void
    {
        abort_unless(
            auth()->user()->role === 'teacher' && $course->teacher_id === auth()->id(),
            403
        );
    }
}
