<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseQuiz;
use App\Models\CourseCertificate;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseQuizCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_a_final_multiple_choice_quiz(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->put(route('quizzes.save', $course), $this->quizPayload())
            ->assertRedirect(route('quizzes.edit', $course))
            ->assertSessionHas('success');

        $quiz = CourseQuiz::where('course_id', $course->id)
            ->with('questions.options')
            ->firstOrFail();

        $this->assertSame(70, $quiz->passing_score);
        $this->assertSame(3, $quiz->max_attempts);
        $this->assertSame(30, $quiz->time_limit_minutes);
        $this->assertCount(1, $quiz->questions);
        $this->assertCount(4, $quiz->questions->first()->options);
        $this->assertSame(
            1,
            $quiz->questions->first()->options->where('is_correct', true)->count()
        );

        $this->actingAs($teacher)
            ->get(route('quizzes.edit', $course))
            ->assertOk()
            ->assertSee('Choose the answer')
            ->assertSee('Correct one');
    }

    public function test_student_can_submit_quiz_and_get_an_immediate_result(): void
    {
        [$teacher, $student, $course, $lesson] = $this->createEnrolledStudentWithCompletedLessons();
        $this->createQuiz($teacher, $course);
        $quiz = $course->quiz()->with('questions.options')->firstOrFail();
        $question = $quiz->questions->first();
        $correctOption = $question->options->firstWhere('is_correct', true);

        $this->actingAs($student)
            ->get(route('quizzes.show', $course))
            ->assertOk()
            ->assertSee('Choose the answer')
            ->assertSee('quiz_session')
            ->assertDontSee('is_correct');
        $quizSession = QuizSession::where('user_id', $student->id)
            ->where('course_quiz_id', $quiz->id)
            ->firstOrFail();

        $this->actingAs($student)
            ->post(route('quizzes.submit', $course), [
                'quiz_session' => $quizSession->id,
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertOk()
            ->assertSee('100%')
            ->assertSee('اجتزت الاختبار');

        $this->assertDatabaseHas('quiz_attempts', [
            'course_quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 100,
            'passed' => true,
        ]);
        $this->assertDatabaseHas('course_certificates', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_quiz_is_unavailable_until_all_lessons_are_completed(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = $this->createLesson($course);
        $this->enrollStudent($student, $course);
        $this->createQuiz($teacher, $course);

        $this->actingAs($student)
            ->get(route('quizzes.show', $course))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('access_notice', 'كمّل كل محاضرات الكورس الأول علشان تفتح الاختبار النهائي.');

        $this->get(route('courses.certificate', $course))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('access_notice', 'أكمل كل محاضرات الكورس علشان تقدر تحصل على الشهادة.');

        $this->assertDatabaseCount('course_certificates', 0);
        $this->assertNotNull($lesson->fresh());
    }

    public function test_unenrolled_student_is_returned_to_course_with_access_notice(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->createLesson($course);
        $this->createQuiz($teacher, $course);

        $this->actingAs($student)
            ->get(route('quizzes.show', $course))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('access_notice', 'اشترك في الكورس الأول علشان تقدر تفتح الاختبار أو الشهادة.');

        $this->followingRedirects()
            ->actingAs($student)
            ->get(route('quizzes.show', $course))
            ->assertOk()
            ->assertSee('id="access-toast"', false)
            ->assertSee('اشترك في الكورس الأول علشان تقدر تفتح الاختبار أو الشهادة.');

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_certificate_is_issued_once_when_every_lesson_is_completed(): void
    {
        [, $student, $course] = $this->createEnrolledStudentWithCompletedLessons();

        $this->actingAs($student)
            ->get(route('courses.certificate', $course))
            ->assertOk()
            ->assertSee('شهادة إتمام')
            ->assertSee($student->name);

        $certificate = CourseCertificate::where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->get(route('courses.certificate', $course))
            ->assertOk()
            ->assertSee($certificate->certificate_number)
            ->assertSee('data:image/svg+xml;base64,', false);
        $this->get(route('certificates.verify', $certificate->certificate_number))
            ->assertOk()
            ->assertSee('شهادة صحيحة')
            ->assertSee($student->name)
            ->assertSee($course->title);

        $this->assertDatabaseCount('course_certificates', 1);
    }

    public function test_student_cannot_view_another_students_quiz_attempt(): void
    {
        [$teacher, $student, $course] = $this->createEnrolledStudentWithCompletedLessons();
        $this->createQuiz($teacher, $course);
        $quiz = $course->quiz()->firstOrFail();
        $attempt = QuizAttempt::create([
            'course_quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 50,
            'correct_answers' => 1,
            'total_questions' => 2,
            'passed' => false,
            'results' => [],
            'submitted_at' => now(),
        ]);
        $anotherStudent = User::factory()->create(['role' => 'student']);
        $this->enrollStudent($anotherStudent, $course);

        $this->actingAs($anotherStudent)
            ->get(route('quizzes.result', [$course, $attempt]))
            ->assertForbidden();
    }

    public function test_student_cannot_answer_with_an_option_from_a_different_question(): void
    {
        [$teacher, $student, $course] = $this->createEnrolledStudentWithCompletedLessons();
        $this->createQuiz($teacher, $course);
        $quiz = $course->quiz()->with('questions.options')->firstOrFail();
        $question = $quiz->questions->first();

        $this->actingAs($student)
            ->get(route('quizzes.show', $course))
            ->assertOk();

        $quizSession = QuizSession::where('user_id', $student->id)
            ->where('course_quiz_id', $quiz->id)
            ->firstOrFail();

        $this->actingAs($student)
            ->postJson(route('quizzes.submit', $course), [
                'quiz_session' => $quizSession->id,
                'answers' => [$question->id => 999999],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_quiz_attempt_limit_and_expired_timer_are_enforced(): void
    {
        [$teacher, $student, $course] = $this->createEnrolledStudentWithCompletedLessons();
        $this->createQuiz($teacher, $course);
        $quiz = $course->quiz()->with('questions.options')->firstOrFail();
        $question = $quiz->questions->first();
        $correctOption = $question->options->firstWhere('is_correct', true);

        for ($attempt = 1; $attempt <= $quiz->max_attempts; $attempt++) {
            QuizSession::create([
                'course_quiz_id' => $quiz->id,
                'user_id' => $student->id,
                'question_order' => [$question->id],
                'expires_at' => now()->subMinute(),
                'status' => 'expired',
            ]);
        }
        $this->actingAs($student)
            ->get(route('quizzes.show', $course))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('access_notice', 'وصلت للحد الأقصى من محاولات الاختبار.');

        $otherStudent = User::factory()->create(['role' => 'student']);
        $this->enrollStudent($otherStudent, $course);
        $lesson = $course->lessons()->firstOrFail();
        LessonProgress::create([
            'user_id' => $otherStudent->id,
            'lesson_id' => $lesson->id,
            'progress' => 100,
            'completed' => true,
            'completed_at' => now(),
        ]);
        $session = QuizSession::create([
            'course_quiz_id' => $quiz->id,
            'user_id' => $otherStudent->id,
            'question_order' => [$question->id],
            'expires_at' => now()->subMinute(),
            'status' => 'active',
        ]);
        $this->actingAs($otherStudent)
            ->from(route('quizzes.show', $course))
            ->post(route('quizzes.submit', $course), [
                'quiz_session' => $session->id,
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertRedirect(route('quizzes.show', $course))
            ->assertSessionHasErrors('answers');

        $this->assertSame('expired', $session->fresh()->status);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    private function createEnrolledStudentWithCompletedLessons(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = $this->createLesson($course);
        $this->enrollStudent($student, $course);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'progress' => 100,
            'completed' => true,
            'completed_at' => now(),
        ]);

        return [$teacher, $student, $course, $lesson];
    }

    private function createQuiz(User $teacher, Course $course): void
    {
        $this->actingAs($teacher)
            ->put(route('quizzes.save', $course), $this->quizPayload())
            ->assertRedirect(route('quizzes.edit', $course));
    }

    private function quizPayload(): array
    {
        return [
            'title' => 'Final quiz',
            'passing_score' => 70,
            'questions' => [
                [
                    'text' => 'Choose the answer',
                    'options' => ['Wrong one', 'Correct one', 'Wrong two', 'Wrong three'],
                    'correct' => 1,
                ],
            ],
        ];
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Quiz course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
    }

    private function createLesson(Course $course): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Course lesson',
            'order' => 1,
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
