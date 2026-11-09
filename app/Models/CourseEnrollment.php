<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'payment_method',
        'status',
        'amount_cents',
        'paymob_order_id',
        'paymob_transaction_id',
        'paid_at',
        'coupon_id',
        'discount_cents',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class, 'course_enrollment_id');
    }

    public function couponRedemption()
    {
        return $this->hasOne(CouponRedemption::class, 'course_enrollment_id');
    }
}
