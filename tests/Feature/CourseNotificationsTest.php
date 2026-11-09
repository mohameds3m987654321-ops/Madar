<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Mail\EmailVerificationCodeMail;
use App\Notifications\CourseActivityNotification;
use App\Services\CourseCertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CourseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_lesson_notifies_only_paid_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $paidStudent = User::factory()->create(['role' => 'student']);
        $pendingStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enroll($paidStudent, $course, 'paid');
        $this->enroll($pendingStudent, $course, 'pending');

        $response = $this->actingAs($teacher)
            ->post(route('lessons.store-for-course', $course), [
                'title' => 'New grammar lesson',
                'content' => 'Lesson description',
                'order' => 1,
            ]);

        $notification = $paidStudent->notifications()->firstOrFail();
        $lesson = Lesson::firstOrFail();
        $response->assertRedirect(route('lessons.edit', [$course, $lesson]));
        $this->assertSame(CourseActivityNotification::class, $notification->type);
        $this->assertSame('lesson_added', $notification->data['event']);
        $this->assertStringContainsString('New grammar lesson', $notification->data['message']);
        $this->assertSame(
            route('lessons.show', $lesson->id, false),
            $notification->data['url']
        );
        $this->assertDatabaseCount('notifications', 2);
        $teacherNotification = $teacher->notifications()->firstOrFail();
        $this->assertSame('student_enrolled', $teacherNotification->data['event']);
        $this->assertSame(0, $pendingStudent->notifications()->count());
    }

    public function test_course_notifications_are_queued_for_each_paid_student(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $students = User::factory()->count(2)->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $students->each(fn (User $student) => $this->enroll($student, $course, 'paid'));

        Queue::fake();

        app(\App\Services\CourseNotificationService::class)->notifyPaidStudents(
            $course,
            new CourseActivityNotification(
                'lesson_added',
                'محاضرة جديدة',
                'تمت إضافة محاضرة جديدة.',
                route('courses.show', $course, false),
            )
        );

        Queue::assertPushed(SendQueuedNotifications::class, 2);
    }

    public function test_new_student_registration_notifies_admins_and_appears_in_their_profile(): void
    {
        config([
            'queue.default' => 'sync',
            'mail.default' => 'array',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->post(route('users.store'), [
            'name' => 'New Student',
            'email' => 'new-student@example.test',
            'student_phone' => '01000000000',
            'parent_phone' => '01100000000',
            'education_stage' => 'secondary',
            'education_grade' => '1',
            'education_system' => 'old',
            'education_type' => 'general',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $student = User::where('email', 'new-student@example.test')->firstOrFail();
        $notification = $admin->notifications()->firstOrFail();

        $this->assertSame('new_user_registered', $notification->data['event']);
        $this->assertStringContainsString('New Student', $notification->data['message']);
        $this->assertSame(route('users.show', $student, false), $notification->data['url']);
        $this->assertSame(0, $teacher->notifications()->count());

        $this->actingAs($admin)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertSee('مستخدم جديد')
            ->assertSee('New Student');
    }

    public function test_registration_email_is_sent_without_queue_and_admin_alert_is_queued(): void
    {
        User::factory()->create(['role' => 'admin']);
        Mail::fake();
        Queue::fake();

        $this->post(route('users.store'), [
            'name' => 'Queued Student',
            'email' => 'queued-student@example.test',
            'student_phone' => '01000000000',
            'parent_phone' => '01100000000',
            'education_stage' => 'secondary',
            'education_grade' => '1',
            'education_system' => 'old',
            'education_type' => 'general',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        Mail::assertSent(EmailVerificationCodeMail::class);
        Queue::assertPushed(SendQueuedNotifications::class, 1);
        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job) => $job->notification instanceof CourseActivityNotification,
        );
    }

    public function test_teacher_is_notified_once_when_enrollment_becomes_paid(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Enrolled Student',
        ]);
        $course = $this->createCourse($teacher);
        $enrollment = CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'vodafone_cash',
            'status' => 'pending',
            'amount_cents' => 25000,
        ]);

        $this->assertSame(0, $teacher->notifications()->count());

        $enrollment->update(['status' => 'paid', 'paid_at' => now()]);
        $enrollment->update(['paymob_transaction_id' => 'transaction-1']);

        $notification = $teacher->notifications()->firstOrFail();
        $this->assertSame('student_enrolled', $notification->data['event']);
        $this->assertStringContainsString('Enrolled Student', $notification->data['message']);
        $this->assertStringContainsString($course->title, $notification->data['message']);
        $this->assertSame(route('courses.edit', $course, false), $notification->data['url']);
        $this->assertSame(1, $teacher->notifications()->count());

        $this->actingAs($teacher)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertSee('طالب جديد في كورسك')
            ->assertSee('Enrolled Student');
    }

    public function test_new_quiz_notifies_paid_students_once_even_when_later_edited(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enroll($student, $course, 'paid');

        $this->actingAs($teacher)
            ->put(route('quizzes.save', $course), $this->quizPayload())
            ->assertRedirect(route('quizzes.edit', $course));

        $notification = $student->notifications()->firstOrFail();
        $this->assertSame('quiz_available', $notification->data['event']);
        $this->assertStringContainsString('أكمل المحاضرات', $notification->data['message']);
        $this->assertSame(route('courses.show', $course, false), $notification->data['url']);

        $this->put(route('quizzes.save', $course), $this->quizPayload())
            ->assertRedirect(route('quizzes.edit', $course));

        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_certificate_notification_is_created_only_once_when_certificate_is_issued(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = $this->createLesson($course);
        $this->enroll($student, $course, 'paid');
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'progress' => 100,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $certificates = app(CourseCertificateService::class);
        $certificate = $certificates->issueIfEligible($course, $student);
        $certificates->issueIfEligible($course, $student);

        $this->assertNotNull($certificate);
        $notification = $student->notifications()->firstOrFail();
        $this->assertSame('certificate_issued', $notification->data['event']);
        $this->assertSame(
            route('courses.certificate', $course, false),
            $notification->data['url']
        );
        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_student_profile_lists_notifications_and_opening_one_marks_it_read(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $student->notify(new CourseActivityNotification(
            'lesson_added',
            'محاضرة جديدة',
            'تمت إضافة محاضرة جديدة.',
            route('courses.show', $course, false),
        ));
        $notification = $student->notifications()->firstOrFail();

        $this->actingAs($student)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertSee('الإشعارات')
            ->assertSee('محاضرة جديدة')
            ->assertSee('1 إشعار غير مقروء');

        $this->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('courses.show', $course, false));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Notification course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);
    }

    private function createLesson(Course $course): Lesson
    {
        return Lesson::create([
            'course_id' => $course->id,
            'title' => 'Completed lesson',
            'order' => 1,
        ]);
    }

    private function enroll(User $student, Course $course, string $status): void
    {
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => $status,
            'amount_cents' => 0,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
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
}
