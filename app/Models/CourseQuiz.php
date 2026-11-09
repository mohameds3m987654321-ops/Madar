<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseQuiz extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'passing_score',
        'max_attempts',
        'time_limit_minutes',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function sessions()
    {
        return $this->hasMany(QuizSession::class);
    }
}
