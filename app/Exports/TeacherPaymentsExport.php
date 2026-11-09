<?php

namespace App\Exports;

use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TeacherPaymentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly int $teacherId) {}

    public function query(): Builder
    {
        return CourseEnrollment::query()
            ->join('courses', 'courses.id', '=', 'course_enrollments.course_id')
            ->join('users', 'users.id', '=', 'course_enrollments.user_id')
            ->where('courses.teacher_id', $this->teacherId)
            ->select([
                'users.name as student_name',
                'users.email as student_email',
                'courses.title as course_title',
                'course_enrollments.payment_method',
                'course_enrollments.status',
                'course_enrollments.amount_cents',
                'course_enrollments.paid_at',
                'course_enrollments.created_at',
            ])
            ->orderBy('course_enrollments.created_at');
    }

    public function headings(): array
    {
        return [
            'اسم الطالب',
            'البريد الإلكتروني',
            'الكورس',
            'طريقة الدفع',
            'حالة الدفع',
            'المبلغ (ج.م)',
            'تاريخ الدفع',
            'تاريخ الاشتراك',
        ];
    }

    public function map($enrollment): array
    {
        return [
            $this->safeText($enrollment->student_name),
            $this->safeText($enrollment->student_email),
            $this->safeText($enrollment->course_title),
            $this->safeText($enrollment->payment_method),
            $this->statusLabel($enrollment->status),
            $enrollment->amount_cents / 100,
            $enrollment->paid_at,
            $enrollment->created_at,
        ];
    }

    private function statusLabel(string $status): string
    {
        return [
            'paid' => 'مدفوع',
            'pending' => 'بانتظار الدفع',
            'failed' => 'فشل الدفع',
        ][$status] ?? $status;
    }

    private function safeText(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) === 1 ? "'".$value : $value;
    }
}
