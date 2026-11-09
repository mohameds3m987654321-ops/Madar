<?php

namespace App;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(?User $user, Course $course): bool
    {
        if ($course->status === 'published') {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->role === 'admin'
            || ($user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id);
    }

    public function create(User $user): bool
    {
        return $user->role === 'teacher';
    }

    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'teacher'
                && (int) $course->teacher_id === (int) $user->id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function publish(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function manage(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function enroll(User $user, Course $course): bool
    {
        return $user->role === 'student'
            && $course->status === 'published';
    }
}
