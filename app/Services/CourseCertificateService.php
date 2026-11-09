<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\User;
use App\Notifications\CourseActivityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseCertificateService
{
    public function issueIfEligible(Course $course, User $user): ?CourseCertificate
    {
        if ($user->role !== 'student' || $course->status !== 'published') {
            return null;
        }

        return DB::transaction(function () use ($course, $user) {
            $enrollment = $course->enrollments()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->lockForUpdate()
                ->first();

            if (!$enrollment) {
                return null;
            }

            $existingCertificate = CourseCertificate::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingCertificate) {
                return $existingCertificate;
            }

            $lessonCount = $course->lessons()->count();
            if ($lessonCount === 0 || $course->lessons()
                ->whereDoesntHave('progresses', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('completed', true);
                })
                ->exists()) {
                return null;
            }

            $certificate = CourseCertificate::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                ],
                [
                    'certificate_number' => (string) Str::uuid(),
                    'issued_at' => now(),
                ]
            );

            if ($certificate->wasRecentlyCreated) {
                $user->notify(new CourseActivityNotification(
                    'certificate_issued',
                    'شهادتك جاهزة',
                    'صدرت شهادة إتمامك لكورس ' . $course->title . '.',
                    route('courses.certificate', $course, false),
                ));
            }

            return $certificate;
        });
    }
}
