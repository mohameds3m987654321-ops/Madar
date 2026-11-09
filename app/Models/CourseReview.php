<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseReview extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'rating',
        'review',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Course::forgetPublicPageCaches());
        static::deleted(fn () => Course::forgetPublicPageCaches());
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
