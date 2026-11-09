<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Users\UpdateManagedUserRequest;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Requests\Users\UserIndexRequest;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CourseEnrollment;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function adminDashboard()
    {
        $stats = [
            'users' => User::count(),
            'students' => User::where('role', 'student')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'draft_courses' => Course::where('status', 'draft')->count(),
            'paid_enrollments' => CourseEnrollment::where('status', 'paid')->count(),
            'pending_enrollments' => CourseEnrollment::where('status', 'pending')->count(),
        ];

        $latestUsers = User::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        $latestCourses = Course::query()
            ->with('teacher:id,name')
            ->latest()
            ->limit(6)
            ->get(['id', 'teacher_id', 'title', 'status', 'created_at']);

        return view('users.dashboard', compact('stats', 'latestUsers', 'latestCourses'));
    }

    public function index(UserIndexRequest $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->validated();
        $usersQuery = User::query();
        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['role'])) {
            $usersQuery->where('role', $filters['role']);
        }

        $users = $usersQuery->paginate(10)->withQueryString();

        $stats = [
            'total' => User::count(),
            'student' => User::where('role', 'student')->count(),
            'teacher' => User::where('role', 'teacher')->count(),
            'admin' => User::where('role', 'admin')->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return view('users.show', ['user' => $user]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function updateAppearance(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:light,dark'],
        ]);

        $request->user()->forceFill([
            'preferred_theme' => $validated['theme'],
        ])->save();

        return response()->json(['theme' => $validated['theme']]);
    }

    public function store(RegisterUserRequest $request, EmailVerificationService $verification)
    {
        $validatedData = $request->validated();

        // Create a new user record in the database
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'student_phone' => $validatedData['student_phone'],
            'parent_phone' => $validatedData['parent_phone'],
            'education_stage' => $validatedData['education_stage'],
            'education_grade' => $validatedData['education_grade'],
            'education_system' => $validatedData['education_system'] ?? null,
            'education_type' => $validatedData['education_type'],
            'password' => bcrypt($validatedData['password']),
            'role' => 'student',
        ]);

        auth()->login($user);
        $request->session()->regenerate();
        $sent = $verification->sendCode($user);

        return redirect()
            ->route('verification.notice')
            ->with($this->verificationSendFlash($sent));
    }

    private function verificationSendFlash(bool $sent, string $prefix = ''): array
    {
        if (!$sent) {
            return ['email_error' => $prefix . 'تعذر إرسال رمز التحقق الآن. حسابك محفوظ؛ راجع إعدادات البريد وحاول إعادة الإرسال.'];
        }

        if (config('mail.default') === 'log') {
            return ['status' => $prefix . 'وضع البريد الحالي لا يرسل رسائل فعلية؛ رمز التحقق مسجل في storage/logs/laravel.log.'];
        }

        return ['status' => $prefix . 'تم إرسال رمز تحقق إلى بريدك الإلكتروني.'];
    }

    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();
        $remember = $request->boolean('remember');
        unset($validatedData['remember']);

        $user = User::where('email', $validatedData['email'])->first();
        $lockoutMinutes = (int) config('auth.login_lockout_minutes', 15);

        if (
            $user?->login_locked_at
            && $user->login_locked_at->isAfter(
                now()->subMinutes($lockoutMinutes)
            )
        ) {
            throw ValidationException::withMessages([
                'email' => "تم إيقاف تسجيل الدخول لهذا الحساب مؤقتًا بسبب محاولات فاشلة. حاول بعد {$lockoutMinutes} دقيقة.",
            ]);
        }

        if ($user?->login_locked_at) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'login_locked_at' => null,
            ])->save();
        }

        if (auth()->attempt($validatedData, $remember)) {
            $user = auth()->user();
            $user->forceFill([
                'failed_login_attempts' => 0,
                'login_locked_at' => null,
            ])->save();
            $request->session()->regenerate();

            if (!$user->hasVerifiedEmail() && $user->role !== 'admin') {
                return redirect()->route('verification.notice');
            }

            if (
                filter_var(config('fortify.require_two_factor', false), FILTER_VALIDATE_BOOLEAN)
                && in_array($user->role, ['admin', 'teacher'], true)
                && $user->hasEnabledTwoFactorAuthentication()
            ) {
                auth()->logout();
                $request->session()->regenerate();
                $request->session()->put([
                    'login.id' => $user->getAuthIdentifier(),
                    'login.remember' => $remember,
                ]);

                return redirect()->route('two-factor.login');
            }

            return redirect()->route('users.profile')
                ->with('success', 'Logged in successfully!');
        }

        if ($user) {
            $user->increment('failed_login_attempts');
            $failedAttempts = $user->fresh()->failed_login_attempts;

            if ($failedAttempts >= 5) {
                $user->forceFill(['login_locked_at' => now()])->save();

                throw ValidationException::withMessages([
                    'email' => 'تم إيقاف تسجيل الدخول لهذا الحساب 15 دقيقة بعد 5 محاولات فاشلة.',
                ]);
            }
        }

        throw ValidationException::withMessages([
            'email' => 'بيانات البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        ]);
    }

    public function unlockLogin(User $user)
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'login_locked_at' => null,
        ])->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'تم رفع حظر تسجيل الدخول عن المستخدم.');
    }


    public function makeTeacher($id)
    {
        // Fetch user data from the database based on the provided ID

        $user = User::find($id);


        // Check if the user exists
        if (!$user) {
            abort(404, 'User not found');
        }

        if ($user->role === 'teacher') {
            return redirect()->back()->with('info', 'User is already a teacher.');
        }

        DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $admins = User::query()->where('role', 'admin')->lockForUpdate()->get(['id']);

            if ((int) $user->getKey() === (int) auth()->id()) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكنك تغيير صلاحية حسابك بنفسك.',
                ]);
            }

            if ($user->role === 'admin' && $admins->count() <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكن إزالة صلاحية آخر مدير في المنصة.',
                ]);
            }

            $user->role = 'teacher';
            $user->save();
        });

        // Redirect to the user's profile page or any other desired location
        return redirect()->route('users.index')
            ->with('success', 'User promoted to teacher successfully!');
    }

    public function makeStudent($id)
    {
        // Fetch user data from the database based on the provided ID
        $user = User::find($id);

        // Check if the user exists
        if (!$user) {
            abort(404, 'User not found');
        }

        // Update the user's role to 'student'
        if ($user->role === 'student') {
            return redirect()->back()->with('info', 'User is already a student.');
        }

        DB::transaction(function () use ($user) {
            $admins = User::query()->where('role', 'admin')->lockForUpdate()->get(['id']);

            if ((int) $user->getKey() === (int) auth()->id()) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكنك تغيير صلاحية حسابك بنفسك.',
                ]);
            }

            if ($user->role === 'admin' && $admins->count() <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكن إزالة صلاحية آخر مدير في المنصة.',
                ]);
            }

            $user->role = 'student';
            $user->save();
        });

        // Redirect to the user's profile page or any other desired location
        return redirect()->route('users.index')
            ->with('success', 'User demoted to student successfully!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('users.update', ['user' => $user]);
    }

    public function update(
        UpdateManagedUserRequest $request,
        $id,
        EmailVerificationService $verification,
    )
    {
        $validatedData = $request->validated();
        $user = DB::transaction(function () use ($validatedData, $id) {
            $user = User::query()->lockForUpdate()->findOrFail($id);
            $emailChanged = strcasecmp($user->email, $validatedData['email']) !== 0;
            $roleChanged = $user->role !== ($validatedData['role'] ?? $user->role);

            if ($roleChanged && $user->role === 'admin') {
                $admins = User::query()->where('role', 'admin')->lockForUpdate()->get(['id']);

                if ($admins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'لا يمكن إزالة صلاحية آخر مدير في المنصة.',
                    ]);
                }
            }

            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            if (isset($validatedData['role'])) {
                $user->role = $validatedData['role'];
            }
            if ($emailChanged) {
                $user->email_verified_at = null;
                $user->email_verification_code_hash = null;
                $user->email_verification_code_expires_at = null;
                $user->email_verification_code_attempts = 0;
            }
            if (!empty($validatedData['password'])) {
                $user->password = bcrypt($validatedData['password']);
            }

            $user->save();

            return $user;
        });
        $emailChanged = $user->wasChanged('email');

        if ($emailChanged) {
            $sent = $verification->sendCode($user);

            return redirect()->route('users.index')
                ->with(
                    $sent ? 'success' : 'error',
                    $this->verificationSendFlash($sent, 'تم تحديث البريد. ')[$sent ? 'status' : 'email_error'],
                );
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $user = User::query()->lockForUpdate()->findOrFail($id);
            $admins = User::query()->where('role', 'admin')->lockForUpdate()->get(['id']);

            if ((int) $user->getKey() === (int) auth()->id()) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكنك حذف حسابك أثناء تسجيل الدخول به.',
                ]);
            }

            if ($user->role === 'admin' && $admins->count() <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكن حذف آخر مدير في المنصة.',
                ]);
            }

            $user->delete();
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully!');
    }

    public function updateProfile(
        UpdateProfileRequest $request,
        EmailVerificationService $verification,
    )
    {
        $user = auth()->user();

        $validatedData = $request->validated();

        $user->name = $validatedData['name'];
        $emailChanged = strcasecmp($user->email, $validatedData['email']) !== 0;
        $user->email = $validatedData['email'];
        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->email_verification_code_hash = null;
            $user->email_verification_code_expires_at = null;
            $user->email_verification_code_attempts = 0;
        }

        if (!empty($validatedData['password'])) {
            $user->password = bcrypt($validatedData['password']);
        }

        $user->save();

        if ($emailChanged) {
            $sent = $verification->sendCode($user);

            return redirect()->route('verification.notice')
                ->with($this->verificationSendFlash($sent, 'تم تحديث البريد. '));
        }

        return redirect()
            ->route('users.profile')
            ->with('success', 'Profile updated successfully!');
    }


    public function profile()
    {
        $user = auth()->user();
        $userId = $user->id;
        $enrollments = CourseEnrollment::query()
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->with([
                'course.teacher',
                'course.lessons' => function ($query) use ($userId) {
                    $query->orderBy('order')
                        ->with([
                            'progresses' => fn($progressQuery) => $progressQuery
                                ->where('user_id', $userId),
                        ]);
                },
            ])
            ->latest('paid_at')
            ->get();

        $recentProgress = collect();
        $watchedSeconds = 0;
        $courses = $enrollments->map(function ($enrollment) use (&$recentProgress, &$watchedSeconds) {
            $course = $enrollment->course;
            $lessons = $course->lessons;
            $completedLessons = 0;
            $progressPoints = 0;
            $startedLessons = collect();

            foreach ($lessons as $lesson) {
                $lessonProgress = $lesson->progresses->first();

                if (!$lessonProgress) {
                    continue;
                }

                $watchedSeconds += (int) $lessonProgress->watched_seconds;
                $completedLessons += (int) $lessonProgress->completed;
                $progressPoints += $lessonProgress->completed
                    ? 100
                    : $lessonProgress->progress;

                $activity = [
                    'lesson' => $lesson,
                    'course' => $course,
                    'progress' => $lessonProgress,
                ];
                $recentProgress->push($activity);

                if ($lessonProgress->started_at && !$lessonProgress->completed) {
                    $startedLessons->push($activity);
                }
            }

            $resumeLesson = $startedLessons
                ->sortByDesc(fn($activity) => $activity['progress']->updated_at)
                ->first()['lesson'] ?? $lessons->first(
                fn($lesson) => !$lesson->progresses->contains('completed', true)
            );

            return [
                'course' => $course,
                'progress' => $lessons->isEmpty()
                    ? 0
                    : (int) round($progressPoints / $lessons->count()),
                'completedLessons' => $completedLessons,
                'lessonsCount' => $lessons->count(),
                'progressPoints' => $progressPoints,
                'resumeLesson' => $resumeLesson,
            ];
        });

        $latestActivities = $recentProgress
            ->sortByDesc(fn($activity) => $activity['progress']->updated_at)
            ->take(6)
            ->values();
        $continueActivity = $recentProgress
            ->filter(fn($activity) => !$activity['progress']->completed)
            ->sortByDesc(fn($activity) => $activity['progress']->updated_at)
            ->first();

        if (!$continueActivity) {
            $nextCourse = $courses->first(fn($course) => $course['resumeLesson']);
            if ($nextCourse) {
                $continueActivity = [
                    'lesson' => $nextCourse['resumeLesson'],
                    'course' => $nextCourse['course'],
                    'progress' => null,
                ];
            }
        }

        $totalLessons = $courses->sum('lessonsCount');
        $overallProgress = $totalLessons > 0
            ? (int) round(
                $courses->sum('progressPoints') / $totalLessons
            )
            : 0;
        $notifications = $user->notifications()->latest()->limit(10)->get();
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        return view('users.profile', [
            'user' => $user,
            'courses' => $courses,
            'latestActivities' => $latestActivities,
            'continueActivity' => $continueActivity,
            'completedLessons' => $courses->sum('completedLessons'),
            'startedLessons' => $recentProgress->count(),
            'overallProgress' => $overallProgress,
            'notifications' => $notifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'learningHours' => intdiv($watchedSeconds, 3600),
            'learningMinutes' => intdiv($watchedSeconds % 3600, 60),
        ]);
    }
    public function logout(Request $request)
    {
        // Log out the user
        auth()->logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        // Redirect to the login page or any other desired location
        return redirect()->route('login')
            ->with('success', 'Logged out successfully!');
    }
}
