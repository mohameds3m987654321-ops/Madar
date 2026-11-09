<?php

namespace App\Http\Requests\Lessons;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lesson = $this->route('lesson');
        $hasVideo = $this->hasFile('video')
            || ($lesson instanceof Lesson && $lesson->video !== null);

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
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
