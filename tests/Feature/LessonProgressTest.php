<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_lesson_records_the_student_start_without_completing_it(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
        ]);
        $this->enrollStudent($student, $course);

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson->id))
            ->assertOk();

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed' => false,
        ]);

        $progress = LessonProgress::where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        $this->assertNotNull($progress->started_at);

        $startedAt = $progress->started_at;

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson->id))
            ->assertOk();

        $this->assertSame(
            $startedAt,
            $progress->fresh()->started_at
        );
        $this->assertSame(1, LessonProgress::where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->count());
    }

    public function test_unenrolled_student_is_returned_to_course_with_access_notice(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
        ]);

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson->id))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('access_notice', 'المحاضرة دي متاحة للمشتركين فقط. اشترك في الكورس علشان تقدر تشوفها.');

        $this->followingRedirects()
            ->actingAs($student)
            ->get(route('lessons.show', $lesson->id))
            ->assertOk()
            ->assertSee('id="access-toast"', false)
            ->assertSee('اشترك في الكورس علشان تقدر تشوفها');

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_course_progress_includes_partially_watched_lessons(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $completedLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Completed lesson',
        ]);
        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Uncompleted lesson',
        ]);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $completedLesson->id,
            'completed' => true,
        ]);
        $partialLesson = Lesson::where('course_id', $course->id)
            ->where('title', 'Uncompleted lesson')
            ->firstOrFail();
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $partialLesson->id,
            'progress' => 20,
        ]);

        $this->actingAs($student)
            ->get(route('courses.show', $course->id))
            ->assertOk()
            ->assertSee('60%');
    }

    public function test_saving_video_progress_records_position_and_partial_progress(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
            'video_duration_seconds' => 300,
        ]);
        $this->enrollStudent($student, $course);
        $progress = LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
        DB::table('lesson_progress')
            ->where('id', $progress->id)
            ->update(['updated_at' => now()->subSeconds(75)]);

        $this->actingAs($student)
            ->patchJson(route('lessons.progress', $lesson->id), [
                'position' => 75,
            ])
            ->assertOk()
            ->assertJson([
                'position' => 60,
                'duration' => 300,
                'watched_seconds' => 30,
                'progress' => 20,
                'completed' => false,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'last_position' => 60,
            'duration' => 300,
            'progress' => 20,
            'completed' => false,
        ]);
        $this->assertSame(20, $course->progressForUser($student->id));

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('data-start-position="60"', false);
    }

    public function test_saving_video_progress_records_bounded_watched_time(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'video' => 'videos/lesson.mp4',
            'video_duration_seconds' => 300,
        ]);
        $this->enrollStudent($student, $course);
        $progress = LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'last_position' => 10,
            'duration' => 300,
            'watched_seconds' => 10,
            'started_at' => now()->subMinute(),
        ]);
        DB::table('lesson_progress')
            ->where('id', $progress->id)
            ->update(['updated_at' => now()->subSeconds(12)]);

        $this->actingAs($student)
            ->patchJson(route('lessons.progress', $lesson->id), [
                'position' => 22,
                'watched_seconds' => 12,
            ])
            ->assertOk()
            ->assertJson(['watched_seconds' => 22]);

        $this->travel(30)->seconds();
        $this->patchJson(route('lessons.progress', $lesson->id), [
            'position' => 200,
            'watched_seconds' => 30,
        ])
            ->assertOk()
            ->assertJson([
                'position' => 82,
                'watched_seconds' => 52,
            ]);

        $this->patchJson(route('lessons.progress', $lesson->id), [
            'position' => 100,
            'watched_seconds' => 300,
        ])
            ->assertOk()
            ->assertJson([
                'position' => 82,
                'watched_seconds' => 52,
            ]);
    }

    public function test_saving_video_progress_rejects_invalid_position_and_students_without_enrollment(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
        ]);
        $this->enrollStudent($student, $course);

        $this->actingAs($student)
            ->patchJson(route('lessons.progress', $lesson->id), [
                'position' => 301,
                'duration' => 300,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);

        $otherStudent = User::factory()->create(['role' => 'student']);

        $this->actingAs($otherStudent)
            ->patchJson(route('lessons.progress', $lesson->id), [
                'position' => 60,
                'duration' => 300,
            ])
            ->assertForbidden();
    }

    public function test_completing_a_lesson_updates_the_students_course_progress(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
        ]);
        $this->enrollStudent($student, $course);

        $this->actingAs($student)
            ->post(route('lessons.complete', $lesson->id))
            ->assertRedirect(route('lessons.show', $lesson->id));

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'progress' => 100,
            'completed' => true,
        ]);
        $this->assertSame(100, $course->progressForUser($student->id));
    }

    public function test_video_lesson_must_be_watched_nearly_to_the_end_before_completion_and_certificate(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Video lesson',
            'video' => 'videos/course-lesson.mp4',
            'video_duration_seconds' => 300,
        ]);
        $this->enrollStudent($student, $course);

        $this->actingAs($student)
            ->post(route('lessons.complete', $lesson))
            ->assertStatus(422);

        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'last_position' => 285,
            'duration' => 300,
            'watched_seconds' => 285,
        ]);

        $this->actingAs($student)
            ->post(route('lessons.complete', $lesson))
            ->assertRedirect(route('lessons.show', $lesson));

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);
        $this->assertDatabaseHas('course_certificates', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_client_cannot_fake_video_duration_position_or_watched_time_to_complete_a_lesson(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Video lesson',
            'video' => 'videos/course-lesson.mp4',
            'video_duration_seconds' => 300,
        ]);
        $this->enrollStudent($student, $course);
        $progress = LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'started_at' => now()->subSecond(),
        ]);
        DB::table('lesson_progress')
            ->where('id', $progress->id)
            ->update(['updated_at' => now()->subSecond()]);

        $this->actingAs($student)
            ->patchJson(route('lessons.progress', $lesson), [
                'position' => 300,
                'duration' => 1,
                'watched_seconds' => 300,
            ])
            ->assertOk()
            ->assertJson([
                'position' => 2,
                'duration' => 300,
                'watched_seconds' => 1,
            ]);

        $this->post(route('lessons.complete', $lesson))
            ->assertStatus(422);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed' => false,
        ]);
    }

    private function enrollStudent(User $student, Course $course): void
    {
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);
    }
}
