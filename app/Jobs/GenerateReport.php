<?php

namespace App\Jobs;

use App\Models\CourseEnrollment;
use App\Models\TeacherReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class GenerateReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $teacherId,
        public readonly int $year,
        public readonly int $month,
    ) {}

    public function handle(): void
    {
        $start = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $paidEnrollments = CourseEnrollment::query()
            ->join('courses', 'courses.id', '=', 'course_enrollments.course_id')
            ->where('courses.teacher_id', $this->teacherId)
            ->where('course_enrollments.status', 'paid')
            ->whereBetween('course_enrollments.paid_at', [$start, $end]);

        $totals = (clone $paidEnrollments)
            ->selectRaw('COALESCE(SUM(course_enrollments.amount_cents), 0) AS earnings_cents')
            ->selectRaw('COUNT(*) AS payment_count')
            ->selectRaw('COUNT(DISTINCT course_enrollments.user_id) AS student_count')
            ->first();

        $courses = (clone $paidEnrollments)
            ->select('courses.id', 'courses.title')
            ->selectRaw('SUM(course_enrollments.amount_cents) AS earnings_cents')
            ->selectRaw('COUNT(*) AS payment_count')
            ->groupBy('courses.id', 'courses.title')
            ->orderBy('courses.title')
            ->get()
            ->map(fn ($course) => [
                'course_id' => (int) $course->id,
                'title' => $course->title,
                'earnings_cents' => (int) $course->earnings_cents,
                'payment_count' => (int) $course->payment_count,
            ])
            ->all();

        TeacherReport::query()->updateOrCreate(
            [
                'teacher_id' => $this->teacherId,
                'year' => $this->year,
                'month' => $this->month,
            ],
            [
                'status' => 'completed',
                'data' => [
                    'earnings_cents' => (int) $totals->earnings_cents,
                    'payment_count' => (int) $totals->payment_count,
                    'student_count' => (int) $totals->student_count,
                    'courses' => $courses,
                ],
                'generated_at' => now(),
            ],
        );
    }
}
