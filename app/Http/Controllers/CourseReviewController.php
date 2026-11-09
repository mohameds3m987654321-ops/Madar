<?php

namespace App\Http\Controllers;

use App\Http\Requests\Courses\StoreCourseReviewRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;

class CourseReviewController extends Controller
{
    public function store(StoreCourseReviewRequest $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        abort_unless($course->status === 'published', 404);
        $isPaidStudent = $course->enrollments()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->exists();
        abort_unless($isPaidStudent, 403, 'يجب الاشتراك في الكورس لتقييمه.');

        $threshold = (int) config('courses.review_completion_percentage', 70);
        abort_unless(
            $course->completionPercentageForUser($user->id) >= $threshold,
            403,
            "أكمل {$threshold}% على الأقل من دروس الكورس قبل تقييمه."
        );

        $validated = $request->validated();

        $course->reviews()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'تم حفظ تقييمك للكورس بنجاح.');
    }
}
