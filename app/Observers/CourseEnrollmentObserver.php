<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Notifications\CourseActivityNotification;

class CourseEnrollmentObserver
{
    public function created(CourseEnrollment $enrollment): void
    {
        if ($enrollment->status === 'paid') {
            Course::forgetPublicPageCaches();
            $this->notifyCourseTeacher($enrollment);
        }
    }

    public function updated(CourseEnrollment $enrollment): void
    {
        if (
            $enrollment->wasChanged('status')
            && ($enrollment->status === 'paid' || $enrollment->getOriginal('status') === 'paid')
        ) {
            Course::forgetPublicPageCaches();
        }

        if ($enrollment->wasChanged('status') && $enrollment->status === 'paid') {
            $this->notifyCourseTeacher($enrollment);
        }
    }

    public function deleted(CourseEnrollment $enrollment): void
    {
        if ($enrollment->status === 'paid') {
            Course::forgetPublicPageCaches();
        }
    }

    private function notifyCourseTeacher(CourseEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['user', 'course.teacher']);

        $student = $enrollment->user;
        $course = $enrollment->course;
        $teacher = $course?->teacher;

        if (
            ! $student
            || $student->role !== 'student'
            || ! $teacher
            || $teacher->role !== 'teacher'
        ) {
            return;
        }

        $teacher->notify(new CourseActivityNotification(
            'student_enrolled',
            'طالب جديد في كورسك',
            'اشترك '.$student->name.' في كورس '.$course->title.'.',
            route('courses.edit', $course, false),
        ));
    }
}
