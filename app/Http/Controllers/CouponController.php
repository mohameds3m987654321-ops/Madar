<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Course $course): View
    {
        $this->ensureOwnership($course);
        $coupons = $course->coupons()
            ->withCount([
                'redemptions as reserved_uses' => fn ($query) =>
                    $query->whereIn('status', ['pending', 'paid']),
                'redemptions as completed_uses' => fn ($query) =>
                    $query->where('status', 'paid'),
            ])
            ->latest()
            ->get();

        return view('Coupons.index', compact('course', 'coupons'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureOwnership($course);
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:48',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons')->where('course_id', $course->id),
            ],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'max_redemptions' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        if ($validated['type'] === 'percentage' && (float) $validated['value'] > 100) {
            return back()->withInput()->withErrors([
                'value' => 'نسبة الخصم لا يمكن أن تتجاوز 100%.',
            ]);
        }

        $course->coupons()->create([
            ...$validated,
            'code' => mb_strtoupper($validated['code']),
        ]);

        return redirect()
            ->route('courses.coupons.index', $course)
            ->with('success', 'تم إنشاء كوبون الخصم.');
    }

    public function toggle(Course $course, Coupon $coupon): RedirectResponse
    {
        $this->ensureOwnership($course);
        abort_unless($coupon->course_id === $course->id, 404);

        DB::transaction(function () use ($coupon) {
            $lockedCoupon = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();
            $lockedCoupon->update(['is_active' => !$lockedCoupon->is_active]);
        });

        return redirect()
            ->route('courses.coupons.index', $course)
            ->with('success', 'تم تحديث حالة الكوبون.');
    }

    private function ensureOwnership(Course $course): void
    {
        abort_unless(
            auth()->user()->role === 'teacher' && $course->teacher_id === auth()->id(),
            403
        );
    }
}
