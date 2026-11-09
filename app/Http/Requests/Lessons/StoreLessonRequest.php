<?php

namespace App\Http\Requests\Lessons;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasVideo = $this->hasFile('video');

        return [
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'video' => ['nullable', 'file', 'mimes:mp4'],
            'video_duration_seconds' => [
                Rule::requiredIf($hasVideo),
                'nullable',
                'integer',
                'min:1',
                'max:86400',
            ],
        ];
    }
}
