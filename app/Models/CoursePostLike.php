<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePostLike extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'course_post_id',
        'user_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CoursePost::class, 'course_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
