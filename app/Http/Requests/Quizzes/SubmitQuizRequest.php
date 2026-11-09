<?php

namespace App\Http\Requests\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_session' => ['required', 'integer', 'exists:quiz_sessions,id'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['required', 'integer', 'distinct'],
        ];
    }
}