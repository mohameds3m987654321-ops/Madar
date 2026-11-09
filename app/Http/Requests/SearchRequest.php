<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', 'courses'),
        ]);
    }

    public function authorize(): bool
    {
        $role = $this->user()?->role;
        $type = $this->input('type');

        return match ($role) {
            'admin' => in_array($type, ['courses', 'users'], true),
            'student' => in_array($type, ['courses', 'teachers'], true),
            'teacher' => $type === 'courses',
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'type' => [
                'required',
                Rule::in(match ($this->user()?->role) {
                    'admin' => ['courses', 'users'],
                    'student' => ['courses', 'teachers'],
                    'teacher' => ['courses'],
                    default => [],
                }),
            ],
        ];
    }
}
