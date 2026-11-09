<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'course_quiz_id',
        'user_id',
        'score',
        'correct_answers',
        'total_questions',
        'passed',
        'results',
        'submitted_at',
        'quiz_session_id',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'results' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz()
    {
        return $this->belongsTo(CourseQuiz::class, 'course_quiz_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(QuizSession::class, 'quiz_session_id');
    }
}
