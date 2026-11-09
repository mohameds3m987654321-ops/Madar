<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoursePost extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'body',
        'external_url',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'is_pinned',
        'pinned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'pinned_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CoursePostComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CoursePostLike::class);
    }
}
