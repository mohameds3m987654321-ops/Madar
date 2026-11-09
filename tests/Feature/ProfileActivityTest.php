<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_learning_activity_and_progress_instead_of_account_edit_fields(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Learner Name',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Learning course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
        $partialLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Recent lesson',
            'order' => 1,
        ]);
        $completedLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Completed lesson',
            'order' => 2,
        ]);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $partialLesson->id,
            'last_position' => 90,
            'duration' => 300,
            'watched_seconds' => 600,
            'progress' => 30,
            'started_at' => now()->subMinutes(10),
        ]);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $completedLesson->id,
            'last_position' => 300,
            'duration' => 300,
            'watched_seconds' => 1800,
            'progress' => 100,
            'started_at' => now()->subHour(),
            'completed' => true,
            'completed_at' => now()->subMinutes(50),
        ]);

        $this->actingAs($student)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertSee('نشاطي التعليمي')
            ->assertSee('Learning course')
            ->assertSee('Recent lesson')
            ->assertSee(route('lessons.show', $partialLesson))
            ->assertSee('65%')
            ->assertSee('محاضرات بدأت مشاهدتها')
            ->assertSee('وقت التعلم')
            ->assertSee('40 دقيقة')
            ->assertSee('مخطط تقدم الكورسات')
            ->assertSee('تبديل إلى الوضع الفاتح')
            ->assertSee('2')
            ->assertDontSee('profileForm')
            ->assertDontSee('name="password"');
    }

    public function test_profile_shows_an_empty_learning_state_for_a_student_without_enrollments(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertSee('لسه ما اشتركتش في أي كورس')
            ->assertSee(route('courses.index'))
            ->assertSee('مفيش نشاط لسه');
    }
}
