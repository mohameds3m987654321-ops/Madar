<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CourseReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_student_can_submit_and_update_one_course_review(): void
    {
        [$student, $course] = $this->createPaidStudentAndCourse();

        $this->actingAs($student)
            ->post(route('courses.reviews.store', $course), [
                'rating' => 4,
                'review' => 'شرح واضح ومفيد جدا',
            ])
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('success');

        $this->actingAs($student)
            ->post(route('courses.reviews.store', $course), [
                'rating' => 5,
                'review' => 'الكورس ممتاز واستفدت منه كثيرا',
            ])
            ->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseCount('course_reviews', 1);
        $this->assertDatabaseHas('course_reviews', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'rating' => 5,
            'review' => 'الكورس ممتاز واستفدت منه كثيرا',
        ]);
    }

    public function test_student_without_a_paid_enrollment_cannot_review(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($student)
            ->post(route('courses.reviews.store', $course), [
                'rating' => 5,
                'review' => 'رأي مفصل في محتوى الكورس',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('course_reviews', 0);
    }

    public function test_paid_student_must_complete_seventy_percent_of_lessons_to_review(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 25000,
            'paid_at' => now(),
        ]);
        foreach (range(1, 10) as $index) {
            $lesson = Lesson::create([
                'course_id' => $course->id,
                'title' => 'Threshold lesson ' . $index,
            ]);
            if ($index <= 6) {
                LessonProgress::create([
                    'user_id' => $student->id,
                    'lesson_id' => $lesson->id,
                    'completed' => true,
                ]);
            }
        }

        $payload = [
            'rating' => 5,
            'review' => 'تجربة تعليمية رائعة ومحتوى مفيد.',
        ];
        $this->actingAs($student)
            ->post(route('courses.reviews.store', $course), $payload)
            ->assertForbidden();

        $seventhLesson = Lesson::where('course_id', $course->id)
            ->where('title', 'Threshold lesson 7')
            ->firstOrFail();
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $seventhLesson->id,
            'completed' => true,
        ]);
        $this->post(route('courses.reviews.store', $course), $payload)
            ->assertRedirect(route('courses.show', $course));
    }

    public function test_review_rating_and_text_are_validated(): void
    {
        [$student, $course] = $this->createPaidStudentAndCourse();

        $this->actingAs($student)
            ->from(route('courses.show', $course))
            ->post(route('courses.reviews.store', $course), [
                'rating' => 6,
                'review' => 'قصير',
            ])
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHasErrors(['rating', 'review']);
    }

    public function test_course_page_and_catalog_show_review_rating_and_catalog_cache_is_invalidated(): void
    {
        [$student, $course] = $this->createPaidStudentAndCourse();
        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'rating' => 5,
            'review' => 'الكورس ممتاز واستفدت منه كثيرا',
        ]);

        $this->actingAs($student)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('5.0')
            ->assertSee('1 تقييم')
            ->assertSee('تحديث تقييمي');

        Cache::put(Course::HOME_PAGE_CACHE_KEY, [], now()->addMinutes(5));
        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'rating' => 3,
            'review' => 'محتوى جيد لكن يحتاج بعض الإضافات',
        ]);

        $this->assertFalse(Cache::has(Course::HOME_PAGE_CACHE_KEY));
        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('4.0')
            ->assertSee('2 تقييم');
    }

    private function createPaidStudentAndCourse(): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 25000,
            'paid_at' => now(),
        ]);
        foreach (range(1, 10) as $index) {
            $lesson = Lesson::create([
                'course_id' => $course->id,
                'title' => 'Lesson ' . $index,
                'order' => $index,
            ]);
            if ($index <= 7) {
                LessonProgress::create([
                    'user_id' => $student->id,
                    'lesson_id' => $lesson->id,
                    'progress' => 100,
                    'completed' => true,
                    'completed_at' => now(),
                ]);
            }
        }

        return [$student, $course];
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 250,
            'status' => 'published',
        ]);
    }
}
