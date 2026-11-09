<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonVideoUpload extends Model
{
    protected $fillable = [
        'token',
        'teacher_id',
        'course_id',
        'lesson_id',
        'title',
        'content',
        'lesson_order',
        'video_duration_seconds',
        'total_chunks',
        'status',
    ];
}
