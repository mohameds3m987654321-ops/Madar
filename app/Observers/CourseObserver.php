<?php

namespace App\Observers;

use App\Models\Course;
use App\Services\CourseNotificationService;

class CourseObserver
{
    public function __construct(
        private readonly CourseNotificationService $notificationService,
    ) {}

    public function created(Course $course): void
    {
        if ($course->status !== 'published') {
            return;
        }

        $this->notifyAdminsOfNewCourse($course);
    }

    public function updated(Course $course): void
    {
        // أول مرة الكورس ينتقل من مسودة إلى منشور → إشعار الأدمن
        if (
            $course->wasChanged('status')
            && $course->status === 'published'
            && $course->getOriginal('status') !== 'published'
        ) {
            $this->notifyAdminsOfNewCourse($course);
        }
    }

    private function notifyAdminsOfNewCourse(Course $course): void
    {
        $course->loadMissing('teacher');

        $teacher = $course->teacher;

        if ($teacher && $teacher->role !== 'teacher') {
            return;
        }

        $this->notificationService->notifyAdminsOfNewCourse($course);
    }
}
