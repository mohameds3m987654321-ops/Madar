<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_only_own_courses_and_enrollment_progress(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'preferred_theme' => 'dark',
        ]);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Enrolled Student',
            'email' => 'student@example.test',
        ]);
        $pendingStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Pending Student',
        ]);

        $course = $this->createCourse($teacher, 'Teacher course');
        $firstLesson = $this->createLesson($course, 'First lesson', 1);
        $this->createLesson($course, 'Second lesson', 2);
        $paidEnrollment = $this->enrollStudent($student, $course, 'paid');
        $this->enrollStudent($pendingStudent, $course, 'pending');
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $firstLesson->id,
            'completed' => true,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $otherCourse = $this->createCourse($otherTeacher, 'Private other teacher course');
        $otherStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Private other student',
        ]);
        $this->enrollStudent($otherStudent, $otherCourse, 'paid');

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('لوحة المدرس')
            ->assertSee('class="teacher-management"', false)
            ->assertSee('data-theme="dark"', false)
            ->assertSee(asset('css/appearance.css'), false)
            ->assertSee('management-header', false)
            ->assertSee(asset('css/management-navigation.css'), false)
            ->assertSee('Teacher course')
            ->assertSee('Enrolled Student')
            ->assertSee('student@example.test')
            ->assertSee('بانتظار الدفع')
            ->assertSee('50%')
            ->assertSee(route('courses.show', $course))
            ->assertSee(route('courses.edit', $course))
            ->assertSee(route('lessons.manage', $course))
            ->assertSee(route('quizzes.edit', $course))
            ->assertSee('إضافة كورس')
            ->assertDontSee('Private other teacher course')
            ->assertDontSee('Private other student');

        $this->assertSame('paid', $paidEnrollment->status);
    }

    public function test_teacher_management_pages_share_the_navigation(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher, 'Course for management navigation');
        $lesson = $this->createLesson($course, 'Lesson for management navigation', 1);

        foreach ([
            route('courses.create'),
            route('courses.edit', $course),
            route('courses.index'),
            route('lessons.create'),
            route('lessons.manage', $course),
            route('lessons.create-for-course', $course),
            route('lessons.edit', [$course, $lesson]),
            route('quizzes.edit', $course),
        ] as $url) {
            $this->actingAs($teacher)
                ->get($url)
                ->assertOk()
                ->assertSee('class="teacher-management"', false)
                ->assertSee('management-header', false)
                ->assertSee(asset('css/management-navigation.css'), false);
        }
    }

    public function test_student_cannot_access_teacher_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('teacher.dashboard'))
            ->assertRedirect();
    }

    public function test_teacher_without_courses_sees_create_course_action(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('لسه ما أضفتش أي كورس')
            ->assertSee(route('courses.create'));
    }

    public function test_teacher_sees_draft_status_and_publish_action_for_their_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $draft = $this->createCourse($teacher, 'Draft course');
        $draft->update(['status' => 'draft']);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('المسودات')
            ->assertSee('Draft course')
            ->assertSee('نشر الكورس')
            ->assertSee(route('courses.publish', $draft));
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

    private function enrollStudent(User $student, Course $course, string $status): CourseEnrollment
    {
        return CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => $status,
            'amount_cents' => 25000,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }
}
