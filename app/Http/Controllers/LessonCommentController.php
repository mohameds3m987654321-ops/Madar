<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lessons\StoreLessonCommentRequest;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Notifications\CourseActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class LessonCommentController extends Controller
{
    public function store(StoreLessonCommentRequest $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();
        $course = $lesson->course;
        $isTeacher = $user->role === 'teacher'
            && $course->teacher_id === $user->id;
        $isEnrolledStudent = $user->role === 'student'
            && $course->status === 'published'
            && $course->enrollments()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->exists();

        abort_unless($isTeacher || $isEnrolledStudent, 403);

        $validated = $request->validated();

        if ($isEnrolledStudent && !empty($validated['parent_id'])) {
            abort(403, 'يمكن للطلاب إضافة أسئلة جديدة فقط.');
        }

        if ($isTeacher && empty($validated['parent_id'])) {
            throw ValidationException::withMessages([
                'parent_id' => 'اختر السؤال الذي تريد الرد عليه.',
            ]);
        }

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId !== null) {
            $parent = LessonComment::query()
                ->where('lesson_id', $lesson->id)
                ->whereKey($parentId)
                ->firstOrFail();
            abort_unless($parent->parent_id === null, 422, 'الردود تكون على السؤال الأصلي فقط.');
        }

        $lesson->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'body' => $validated['body'],
        ]);

        if ($isTeacher && $parent->user_id !== $user->id) {
            $parent->user->notify(new CourseActivityNotification(
                'question_answered',
                'تم الرد على سؤالك',
                'رد مدرس الكورس على سؤالك في محاضرة ' . $lesson->title . '.',
                route('lessons.show', $lesson, false) . '#lesson-qa',
            ));
        }

        return redirect()
            ->to(route('lessons.show', $lesson) . '#lesson-qa')
            ->with(
                'lesson_comment_success',
                $isTeacher ? 'تم إرسال ردك على السؤال.' : 'تم إرسال سؤالك للمدرس.'
            );
    }
}
