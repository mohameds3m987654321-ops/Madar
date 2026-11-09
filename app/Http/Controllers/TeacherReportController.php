<?php

namespace App\Http\Controllers;

use App\Exports\TeacherPaymentsExport;
use App\Exports\TeacherStudentsExport;
use App\Jobs\GenerateReport;
use App\Models\TeacherReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherReportController extends Controller
{
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m', 'before_or_equal:today'],
        ]);
        $reportMonth = Carbon::createFromFormat('!Y-m', $validated['month']);

        TeacherReport::query()->updateOrCreate(
            [
                'teacher_id' => $request->user()->id,
                'year' => $reportMonth->year,
                'month' => $reportMonth->month,
            ],
            [
                'status' => 'queued',
                'data' => null,
                'generated_at' => null,
            ],
        );

        GenerateReport::dispatch(
            (int) $request->user()->id,
            $reportMonth->year,
            $reportMonth->month,
        );

        return back()->with('success', 'تمت إضافة التقرير إلى قائمة الانتظار.');
    }

    public function students(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new TeacherStudentsExport((int) $request->user()->id),
            'teacher-students-'.$this->downloadDate().'.xlsx',
        );
    }

    public function payments(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new TeacherPaymentsExport((int) $request->user()->id),
            'teacher-payments-'.$this->downloadDate().'.xlsx',
        );
    }

    private function downloadDate(): string
    {
        return now()->format('Y-m-d');
    }
}
