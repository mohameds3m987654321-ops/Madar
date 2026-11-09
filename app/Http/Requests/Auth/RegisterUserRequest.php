<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'student_phone' => ['required', 'string', 'max:20'],
            'parent_phone' => ['required', 'string', 'max:20'],
            'education_stage' => ['required', 'string', 'in:preparatory,secondary'],
            'education_grade' => ['required', 'string', 'in:1,2,3'],
            'education_system' => [
                'exclude_unless:education_stage,secondary',
                'required',
                Rule::in(['baccalaureate', 'old']),
            ],
            'education_type' => ['required', 'string', 'in:general,azhar'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }
}
