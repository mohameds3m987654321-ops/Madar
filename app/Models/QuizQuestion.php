<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['course_quiz_id', 'text', 'position'];

    public function quiz()
    {
        return $this->belongsTo(CourseQuiz::class, 'course_quiz_id');
    }

    public function options()
    {
        return $this->hasMany(QuizOption::class)->orderBy('position');
    }
}
