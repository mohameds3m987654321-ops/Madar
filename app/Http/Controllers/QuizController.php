<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quizzes\SaveQuizRequest;
use App\Http\Requests\Quizzes\SubmitQuizRequest;
use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\User;
use App\Notifications\CourseActivityNotification;
use App\Services\CourseCertificateService;
use App\Services\CertificateQrCodeService;
use App\Services\CourseNotificationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function edit(Course $course)
    {
        $this->ensureCourseOwnership($course);
        $quiz = $course->quiz()->with('questions.options')->first();

        return view('Quizzes.edit', compact('course', 'quiz'));
    }

    public function save(
        SaveQuizRequest $request,
        Course $course,
        CourseNotificationService $notifications
    )
    {
        $this->ensureCourseOwnership($course);

        $validated = $request->validated();

        $quizCreated = DB::transaction(function () use ($course, $validated) {
            $quiz = $course->quiz()->firstOrCreate(
                ['course_id' => $course->id],
                [
                    'title' => $validated['title'],
                    'passing_score' => $validated['passing_score'],
                ]
            );
            $quiz->update([
                'title' => $validated['title'],
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'] ?? 3,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? 30,
            ]);
            $quiz->sessions()->where('status', 'active')->update(['status' => 'expired']);
            $quiz->questions()->delete();

            foreach ($validated['questions'] as $questionPosition => $questionData) {
                $question = $quiz->questions()->create([
                    'text' => $questionData['text'],
                    'position' => $questionPosition + 1,
                ]);

                foreach ($questionData['options'] as $optionPosition => $optionText) {
                    $question->options()->create([
                        'text' => $optionText,
                        'position' => $optionPosition,
                        'is_correct' => $optionPosition === (int) $questionData['correct'],
                    ]);
                }
            }

            return $quiz->wasRecentlyCreated;
        });

        if ($quizCreated) {
            $notifications->notifyPaidStudents($course, new CourseActivityNotification(
                'quiz_available',
                'الاختبار النهائي جاهز',
                'أضاف المدرس الاختبار النهائي لكورس ' . $course->title . '. أكمل المحاضرات لفتحه.',
                route('courses.show', $course, false),
            ));
        }

        return redirect()
            ->route('quizzes.edit', $course)
            ->with('success', 'تم حفظ الاختبار النهائي.');
    }

    public function show(Course $course, CourseCertificateService $certificates)
    {
        $user = auth()->user();
        $this->ensureStudentCourseAccess($course, $user);
        if (!$this->allLessonsCompleted($course, $user)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('access_notice', 'كمّل كل محاضرات الكورس الأول علشان تفتح الاختبار النهائي.');
        }

        $quiz = $course->quiz()
            ->with([
                'questions' => fn ($query) => $query->orderBy('position'),
                'questions.options' => fn ($query) => $query
                    ->select(['id', 'quiz_question_id', 'text', 'position'])
                    ->orderBy('position'),
            ])
            ->firstOrFail();
        abort_if($quiz->questions->isEmpty(), 404);

        $quizSession = DB::transaction(function () use ($quiz, $user) {
            $lockedQuiz = CourseQuiz::query()
                ->whereKey($quiz->id)
                ->lockForUpdate()
                ->firstOrFail();
            $now = now();

            $lockedQuiz->sessions()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '<=', $now)
                ->update(['status' => 'expired']);

            $activeSession = $lockedQuiz->sessions()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '>', $now)
                ->latest()
                ->first();
            if ($activeSession) {
                return $activeSession;
            }

            $attemptsUsed = $lockedQuiz->sessions()
                ->where('user_id', $user->id)
                ->count()
                + $lockedQuiz->attempts()
                    ->where('user_id', $user->id)
                    ->whereNull('quiz_session_id')
                    ->count();
            if ($attemptsUsed >= $lockedQuiz->max_attempts) {
                return null;
            }

            return $lockedQuiz->sessions()->create([
                'user_id' => $user->id,
                'question_order' => $quiz->questions->shuffle()->pluck('id')->values()->all(),
                'expires_at' => $now->copy()->addMinutes($lockedQuiz->time_limit_minutes),
                'status' => 'active',
            ]);
        });
        if (!$quizSession) {
            return redirect()
                ->route('courses.show', $course)
                ->with('access_notice', 'وصلت للحد الأقصى من محاولات الاختبار.');
        }

        $questionsById = $quiz->questions->keyBy('id');
        $orderedQuestions = collect($quizSession->question_order)
            ->map(fn ($questionId) => $questionsById->get($questionId))
            ->filter()
            ->values();
        abort_unless($orderedQuestions->count() === $quiz->questions->count(), 409);
        $quiz->setRelation('questions', $orderedQuestions);

        $certificate = $certificates->issueIfEligible($course, $user);
        $attempts = $quiz->attempts()
            ->where('user_id', $user->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $attemptsUsed = $quiz->sessions()->where('user_id', $user->id)->count()
            + $quiz->attempts()
                ->where('user_id', $user->id)
                ->whereNull('quiz_session_id')
                ->count();

        return view('Quizzes.show', compact(
            'course',
            'quiz',
            'attempts',
            'certificate',
            'quizSession',
            'attemptsUsed'
        ));
    }

    public function submit(SubmitQuizRequest $request, Course $course, CourseCertificateService $certificates)
    {
        $user = auth()->user();
        $this->ensureStudentCourseAccess($course, $user);
        if (!$this->allLessonsCompleted($course, $user)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('access_notice', 'كمّل كل محاضرات الكورس الأول علشان تفتح الاختبار النهائي.');
        }

        $validated = $request->validated();
        $quiz = $course->quiz()->with(['questions.options'])->firstOrFail();
        abort_if($quiz->questions->isEmpty(), 404);

        $submission = DB::transaction(function () use ($quiz, $user, $validated) {
            $session = QuizSession::query()
                ->whereKey($validated['quiz_session'])
                ->where('course_quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'active') {
                throw ValidationException::withMessages([
                    'answers' => 'انتهت صلاحية جلسة الاختبار. ابدأ محاولة جديدة.',
                ]);
            }

            if (now()->greaterThan($session->expires_at->copy()->addSeconds(30))) {
                $session->update(['status' => 'expired']);

                return null;
            }

            $questionIds = array_map('strval', $session->question_order);
            $answerQuestionIds = array_map(
                'strval',
                array_keys($validated['answers'] ?? [])
            );
            if (array_diff($answerQuestionIds, $questionIds) !== []) {
                throw ValidationException::withMessages([
                    'answers' => 'أحد الإجابات لا يتبع جلسة الاختبار الحالية.',
                ]);
            }

            $questionsById = $quiz->questions->keyBy('id');
            $orderedQuestions = collect($session->question_order)
                ->map(fn ($questionId) => $questionsById->get($questionId))
                ->filter()
                ->values();
            abort_unless($orderedQuestions->count() === count($questionIds), 409);

            $correctAnswers = 0;
            $results = [];
            $answers = $validated['answers'] ?? [];

            foreach ($orderedQuestions as $question) {
                $selectedOptionId = isset($answers[$question->id])
                    ? (int) $answers[$question->id]
                    : null;
                $selectedOption = $selectedOptionId
                    ? $question->options->firstWhere('id', $selectedOptionId)
                    : null;
                if ($selectedOptionId !== null && !$selectedOption) {
                    throw ValidationException::withMessages([
                        'answers' => 'أحد الاختيارات لا يتبع السؤال المحدد.',
                    ]);
                }

                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $correctOption && $selectedOption?->id === $correctOption->id;
                $correctAnswers += (int) $isCorrect;
                $results[] = [
                    'question' => $question->text,
                    'selected' => $selectedOption?->text,
                    'correct' => $correctOption?->text,
                    'is_correct' => $isCorrect,
                ];
            }

            $totalQuestions = $orderedQuestions->count();
            $score = (int) round(($correctAnswers / $totalQuestions) * 100);
            $attempt = $quiz->attempts()->create([
                'user_id' => $user->id,
                'quiz_session_id' => $session->id,
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'passed' => $score >= $quiz->passing_score,
                'results' => $results,
                'submitted_at' => now(),
            ]);
            $session->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return $attempt;
        });

        if (!$submission) {
            throw ValidationException::withMessages([
                'answers' => 'انتهى الوقت المخصص للاختبار.',
            ]);
        }

        $attempt = $submission;
        $certificate = $certificates->issueIfEligible($course, $user);

        return view('Quizzes.result', compact('course', 'quiz', 'attempt', 'certificate'));
    }

    public function result(Course $course, QuizAttempt $attempt)
    {
        $this->ensureStudentCourseAccess($course, auth()->user());
        abort_unless($attempt->user_id === auth()->id(), 403);
        abort_unless($attempt->quiz?->course_id === $course->id, 404);

        return view('Quizzes.result', [
            'course' => $course,
            'quiz' => $attempt->quiz,
            'attempt' => $attempt,
            'certificate' => null,
        ]);
    }

    public function certificate(
        Course $course,
        CourseCertificateService $certificates,
        CertificateQrCodeService $qrCodes
    )
    {
        $user = auth()->user();
        $this->ensureStudentCourseAccess($course, $user);
        $certificate = $certificates->issueIfEligible($course, $user);
        if (!$certificate) {
            return redirect()
                ->route('courses.show', $course)
                ->with('access_notice', 'أكمل كل محاضرات الكورس علشان تقدر تحصل على الشهادة.');
        }

        $verificationUrl = route('certificates.verify', $certificate->certificate_number);
        $certificateQrCode = $qrCodes->dataUri($verificationUrl);

        return view('Courses.certificate', compact(
            'course',
            'certificate',
            'user',
            'verificationUrl',
            'certificateQrCode'
        ));
    }

    private function ensureCourseOwnership(Course $course): void
    {
        abort_unless(
            auth()->user()->role === 'teacher' && $course->teacher_id === auth()->id(),
            403
        );
    }

    private function ensureStudentCourseAccess(Course $course, User $user): void
    {
        abort_unless($user->role === 'student', 403);
        abort_unless($course->status === 'published', 404);

        if (!$course->enrollments()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->exists()) {
            throw new HttpResponseException(
                redirect()
                    ->route('courses.show', $course)
                    ->with('access_notice', 'اشترك في الكورس الأول علشان تقدر تفتح الاختبار أو الشهادة.')
            );
        }
    }

    private function allLessonsCompleted(Course $course, User $user): bool
    {
        return $course->lessons()->exists()
            && !$course->lessons()
                ->whereDoesntHave('progresses', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('completed', true);
                })
                ->exists();
    }
}
