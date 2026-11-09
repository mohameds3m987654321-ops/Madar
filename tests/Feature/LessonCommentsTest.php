<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_post_a_question_and_see_the_discussion(): void
    {
        [$teacher, $student, $course, $lesson] = $this->createLessonWithEnrollment();

        $this->actingAs($student)
            ->post(route('lessons.comments.store', $lesson), [
                'body' => 'هل يمكن شرح هذه النقطة مرة أخرى؟',
            ])
            ->assertRedirect(route('lessons.show', $lesson) . '#lesson-qa')
            ->assertSessionHas('lesson_comment_success');

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('الأسئلة والنقاش')
            ->assertSee('هل يمكن شرح هذه النقطة مرة أخرى؟')
            ->assertSee($student->name);

        $this->assertDatabaseHas('lesson_comments', [
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'parent_id' => null,
            'body' => 'هل يمكن شرح هذه النقطة مرة أخرى؟',
        ]);
    }

    public function test_course_teacher_can_reply_to_a_student_question(): void
    {
        [$teacher, $student, $course, $lesson] = $this->createLessonWithEnrollment();
        $question = LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'body' => 'ما المقصود بهذه القاعدة؟',
        ]);

        $this->actingAs($teacher)
            ->post(route('lessons.comments.store', $lesson), [
                'parent_id' => $question->id,
                'body' => 'المقصود هو تطبيق القاعدة على المثال الموجود في الفيديو.',
            ])
            ->assertRedirect(route('lessons.show', $lesson) . '#lesson-qa')
            ->assertSessionHas('lesson_comment_success');

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('المقصود هو تطبيق القاعدة على المثال الموجود في الفيديو.')
            ->assertSee('مدرس الكورس');

        $this->assertDatabaseHas('lesson_comments', [
            'lesson_id' => $lesson->id,
            'user_id' => $teacher->id,
            'parent_id' => $question->id,
            'body' => 'المقصود هو تطبيق القاعدة على المثال الموجود في الفيديو.',
        ]);
        $notification = $student->notifications()->firstOrFail();
        $this->assertSame('question_answered', $notification->data['event']);
        $this->assertSame(
            route('lessons.show', $lesson, false) . '#lesson-qa',
            $notification->data['url']
        );
    }

    public function test_unenrolled_student_cannot_ask_and_student_cannot_reply(): void
    {
        [$teacher, $student, $course, $lesson] = $this->createLessonWithEnrollment();
        $anotherStudent = User::factory()->create(['role' => 'student']);
        $question = LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'body' => 'سؤال الطالب المشترك',
        ]);

        $this->actingAs($anotherStudent)
            ->post(route('lessons.comments.store', $lesson), [
                'body' => 'سؤال من طالب غير مشترك',
            ])
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('lessons.comments.store', $lesson), [
                'parent_id' => $question->id,
                'body' => 'رد من طالب على السؤال',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('lesson_comments', 1);
    }

    public function test_only_the_course_teacher_can_reply_and_replies_must_belong_to_the_lesson(): void
    {
        [$teacher, $student, $course, $lesson] = $this->createLessonWithEnrollment();
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $question = LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'body' => 'سؤال أصلي',
        ]);
        $anotherLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'محاضرة أخرى',
        ]);
        $otherQuestion = LessonComment::create([
            'lesson_id' => $anotherLesson->id,
            'user_id' => $student->id,
            'body' => 'سؤال بمحاضرة أخرى',
        ]);

        $this->actingAs($anotherTeacher)
            ->post(route('lessons.comments.store', $lesson), [
                'parent_id' => $question->id,
                'body' => 'رد مدرس آخر',
            ])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->post(route('lessons.comments.store', $lesson), [
                'parent_id' => $otherQuestion->id,
                'body' => 'رد على سؤال من محاضرة أخرى',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('lesson_comments', 2);
    }

    private function createLessonWithEnrollment(): array
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
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        return [$teacher, $student, $course, $lesson];
    }
}
