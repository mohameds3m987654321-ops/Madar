<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $userId = $this->route('id');
        $isUpdatingSelf = (string) $userId === (string) $this->user()->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
            'role' => $isUpdatingSelf
                ? ['exclude']
                : ['required', Rule::in(['student', 'teacher', 'admin'])],
        ];
    }
}
