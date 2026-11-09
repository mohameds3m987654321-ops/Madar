<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Course extends Model
{
    public const HOME_PAGE_CACHE_KEY = 'home.page.v1';

    protected $fillable = [
        'title',
        'description',
        'education_stage',
        'education_grade',
        'education_type',
        'education_system',
        'teacher_id',
        'image',
        'price',
        'status',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetPublicPageCaches());
        static::deleted(fn () => self::forgetPublicPageCaches());
    }

    public static function forgetPublicPageCaches(): void
    {
        Cache::forget(self::HOME_PAGE_CACHE_KEY);
    }

    /**
     * The Teacher of the course.
     *
     * @return BelongsTo<User, $this>
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function quiz()
    {
        return $this->hasOne(CourseQuiz::class);
    }

    public function certificates()
    {
        return $this->hasMany(CourseCertificate::class);
    }

    public function reviews()
    {
        return $this->hasMany(CourseReview::class);
    }

    public function posts()
    {
        return $this->hasMany(CoursePost::class);
    }

    public function faqs()
    {
        return $this->hasMany(CourseFaq::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function progressForUser(int $userId): int
    {
        return self::completionStatsForUser($userId, [$this->id])[$this->id]['progress']
            ?? 0;
    }

    public function completionPercentageForUser(int $userId): int
    {
        return self::completionStatsForUser($userId, [$this->id])[$this->id]['completion']
            ?? 0;
    }

    /**
     * استعلام واحد يجيب (عدد المحاضرات، نقاط التقدم، المحاضرات المكتملة) لكورسات متعددة دفعة واحدة،
     * بدل استعلامين منفصلين لكل كورس.
     *
     * @param  list<int>  $courseIds
     * @return array<int, array{lessons: int, progress: int, completion: int}>
     */
    public static function completionStatsForUser(int $userId, array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $rows = DB::table('lessons')
            ->leftJoin('lesson_progress', function ($join) use ($userId) {
                $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                    ->where('lesson_progress.user_id', '=', $userId);
            })
            ->whereIn('lessons.course_id', $courseIds)
            ->groupBy('lessons.course_id')
            ->get([
                'lessons.course_id',
                DB::raw('COUNT(lessons.id) AS lessons_count'),
                DB::raw('COALESCE(SUM(CASE WHEN lesson_progress.completed = 1 THEN 100 ELSE COALESCE(lesson_progress.progress, 0) END), 0) AS progress_points'),
                DB::raw('COALESCE(SUM(CASE WHEN lesson_progress.completed = 1 THEN 1 ELSE 0 END), 0) AS completed_count'),
            ]);

        $stats = [];

        foreach ($rows as $row) {
            $lessonCount = (int) $row->lessons_count;
            $stats[(int) $row->course_id] = [
                'lessons' => $lessonCount,
                'progress' => $lessonCount === 0
                    ? 0
                    : (int) round((int) $row->progress_points / $lessonCount),
                'completion' => $lessonCount === 0
                    ? 0
                    : (int) floor(((int) $row->completed_count / $lessonCount) * 100),
            ];
        }

        foreach ($courseIds as $courseId) {
            $stats[$courseId] ??= [
                'lessons' => 0,
                'progress' => 0,
                'completion' => 0,
            ];
        }

        return $stats;
    }
}
