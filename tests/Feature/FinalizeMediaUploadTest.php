<?php

namespace Tests\Feature;

use App\Jobs\FinalizeMediaUpload;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\CourseActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use UnexpectedValueException;

class FinalizeMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_a_course_image_and_removes_the_replaced_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('course_images/old.jpg', 'old image');
        Storage::disk('public')->put('pending/course_images/new.jpg', 'new image');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
            'image' => 'pending/course_images/new.jpg',
        ]);

        (new FinalizeMediaUpload(
            'course_image',
            $course->id,
            'pending/course_images/new.jpg',
            'course_images/old.jpg',
        ))->handle();

        $this->assertSame('course_images/new.jpg', $course->fresh()->image);
        Storage::disk('public')->assertExists('course_images/new.jpg');
        Storage::disk('public')->assertMissing('pending/course_images/new.jpg');
        Storage::disk('public')->assertMissing('course_images/old.jpg');
    }

    public function test_it_does_not_publish_an_svg_course_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pending/course_images/malicious.svg', '<svg></svg>');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
            'image' => 'pending/course_images/malicious.svg',
        ]);

        try {
            (new FinalizeMediaUpload(
                'course_image',
                $course->id,
                'pending/course_images/malicious.svg',
            ))->handle();

            $this->fail('SVG course images must not be published.');
        } catch (UnexpectedValueException $exception) {
            $this->assertSame('Unsupported course image format.', $exception->getMessage());
        }

        Storage::disk('public')->assertMissing('pending/course_images/malicious.svg');
        Storage::disk('public')->assertMissing('course_images/malicious.svg');
    }

    public function test_it_moves_a_lesson_video_to_its_final_location(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('pending/videos/lecture.mp4', 'video');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'pending/videos/lecture.mp4',
        ]);

        (new FinalizeMediaUpload(
            'lesson_video',
            $lesson->id,
            'pending/videos/lecture.mp4',
        ))->handle();

        $this->assertSame('videos/lecture.mp4', $lesson->fresh()->video);
        Storage::disk('private')->assertExists('videos/lecture.mp4');
        Storage::disk('private')->assertMissing('pending/videos/lecture.mp4');
    }

    public function test_it_notifies_paid_students_after_a_lesson_video_is_published(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('pending/videos/lecture.mp4', 'video');
        Notification::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
        ]);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'pending/videos/lecture.mp4',
        ]);

        (new FinalizeMediaUpload(
            'lesson_video',
            $lesson->id,
            'pending/videos/lecture.mp4',
            null,
            true,
        ))->handle();

        Notification::assertSentTo($student, CourseActivityNotification::class);
        $this->assertSame('videos/lecture.mp4', $lesson->fresh()->video);
    }

    public function test_it_does_not_replace_media_from_a_newer_upload(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('pending/videos/old.mp4', 'old upload');
        Storage::disk('private')->put('pending/videos/new.mp4', 'new upload');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'pending/videos/new.mp4',
        ]);

        (new FinalizeMediaUpload(
            'lesson_video',
            $lesson->id,
            'pending/videos/old.mp4',
        ))->handle();

        $this->assertSame('pending/videos/new.mp4', $lesson->fresh()->video);
        Storage::disk('private')->assertMissing('pending/videos/old.mp4');
        Storage::disk('private')->assertExists('pending/videos/new.mp4');
    }

    public function test_uploading_a_lesson_video_dispatches_the_background_job(): void
    {
        Storage::fake('private');
        Queue::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('lessons.store-for-course', $course), [
                'title' => 'Lesson',
                'video' => UploadedFile::fake()->create('lesson.mp4', 100, 'video/mp4'),
                'video_duration_seconds' => 120,
            ]);

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();
        $response->assertRedirect(route('lessons.edit', [$course, $lesson]));

        $this->assertSame(
            'تم استلام المحاضرة وحفظ الفيديو في مساحة آمنة. سيظهر بعد تجهيز الفيديو وتحديث الصفحة، شكرًا لصبرك.',
            session('success')
        );

        $this->assertStringStartsWith('pending/videos/', $lesson->video);
        $this->actingAs($teacher)
            ->get(route('lessons.manage', $course))
            ->assertOk()
            ->assertSee('الفيديو قيد التجهيز، حدّث الصفحة بعد قليل لمتابعة الحالة');

        Queue::assertPushed(
            FinalizeMediaUpload::class,
            fn (FinalizeMediaUpload $job) => $job->mediaType === 'lesson_video'
                && $job->ownerId === $lesson->id
                && $job->temporaryPath === $lesson->video,
        );
    }

    public function test_lesson_video_upload_is_not_limited_to_twenty_megabytes_by_validation(): void
    {
        Storage::fake('private');
        Queue::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('lessons.store-for-course', $course), [
                'title' => 'Long lecture',
                'video' => UploadedFile::fake()->create('long-lecture.mp4', 21 * 1024, 'video/mp4'),
                'video_duration_seconds' => 36000,
            ]);

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();
        $response->assertRedirect(route('lessons.edit', [$course, $lesson]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Long lecture',
            'video_duration_seconds' => 36000,
        ]);
    }

    public function test_lesson_video_finalization_jobs_are_serialized(): void
    {
        $job = new FinalizeMediaUpload('lesson_video', 1, 'pending/videos/lecture.mp4');

        $this->assertCount(1, $job->middleware());
        $this->assertInstanceOf(WithoutOverlapping::class, $job->middleware()[0]);
        $this->assertSame('lesson-video-finalization', $job->middleware()[0]->key);
        $this->assertSame(0, $job->tries);
        $this->assertSame(3, $job->maxExceptions);
    }

    public function test_existing_public_lesson_videos_can_be_moved_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Storage::disk('public')->put('videos/lecture.mp4', 'lesson video');
        Storage::disk('public')->put('videos/orphan.mp4', 'orphaned video');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lecture.mp4',
        ]);

        $this->artisan('media:secure-lesson-videos')->assertSuccessful();

        $this->assertSame('videos/lecture.mp4', $lesson->fresh()->video);
        Storage::disk('private')->assertExists('videos/lecture.mp4');
        Storage::disk('public')->assertMissing('videos/lecture.mp4');
        Storage::disk('public')->assertMissing('videos/orphan.mp4');
    }
}
