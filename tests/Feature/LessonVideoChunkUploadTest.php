<?php

namespace Tests\Feature;

use App\Jobs\FinalizeMediaUpload;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonVideoUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonVideoChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_upload_a_large_lesson_video_in_chunks(): void
    {
        Storage::fake('private');
        Queue::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $startResponse = $this->actingAs($teacher)->postJson(
            route('lessons.video-uploads.start', $course),
            [
                'title' => 'Ten hour lecture',
                'content' => 'Long lesson',
                'order' => 0,
                'video_duration_seconds' => 36000,
            ],
        )->assertCreated();

        $uploadId = $startResponse->json('upload_id');
        $video = pack('N', 12) . 'ftypisom' . 'lesson-video-data';
        $firstChunk = substr($video, 0, 10);
        $secondChunk = substr($video, 10);

        foreach ([$firstChunk, $secondChunk] as $index => $contents) {
            $this->postJson(route('lessons.video-uploads.chunk', [$course, $uploadId]), [
                'video_chunk' => UploadedFile::fake()->createWithContent('chunk.part', $contents),
                'chunk_index' => $index,
                'total_chunks' => 2,
            ])->assertOk()->assertJson([
                'chunk_index' => $index,
                'received' => true,
            ]);
        }

        $finishResponse = $this->postJson(
            route('lessons.video-uploads.finish', [$course, $uploadId]),
        )->assertCreated();

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();
        $this->assertSame(36000, $lesson->video_duration_seconds);
        $this->assertSame($video, Storage::disk('private')->get($lesson->video));
        $this->assertSame(route('lessons.manage', $course), $finishResponse->json('redirect'));
        $this->assertDatabaseHas('lesson_video_uploads', [
            'token' => $uploadId,
            'status' => 'completed',
            'lesson_id' => $lesson->id,
        ]);
        Storage::disk('private')->assertMissing("pending/lesson-uploads/{$uploadId}/chunks/00000000.part");
        Queue::assertPushed(
            FinalizeMediaUpload::class,
            fn (FinalizeMediaUpload $job) => $job->ownerId === $lesson->id
                && $job->temporaryPath === $lesson->video
                && $job->notifyPaidStudents,
        );

        $this->postJson(route('lessons.video-uploads.finish', [$course, $uploadId]))
            ->assertOk()
            ->assertJson(['redirect' => route('lessons.manage', $course)]);
        $this->assertDatabaseCount('lessons', 1);
    }

    public function test_incomplete_chunk_upload_does_not_create_a_lesson(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $uploadId = $this->actingAs($teacher)->postJson(
            route('lessons.video-uploads.start', $course),
            [
                'title' => 'Incomplete lecture',
                'order' => 1,
                'video_duration_seconds' => 600,
            ],
        )->assertCreated()->json('upload_id');

        $this->postJson(route('lessons.video-uploads.chunk', [$course, $uploadId]), [
            'video_chunk' => UploadedFile::fake()->createWithContent('chunk.part', pack('N', 12) . 'ftyp'),
            'chunk_index' => 0,
            'total_chunks' => 2,
        ])->assertOk();

        $this->postJson(route('lessons.video-uploads.finish', [$course, $uploadId]))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('lessons', [
            'course_id' => $course->id,
            'title' => 'Incomplete lecture',
        ]);
        $this->assertDatabaseHas('lesson_video_uploads', [
            'token' => $uploadId,
            'status' => 'uploading',
        ]);
    }

    public function test_cleanup_removes_expired_temporary_uploads(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $upload = LessonVideoUpload::create([
            'token' => '550e8400-e29b-41d4-a716-446655440000',
            'teacher_id' => $teacher->id,
            'course_id' => $course->id,
            'title' => 'Abandoned lecture',
            'lesson_order' => 1,
            'video_duration_seconds' => 600,
            'total_chunks' => 1,
            'status' => 'uploading',
        ]);
        Storage::disk('private')->put(
            "pending/lesson-uploads/{$upload->token}/chunks/00000000.part",
            'unfinished',
        );
        Storage::disk('private')->put("pending/videos/{$upload->token}.mp4", 'partial');
        $upload->forceFill(['updated_at' => now()->subHours(25)])->saveQuietly();

        $this->artisan('lessons:cleanup-uploads')->assertSuccessful();

        $this->assertDatabaseMissing('lesson_video_uploads', ['id' => $upload->id]);
        Storage::disk('private')->assertMissing("pending/lesson-uploads/{$upload->token}/chunks/00000000.part");
        Storage::disk('private')->assertMissing("pending/videos/{$upload->token}.mp4");
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Owned course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'draft',
        ]);
    }
}
