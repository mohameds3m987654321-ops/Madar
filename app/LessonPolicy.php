<?php

namespace App;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course()->first();

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return $course !== null && (int) $course->teacher_id === (int) $user->id;
        }

        if ($user->role !== 'student') {
            return false;
        }

        return $course !== null
            && $course->status === 'published'
            && $course->enrollments()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->exists();
    }

    public function stream(User $user, Lesson $lesson): bool
    {
        return $this->view($user, $lesson);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'teacher'
                && $lesson->course !== null
                && (int) $lesson->course->teacher_id === (int) $user->id);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    public function manage(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    public function respond(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
