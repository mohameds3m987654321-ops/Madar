<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'content',
        'order',
        'video',
        'video_duration_seconds',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Course::forgetPublicPageCaches());
        static::deleted(fn () => Course::forgetPublicPageCaches());
    }

    public function isVideoProcessing(): bool
    {
        return is_string($this->video)
            && str_starts_with($this->video, 'pending/videos/');
    }

    public function progresses()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function comments()
    {
        return $this->hasMany(LessonComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class);
    }
}
