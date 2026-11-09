<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CourseCommunityController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LessonCommentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonVideoUploadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RefundRequestController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SupportAssistantController;
use App\Http\Controllers\TeacherReportController;
use App\Http\Controllers\UserController;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $data = Cache::remember(Course::HOME_PAGE_CACHE_KEY, now()->addMinutes(5), function () {
        $courses = Course::query()
            ->where('status', 'published')
            ->with('teacher:id,name,image')
            ->withCount(['lessons', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(3)
            ->get();

        $teachers = User::query()
            ->where('role', 'teacher')
            ->whereHas('courses', fn ($query) => $query->where('status', 'published'))
            ->withCount(['courses' => fn ($query) => $query->where('status', 'published')])
            ->latest()
            ->take(3)
            ->get();

        $reviews = CourseReview::query()
            ->whereHas('course', fn ($query) => $query->where('status', 'published'))
            ->with(['user:id,name,image', 'course:id,title'])
            ->latest()
            ->take(3)
            ->get();

        // استعلامان مجمّعان بدل 5 استعلامات منفصلة للإحصائيات
        $publishedCourseIds = Course::query()
            ->where('status', 'published')
            ->pluck('id');

        [$teacherStats, $reviewStats] = [
            User::query()
                ->where('role', 'teacher')
                ->whereHas('courses', fn ($query) => $query->where('status', 'published'))
                ->selectRaw('COUNT(*) AS teachers')
                ->first(),
            CourseReview::query()
                ->whereIn('course_id', $publishedCourseIds)
                ->selectRaw('COUNT(*) AS reviews, AVG(rating) AS rating')
                ->first(),
        ];

        $stats = [
            'students' => CourseEnrollment::query()
                ->where('status', 'paid')
                ->whereIn('course_id', $publishedCourseIds)
                ->distinct('user_id')
                ->count('user_id'),
            'courses' => $publishedCourseIds->count(),
            'teachers' => (int) ($teacherStats->teachers ?? 0),
            'reviews' => (int) ($reviewStats->reviews ?? 0),
            'rating' => $reviewStats->rating !== null
                ? round((float) $reviewStats->rating, 2)
                : null,
        ];

        return compact('courses', 'teachers', 'reviews', 'stats');
    });

    return view('welcome', $data);
})->name('home');

Route::get('/guide', function () {
    return view('guide.index', [
        'guidePages' => config('page_guide.pages'),
    ]);
})->name('guide');
Route::get('/certificates/verify/{certificateNumber}', [CertificateVerificationController::class, 'show'])
    ->where('certificateNumber', '[A-Fa-f0-9-]{36}')
    ->name('certificates.verify');

Route::redirect('/Sigin', '/');

Route::get('/login', function () {
    return view('users.login');
})->name('login');

Route::get('/register', [UserController::class, 'create'])
    ->name('users.create');

Route::post('/assistant/message', [SupportAssistantController::class, 'answer'])
    ->middleware('throttle:assistant-message')
    ->name('assistant.message');
Route::post('/assistant/escalate', [SupportAssistantController::class, 'escalate'])
    ->middleware('throttle:assistant-escalation')
    ->name('assistant.escalate');
Route::get('/assistant/conversations/{token}', [SupportAssistantController::class, 'conversation'])
    ->middleware('throttle:assistant-conversation')
    ->name('assistant.conversation.show');
Route::post('/assistant/conversations/{token}/messages', [SupportAssistantController::class, 'reply'])
    ->middleware('throttle:assistant-conversation')
    ->name('assistant.conversation.reply');

Route::post('/login', [UserController::class, 'login'])
    ->middleware(['guest:web', 'throttle:login'])
    ->name('login.store');

/*
|--------------------------------------------------------------------------
| Password Reset
|--------------------------------------------------------------------------
*/

Route::middleware('guest:web')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/two-factor-setup', function () {
        $user = auth()->user();
        abort_unless(in_array($user->role, ['admin', 'teacher'], true), 403);

        $secret = $user->two_factor_secret
            ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret)
            : null;

        return view('auth.two-factor-setup', compact('user', 'secret'));
    })->name('two-factor.setup');

    Route::post('/appearance', [UserController::class, 'updateAppearance'])
        ->name('appearance.update');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyLegacyLink'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verify/code', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:verification-code')
        ->name('verification.code');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification-resend')
        ->name('verification.send');
    Route::patch('/email/verification-email', [EmailVerificationController::class, 'updateEmail'])
        ->middleware('throttle:verification-resend')
        ->name('verification.email.update');
    Route::post('/logout', [UserController::class, 'logout'])
        ->name('logout');
});

Route::match(['get', 'post'], '/payments/paymob/callback', [CourseController::class, 'paymobCallback'])
    ->name('payments.paymob.callback');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified-or-admin'])->group(function () {

    Route::get('/search', SearchController::class)
        ->name('search');

    // Profile
    Route::get('/profile', [UserController::class, 'profile'])
        ->name('users.profile');
    Route::get('/users/profile', [UserController::class, 'profile']);

    Route::put('/profile', [UserController::class, 'updateProfile'])
        ->name('users.profile.update');
    Route::put('/users/profile', [UserController::class, 'updateProfile']);

    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::get('/payments', [PaymentController::class, 'index'])
        ->name('payments.index');
    Route::get('/payments/{enrollment}/invoice', [PaymentController::class, 'invoice'])
        ->name('payments.invoice');
    Route::post('/payments/{enrollment}/refund-requests', [RefundRequestController::class, 'store'])
        ->name('refunds.store');

    // Courses
    Route::get('/courses', [CourseController::class, 'index'])
        ->name('courses.index');

    Route::get('/my-courses', [CourseController::class, 'myCourses'])
        ->name('courses.my');

    Route::get('/courses-edit/{id}', [CourseController::class, 'edit'])
        ->name('courses.edit');

    Route::get('/courses/{id}', [CourseController::class, 'show'])
        ->whereNumber('id')
        ->name('courses.show');

    Route::post('/courses/{course}/enroll', [CourseController::class, 'enrollFree'])
        ->name('courses.enroll');

    Route::post('/courses/{course}/checkout', [CourseController::class, 'checkout'])
        ->middleware('throttle:coupon-validation')
        ->name('courses.checkout');

    Route::post('/courses/{course}/reviews', [CourseReviewController::class, 'store'])
        ->name('courses.reviews.store');
    Route::get('/courses/{course}/community', [CourseCommunityController::class, 'index'])
        ->name('courses.community.index');
    Route::post('/courses/{course}/community/posts', [CourseCommunityController::class, 'storePost'])
        ->name('courses.community.posts.store');
    Route::post('/courses/{course}/community/posts/{post}/comments', [CourseCommunityController::class, 'storeComment'])
        ->name('courses.community.comments.store');
    Route::post('/courses/{course}/community/faqs', [CourseCommunityController::class, 'storeFaq'])
        ->name('courses.community.faqs.store');
    Route::put('/courses/{course}/community/faqs/{faq}', [CourseCommunityController::class, 'updateFaq'])
        ->name('courses.community.faqs.update');
    Route::delete('/courses/{course}/community/faqs/{faq}', [CourseCommunityController::class, 'destroyFaq'])
        ->name('courses.community.faqs.destroy');
    Route::post('/courses/{course}/community/posts/{post}/like', [CourseCommunityController::class, 'toggleLike'])
        ->name('courses.community.likes.toggle');
    Route::patch('/courses/{course}/community/posts/{post}/pin', [CourseCommunityController::class, 'togglePin'])
        ->name('courses.community.posts.pin');
    Route::delete('/courses/{course}/community/posts/{post}', [CourseCommunityController::class, 'destroyPost'])
        ->name('courses.community.posts.destroy');
    Route::get('/courses/{course}/community/posts/{post}/attachment', [CourseCommunityController::class, 'attachment'])
        ->name('courses.community.posts.attachment');

    // Lesson Progress
    Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete'])
        ->name('lessons.complete');

    Route::patch('/lessons/{lesson}/progress', [LessonController::class, 'saveProgress'])
        ->name('lessons.progress');
    Route::get('/lessons/{lesson}/video', [LessonController::class, 'video'])
        ->name('lessons.video');
    Route::get('/lessons/{lesson}/attachments/{attachment}', [LessonController::class, 'downloadAttachment'])
        ->name('lessons.attachments.download');
    Route::post('/lessons/{lesson}/comments', [LessonCommentController::class, 'store'])
        ->name('lessons.comments.store');
    Route::get('/courses/{course}/certificate', [QuizController::class, 'certificate'])
        ->name('courses.certificate');
    Route::get('/courses/{course}/quiz', [QuizController::class, 'show'])
        ->name('quizzes.show');
    Route::post('/courses/{course}/quiz/attempts', [QuizController::class, 'submit'])
        ->name('quizzes.submit');
    Route::get('/courses/{course}/quiz/attempts/{attempt}', [QuizController::class, 'result'])
        ->name('quizzes.result');

    // Create Lesson
    Route::get('/lessons/create', [LessonController::class, 'create'])
        ->middleware('teacher')
        ->name('lessons.create');

    // Show Lesson
    Route::get('/lessons/{id}', [LessonController::class, 'show'])
        ->whereNumber('id')
        ->name('lessons.show');
});

/*
|--------------------------------------------------------------------------
| Users Store
|--------------------------------------------------------------------------
*/

Route::post('/users', [UserController::class, 'store'])
    ->middleware('throttle:registration')
    ->name('users.store');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified-or-admin', 'admin', 'two-factor'])->group(function () {

    Route::get('/admin', [UserController::class, 'adminDashboard'])
        ->name('admin.dashboard');
    Route::get('/admin/refunds', [RefundRequestController::class, 'index'])
        ->name('refunds.index');
    Route::patch('/admin/refunds/{refundRequest}', [RefundRequestController::class, 'update'])
        ->name('refunds.update');
    Route::get('/admin/support-inquiries', [SupportAssistantController::class, 'index'])
        ->name('admin.support-inquiries.index');
    Route::patch('/admin/support-inquiries/{inquiry}', [SupportAssistantController::class, 'update'])
        ->name('admin.support-inquiries.update');
    Route::post('/admin/support-inquiries/{inquiry}/reply', [SupportAssistantController::class, 'respond'])
        ->name('admin.support-inquiries.respond');
    Route::delete('/admin/support-inquiries/{inquiry}', [SupportAssistantController::class, 'destroy'])
        ->name('admin.support-inquiries.destroy');

    // Users management
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/{id}/edit', [UserController::class, 'edit'])
        ->name('users.edit');

    Route::put('/users/{id}', [UserController::class, 'update'])
        ->name('users.update');

    Route::post('/users/{user}/login-unlock', [UserController::class, 'unlockLogin'])
        ->name('users.login-unlock');

    Route::delete('/users/{id}', [UserController::class, 'destroy'])
        ->name('users.destroy');

    // Show user
    Route::get('/StudentToTecher/{id}', [UserController::class, 'show'])
        ->name('users.show');

    // Change user role
    Route::post('/TeacherToStudent/{id}', [UserController::class, 'makeStudent'])
        ->name('users.makeStudent');

    Route::post('/make-teacher/{id}', [UserController::class, 'makeTeacher'])
        ->name('make-teacher');
});

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified-or-admin', 'teacher', 'two-factor'])->group(function () {

    Route::get('/teacher/dashboard', [CourseController::class, 'teacherDashboard'])
        ->name('teacher.dashboard');
    Route::post('/teacher/reports', [TeacherReportController::class, 'generate'])
        ->name('teacher.reports.generate');
    Route::get('/teacher/exports/students', [TeacherReportController::class, 'students'])
        ->name('teacher.exports.students');
    Route::get('/teacher/exports/payments', [TeacherReportController::class, 'payments'])
        ->name('teacher.exports.payments');

    // Create Course
    Route::get('/courses/create', [CourseController::class, 'create'])
        ->name('courses.create');

    // Store Course
    Route::post('/courses', [CourseController::class, 'store'])
        ->name('courses.store');

    Route::put('/courses/{id}', [CourseController::class, 'update'])
        ->whereNumber('id')
        ->name('courses.update');
    Route::get('/courses/{course}/quiz/edit', [QuizController::class, 'edit'])
        ->name('quizzes.edit');
    Route::put('/courses/{course}/quiz', [QuizController::class, 'save'])
        ->name('quizzes.save');
    Route::get('/courses/{course}/coupons', [CouponController::class, 'index'])
        ->name('courses.coupons.index');
    Route::post('/courses/{course}/coupons', [CouponController::class, 'store'])
        ->name('courses.coupons.store');
    Route::patch('/courses/{course}/coupons/{coupon}', [CouponController::class, 'toggle'])
        ->name('courses.coupons.toggle');

    Route::get('/courses/{course}/lessons', [LessonController::class, 'manage'])
        ->name('lessons.manage');
    Route::get('/courses/{course}/lessons/create', [LessonController::class, 'createForCourse'])
        ->name('lessons.create-for-course');
    Route::post('/courses/{course}/lessons', [LessonController::class, 'storeForCourse'])
        ->name('lessons.store-for-course');
    Route::post('/courses/{course}/lesson-uploads', [LessonVideoUploadController::class, 'start'])
        ->name('lessons.video-uploads.start');
    Route::post('/courses/{course}/lesson-uploads/{uploadToken}/chunks', [LessonVideoUploadController::class, 'storeChunk'])
        ->whereUuid('uploadToken')
        ->name('lessons.video-uploads.chunk');
    Route::post('/courses/{course}/lesson-uploads/{uploadToken}/complete', [LessonVideoUploadController::class, 'finish'])
        ->whereUuid('uploadToken')
        ->name('lessons.video-uploads.finish');
    Route::get('/courses/{course}/lessons/{lesson}/edit', [LessonController::class, 'editForCourse'])
        ->name('lessons.edit');
    Route::put('/courses/{course}/lessons/{lesson}', [LessonController::class, 'updateForCourse'])
        ->name('lessons.update');
    Route::post('/courses/{course}/lessons/{lesson}/attachments', [LessonController::class, 'storeAttachment'])
        ->name('lessons.attachments.store');
    Route::delete('/courses/{course}/lessons/{lesson}/attachments/{attachment}', [LessonController::class, 'destroyAttachment'])
        ->name('lessons.attachments.destroy');
    Route::delete('/courses/{course}/lessons/{lesson}', [LessonController::class, 'destroyForCourse'])
        ->name('lessons.destroy');

    // Publish Course
    Route::post('/courses/{id}/publish', [CourseController::class, 'publish'])
        ->name('courses.publish');

    // Delete Course
    Route::delete('/courses/{id}', [CourseController::class, 'destroy'])
        ->name('courses.destroy');

    /*
    |--------------------------------------------------------------------------
    | Lessons
    |--------------------------------------------------------------------------
    */

    // صفحة إضافة محاضرة

    Route::post('lessons/create', [LessonController::class, 'store'])
        ->name('lessons.store');

    Route::get('lessons', [LessonController::class, 'index'])
        ->name('lessons.index');
});

/*
|--------------------------------------------------------------------------
| Lessons Index
|--------------------------------------------------------------------------
*/
