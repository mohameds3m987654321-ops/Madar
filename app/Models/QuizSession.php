<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizSession extends Model
{
    protected $fillable = [
        'course_quiz_id',
        'user_id',
        'question_order',
        'expires_at',
        'submitted_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'question_order' => 'array',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class, 'course_quiz_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
