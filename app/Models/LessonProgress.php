<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Lesson;

class LessonProgress extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'last_position',
        'duration',
        'watched_seconds',
        'progress',
        'started_at',
        'completed',
        'completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
