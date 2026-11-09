<?php

namespace App\Http\Requests\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

class SaveQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.text' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array:0,1,2,3', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:255'],
            'questions.*.correct' => ['required', 'integer', 'min:0', 'max:3'],
        ];
    }
}
