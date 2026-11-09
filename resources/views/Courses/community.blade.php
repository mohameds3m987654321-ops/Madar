<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->user()->preferred_theme ?? 'light' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مجموعة {{ $course->title }} | منصة مدار</title>
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.7;
        }

        a {
            color: inherit;
        }

        button,
        input,
        textarea {
            font: inherit;
        }

        main {
            width: min(1080px, calc(100% - 36px));
            margin: 28px auto 56px;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px max(18px, calc((100% - 1080px) / 2));
            border-bottom: 1px solid var(--line);
            background: var(--surface-solid);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        header a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
            font-size: .9rem;
        }

        .heading {
            margin-bottom: 24px;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.4rem, 4vw, 1.8rem);
            font-weight: 700;
            color: var(--teal);
        }

        .muted,
        .meta {
            color: var(--muted);
        }

        .community-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            align-items: start;
            gap: 24px;
        }

        .feed,
        .sidebar {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        .panel,
        article,
        .side-card {
            margin-bottom: 16px;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .panel h2,
        .side-card h2 {
            margin: 0 0 12px;
            font-size: 1rem;
            font-weight: 700;
        }

        .composer {
            padding: 16px;
            background: var(--surface-raised);
            border-color: var(--brand-line);
        }

        textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            outline: 0;
            background: var(--surface);
            color: var(--ink);
            resize: none;
            transition: border-color .2s;
        }

        textarea:focus {
            border-color: var(--brand-line);
        }

        .composer-tools {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 1px solid var(--line);
        }

        .tool-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 36px;
            padding: 5px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--ink-2);
            font-size: .82rem;
            cursor: pointer;
        }

        .icon {
            width: 18px;
            height: 18px;
            flex: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .tool-button:hover {
            border-color: var(--brand);
            color: var(--brand);
        }

        .publish-button {
            margin-inline-start: auto;
            padding-inline: 20px;
        }

        .attachment-name {
            min-width: 0;
            overflow: hidden;
            color: var(--brand);
            font-size: .78rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        input[type="url"],
        input[type="file"],
        .faq-form input,
        .faq-form textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--ink);
        }

        .faq-list {
            display: grid;
            gap: 10px;
        }

        .faq-item {
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface-soft);
        }

        .faq-item summary {
            color: var(--ink);
            font-weight: 700;
            cursor: pointer;
        }

        .faq-answer {
            margin: 10px 0 0;
            color: var(--ink-2);
            white-space: pre-line;
        }

        .faq-form {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .faq-form textarea {
            min-height: 76px;
            resize: vertical;
        }

        .faq-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .faq-delete {
            border-color: var(--line);
            background: transparent;
            color: var(--red);
        }

        .link-field {
            margin-top: 12px;
            padding: 12px;
            background: var(--surface-soft);
            border-radius: 12px;
        }

        .link-field label {
            display: block;
            margin: 0 0 6px;
            font-size: .82rem;
            font-weight: 600;
        }

        .hint {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: .8rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 8px 16px;
            border: 1px solid var(--brand);
            border-radius: 11px;
            background: var(--brand);
            color: #fff;
            font: inherit;
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .text-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--ink-2);
            cursor: pointer;
        }

        .text-button:hover {
            border-color: var(--brand);
            color: var(--brand);
        }

        .text-button.delete-btn:hover {
            border-color: var(--red);
            background: var(--red-soft);
        }

        .post-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .avatar {
            display: grid;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            place-items: center;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .post-author {
            font-weight: 700;
            color: var(--teal);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .teacher-badge {
            background: var(--brand);
            color: #fff;
            font-size: .7rem;
            padding: 1px 6px;
            border-radius: 5px;
            font-weight: 600;
        }


        .admin-badge {
            background: var(--teal);
            color: #fff;
            font-size: .7rem;
            padding: 1px 6px;
            border-radius: 5px;
            font-weight: 600;
        }

        .teacher-badge.sm,
        .admin-badge.sm {
            font-size: .65rem;
            padding: 0 4px;
        }

        .student-alert {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            color: var(--blue);
            font-size: .8rem;
            font-weight: 700;
            background: var(--blue-soft);
            padding: 4px 10px;
            border-radius: 8px;
        }

        article.student-post {
            border-right: 4px solid var(--blue);
        }

        .pinned {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            color: var(--gold);
            font-size: .8rem;
            font-weight: 700;
            background: var(--gold-soft);
            padding: 4px 10px;
            border-radius: 8px;
        }

        article.is-pinned {
            border: 2px solid var(--gold);
        }

        .post-body {
            margin: 12px 0;
            font-size: 1rem;
            color: var(--ink);
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .attachment {
            display: block;
            margin: 12px 0;
        }

        .attachment img,
        .attachment video {
            display: block;
            max-width: 100%;
            max-height: 500px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .attachment a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface-soft);
            color: var(--brand);
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
        }

        .post-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--muted);
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .action-btn:hover {
            border-color: var(--brand);
            color: var(--brand);
        }

        .action-btn.liked {
            background: var(--brand-soft);
            color: var(--brand);
            border-color: var(--brand);
        }

        .post-admin-actions {
            margin-inline-start: auto;
            display: flex;
            gap: 6px;
        }

        .comments-section {
            margin-top: 16px;
            background: var(--surface-soft);
            border-radius: 12px;
            padding: 12px;
        }

        .comments-list {
            display: grid;
            gap: 8px;
            margin-bottom: 12px;
        }

        .comment {
            padding: 10px;
            border-radius: 10px;
            background: var(--surface);
            border: 1px solid var(--line);
        }

        .comment.teacher-comment {
            border-right: 4px solid var(--brand);
            background: var(--brand-soft);
            box-shadow: inset 0 0 10px rgba(20, 120, 102, 0.05);
        }

        .comment.admin-comment {
            border-right: 4px solid var(--teal);
            background: var(--surface-raised);
        }

        .comment-author {
            font-weight: 700;
            font-size: .85rem;
        }

        .comment-body {
            margin-top: 4px;
            font-size: .9rem;
            color: var(--ink-2);
        }

        .comment-form {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .comment-form textarea {
            min-height: 40px;
            padding: 8px 12px;
            font-size: .88rem;
            border: 1px solid var(--line);
        }

        .comment-form .button {
            min-height: 40px;
        }

        .side-card {
            padding: 18px;
        }

        .group-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .group-stat {
            padding: 12px 8px;
            border-radius: 12px;
            background: var(--surface-soft);
            border: 1px solid var(--line);
            text-align: center;
        }

        .group-stat strong {
            display: block;
            color: var(--teal);
            font-size: 1.1rem;
        }

        .group-stat span {
            color: var(--muted);
            font-size: .75rem;
        }

        .side-link {
            display: flex;
            justify-content: center;
            margin-top: 14px;
            color: var(--brand);
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
            padding: 8px;
            border: 1px solid var(--brand-line);
            border-radius: 10px;
        }

        .side-link:hover {
            background: var(--brand);
            color: #fff;
        }

        .coming-soon {
            display: inline-block;
            margin-bottom: 8px;
            padding: 2px 8px;
            border-radius: 6px;
            background: var(--teal);
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
        }

        .community-feedback {
            position: fixed;
            z-index: 200;
            inset-inline: 20px;
            bottom: 20px;
            width: fit-content;
            max-width: min(440px, calc(100% - 40px));
            margin-inline: auto;
            padding: 10px 16px;
            border: 1px solid var(--brand-line);
            border-radius: 12px;
            background: var(--surface-solid);
            color: var(--ink);
            box-shadow: var(--shadow);
            opacity: 0;
            pointer-events: none;
            transform: translateY(12px);
            transition: opacity .2s ease, transform .2s ease;
        }

        .community-feedback.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .community-feedback.error {
            border-color: var(--red);
            color: var(--red);
        }

        .community-pending {
            opacity: .62;
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
        }

        .community-entering {
            animation: community-enter .28s ease-out both;
        }

        @keyframes community-enter {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 860px) {
            .community-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }
    </style>

    @include('components.appearance')
</head>

<body class="motion-ready">
    <svg aria-hidden="true" width="0" height="0" style="position:absolute;overflow:hidden">

        <symbol id="icon-photo" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <circle cx="8.5" cy="9" r="1.5" />
            <path d="m21 15-5-5L5 20" />
        </symbol>
        <symbol id="icon-video" viewBox="0 0 24 24">
            <rect x="3" y="5" width="14" height="14" rx="2" />
            <path d="m17 10 4-3v10l-4-3z" />
        </symbol>
        <symbol id="icon-file" viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6M8 13h8M8 17h8" />
        </symbol>
        <symbol id="icon-link" viewBox="0 0 24 24">
            <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" />
            <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" />
        </symbol>
        <symbol id="icon-pin" viewBox="0 0 24 24">
            <path d="m16 3 5 5-4 1-4 4 1 4-2 2-4-5-5 5 5-7-5-4 2-2 4 1 4-4z" />
        </symbol>
        <symbol id="icon-heart" viewBox="0 0 24 24">
            <path
                d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8z" />
        </symbol>
        <symbol id="icon-message" viewBox="0 0 24 24">
            <path
                d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z" />
        </symbol>
        <symbol id="icon-lock" viewBox="0 0 24 24">
            <rect x="4" y="11" width="16" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 1 1 8 0v4" />
        </symbol>
        <symbol id="icon-alert" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
        </symbol>
    </svg>
    <header class="md-reveal">
        <a href="{{ route('courses.show', $course) }}" class="md-btn">← العودة إلى الكورس</a>
        <span class="muted">مجموعة الكورس التفاعلية</span>
    </header>

    <main>
        <div class="heading md-reveal">
            <h1>{{ $course->title }}</h1>
            <div class="muted">تواصل مباشر، إعلانات، ومشاركة ملفات مع المدرّس</div>
        </div>

        @if (session('success'))
            <div class="alert md-reveal" role="status" style="animation: md-pop .5s ease;">{{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert error md-reveal" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="community-layout" id="community-root">
            <section class="feed md-stagger" aria-label="منشورات المجموعة">





































                <section class="panel composer md-lift" aria-label="إنشاء منشور">
                    <form method="POST" action="{{ route('courses.community.posts.store', $course) }}"
                        enctype="multipart/form-data" data-community-async data-form-type="post">
                        @csrf
                        <textarea name="body" id="composer-textarea" maxlength="5000"
                            placeholder="{{ $isTeacher ? 'بماذا تريد إخبار طلابك اليوم؟' : 'اسأل المدرّس سؤالاً أو شارك استفساراً...' }}">{{ old('body') }}</textarea>
                        <input id="post-attachment" type="file" name="attachment"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.mp4,.webm" hidden>
                        <div class="composer-tools">
                            <button class="tool-button md-btn" type="button" data-attachment-accept="image/*"
                                title="إرفاق صورة"><svg class="icon">
                                    <use href="#icon-photo" />
                                </svg><span>صورة</span></button>
                            <button class="tool-button md-btn" type="button"
                                data-attachment-accept="video/mp4,video/webm" title="إرفاق فيديو"><svg class="icon">
                                    <use href="#icon-video" />
                                </svg><span>فيديو</span></button>
                            <button class="tool-button md-btn" type="button" data-attachment-accept=".pdf"
                                title="إرفاق ملف"><svg class="icon">
                                    <use href="#icon-file" />
                                </svg><span>ملف</span></button>
                            <button class="tool-button md-btn" type="button" id="toggle-link-field"
                                aria-expanded="{{ old('external_url') ? 'true' : 'false' }}"><svg class="icon">
                                    <use href="#icon-link" />
                                </svg><span>رابط</span></button>
                            <span class="attachment-name" id="attachment-name"></span>
                            <button class="button publish-button md-btn"
                                type="submit">{{ $isTeacher ? 'نشر الإعلان' : 'إرسال الاستفسار' }}</button>
                        </div>
                        <div class="link-field" id="link-field" @if (!old('external_url')) hidden @endif>
                            <label for="post-url">رابط خارجي مهم</label>
                            <input id="post-url" type="url" name="external_url" maxlength="2048"
                                value="{{ old('external_url') }}" placeholder="https://example.com">
                        </div>
                    </form>
                </section>

                @forelse ($posts as $post)
                    @include('Courses.partials.community-post', ['course' => $course, 'post' => $post, 'isTeacher' => $isTeacher])
                @empty
                    <div class="panel empty" data-empty-state>لا توجد منشورات بعد.
                        {{ $isTeacher ? 'ابدأ بإعلان أول لطلابك.' : 'تابع المجموعة للاطلاع على إعلانات المدرّس.' }}
                    </div>
                @endforelse

                @if ($posts->hasPages())
                    <div class="pagination">{{ $posts->links() }}</div>
                @endif
            </section>

            <aside class="sidebar md-stagger" aria-label="معلومات المجموعة">
                <section class="side-card md-reveal md-lift">
                    <h2>مدرّس الكورس</h2>
                    <div class="course-teacher">
                        <span class="avatar"
                            style="background: var(--brand); color: #fff">{{ mb_substr($course->teacher->name, 0, 1) }}</span>
                        <div>
                            <strong>{{ $course->teacher->name }}</strong>
                            <div class="meta">يتفاعل معكم في هذه المجموعة</div>
                        </div>
                    </div>
                </section>
                <section class="side-card md-reveal md-lift">
                    <h2>نشاط المجموعة</h2>
                    <p class="hint">أعداد الطلاب والمنشورات والتعليقات والإعجابات، وتتحدث بعد كل تفاعل.</p>
                    <div class="group-stats">
                        <div class="group-stat"><strong data-community-stat="students">{{ number_format($studentCount) }}</strong><span>طالب</span>
                        </div>
                        <div class="group-stat"><strong data-community-stat="posts">{{ number_format($postCount) }}</strong><span>منشور</span>
                        </div>
                        <div class="group-stat"><strong data-community-stat="comments">{{ number_format($commentCount) }}</strong><span>تعليق</span>
                        </div>
                        <div class="group-stat"><strong data-community-stat="likes">{{ number_format($likeCount) }}</strong><span>إعجاب</span>
                        </div>
                    </div>
                </section>
                <section class="side-card md-reveal md-lift">
                    <h2>عن الكورس</h2>
                    <p style="font-size: .88rem; line-height: 1.6">
                        {{ \Illuminate\Support\Str::limit($course->description, 150) }}</p>
                    <a class="side-link md-btn" href="{{ route('courses.show', $course) }}">عرض التفاصيل ←</a>
                </section>

                <section class="side-card md-reveal md-lift"
                    style="border: 1px dashed var(--brand-line); background: var(--brand-soft)">
                    <h2 style="color: var(--brand)">الأسئلة الشائعة</h2>
                    @if ($faqs->isNotEmpty())
                        <div class="faq-list">
                            @foreach ($faqs as $faq)
                                <details class="faq-item">
                                    <summary>{{ $faq->question }}</summary>
                                    <p class="faq-answer">{{ $faq->answer }}</p>
                                    @if ($isTeacher)
                                        <details class="faq-editor">
                                            <summary>تعديل السؤال</summary>
                                            <form class="faq-form" method="POST"
                                                action="{{ route('courses.community.faqs.update', [$course, $faq]) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="question" maxlength="500" required
                                                    aria-label="السؤال" value="{{ $faq->question }}">
                                                <textarea name="answer" maxlength="5000" required
                                                    aria-label="الإجابة">{{ $faq->answer }}</textarea>
                                                <div class="faq-actions">
                                                    <button class="button md-btn" type="submit">حفظ التعديل</button>
                                                    <button class="button faq-delete md-btn" type="submit" form="delete-faq-{{ $faq->id }}">حذف</button>
                                                </div>
                                            </form>
                                            <form id="delete-faq-{{ $faq->id }}" method="POST"
                                                action="{{ route('courses.community.faqs.destroy', [$course, $faq]) }}"
                                                onsubmit="return confirm('حذف هذا السؤال الشائع؟');">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </details>
                                    @endif
                                </details>
                            @endforeach
                        </div>
                    @else
                        <p class="hint">
                            {{ $isTeacher ? 'أضف هنا إجابات الأسئلة التي تتكرر من طلاب الكورس.' : 'لم يضف المدرّس أسئلة شائعة لهذا الكورس بعد.' }}
                        </p>
                    @endif

                    @if ($isTeacher)
                        <form class="faq-form" method="POST"
                            action="{{ route('courses.community.faqs.store', $course) }}">
                            @csrf
                            <input type="text" name="question" maxlength="500" required
                                placeholder="اكتب السؤال الشائع">
                            <textarea name="answer" maxlength="5000" required
                                placeholder="اكتب إجابة المدرّس"></textarea>
                            <button class="button md-btn" type="submit">إضافة سؤال شائع</button>
                        </form>
                    @endif
                </section>
            </aside>

        </div>
    </main>
    <div class="community-feedback" id="community-feedback" role="status" aria-live="polite"></div>
    <script>
        const attachmentInput = document.getElementById('post-attachment');
        const feed = document.querySelector('.feed');
        const feedback = document.getElementById('community-feedback');
        let feedbackTimer;

        function showCommunityFeedback(message, isError = false) {
            feedback.textContent = message;
            feedback.classList.toggle('error', isError);
            feedback.classList.add('visible');
            window.clearTimeout(feedbackTimer);
            feedbackTimer = window.setTimeout(() => feedback.classList.remove('visible'), 3500);
        }

        function updateCommunityStats(stats = {}) {
            const formatter = new Intl.NumberFormat('ar-EG');
            Object.entries(stats).forEach(([key, value]) => {
                const counter = document.querySelector(`[data-community-stat="${key}"]`);
                if (counter) counter.textContent = formatter.format(value);
            });
        }

        function parseCommunityHtml(html) {
            const template = document.createElement('template');
            template.innerHTML = html.trim();
            return template.content.firstElementChild;
        }

        function insertPost(post) {
            const firstUnpinnedPost = Array.from(feed.children).find((element) =>
                element.matches('article') && !element.classList.contains('is-pinned')
            );
            const pagination = feed.querySelector('.pagination');
            if (firstUnpinnedPost) {
                feed.insertBefore(post, firstUnpinnedPost);
            } else if (pagination) {
                feed.insertBefore(post, pagination);
            } else {
                feed.append(post);
            }
        }

        function setFormBusy(form, busy) {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = busy;
                button.setAttribute('aria-busy', String(busy));
            });
        }

        async function sendCommunityForm(form) {
            const formType = form.dataset.formType;
            const post = form.closest('[data-post-id]');
            const textarea = form.querySelector('textarea[name="body"]');
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton?.textContent;
            let pendingElement = null;
            let previousLike = null;

            if (formType === 'post') {
                pendingElement = document.createElement('article');
                pendingElement.className = 'community-pending';
                pendingElement.textContent = 'جارٍ نشر المنشور...';
                insertPost(pendingElement);
            } else if (form.classList.contains('comment-form')) {
                pendingElement = document.createElement('div');
                pendingElement.className = 'comment community-pending';
                pendingElement.textContent = 'جارٍ إضافة التعليق...';
                post.querySelector('.comments-list').append(pendingElement);
            } else if (form.action.includes('/like')) {
                const likeButton = form.querySelector('button[type="submit"]');
                const countElement = likeButton.querySelector('span');
                previousLike = {
                    liked: likeButton.classList.contains('liked'),
                    count: Number(likeButton.dataset.likeCount) || 0,
                };
                const nextLiked = !previousLike.liked;
                likeButton.classList.toggle('liked', nextLiked);
                likeButton.setAttribute('aria-pressed', String(nextLiked));
                countElement.textContent = new Intl.NumberFormat('ar-EG')
                    .format(previousLike.count + (nextLiked ? 1 : -1));
            } else if (form.action.includes('/posts/') && form.action.endsWith('/pin')) {
                post.classList.add('community-pending');
            } else if (form.action.includes('/posts/') && form.querySelector('input[name="_method"][value="DELETE"]')) {
                post.classList.add('community-pending');
            }

            setFormBusy(form, true);
            if (submitButton && formType !== 'post') submitButton.textContent = 'جارٍ الإرسال...';

            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                    },
                });
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error(response.status === 419
                        ? 'انتهت الجلسة. حدّث الصفحة وسجّل الدخول مرة أخرى.'
                        : 'تعذر تنفيذ الطلب. تحقق من اتصالك وحاول مرة أخرى.');
                }

                const result = await response.json();
                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'تعذر تنفيذ الطلب.');
                }

                if (formType === 'post') {
                    const newPost = parseCommunityHtml(result.html);
                    if (!newPost) throw new Error('تعذر عرض المنشور بعد نشره.');
                    newPost.classList.add('community-entering');
                    pendingElement.replaceWith(newPost);
                    feed.querySelector('[data-empty-state]')?.remove();
                    form.reset();
                    const linkField = document.getElementById('link-field');
                    if (linkField) linkField.hidden = true;
                    document.getElementById('toggle-link-field')?.setAttribute('aria-expanded', 'false');
                    document.getElementById('attachment-name').textContent = '';
                    const composer = document.getElementById('composer-textarea');
                    if (composer) composer.style.height = '';
                    newPost.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (form.classList.contains('comment-form')) {
                    const newComment = parseCommunityHtml(result.html);
                    if (!newComment) throw new Error('تعذر عرض التعليق بعد إضافته.');
                    newComment.classList.add('community-entering');
                    pendingElement.replaceWith(newComment);
                    const count = post.querySelector('.comment-count');
                    if (count) count.textContent = result.comment_count;
                    form.reset();
                    newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else if (form.action.includes('/like')) {
                    const likeButton = form.querySelector('button[type="submit"]');
                    likeButton.querySelector('span').textContent =
                        new Intl.NumberFormat('ar-EG').format(result.likes_count);
                    likeButton.dataset.likeCount = result.likes_count;
                    likeButton.classList.toggle('liked', result.liked);
                    likeButton.setAttribute('aria-pressed', String(result.liked));
                } else if (form.action.endsWith('/pin')) {
                    post.classList.toggle('is-pinned', result.pinned);
                    post.classList.remove('community-pending');
                    const pinLabel = post.querySelector('.pinned');
                    if (result.pinned && !pinLabel) {
                        const label = document.createElement('div');
                        label.className = 'pinned community-entering';
                        label.innerHTML = '<svg class="icon"><use href="#icon-pin"></use></svg> منشور مثبت من المدرّس';
                        post.prepend(label);
                    } else if (!result.pinned) {
                        pinLabel?.remove();
                    }
                    const pinButton = form.querySelector('button');
                    if (pinButton) pinButton.title = result.pinned ? 'إلغاء التثبيت' : 'تثبيت';
                    if (result.pinned) {
                        const firstPinnedPost = Array.from(feed.children).find((element) =>
                            element !== post && element.matches('article.is-pinned')
                        );
                        const firstOtherPost = Array.from(feed.children).find((element) =>
                            element !== post && element.matches('article')
                        );
                        if (firstPinnedPost) firstPinnedPost.before(post);
                        else if (firstOtherPost) firstOtherPost.before(post);
                        else insertPost(post);
                    } else {
                        const firstUnpinnedPost = Array.from(feed.children).find((element) =>
                            element !== post && element.matches('article:not(.is-pinned)')
                        );
                        if (firstUnpinnedPost) firstUnpinnedPost.before(post);
                        else {
                            const pagination = feed.querySelector('.pagination');
                            if (pagination) pagination.before(post);
                            else feed.append(post);
                        }
                    }
                } else if (form.querySelector('input[name="_method"][value="DELETE"]')) {
                    post.style.height = `${post.offsetHeight}px`;
                    post.style.overflow = 'hidden';
                    post.style.transition = 'opacity .2s ease, height .2s ease, margin .2s ease, padding .2s ease';
                    requestAnimationFrame(() => {
                        post.style.opacity = '0';
                        post.style.height = '0';
                        post.style.margin = '0';
                        post.style.paddingBlock = '0';
                    });
                    window.setTimeout(() => {
                        post.remove();
                        if (!feed.querySelector('article')) {
                            const emptyState = document.createElement('div');
                            emptyState.className = 'panel empty';
                            emptyState.dataset.emptyState = '';
                            emptyState.textContent = 'لا توجد منشورات في هذه المجموعة حاليًا.';
                            feed.append(emptyState);
                        }
                    }, 220);
                }

                updateCommunityStats(result.stats);
                showCommunityFeedback(result.message || 'تم التحديث بنجاح.');
            } catch (error) {
                pendingElement?.remove();
                post?.classList.remove('community-pending');
                if (previousLike) {
                    const likeButton = form.querySelector('button[type="submit"]');
                    likeButton.classList.toggle('liked', previousLike.liked);
                    likeButton.setAttribute('aria-pressed', String(previousLike.liked));
                    likeButton.querySelector('span').textContent =
                        new Intl.NumberFormat('ar-EG').format(previousLike.count);
                    likeButton.dataset.likeCount = previousLike.count;
                }
                showCommunityFeedback(
                    error instanceof Error ? error.message : 'تعذر تنفيذ الطلب. حاول مرة أخرى.',
                    true
                );
            } finally {
                setFormBusy(form, false);
                if (submitButton && originalButtonText) submitButton.textContent = originalButtonText;
            }
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-community-async]');
            if (!form) return;
            event.preventDefault();
            sendCommunityForm(form);
        });

        document.querySelectorAll('[data-attachment-accept]').forEach((button) => {
            button.addEventListener('click', () => {
                attachmentInput.accept = button.dataset.attachmentAccept;
                attachmentInput.click();
            });
        });

        attachmentInput?.addEventListener('change', () => {
            const name = attachmentInput.files?.[0]?.name ?? '';
            document.getElementById('attachment-name').textContent = name ? `📎 ${name}` : '';
        });

        document.getElementById('toggle-link-field')?.addEventListener('click', (event) => {
            const field = document.getElementById('link-field');
            field.hidden = !field.hidden;
            event.currentTarget.setAttribute('aria-expanded', String(!field.hidden));
            if (!field.hidden) document.getElementById('post-url').focus();
        });

        // Auto-resize textarea
        const composerTextarea = document.getElementById('composer-textarea');
        if (composerTextarea) {
            composerTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    </script>

</body>

</html>
