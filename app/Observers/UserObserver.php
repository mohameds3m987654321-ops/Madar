<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\User;
use App\Services\CourseNotificationService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function __construct(
        private readonly CourseNotificationService $notificationService,
    ) {}

    public function saved(User $user): void
    {
        if ($user->wasChanged(['name', 'image', 'role'])) {
            Course::forgetPublicPageCaches();
        }
    }

    public function deleted(User $user): void
    {
        Course::forgetPublicPageCaches();
    }

    public function created(User $user): void
    {
        Log::info('User account created.', [
            'user_id' => $user->getKey(),
        ]);

        if ($user->role !== 'student') {
            return;
        }

        $this->notificationService->notifyAdmins(
            'new_user_registered',
            'مستخدم جديد',
            'أنشأ '.$user->name.' حسابًا جديدًا.',
            route('users.show', $user, false),
        );
    }
}
