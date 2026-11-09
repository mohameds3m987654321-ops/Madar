<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function apply(
        Course $course,
        CourseEnrollment $enrollment,
        string $couponCode
    ): int {
        $code = mb_strtoupper(trim($couponCode));
        $coupon = Coupon::query()
            ->where('course_id', $course->id)
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if (
            !$coupon
            || !$coupon->is_active
            || ($coupon->starts_at && $coupon->starts_at->isFuture())
            || ($coupon->expires_at && $coupon->expires_at->isPast())
        ) {
            throw ValidationException::withMessages([
                'coupon_code' => 'كود الخصم غير صالح أو غير متاح حاليًا.',
            ]);
        }

        $existingRedemption = CouponRedemption::query()
            ->where('course_enrollment_id', $enrollment->id)
            ->first();
        $reservedUses = $coupon->redemptions()
            ->whereIn('status', ['pending', 'paid'])
            ->when(
                $existingRedemption?->coupon_id === $coupon->id,
                fn ($query) => $query->where('course_enrollment_id', '!=', $enrollment->id)
            )
            ->count();

        if ($coupon->max_redemptions !== null && $reservedUses >= $coupon->max_redemptions) {
            throw ValidationException::withMessages([
                'coupon_code' => 'كود الخصم غير صالح أو غير متاح حاليًا.',
            ]);
        }

        $priceCents = (int) round((float) $course->price * 100);
        $discountCents = $coupon->type === 'percentage'
            ? (int) round($priceCents * ((float) $coupon->value / 100))
            : (int) round((float) $coupon->value * 100);
        $discountCents = min($priceCents, max(0, $discountCents));

        $enrollment->fill([
            'coupon_id' => $coupon->id,
            'discount_cents' => $discountCents,
            'amount_cents' => $priceCents - $discountCents,
        ]);

        CouponRedemption::updateOrCreate(
            ['course_enrollment_id' => $enrollment->id],
            [
                'coupon_id' => $coupon->id,
                'status' => 'pending',
            ]
        );

        return $discountCents;
    }
}
