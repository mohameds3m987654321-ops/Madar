<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->role === 'student', 403);

        $enrollments = CourseEnrollment::query()
            ->where('user_id', auth()->id())
            ->with(['course.teacher', 'coupon', 'refundRequest'])
            ->latest()
            ->paginate(15);

        return view('Payments.index', compact('enrollments'));
    }

    public function invoice(CourseEnrollment $enrollment): View|RedirectResponse
    {
        abort_unless(
            auth()->user()->role === 'student' && $enrollment->user_id === auth()->id(),
            403
        );
        abort_unless(in_array($enrollment->status, ['paid', 'refunded'], true), 404);

        $enrollment->load(['course.teacher', 'user', 'coupon']);

        return view('Payments.invoice', compact('enrollment'));
    }
}
