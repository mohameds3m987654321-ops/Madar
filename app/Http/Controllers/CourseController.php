<?php

namespace App\Http\Controllers;

use App\Http\Requests\Courses\CheckoutRequest;
use App\Http\Requests\Courses\CourseCatalogRequest;
use App\Http\Requests\Courses\CourseFormRequest;
use App\Jobs\FinalizeMediaUpload;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\Scopes\PublishedCoursesScope;
use App\Models\TeacherReport;
use App\Services\CouponService;
use App\Services\CourseCertificateService;
use App\Services\PaymobCallbackAuditService;
use App\Services\PaymobService;
use App\Services\UploadedMediaStager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function index(CourseCatalogRequest $request)
    {
        $search = trim((string) ($request->validated('q') ?? ''));
        $query = Course::query()
            ->withGlobalScope(PublishedCoursesScope::class, new PublishedCoursesScope)
            ->with('teacher:id,name,image')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'));
        $student = $request->user();
        if ($student?->role === 'student' && $student->education_stage && $student->education_grade && $student->education_type) {
            $query->where(function ($query) use ($student) {
                $query->whereNull('education_stage')
                    ->orWhere(function ($query) use ($student) {
                        $query->where('education_stage', $student->education_stage)
                            ->where('education_grade', $student->education_grade)
                            ->where('education_type', $student->education_type)
                            ->where(function ($query) use ($student) {
                                $query->whereNull('education_system')
                                    ->orWhere('education_system', '')
                                    ->orWhere('education_system', $student->education_system);
                            });
                    });
            });
        }

        $courses = $query->latest()->paginate(12)->withQueryString();

        return view('Courses.index', compact('courses'));
    }

    public function myCourses()
    {
        abort_unless(auth()->user()->role === 'student', 403);

        $userId = auth()->id();

        $courses = Course::query()
            ->whereHas('enrollments', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'paid');
            })
            ->with('teacher:id,name,image')
            ->withCount('lessons')
            ->get();

        if ($courses->isEmpty()) {
            return view('Courses.my', ['courses' => collect()]);
        }

        // استعلام واحد: كل محاضرات كورسات الطالب مع تقدمه في كل محاضرة
        $lessonsByCourse = Lesson::query()
            ->whereIn('course_id', $courses->modelKeys())
            ->leftJoin('lesson_progress', function ($join) use ($userId) {
                $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                    ->where('lesson_progress.user_id', '=', $userId);
            })
            ->orderBy('lessons.order')
            ->get([
                'lessons.id',
                'lessons.course_id',
                'lessons.title',
                'lesson_progress.completed',
                'lesson_progress.progress',
                'lesson_progress.started_at',
            ])
            ->groupBy('course_id');

        $courses = $courses->map(function (Course $course) use ($lessonsByCourse) {
            $lessons = $lessonsByCourse->get($course->id, collect());
            $lessonCount = $course->lessons_count;
            $completedCount = 0;
            $progressPoints = 0;
            $firstUncompletedLesson = null;
            $inProgressLesson = null;
            $latestStartedAt = null;

            foreach ($lessons as $lesson) {
                $isCompleted = (bool) $lesson->completed;
                $completedCount += (int) $isCompleted;
                $progressPoints += $isCompleted ? 100 : (int) $lesson->progress;

                if ($isCompleted) {
                    continue;
                }

                $firstUncompletedLesson ??= $lesson;

                if (
                    $lesson->started_at !== null
                    && ($latestStartedAt === null || $lesson->started_at > $latestStartedAt)
                ) {
                    $latestStartedAt = $lesson->started_at;
                    $inProgressLesson = $lesson;
                }
            }

            return [
                'course' => $course,
                'progress' => $lessonCount === 0
                    ? 0
                    : (int) round($progressPoints / $lessonCount),
                'resumeLesson' => $inProgressLesson ?? $firstUncompletedLesson,
                'isComplete' => $lessonCount > 0
                    && $completedCount === $lessonCount,
            ];
        });

        return view('Courses.my', compact('courses'));
    }

    public function teacherDashboard()
    {
        abort_unless(auth()->user()->role === 'teacher', 403);

        $courses = Course::query()
            ->where('teacher_id', auth()->id())
            ->withCount('lessons')
            ->with([
                'enrollments' => fn ($query) => $query
                    ->with('user:id,name,email')
                    ->latest(),
            ])
            ->latest()
            ->get();

        $courseIds = $courses->modelKeys();
        $completedProgress = collect();

        if ($courseIds !== []) {
            $completedProgress = DB::table('lesson_progress')
                ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
                ->join('course_enrollments', function ($join) {
                    $join->on('course_enrollments.course_id', '=', 'lessons.course_id')
                        ->on('course_enrollments.user_id', '=', 'lesson_progress.user_id')
                        ->where('course_enrollments.status', '=', 'paid');
                })
                ->whereIn('lessons.course_id', $courseIds)
                ->select('lessons.course_id', 'lesson_progress.user_id')
                ->selectRaw(
                    'COUNT(DISTINCT CASE WHEN lesson_progress.completed = 1 THEN lesson_progress.lesson_id END) AS completed_lessons'
                )
                ->selectRaw(
                    'SUM(CASE WHEN lesson_progress.completed = 1 THEN 100 ELSE lesson_progress.progress END) AS progress_points'
                )
                ->groupBy('lessons.course_id', 'lesson_progress.user_id')
                ->get()
                ->keyBy(fn ($row) => $row->course_id.':'.$row->user_id);
        }

        foreach ($courses as $course) {
            foreach ($course->enrollments as $enrollment) {
                $progress = $completedProgress->get(
                    $course->id.':'.$enrollment->user_id
                );
                $completedCount = (int) ($progress->completed_lessons ?? 0);

                $enrollment->setAttribute('completed_lessons_count', $completedCount);
                $enrollment->setAttribute(
                    'progress_percentage',
                    $enrollment->status === 'paid' && $course->lessons_count > 0
                        ? (int) round(($progress->progress_points ?? 0) / $course->lessons_count)
                        : 0
                );
            }
        }

        $summary = [
            'courses' => $courses->count(),
            'published' => $courses->where('status', 'published')->count(),
            'drafts' => $courses->where('status', 'draft')->count(),
            'students' => $courses
                ->flatMap(fn ($course) => $course->enrollments
                    ->where('status', 'paid')
                    ->pluck('user_id'))
                ->unique()
                ->count(),
            'pending' => $courses
                ->sum(fn ($course) => $course->enrollments
                    ->where('status', 'pending')
                    ->count()),
        ];

        $now = now();
        $earnings = CourseEnrollment::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', 'paid');
        $summary['earnings_total_cents'] = (int) (clone $earnings)->sum('amount_cents');
        $summary['earnings_month_cents'] = (int) (clone $earnings)
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->sum('amount_cents');

        $lessonStats = collect();
        if ($courseIds !== []) {
            $lessonStats = DB::table('lessons')
                ->join('courses', 'courses.id', '=', 'lessons.course_id')
                ->leftJoin('course_enrollments', function ($join) {
                    $join->on('course_enrollments.course_id', '=', 'courses.id')
                        ->where('course_enrollments.status', '=', 'paid');
                })
                ->leftJoin('lesson_progress', function ($join) {
                    $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                        ->on('lesson_progress.user_id', '=', 'course_enrollments.user_id');
                })
                ->whereIn('courses.id', $courseIds)
                ->select('lessons.id', 'lessons.title as lesson_title', 'courses.title as course_title')
                ->selectRaw('COUNT(DISTINCT course_enrollments.user_id) AS enrolled_students')
                ->selectRaw(
                    'COUNT(DISTINCT CASE WHEN lesson_progress.started_at IS NOT NULL THEN course_enrollments.user_id END) AS started_students'
                )
                ->selectRaw(
                    'COUNT(DISTINCT CASE WHEN lesson_progress.started_at IS NOT NULL AND lesson_progress.completed = 0 THEN course_enrollments.user_id END) AS abandoned_students'
                )
                ->selectRaw(
                    'COUNT(DISTINCT CASE WHEN lesson_progress.completed = 1 THEN course_enrollments.user_id END) AS completed_students'
                )
                ->groupBy('lessons.id', 'lessons.title', 'courses.title')
                ->get()
                ->map(function ($lesson) {
                    $lesson->completion_percentage = (int) $lesson->enrolled_students > 0
                        ? (int) round($lesson->completed_students / $lesson->enrolled_students * 100)
                        : 0;
                    $lesson->abandonment_percentage = (int) $lesson->started_students > 0
                        ? (int) round($lesson->abandoned_students / $lesson->started_students * 100)
                        : 0;

                    return $lesson;
                })
                ->sortByDesc('abandoned_students')
                ->take(5)
                ->values();
        }

        $reports = TeacherReport::query()
            ->where('teacher_id', auth()->id())
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(6)
            ->get();

        return view('Courses.teacher-dashboard', compact(
            'courses',
            'summary',
            'lessonStats',
            'reports',
        ));
    }

    public function create()
    {
        return view('Courses.create');
    }

    public function store(CourseFormRequest $request, UploadedMediaStager $mediaStager)
    {
        $validatedData = $request->validated();
        $temporaryImagePath = $request->hasFile('image')
            ? $mediaStager->stage($request->file('image'), 'course_images')
            : null;

        $validatedData['status'] = 'published';
        $validatedData['teacher_id'] = auth()->id();

        if ($temporaryImagePath) {
            $validatedData['image'] = $temporaryImagePath;
        }

        $course = Course::create($validatedData);
        if ($temporaryImagePath) {
            FinalizeMediaUpload::dispatch(
                'course_image',
                $course->id,
                $temporaryImagePath,
            )->afterCommit();
        }

        return redirect()
            ->route('courses.index')
            ->with('success', 'تم إنشاء الكورس ونشره بنجاح — أصبح ظاهرًا للطلاب.');
    }

    public function publish($id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('publish', $course);

        $course->status = 'published';
        $course->save();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course published successfully.');
    }

    public function show($id, CourseCertificateService $certificates)
    {
        $course = Course::findOrFail($id);
        $user = auth()->user();
        if (
            $course->status !== 'published'
            && $user->role !== 'admin'
            && ! ($user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id)
        ) {
            abort(404);
        }
        $this->authorize('view', $course);

        $lessons = $course->lessons()->orderBy('order')->get();
        $courseProgress = $course->progressForUser(auth()->id());
        $enrollment = $course->enrollments()
            ->where('user_id', auth()->id())
            ->first();
        $isEnrolled = auth()->user()->role !== 'student'
            || $enrollment?->status === 'paid';
        $completedLessonCount = DB::table('lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->where('lessons.course_id', $course->id)
            ->where('lesson_progress.user_id', auth()->id())
            ->where('lesson_progress.completed', true)
            ->distinct()
            ->count('lesson_progress.lesson_id');
        $reviewThreshold = (int) config('courses.review_completion_percentage', 70);
        $completionPercentage = $lessons->isEmpty()
            ? 0
            : (int) floor(($completedLessonCount / $lessons->count()) * 100);
        $allLessonsCompleted = $lessons->isNotEmpty()
            && $completedLessonCount === $lessons->count();
        $quiz = $course->quiz()->withCount('questions')->first();
        $course->loadAvg('reviews', 'rating')->loadCount('reviews');
        $reviews = $course->reviews()
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();
        $myReview = auth()->user()->role === 'student'
            ? $course->reviews()->where('user_id', auth()->id())->first()
            : null;
        $canReview = auth()->user()->role === 'student'
            && $enrollment?->status === 'paid'
            && $completionPercentage >= $reviewThreshold;
        $certificate = auth()->user()->role === 'student'
            ? $certificates->issueIfEligible($course, auth()->user())
            : null;

        return view('Courses.show', compact(
            'course',
            'lessons',
            'courseProgress',
            'enrollment',
            'isEnrolled',
            'allLessonsCompleted',
            'quiz',
            'certificate',
            'reviews',
            'myReview',
            'canReview',
            'reviewThreshold',
            'completionPercentage'
        ));
    }

    public function enrollFree(Course $course)
    {
        abort_unless($course->status === 'published', 404);
        $this->authorize('enroll', $course);
        abort_if((float) $course->price > 0, 422, 'This course requires payment.');

        $course->enrollments()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'payment_method' => 'free',
                'status' => 'paid',
                'amount_cents' => 0,
                'paid_at' => now(),
            ]
        );

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'تم الاشتراك في الكورس بنجاح.');
    }

    public function checkout(
        CheckoutRequest $request,
        Course $course,
        PaymobService $paymob,
        CouponService $coupons
    ) {
        abort_unless(auth()->user()->role === 'student', 403);
        abort_unless($course->status === 'published', 404);
        abort_if((float) $course->price <= 0, 422, 'This course is free.');

        $validated = $request->validated();
        if ($course->enrollments()
            ->where('user_id', auth()->id())
            ->where('status', 'paid')
            ->exists()) {
            return redirect()
                ->route('courses.show', $course)
                ->with('success', 'أنت مشترك بالفعل في هذا الكورس.');
        }

        $enrollment = DB::transaction(function () use ($course, $validated, $coupons) {
            $enrollment = $course->enrollments()
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();
            $enrollment ??= new CourseEnrollment([
                'user_id' => auth()->id(),
                'course_id' => $course->id,
            ]);

            abort_unless($enrollment->status !== 'paid', 409, 'أنت مشترك بالفعل في هذا الكورس.');
            abort_unless($enrollment->status !== 'refunded', 409, 'لا يمكن إعادة الدفع لهذا الاشتراك.');

            $enrollment->fill([
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'amount_cents' => (int) round((float) $course->price * 100),
                'paymob_order_id' => null,
                'paymob_transaction_id' => null,
                'paid_at' => null,
                'coupon_id' => null,
                'discount_cents' => 0,
            ])->save();

            if (! empty($validated['coupon_code'])) {
                $coupons->apply($course, $enrollment, $validated['coupon_code']);
                $enrollment->save();
            } else {
                $enrollment->couponRedemption()->update(['status' => 'failed']);
            }

            if ($enrollment->amount_cents === 0) {
                $enrollment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $enrollment->couponRedemption()->update(['status' => 'paid']);
            }

            return $enrollment;
        });

        if ($enrollment->status === 'paid') {
            return redirect()
                ->route('courses.show', $course)
                ->with('success', 'تم تطبيق الخصم والاشتراك في الكورس بنجاح.');
        }

        try {
            $checkoutUrl = $paymob->createCheckout(
                $course,
                auth()->user(),
                $enrollment,
                $validated['phone_number']
            );
        } catch (ConnectionException $exception) {
            $this->markCheckoutFailed($enrollment);
            Log::warning('Paymob connection failed while creating checkout.', [
                'course_id' => $course->id,
                'user_id' => auth()->id(),
                'exception' => $exception::class,
            ]);

            return back()->withInput()->withErrors([
                'payment' => 'تعذر الاتصال ببوابة الدفع. حاول مرة أخرى لاحقًا.',
            ]);
        } catch (RequestException|\RuntimeException $exception) {
            $this->markCheckoutFailed($enrollment);
            Log::warning('Paymob rejected checkout creation.', [
                'course_id' => $course->id,
                'user_id' => auth()->id(),
                'exception' => $exception::class,
                'status' => $exception instanceof RequestException
                    ? $exception->response->status()
                    : null,
            ]);

            return back()->withInput()->withErrors([
                'payment' => 'تعذر بدء الدفع. تأكد من إعدادات Paymob وجرّب مرة أخرى.',
            ]);
        }

        return redirect()->away($checkoutUrl);
    }

    public function paymobCallback(Request $request, PaymobService $paymob)
    {
        $audit = app(PaymobCallbackAuditService::class);
        $auditLog = $audit->record($request, $paymob->verifyCallback($request));

        if (! $auditLog->signature_valid) {
            $audit->finish($auditLog, 'rejected', 'invalid_signature');
            abort(403, 'Invalid payment callback.');
        }

        $order = $request->input('order');
        $orderId = is_array($order) ? ($order['id'] ?? null) : $order;
        if ((! is_string($orderId) && ! is_int($orderId)) || ! ctype_digit((string) $orderId)) {
            $audit->finish($auditLog, 'rejected', 'invalid_order_id');
            abort(404);
        }

        $enrollment = CourseEnrollment::where('paymob_order_id', (string) $orderId)->first();
        if (! $enrollment) {
            $audit->finish($auditLog, 'rejected', 'unknown_order');
            abort(404);
        }
        $expectedIntegrationId = config(
            "services.paymob.integrations.{$enrollment->payment_method}"
        );
        $amountCents = $request->input('amount_cents');
        $isSuccessful = $request->boolean('success')
            && ! $request->boolean('pending')
            && ! $request->boolean('error_occured')
            && $request->input('currency') === 'EGP'
            && (is_string($amountCents) || is_int($amountCents))
            && ctype_digit((string) $amountCents)
            && (string) $amountCents === (string) $enrollment->amount_cents
            && is_string($expectedIntegrationId)
            && $expectedIntegrationId !== ''
            && (string) $request->input('integration_id') === (string) $expectedIntegrationId;

        $outcome = DB::transaction(function () use ($enrollment, $request, $isSuccessful) {
            $lockedEnrollment = CourseEnrollment::whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedEnrollment->status === 'paid') {
                return 'duplicate_success';
            }

            if ($lockedEnrollment->status !== 'pending') {
                return 'invalid_enrollment_state';
            }

            $lockedEnrollment->update([
                'status' => $isSuccessful ? 'paid' : 'failed',
                'paymob_transaction_id' => (string) $request->input('id'),
                'paid_at' => $isSuccessful ? now() : null,
            ]);
            $lockedEnrollment->couponRedemption()
                ->update(['status' => $isSuccessful ? 'paid' : 'failed']);

            return $isSuccessful ? 'paid' : 'payment_failed';
        });

        $audit->finish(
            $auditLog,
            $outcome,
            match ($outcome) {
                'payment_failed' => 'payment_not_confirmed_or_data_mismatch',
                'invalid_enrollment_state' => 'invalid_enrollment_state',
                default => null,
            }
        );

        abort_if($outcome === 'invalid_enrollment_state', 409, 'Payment callback cannot update this enrollment.');

        if ($request->isMethod('post')) {
            return response()->noContent();
        }

        return redirect()
            ->route('courses.show', $enrollment->course_id)
            ->with(
                $isSuccessful ? 'success' : 'error',
                $isSuccessful ? 'تم الدفع والاشتراك في الكورس بنجاح.' : 'لم يتم تأكيد الدفع.'
            );
    }

    private function markCheckoutFailed(CourseEnrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $lockedEnrollment = CourseEnrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($lockedEnrollment->status === 'pending') {
                $lockedEnrollment->update(['status' => 'failed']);
                $lockedEnrollment->couponRedemption()->update(['status' => 'failed']);
            }
        });
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('update', $course);

        $course->loadCount('lessons');

        return view('Courses.edit', compact('course'));
    }

    public function update(CourseFormRequest $request, $id, UploadedMediaStager $mediaStager)
    {

        $course = Course::findOrFail($id);
        $this->authorize('update', $course);

        $validatedData = $request->validated();
        $temporaryImagePath = $request->hasFile('image')
            ? $mediaStager->stage($request->file('image'), 'course_images')
            : null;

        if ($temporaryImagePath) {
            $validatedData['image'] = $temporaryImagePath;
        }

        $previousImagePath = $course->image;
        $course->update($validatedData);
        if ($temporaryImagePath) {
            FinalizeMediaUpload::dispatch(
                'course_image',
                $course->id,
                $temporaryImagePath,
                $previousImagePath,
            )->afterCommit();
        }

        return redirect()
            ->route('courses.edit', $course->id)
            ->with('success', 'تم حفظ تعديلات الكورس بنجاح.');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
