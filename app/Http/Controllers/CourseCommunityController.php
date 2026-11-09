<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\CoursePost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseCommunityController extends Controller
{
    public function index(Course $course, Request $request): View
    {
        $this->authorizeCommunityAccess($request, $course);
        $course->load('teacher:id,name');
        $faqs = $course->faqs()->oldest()->get();

        $posts = $course->posts()
            ->with([
                'user:id,name,role',
                'comments' => fn($query) => $query
                    ->with('user:id,name,role')
                    ->oldest(),
            ])
            ->withCount('likes')
            ->withExists([
                'likes as liked_by_current_user' => fn($query) => $query
                    ->where('user_id', $request->user()->id),
            ])
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest()
            ->paginate(20);

        $commentCount = DB::table('course_post_comments')
            ->join('course_posts', 'course_posts.id', '=', 'course_post_comments.course_post_id')
            ->where('course_posts.course_id', $course->id)
            ->count();
        $likeCount = DB::table('course_post_likes')
            ->join('course_posts', 'course_posts.id', '=', 'course_post_likes.course_post_id')
            ->where('course_posts.course_id', $course->id)
            ->count();

        return view('Courses.community', [
            'course' => $course,
            'posts' => $posts,
            'faqs' => $faqs,
            'isTeacher' => $request->user()->role === 'teacher',
            'studentCount' => $course->enrollments()->where('status', 'paid')->count(),
            'postCount' => $course->posts()->count(),
            'commentCount' => $commentCount,
            'likeCount' => $likeCount,
        ]);
    }

    public function storePost(Request $request, Course $course)
    {
        $this->authorizeCommunityAccess($request, $course);
        // السماح للمدرس أو الطالب بالنشر
        abort_unless(in_array($request->user()->role, ['teacher', 'student']), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without_all:attachment,external_url'],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,mp4,webm',
                'max:51200',
            ],
        ]);


        if (
            blank($validated['body'] ?? null)
            && blank($validated['external_url'] ?? null)
            && ! $request->hasFile('attachment')
        ) {
            throw ValidationException::withMessages([
                'body' => 'أضف نصًا أو ملفًا أو رابطًا قبل نشر الإعلان.',
            ]);
        }

        $attachment = $request->file('attachment');
        $attachmentPath = $attachment
            ? $attachment->store("community/{$course->id}", 'private')
            : null;

        $post = $course->posts()->create([
            'user_id' => $request->user()->id,
            'body' => filled($validated['body'] ?? null) ? trim($validated['body']) : null,
            'external_url' => $validated['external_url'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachment?->getClientOriginalName(),
            'attachment_mime' => $attachment?->getMimeType(),
        ]);
        $post->load([
            'user:id,name,role',
            'comments' => fn ($query) => $query->with('user:id,name,role')->oldest(),
        ]);
        $post->setAttribute('likes_count', 0);
        $post->setAttribute('liked_by_current_user', false);

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('Courses.partials.community-post', [
                    'course' => $course,
                    'post' => $post,
                    'isTeacher' => $request->user()->role === 'teacher',
                ])->render(),
                'stats' => $this->communityStats($course),
                'message' => 'تم نشر المنشور في المجموعة.',
            ], 201);
        }

        return back()->with('success', 'تم نشر الإعلان في مجموعة الكورس.');
    }

    public function storeComment(Request $request, Course $course, CoursePost $post)
    {
        $this->authorizePostAccess($request, $course, $post);
        $user = $request->user();

        if ($post->user->role === 'student') {
            $isCourseTeacher = $user->role === 'teacher'
                && (int) $course->teacher_id === (int) $user->id;
            $isQuestionAuthor = $user->role === 'student'
                && (int) $post->user_id === (int) $user->id;

            abort_unless(
                $isCourseTeacher || $isQuestionAuthor,
                403,
                'الرد على استفسار الطالب متاح لصاحب الاستفسار ومدرّس الكورس فقط.'
            );
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);


        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        if ($request->expectsJson()) {
            $comment->load('user:id,name,role');

            return response()->json([
                'html' => view('Courses.partials.community-comment', compact('comment'))->render(),
                'comment_count' => $post->comments()->count(),
                'stats' => $this->communityStats($course),
                'message' => 'تم إضافة تعليقك.',
            ], 201);
        }

        return back()->with('success', 'تم إضافة تعليقك.');
    }

    public function storeFaq(Request $request, Course $course)
    {
        $this->authorizeCourseTeacher($request, $course);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
        ]);

        $course->faqs()->create([
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
        ]);

        return back()->with('success', 'تمت إضافة السؤال الشائع.');
    }

    public function updateFaq(Request $request, Course $course, CourseFaq $faq)
    {
        $this->authorizeCourseTeacher($request, $course);
        abort_unless((int) $faq->course_id === (int) $course->id, 404);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
        ]);

        $faq->update([
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
        ]);

        return back()->with('success', 'تم تحديث السؤال الشائع.');
    }

    public function destroyFaq(Request $request, Course $course, CourseFaq $faq)
    {
        $this->authorizeCourseTeacher($request, $course);
        abort_unless((int) $faq->course_id === (int) $course->id, 404);

        $faq->delete();

        return back()->with('success', 'تم حذف السؤال الشائع.');
    }

    public function toggleLike(Request $request, Course $course, CoursePost $post)
    {
        $this->authorizePostAccess($request, $course, $post);

        $liked = DB::transaction(function () use ($request, $post): bool {
            $lockedPost = CoursePost::query()->lockForUpdate()->findOrFail($post->id);
            $like = $lockedPost->likes()
                ->where('user_id', $request->user()->id)
                ->first();

            if ($like) {
                $like->delete();

                return false;
            }

            $lockedPost->likes()->create(['user_id' => $request->user()->id]);

            return true;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $post->likes()->count(),
                'stats' => $this->communityStats($course),
                'message' => $liked ? 'تم تسجيل إعجابك.' : 'تم إلغاء إعجابك.',
            ]);
        }

        return back()->with('success', $liked ? 'تم تسجيل إعجابك.' : 'تم إلغاء إعجابك.');
    }

    public function togglePin(Request $request, Course $course, CoursePost $post)
    {
        $this->authorizePostAccess($request, $course, $post);
        abort_unless(
            $request->user()->role === 'teacher'
                && (int) $course->teacher_id === (int) $request->user()->id,
            403
        );

        $pinned = ! $post->is_pinned;
        $post->update([
            'is_pinned' => $pinned,
            'pinned_at' => $pinned ? now() : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'pinned' => $pinned,
                'message' => $pinned ? 'تم تثبيت المنشور.' : 'تم إلغاء تثبيت المنشور.',
            ]);
        }

        return back()->with('success', $pinned ? 'تم تثبيت المنشور.' : 'تم إلغاء تثبيت المنشور.');
    }

    public function destroyPost(Request $request, Course $course, CoursePost $post)
    {
        $this->authorizePostAccess($request, $course, $post);
        abort_unless(
            (int) $post->user_id === (int) $request->user()->id
                || ($request->user()->role === 'teacher'
                    && (int) $course->teacher_id === (int) $request->user()->id),
            403
        );

        $attachmentPath = $post->attachment_path;
        $post->delete();

        if ($attachmentPath) {
            Storage::disk('private')->delete($attachmentPath);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'stats' => $this->communityStats($course),
                'message' => 'تم حذف المنشور.',
            ]);
        }

        return back()->with('success', 'تم حذف المنشور.');
    }

    public function attachment(Request $request, Course $course, CoursePost $post): BinaryFileResponse
    {
        $this->authorizePostAccess($request, $course, $post);

        $path = $post->attachment_path;
        abort_unless(
            is_string($path)
                && preg_match(
                    '/\Acommunity\/' . preg_quote((string) $course->id, '/') . '\/[A-Za-z0-9._-]+\z/',
                    $path
                ) === 1,
            404
        );

        $disk = Storage::disk('private');
        abort_unless($disk->exists($path), 404);

        $response = response()->file($disk->path($path), [
            'Content-Type' => $post->attachment_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function authorizePostAccess(Request $request, Course $course, CoursePost $post): void
    {
        abort_unless((int) $post->course_id === (int) $course->id, 404);
        $this->authorizeCommunityAccess($request, $course);
    }

    /**
     * @return array{students: int, posts: int, comments: int, likes: int}
     */
    private function communityStats(Course $course): array
    {
        return [
            'students' => $course->enrollments()->where('status', 'paid')->count(),
            'posts' => $course->posts()->count(),
            'comments' => DB::table('course_post_comments')
                ->join('course_posts', 'course_posts.id', '=', 'course_post_comments.course_post_id')
                ->where('course_posts.course_id', $course->id)
                ->count(),
            'likes' => DB::table('course_post_likes')
                ->join('course_posts', 'course_posts.id', '=', 'course_post_likes.course_post_id')
                ->where('course_posts.course_id', $course->id)
                ->count(),
        ];
    }

    private function authorizeCommunityAccess(Request $request, Course $course): void
    {
        $user = $request->user();
        $isCourseTeacher = $user->role === 'teacher'
            && (int) $course->teacher_id === (int) $user->id;
        $isPaidStudent = $user->role === 'student'
            && $course->enrollments()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->exists();

        abort_unless($isCourseTeacher || $isPaidStudent, 403);
    }

    private function authorizeCourseTeacher(Request $request, Course $course): void
    {
        abort_unless(
            $request->user()->role === 'teacher'
                && (int) $course->teacher_id === (int) $request->user()->id,
            403
        );
    }
}
