<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'education_stage' => ['nullable', Rule::in(['preparatory', 'secondary'])],
            'education_grade' => [
                'exclude_unless:education_stage,preparatory,secondary',
                'required',
                Rule::in(['1', '2', '3']),
            ],
            'education_type' => [
                'exclude_unless:education_stage,preparatory,secondary',
                'required',
                Rule::in(['general', 'azhar']),
            ],
            'education_system' => [
                'exclude_unless:education_stage,secondary',
                'nullable',
                Rule::in(['baccalaureate', 'old']),
            ],
        ];
    }
}
