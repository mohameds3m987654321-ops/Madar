<article class="{{ $post->is_pinned ? 'is-pinned' : '' }} {{ $post->user->role === 'student' ? 'student-post' : '' }}"
    data-post-id="{{ $post->id }}">
    @if ($post->is_pinned)
        <div class="pinned"><svg class="icon"><use href="#icon-pin" /></svg> منشور مثبت من المدرّس</div>
    @endif
    @if ($post->user->role === 'student')
        <div class="student-alert"><svg class="icon"><use href="#icon-alert" /></svg> سؤال من طالب - بانتظار رد المدرّس</div>
    @endif
    <div class="post-head">
        <span class="avatar"
            style="background: {{ $post->user->role === 'teacher' ? 'var(--brand)' : 'var(--brand-soft)' }}; color: {{ $post->user->role === 'teacher' ? '#fff' : 'var(--brand)' }}">
            {{ mb_substr($post->user->name, 0, 1) }}
        </span>
        <div>
            <div class="post-author">
                {{ $post->user->name }}
                @if ($post->user->role === 'teacher')
                    <span class="teacher-badge">المدرّس</span>
                @endif
            </div>
            <div class="meta">{{ $post->created_at->diffForHumans() }}</div>
        </div>
    </div>

    @if ($post->body)
        <div class="post-body">{{ $post->body }}</div>
    @endif

    @if ($post->attachment_path)
        @php($attachmentUrl = route('courses.community.posts.attachment', [$course, $post]))
        <div class="attachment">
            @if (str_starts_with($post->attachment_mime ?? '', 'image/'))
                <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $attachmentUrl }}" alt="{{ $post->attachment_name }}" loading="lazy">
                </a>
            @elseif (str_starts_with($post->attachment_mime ?? '', 'video/'))
                <video controls preload="none" src="{{ $attachmentUrl }}"></video>
                <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">فتح الفيديو</a>
            @else
                <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">
                    📎 {{ $post->attachment_name ?: 'عرض الملف المرفق' }}
                </a>
            @endif
        </div>
    @endif

    @if ($post->external_url)
        <a class="external-link" href="{{ $post->external_url }}" target="_blank" rel="noopener noreferrer">
            فتح الرابط المرفق
        </a>
    @endif

    <div class="post-actions">
        <form method="POST" action="{{ route('courses.community.likes.toggle', [$course, $post]) }}" data-community-async>
            @csrf
            <button class="action-btn {{ $post->liked_by_current_user ? 'liked' : '' }} md-btn" type="submit"
                aria-pressed="{{ $post->liked_by_current_user ? 'true' : 'false' }}"
                data-like-count="{{ $post->likes_count }}">
                <svg class="icon"><use href="#icon-heart" /></svg>
                <span>{{ $post->likes_count }}</span>
            </button>
        </form>
        <button class="action-btn md-btn toggle-comments" type="button">
            <svg class="icon"><use href="#icon-message" /></svg>
            <span class="comment-count">{{ $post->comments->count() }}</span>
        </button>

        <div class="post-admin-actions">
            @if ($isTeacher)
                <form method="POST" action="{{ route('courses.community.posts.pin', [$course, $post]) }}" data-community-async>
                    @csrf
                    @method('PATCH')
                    <button class="text-button md-btn" type="submit"
                        title="{{ $post->is_pinned ? 'إلغاء التثبيت' : 'تثبيت' }}">
                        <svg class="icon"><use href="#icon-pin" /></svg>
                    </button>
                </form>
            @endif
            @if ($isTeacher || (int) $post->user_id === (int) auth()->id())
                <form method="POST" action="{{ route('courses.community.posts.destroy', [$course, $post]) }}"
                    data-community-async onsubmit="return confirm('هل تريد حذف هذا المنشور؟');">
                    @csrf
                    @method('DELETE')
                    <button class="text-button md-btn delete-btn" type="submit" title="حذف">
                        <svg class="icon" style="stroke: var(--red)">
                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="comments-section">
        <div class="comments-list">
            @foreach ($post->comments as $comment)
                @include('Courses.partials.community-comment', ['comment' => $comment])
            @endforeach
        </div>
        @if (
            $post->user->role !== 'student'
            || (int) $post->user_id === (int) auth()->id()
            || ($isTeacher && (int) $course->teacher_id === (int) auth()->id())
        )
            <form class="comment-form" method="POST"
                action="{{ route('courses.community.comments.store', [$course, $post]) }}" data-community-async>
                @csrf
                <textarea name="body" minlength="2" maxlength="2000" required aria-label="اكتب تعليقك"
                    placeholder="{{ $post->user->role === 'student' ? ((int) $post->user_id === (int) auth()->id() ? 'أضف توضيحاً لاستفسارك...' : 'أجب عن استفسار الطالب...') : 'أضف تعليقاً...' }}"></textarea>
                <button class="button md-btn" type="submit">{{ $post->user->role === 'student' ? 'إضافة رد' : 'تعليق' }}</button>
            </form>
        @else
            <div class="hint" style="text-align: center; padding: 10px;">
                <svg class="icon" style="width:14px; height:14px;"><use href="#icon-lock" /></svg>
                الرد على هذا الاستفسار متاح لصاحبه ومدرّس الكورس فقط.
            </div>
        @endif
    </div>
</article>
