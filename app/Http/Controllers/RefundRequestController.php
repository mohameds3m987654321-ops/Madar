<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use App\Models\RefundRequest;
use App\Notifications\CourseActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RefundRequestController extends Controller
{
    public function store(Request $request, CourseEnrollment $enrollment): RedirectResponse
    {
        abort_unless(
            $request->user()->role === 'student'
                && $enrollment->user_id === $request->user()->id,
            403
        );
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $enrollment, $validated) {
            $lockedEnrollment = CourseEnrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless(
                $lockedEnrollment->status === 'paid' && $lockedEnrollment->amount_cents > 0,
                422,
                'لا يمكن طلب استرداد لهذا الدفع.'
            );
            abort_if(
                $lockedEnrollment->refundRequest()->exists(),
                409,
                'يوجد طلب استرداد مسجل لهذا الدفع بالفعل.'
            );

            $lockedEnrollment->refundRequest()->create([
                'user_id' => $request->user()->id,
                'reason' => $validated['reason'],
            ]);
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'تم تسجيل طلب الاسترداد للمراجعة اليدوية.');
    }

    public function index(): View
    {
        $requests = RefundRequest::query()
            ->with(['user:id,name,email', 'enrollment.course:id,title'])
            ->latest()
            ->paginate(20);

        return view('Refunds.index', compact('requests'));
    }

    public function update(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'completed'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $refundRequest, $validated) {
            $lockedRequest = RefundRequest::query()
                ->whereKey($refundRequest->id)
                ->lockForUpdate()
                ->with('enrollment.couponRedemption')
                ->firstOrFail();
            $nextStatus = $validated['status'];
            $allowed = match ($lockedRequest->status) {
                'pending' => ['approved', 'rejected'],
                'approved' => ['completed'],
                default => [],
            };
            abort_unless(in_array($nextStatus, $allowed, true), 422, 'حالة طلب الاسترداد غير صالحة.');

            $lockedRequest->update([
                'status' => $nextStatus,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
            ]);

            if ($nextStatus === 'completed') {
                $lockedRequest->enrollment->update(['status' => 'refunded']);
                $lockedRequest->enrollment->couponRedemption?->update(['status' => 'refunded']);
            }

            $statusText = [
                'approved' => 'تمت الموافقة على طلب الاسترداد. سيُعالج رد المبلغ يدويًا.',
                'rejected' => 'تم رفض طلب الاسترداد.',
                'completed' => 'تم تأكيد إتمام الاسترداد.',
            ][$nextStatus];

            $lockedRequest->user->notify(new CourseActivityNotification(
                'refund_updated',
                'تحديث طلب الاسترداد',
                $statusText,
                route('payments.index', [], false),
            ));
        });

        return redirect()
            ->route('refunds.index')
            ->with('success', 'تم تحديث طلب الاسترداد.');
    }
}
