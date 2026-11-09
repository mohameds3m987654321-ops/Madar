<?php

namespace App\Providers;

use App\CoursePolicy;
use App\LessonPolicy;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as BaseAuthServiceProvider;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    protected $policies = [
        Course::class => CoursePolicy::class,
        Lesson::class => LessonPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
