<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_paid_courses_with_progress_and_latest_started_lesson_to_resume(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'My enrolled course');
        $completedLesson = $this->createLesson($course, 'Completed lesson', 1);
        $startedLesson = $this->createLesson($course, 'Started lesson', 2);
        $latestStartedLesson = $this->createLesson($course, 'Latest lesson', 3);

        $this->enrollStudent($student, $course);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $completedLesson->id,
            'completed' => true,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(50),
        ]);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $startedLesson->id,
            'started_at' => now()->subMinutes(10),
        ]);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $latestStartedLesson->id,
            'started_at' => now()->subMinutes(2),
        ]);

        $pendingCourse = $this->createCourse($teacher, 'Pending course');
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $pendingCourse->id,
            'payment_method' => 'fawry',
            'status' => 'pending',
            'amount_cents' => 25000,
        ]);

        $otherStudentsCourse = $this->createCourse($teacher, 'Another student course');
        $this->enrollStudent($otherStudent, $otherStudentsCourse);

        $this->actingAs($student)
            ->get(route('courses.my'))
            ->assertOk()
            ->assertSee('My enrolled course')
            ->assertSee('33%')
            ->assertSee(route('lessons.show', $latestStartedLesson))
            ->assertDontSee('Pending course')
            ->assertDontSee('Another student course');
    }

    public function test_student_without_enrollments_sees_empty_state(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('courses.my'))
            ->assertOk()
            ->assertSee('لسه ما اشتركتش في أي كورس');
    }

    public function test_non_student_cannot_access_my_courses(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('courses.my'))
            ->assertForbidden();
    }

    public function test_completed_course_is_marked_complete_without_a_resume_link(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'Completed course');
        $lesson = $this->createLesson($course, 'Only lesson', 1);
        $this->enrollStudent($student, $course);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('courses.my'))
            ->assertOk()
            ->assertSee('مراجعة الكورس')
            ->assertDontSee(route('lessons.show', $lesson));
    }

    private function createCourse(User $teacher, string $title): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => $title,
            'description' => 'Course description',
            'price' => 250,
            'status' => 'published',
        ]);
    }

    private function createLesson(Course $course, string $title, int $order): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => $title,
            'order' => $order,
        ]);
    }

    private function enrollStudent(User $student, Course $course): void
    {
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 25000,
            'paid_at' => now(),
        ]);
    }
}
