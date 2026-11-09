<?php

namespace Tests\Feature;

use App\Exports\TeacherPaymentsExport;
use App\Exports\TeacherStudentsExport;
use App\Jobs\GenerateReport;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\TeacherReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TeacherReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_queue_a_monthly_report(): void
    {
        Queue::fake();
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->post(route('teacher.reports.generate'), ['month' => now()->format('Y-m')])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report = TeacherReport::query()->sole();
        $this->assertSame('queued', $report->status);
        $this->assertSame($teacher->id, $report->teacher_id);

        Queue::assertPushed(GenerateReport::class, fn (GenerateReport $job) => (
            $job->teacherId === $teacher->id
            && $job->year === now()->year
            && $job->month === now()->month
        ));
    }

    public function test_generate_report_saves_only_paid_income_for_the_teachers_month(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $firstStudent = User::factory()->create(['role' => 'student']);
        $secondStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'Owned course');
        $otherCourse = $this->createCourse($otherTeacher, 'Other course');

        $this->enrollStudent($firstStudent, $course, 'paid', 12500, now());
        $this->enrollStudent($secondStudent, $course, 'paid', 25000, now());
        $this->enrollStudent(User::factory()->create(['role' => 'student']), $course, 'pending', 5000, null);
        $this->enrollStudent(User::factory()->create(['role' => 'student']), $otherCourse, 'paid', 99000, now());

        (new GenerateReport($teacher->id, now()->year, now()->month))->handle();

        $report = TeacherReport::query()->sole();
        $this->assertSame('completed', $report->status);
        $this->assertSame(37500, $report->data['earnings_cents']);
        $this->assertSame(2, $report->data['payment_count']);
        $this->assertSame(2, $report->data['student_count']);
        $this->assertSame('Owned course', $report->data['courses'][0]['title']);
    }

    public function test_dashboard_shows_monthly_income_and_abandoned_lesson_rates(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $firstStudent = User::factory()->create(['role' => 'student']);
        $secondStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'Analytics course');
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson with drop-off',
            'order' => 1,
        ]);
        $this->enrollStudent($firstStudent, $course, 'paid', 12345, now());
        $this->enrollStudent($secondStudent, $course, 'paid', 12345, now());

        LessonProgress::create([
            'user_id' => $firstStudent->id,
            'lesson_id' => $lesson->id,
            'started_at' => now()->subHour(),
            'completed' => false,
            'progress' => 45,
        ]);
        LessonProgress::create([
            'user_id' => $secondStudent->id,
            'lesson_id' => $lesson->id,
            'started_at' => now()->subHour(),
            'completed' => true,
            'completed_at' => now(),
            'progress' => 100,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('إجمالي الإيرادات المؤكدة')
            ->assertSee('246.90 ج.م')
            ->assertSee('Lesson with drop-off')
            ->assertSee('تركوا الدرس')
            ->assertSee('نسبة الإكمال')
            ->assertSee('50%');
    }

    public function test_teacher_can_download_only_their_student_and_payment_exports(): void
    {
        Excel::fake();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Teacher student']);
        $otherStudent = User::factory()->create(['role' => 'student', 'name' => 'Other student']);
        $course = $this->createCourse($teacher, 'Teacher course');
        $otherCourse = $this->createCourse($otherTeacher, 'Other teacher course');
        $this->enrollStudent($student, $course, 'paid', 25000, now());
        $this->enrollStudent($otherStudent, $otherCourse, 'paid', 25000, now());
        $date = now()->format('Y-m-d');

        $this->actingAs($teacher)
            ->get(route('teacher.exports.students'))
            ->assertOk();
        Excel::assertDownloaded("teacher-students-{$date}.xlsx", function (TeacherStudentsExport $export) use ($student) {
            return $export->query()->pluck('student_name')->all() === [$student->name];
        });

        $this->actingAs($teacher)
            ->get(route('teacher.exports.payments'))
            ->assertOk();
        Excel::assertDownloaded("teacher-payments-{$date}.xlsx", function (TeacherPaymentsExport $export) use ($student) {
            return $export->query()->pluck('student_name')->all() === [$student->name];
        });
    }

    public function test_student_export_is_a_valid_xlsx_file(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'Export course');
        $this->enrollStudent($student, $course, 'paid', 25000, now());

        $contents = Excel::raw(
            new TeacherStudentsExport($teacher->id),
            \Maatwebsite\Excel\Excel::XLSX,
        );

        $this->assertStringStartsWith('PK', $contents);
    }

    public function test_student_cannot_request_teacher_reports_or_exports(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('teacher.reports.generate'), ['month' => now()->format('Y-m')])
            ->assertRedirect(route('users.profile'));

        $this->actingAs($student)
            ->get(route('teacher.exports.students'))
            ->assertRedirect(route('users.profile'));
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

    private function enrollStudent(
        User $student,
        Course $course,
        string $status,
        int $amountCents,
        ?Carbon $paidAt,
    ): CourseEnrollment {
        return CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => $status,
            'amount_cents' => $amountCents,
            'paid_at' => $paidAt,
        ]);
    }
}
