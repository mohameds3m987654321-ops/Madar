<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonVideoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_enrolled_students_can_stream_a_published_course_video(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('videos/lesson.mp4', 'private lesson video');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
        ]);

        $this->actingAs($otherStudent)
            ->get(route('lessons.video', $lesson))
            ->assertForbidden();

        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('lessons.video', $lesson), $this->videoRequestHeaders())
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(
            'private lesson video',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_course_teacher_and_admin_can_stream_course_videos(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('videos/lesson.mp4', 'private lesson video');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
        ]);

        $this->actingAs($teacher)
            ->get(route('lessons.video', $lesson), $this->videoRequestHeaders())
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('lessons.video', $lesson), $this->videoRequestHeaders())
            ->assertOk();
    }

    public function test_authorized_video_stream_supports_http_range_requests(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('videos/lesson.mp4', '0123456789');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
        ]);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('lessons.video', $lesson), [
                ...$this->videoRequestHeaders(),
                'Range' => 'bytes=2-5',
            ])
            ->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 2-5/10')
            ->assertHeader('Accept-Ranges', 'bytes');
    }

    public function test_paid_students_cannot_access_a_course_video_after_it_becomes_a_draft(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('videos/lesson.mp4', 'private lesson video');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $course->update(['status' => 'draft']);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
        ]);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('lessons.video', $lesson))
            ->assertForbidden();
    }

    public function test_video_cannot_be_opened_directly_or_embedded_from_another_site(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('videos/lesson.mp4', 'private lesson video');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
        ]);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('lessons.video', $lesson), [
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Site' => 'same-origin',
            ])
            ->assertNotFound();

        $this->actingAs($student)
            ->get(route('lessons.video', $lesson), [
                'Sec-Fetch-Dest' => 'video',
                'Sec-Fetch-Site' => 'cross-site',
            ])
            ->assertNotFound();
    }

    public function test_video_urls_in_the_lesson_page_use_the_authorized_stream_route(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
        ]);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee(route('lessons.video', $lesson), false)
            ->assertDontSee('فتح فيديو المحاضرة في صفحة جديدة')
            ->assertSee('المحتوى مخصص لـ ' . $student->name)
            ->assertSee('controlslist="nodownload"', false)
            ->assertSee('disablepictureinpicture', false)
            ->assertSee('contextmenu', false)
            ->assertDontSee('storage/videos/lesson.mp4', false);
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
    }

    private function videoRequestHeaders(): array
    {
        return [
            'Sec-Fetch-Dest' => 'video',
            'Sec-Fetch-Site' => 'same-origin',
        ];
    }
}
