<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Scopes\AdminUsersScope;
use App\Models\User;
use App\Notifications\CourseActivityNotification;
use Illuminate\Support\Facades\Notification;

class CourseNotificationService
{
    public function notifyPaidStudents(Course $course, CourseActivityNotification $notification): void
    {
        $course->enrollments()
            ->where('status', 'paid')
            ->whereHas('user', fn ($query) => $query->where('role', 'student'))
            ->with('user:id,name,email,role')
            ->chunkById(100, function ($enrollments) use ($notification) {
                Notification::send(
                    $enrollments->pluck('user')->filter()->values(),
                    $notification
                );
            });
    }

    public function notifyAdmins(string $event, string $title, string $message, string $url): void
    {
        $admins = User::query()
            ->withGlobalScope(AdminUsersScope::class, new AdminUsersScope)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new CourseActivityNotification($event, $title, $message, $url));
    }

    public function notifyAdminsOfNewCourse(Course $course): void
    {
        $teacherName = $course->teacher->name ?? 'مدرس';

        $this->notifyAdmins(
            'new_course_created',
            'كورس جديد',
            'أنشأ '.$teacherName.' كورسًا جديدًا: '.$course->title.'.',
            route('courses.show', $course, false),
        );
    }
}
