<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $lesson->title }} | منصة مدار</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Cairo", sans-serif;
            background: #0b1117;
            color: #e8eef3;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        /* =========================
           Header
        ========================= */

        header {
            height: 72px;
            background: #101820;
            border-bottom: 1px solid #202b35;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 5%;

            position: sticky;
            top: 0;

            z-index: 100;
        }

        .brand {
            font-size: 25px;
            font-weight: 800;
            color: #18c7a3;
        }

        .header-course {
            display: flex;
            align-items: center;
            gap: 12px;

            color: #8794a1;
            font-size: 14px;
        }

        .header-course strong {
            color: #dce5eb;
        }

        .progress-mini {
            width: 120px;
            height: 6px;

            background: #26333d;

            border-radius: 20px;
            overflow: hidden;
        }

        .progress-mini span {
            display: block;

            width: {{ $courseProgress }}%;
            height: 100%;

            background: #18c7a3;

            border-radius: inherit;
        }

        .user-circle {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            background: #12382f;
            color: #18c7a3;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 800;
        }

        /* =========================
           Page
        ========================= */

        .page {
            max-width: 1450px;
            margin: auto;

            padding: 30px 5%;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            color: #84919d;

            font-size: 14px;

            margin-bottom: 25px;

            transition: .2s;
        }

        .back:hover {
            color: #18c7a3;
        }

        /* =========================
           Layout
        ========================= */

        .layout {
            display: grid;

            grid-template-columns: 1fr 350px;

            gap: 28px;

            align-items: start;
        }

        .main {
            min-width: 0;
        }

        /* =========================
           Hero
        ========================= */

        .hero {
            margin-bottom: 25px;
        }

        .unit {
            color: #18c7a3;

            font-size: 14px;
            font-weight: 700;

            margin-bottom: 7px;
        }

        .hero h1 {
            font-size: 34px;
            line-height: 1.4;

            margin-bottom: 8px;

            color: #f2f6f8;
        }

        .hero p {
            color: #84919d;
            font-size: 15px;
        }

        /* =========================
           Video
        ========================= */

        .video-card {
            background: #101820;

            border: 1px solid #202b35;

            border-radius: 22px;

            padding: 15px;

            box-shadow: 0 15px 45px rgba(0, 0, 0, .22);

            margin-bottom: 25px;
        }

        .video-wrapper {
            width: 100%;

            background: #05080b;

            border-radius: 15px;

            overflow: hidden;

            position: relative;
        }

        .video-wrapper video {
            display: block;

            width: 100%;

            max-height: 650px;

            background: #05080b;
        }

        .video-watermark {
            position: absolute;
            top: 12%;
            left: 8%;
            z-index: 1;
            max-width: 84%;
            color: rgba(255, 255, 255, .62);
            font-size: clamp(13px, 2vw, 20px);
            font-weight: 700;
            text-shadow: 0 1px 4px rgba(0, 0, 0, .9);
            pointer-events: none;
            user-select: none;
            transition: top 1.5s ease, left 1.5s ease;
        }

        .no-video {
            min-height: 400px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-direction: column;

            gap: 12px;

            color: #71808d;
        }

        .no-video-icon {
            width: 70px;
            height: 70px;

            border-radius: 20px;

            background: #17242d;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;

            color: #18c7a3;
        }

        /* =========================
           Video Info
        ========================= */

        .video-info {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 18px 8px 5px;
        }

        .media-info {
            display: flex;
            align-items: center;

            gap: 14px;
        }

        .album {
            width: 52px;
            height: 52px;

            border-radius: 15px;

            background: #12382f;
            color: #18c7a3;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .media-info h3 {
            font-size: 16px;

            color: #e8eef3;

            margin-bottom: 3px;
        }

        .media-info small {
            color: #758391;
        }

        .complete-btn {
            border: 1px solid #185d4e;

            background: #12382f;

            color: #18c7a3;

            padding: 10px 15px;

            border-radius: 11px;

            font-weight: 700;

            cursor: pointer;

            transition: .2s;
        }

        .complete-btn:hover {
            background: #18c7a3;
            color: #07110f;
        }

        /* =========================
           Content Panels
        ========================= */

        .panels {
            display: grid;

            grid-template-columns: 1fr;

            gap: 20px;
        }

        .panel {
            background: #101820;

            border: 1px solid #202b35;

            border-radius: 20px;

            padding: 25px;
        }

        .panel h2 {
            font-size: 18px;

            color: #f0f4f6;

            margin-bottom: 15px;
        }

        .description {
            color: #9aa7b2;

            line-height: 2;

            font-size: 14px;

            white-space: pre-line;
        }

        .qa-panel { margin-top: 20px; }
        .qa-panel h2 { margin-bottom: 8px; }
        .qa-intro, .qa-empty { color: #9aa7b2; font-size: 14px; line-height: 1.8; }
        .qa-form { display: grid; gap: 9px; margin-top: 15px; }
        .qa-form label { font-size: 14px; font-weight: 700; color: #dce5eb; }
        .qa-form textarea {
            width: 100%;
            min-height: 100px;
            resize: vertical;
            border: 1px solid #2d414d;
            border-radius: 11px;
            padding: 11px 13px;
            background: #0b1117;
            color: #e8eef3;
            font: inherit;
        }
        .qa-form textarea:focus-visible { outline: 2px solid #18c7a3; outline-offset: 2px; }
        .qa-submit {
            justify-self: start;
            border: 0;
            border-radius: 10px;
            padding: 9px 16px;
            background: #18c7a3;
            color: #07110f;
            font-weight: 800;
            cursor: pointer;
        }
        .qa-submit:hover { background: #32dbb8; }
        .qa-list { display: grid; gap: 14px; margin-top: 18px; }
        .qa-question, .qa-reply { padding: 15px; border: 1px solid #202b35; border-radius: 13px; background: #0c141b; }
        .qa-reply { margin: 12px 0 0 24px; border-color: #185d4e; background: #10221f; }
        .qa-author { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .qa-author strong { color: #e8eef3; }
        .qa-author small { color: #758391; }
        .qa-badge { border-radius: 20px; padding: 2px 9px; background: #12382f; color: #18c7a3; font-size: 11px; }
        .qa-body { margin-top: 8px; color: #b9c5ce; line-height: 1.9; white-space: pre-line; overflow-wrap: anywhere; }
        .qa-error { color: #ff9f9f; font-size: 13px; }
        .qa-alert { margin-top: 12px; padding: 10px 12px; border-radius: 10px; background: #12382f; color: #80e3ce; }

        /* =========================
           Sidebar
        ========================= */

        .sidebar {
            position: sticky;
            top: 95px;
        }

        .course-card {
            background: #101820;

            border: 1px solid #202b35;

            border-radius: 20px;

            padding: 20px;
        }

        .course-card h2 {
            font-size: 18px;

            line-height: 1.6;

            color: #edf2f5;

            margin-bottom: 8px;
        }

        .teacher {
            color: #758391;

            font-size: 13px;

            margin-bottom: 18px;
        }

        /* =========================
           Progress
        ========================= */

        .course-progress {
            margin-bottom: 20px;
        }

        .progress-text {
            display: flex;

            justify-content: space-between;

            font-size: 12px;

            color: #758391;

            margin-bottom: 7px;
        }

        .progress-text strong {
            color: #18c7a3;
        }

        .progress-bar {
            height: 7px;

            background: #26333d;

            border-radius: 20px;

            overflow: hidden;
        }

        .progress-bar span {
            display: block;

            height: 100%;

            width: {{ $courseProgress }}%;

            background: #18c7a3;
        }

        /* =========================
           Lessons
        ========================= */

        .chapters {
            border-top: 1px solid #202b35;

            padding-top: 15px;
        }

        .chapter-title {
            font-weight: 800;

            font-size: 14px;

            color: #dce5eb;

            margin-bottom: 8px;
        }

        .lesson-link {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 8px;

            padding: 10px;

            border-radius: 10px;

            font-size: 13px;

            color: #84919d;

            margin-bottom: 3px;

            transition: .2s;
        }

        .lesson-link:hover {
            background: #152a28;

            color: #18c7a3;
        }

        .lesson-link.active {
            background: #12382f;

            color: #18c7a3;

            font-weight: 700;
        }

        .lesson-number {
            min-width: 25px;
            height: 25px;

            border-radius: 8px;

            background: #1a252e;

            color: #9aa7b2;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 11px;
        }

        .lesson-link.active .lesson-number {
            background: #18c7a3;

            color: #07110f;
        }

        /* =========================
           Resources
        ========================= */

        .resources {
            margin-top: 20px;

            border-top: 1px solid #202b35;

            padding-top: 18px;
        }

        .resources h3 {
            font-size: 14px;

            color: #dce5eb;

            margin-bottom: 10px;
        }

        .resource {
            display: flex;

            align-items: center;

            gap: 8px;

            padding: 10px;

            background: #151e26;

            border-radius: 10px;

            color: #8b98a4;

            font-size: 13px;

            margin-bottom: 7px;
        }

        .resource:hover {
            color: #18c7a3;

            background: #152a28;
        }

        /* =========================
           Responsive
        ========================= */

        @media (max-width: 1000px) {

            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

        }

        @media (max-width: 700px) {

            header {
                padding: 0 20px;
            }

            .header-course {
                display: none;
            }

            .page {
                padding: 20px;
            }

            .hero h1 {
                font-size: 26px;
            }

            .video-card {
                padding: 10px;
            }

            .video-info {
                align-items: flex-start;

                flex-direction: column;
            }

            .complete-btn {
                width: 100%;
            }

            .no-video {
                min-height: 250px;
            }

        }

    </style>
    @include('components.appearance')
</head>


<body class="lesson-player">


<header>

    <div class="brand">
        مَدار
    </div>


    <div class="header-course">

        <span>
            {{ $course->title }}
        </span>

        <div class="progress-mini" role="progressbar" aria-valuenow="{{ $courseProgress }}" aria-valuemin="0" aria-valuemax="100" aria-label="نسبة إنجاز الكورس">
            <span></span>
        </div>

        <strong>
            {{ $courseProgress }}%
        </strong>

    </div>


    <div class="user-circle">

        {{ mb_substr(auth()->user()->name, 0, 1) }}

    </div>

    <button
        type="button"
        class="theme-toggle"
        id="lesson-theme-toggle"
        aria-label="تبديل الوضع الفاتح والداكن"
        aria-pressed="{{ (auth()->user()->preferred_theme ?? 'light') === 'dark' ? 'true' : 'false' }}"
        title="تبديل الوضع"
    >
        <span aria-hidden="true">◐</span>
        <span class="theme-toggle-label">المظهر</span>
    </button>

</header>



<div class="page">


    <a
        href="{{ route('courses.show', $course->id) }}"
        class="back"
    >
        ← العودة إلى الكورس
    </a>



    <div class="layout">


        <!-- =========================
             Main
        ========================= -->

        <main class="main">


            <section class="hero">

                <div class="unit">

                    المحاضرة {{ $lesson->order }}

                </div>


                <h1>

                    {{ $lesson->title }}

                </h1>


                <p>

                    {{ $course->title }}

                </p>

            </section>



            <!-- =========================
                 Video
            ========================= -->

            <section class="video-card">


                <div class="video-wrapper">


                    
@if($lesson->video && !$lesson->isVideoProcessing())
    <video
        id="lesson-video"
        controls
        controlslist="nodownload"
        disablepictureinpicture
        preload="metadata"
        playsinline
        @if(auth()->user()->role === 'student')
            data-start-position="{{ $progress?->last_position ?? 0 }}"
            data-progress-url="{{ route('lessons.progress', $lesson->id) }}"
        @endif
    >
        <source
            src="{{ route('lessons.video', $lesson) }}"
            type="video/mp4"
        >

        المتصفح الخاص بك لا يدعم تشغيل الفيديو.
    </video>
    <div class="video-watermark" aria-hidden="true">
        المحتوى مخصص لـ {{ auth()->user()->name }}
    </div>
@else
    <div class="no-video">
        <div class="no-video-icon">▶</div>

        <strong>{{ $lesson->isVideoProcessing() ? 'فيديو المحاضرة قيد التجهيز' : 'لا يوجد فيديو لهذه المحاضرة' }}</strong>

        <span>{{ $lesson->isVideoProcessing() ? 'تم استلام الفيديو، حدّث الصفحة بعد قليل لمتابعة الحالة. شكرًا لصبرك.' : 'يمكنك قراءة محتوى المحاضرة بالأسفل.' }}</span>
    </div>
@endif


                </div>





                <div class="video-info">


                    <div class="media-info">

                        <div class="album">
                            ▶
                        </div>


                        <div>

                            <h3>
                                {{ $lesson->title }}
                            </h3>

                            <small>
                                {{ $course->teacher->name }}
                            </small>

                        </div>

                    </div>



                    @if(auth()->user()->role === 'student')
                        @if($progress && $progress->completed)
                            <button type="button" class="complete-btn" disabled>
                                ✅ تم إكمال المحاضرة
                            </button>
                        @else
                            <form
                                id="complete-lesson-form"
                                action="{{ route('lessons.complete', $lesson->id) }}"
                                method="POST"
                            >
                                @csrf

                                <button
                                    id="complete-lesson-button"
                                    type="submit"
                                    class="complete-btn"
                                    @if($lesson->video) disabled @endif
                                >
                                    تم إكمال المحاضرة
                                </button>
                            </form>
                        @endif

                        @if($certificate)
                            <a class="complete-btn" href="{{ route('courses.certificate', $course) }}">
                                🎓 عرض شهادة إتمام الكورس
                            </a>
                        @endif

                        @if($lesson->video && !$lesson->isVideoProcessing())
                            <small id="progress-save-status" aria-live="polite"></small>
                        @endif
                    @endif

                </div>


            </section>



            <!-- =========================
                 Lesson Content
            ========================= -->

            <section class="panel lesson-content">
                <div
                    class="lesson-tabs"
                    role="tablist"
                    aria-label="محتوى المحاضرة"
                    data-initial-tab="{{ session()->has('lesson_comment_success') || $errors->has('body') || $errors->has('parent_id') ? 'discussion' : 'description' }}"
                >
                    <button class="lesson-tab" id="tab-description" type="button" role="tab"
                            aria-controls="panel-description" aria-selected="true" tabindex="0">
                        الوصف
                    </button>
                    <button class="lesson-tab" id="tab-resources" type="button" role="tab"
                            aria-controls="panel-resources" aria-selected="false" tabindex="-1">
                        المرفقات والملفات
                    </button>
                    <button class="lesson-tab" id="tab-notes" type="button" role="tab"
                            aria-controls="panel-notes" aria-selected="false" tabindex="-1">
                        ملاحظاتي
                    </button>
                    <button class="lesson-tab" id="tab-discussion" type="button" role="tab"
                            aria-controls="panel-discussion" aria-selected="false" tabindex="-1">
                        الأسئلة والنقاش
                    </button>
                </div>

                <div class="lesson-tab-panel" id="panel-description" role="tabpanel"
                     aria-labelledby="tab-description" tabindex="0">
                    <h2>محتوى المحاضرة</h2>
                    <p class="description">{{ $lesson->content ?: 'لا يوجد محتوى لهذه المحاضرة حتى الآن.' }}</p>
                </div>

                <div class="lesson-tab-panel" id="panel-resources" role="tabpanel"
                     aria-labelledby="tab-resources" tabindex="0" hidden>
                    <div class="resources">
                        <h2>المرفقات والملفات</h2>
                        @forelse ($lesson->attachments as $attachment)
                            <a class="resource" href="{{ route('lessons.attachments.download', [$lesson, $attachment]) }}">
                                <span aria-hidden="true">📎</span>
                                <span>{{ $attachment->original_name }}</span>
                                <span>تنزيل</span>
                            </a>
                        @empty
                            <p class="description">لا توجد ملفات مرفقة إضافية بهذه المحاضرة.</p>
                        @endforelse
                    </div>
                </div>

                <div class="lesson-tab-panel" id="panel-notes" role="tabpanel"
                     aria-labelledby="tab-notes" tabindex="0" hidden>
                    <h2>ملاحظاتي</h2>
                    <p class="notes-hint">تُحفظ ملاحظاتك على هذا الجهاز فقط، ويمكنك الرجوع إليها عند مراجعة المحاضرة.</p>
                    <label class="sr-only" for="lesson-notes">ملاحظاتك على المحاضرة</label>
                    <textarea id="lesson-notes" class="lesson-notes" maxlength="10000"
                              placeholder="اكتب ملاحظاتك هنا..."></textarea>
                    <p id="lesson-notes-status" aria-live="polite"></p>
                </div>

                <div class="lesson-tab-panel" id="panel-discussion" role="tabpanel"
                     aria-labelledby="tab-discussion" tabindex="0" hidden>
                    <section class="qa-panel" id="lesson-qa" aria-labelledby="lesson-qa-title">
                <h2 id="lesson-qa-title">الأسئلة والنقاش</h2>
                <p class="qa-intro">اسأل عن أي نقطة في المحاضرة، وسيجيب مدرس الكورس على سؤالك.</p>

                @if (session('lesson_comment_success'))
                    <div class="qa-alert" role="status">{{ session('lesson_comment_success') }}</div>
                @endif

                @if (auth()->user()->role === 'student')
                    <form class="qa-form" method="POST" action="{{ route('lessons.comments.store', $lesson) }}">
                        @csrf
                        <label for="question-body">اكتب سؤالك</label>
                        <textarea id="question-body" name="body" minlength="2" maxlength="2000" required
                                  placeholder="اكتب سؤالك عن محتوى المحاضرة...">{{ old('parent_id') ? '' : old('body') }}</textarea>
                        @error('body')
                            @if (!old('parent_id'))
                                <div class="qa-error">{{ $message }}</div>
                            @endif
                        @enderror
                        <button class="qa-submit" type="submit">إرسال السؤال</button>
                    </form>
                @endif

                @error('parent_id')
                    <div class="qa-error">{{ $message }}</div>
                @enderror

                @if ($comments->isNotEmpty())
                    <div class="qa-list">
                        @foreach ($comments as $comment)
                            <article class="qa-question">
                                <div class="qa-author">
                                    <strong>{{ $comment->user->name }}</strong>
                                    <small>{{ $comment->created_at->format('Y/m/d H:i') }}</small>
                                </div>
                                <p class="qa-body">{{ $comment->body }}</p>

                                @foreach ($comment->replies as $reply)
                                    <div class="qa-reply">
                                        <div class="qa-author">
                                            <strong>
                                                {{ $reply->user->name }}
                                                <span class="qa-badge">مدرس الكورس</span>
                                            </strong>
                                            <small>{{ $reply->created_at->format('Y/m/d H:i') }}</small>
                                        </div>
                                        <p class="qa-body">{{ $reply->body }}</p>
                                    </div>
                                @endforeach

                                @if ($canRespondToQuestions)
                                    <form class="qa-form" method="POST" action="{{ route('lessons.comments.store', $lesson) }}">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        <label for="reply-{{ $comment->id }}">الرد على السؤال</label>
                                        <textarea id="reply-{{ $comment->id }}" name="body" minlength="2" maxlength="2000"
                                                  required placeholder="اكتب ردك للطالب...">{{ (string) old('parent_id') === (string) $comment->id ? old('body') : '' }}</textarea>
                                        @if ((string) old('parent_id') === (string) $comment->id)
                                            @error('body')
                                                <div class="qa-error">{{ $message }}</div>
                                            @enderror
                                        @endif
                                        <button class="qa-submit" type="submit">إرسال الرد</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    <div class="qa-pagination">{{ $comments->links() }}</div>
                @else
                    <p class="qa-empty" style="margin-top:16px">لا توجد أسئلة بعد. ابدأ النقاش بطرح أول سؤال.</p>
                @endif
                    </section>
                </div>
            </section>


        </main>



        <!-- =========================
             Sidebar
        ========================= -->

        <aside class="sidebar">


            <div class="course-card">


                <h2>
                    {{ $course->title }}
                </h2>


                <div class="teacher">

                    المدرس:
                    {{ $course->teacher->name }}

                </div>



                <div class="course-progress">


                    <div class="progress-text">

                        <span>
                            تقدمك في الكورس
                        </span>

                        <strong>
                            {{ $courseProgress }}%
                        </strong>

                    </div>


                    <div class="progress-bar">

                        <span></span>

                    </div>


                </div>



                <div class="chapters">
                    <details class="chapter-accordion" open>
                        <summary>
                            <span>المحاضرات</span>
                            <span class="chapter-count">{{ $courseLessons->count() }} درس</span>
                        </summary>
                        <div class="chapter-list">
                    @forelse($courseLessons as $courseLesson)
                        @php($isComplete = $courseLesson->progresses->first()?->completed ?? false)


                            <a
                                href="{{ route('lessons.show', $courseLesson->id) }}"
                                class="lesson-link {{ $courseLesson->id == $lesson->id ? 'active' : '' }} {{ $isComplete ? 'is-complete' : '' }}"
                            >


                                <span
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                    "
                                >


                                    <span class="lesson-number">

                                        {{ $courseLesson->order }}

                                    </span>


                                    <span>

                                        {{ $courseLesson->title }}

                                    </span>


                                </span>



                                @if ($isComplete)
                                    <span class="lesson-state" aria-label="محاضرة مكتملة">✓</span>
                                @elseif ($courseLesson->id == $lesson->id)
                                    <span class="lesson-state" aria-label="المحاضرة الحالية">▶</span>
                                @else
                                    <span class="lesson-state" aria-label="محاضرة غير مكتملة">○</span>
                                @endif


                            </a>


                        @empty


                            <p
                                style="
                                    color:#758391;
                                    font-size:13px;
                                "
                            >

                                لا توجد محاضرات حالياً.

                            </p>


                        @endforelse


                            </div>
                        </details>
                    </div>


            </div>


        </aside>


    </div>

</div>



<script>

    const lessonTabs = document.querySelector('.lesson-tabs');
    const tabButtons = lessonTabs ? Array.from(lessonTabs.querySelectorAll('[role="tab"]')) : [];
    const tabPanels = tabButtons.map((button) => document.getElementById(button.getAttribute('aria-controls')));

    const activateLessonTab = (tabId, moveFocus = false) => {
        const selectedButton = tabButtons.find((button) => button.id === tabId);
        if (!selectedButton) return;

        tabButtons.forEach((button) => {
            const isSelected = button === selectedButton;
            button.setAttribute('aria-selected', String(isSelected));
            button.tabIndex = isSelected ? 0 : -1;
        });

        tabPanels.forEach((panel) => {
            panel.hidden = panel.getAttribute('aria-labelledby') !== tabId;
        });

        if (moveFocus) selectedButton.focus();
    };

    if (lessonTabs) {
        activateLessonTab(
            `tab-${lessonTabs.dataset.initialTab || 'description'}`,
        );

        lessonTabs.addEventListener('click', (event) => {
            const button = event.target.closest('[role="tab"]');
            if (button) activateLessonTab(button.id);
        });

        lessonTabs.addEventListener('keydown', (event) => {
            const currentIndex = tabButtons.indexOf(document.activeElement);
            if (currentIndex < 0) return;

            let nextIndex = currentIndex;
            if (event.key === 'ArrowRight') nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
            if (event.key === 'ArrowLeft') nextIndex = (currentIndex + 1) % tabButtons.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = tabButtons.length - 1;
            if (nextIndex === currentIndex) return;

            event.preventDefault();
            activateLessonTab(tabButtons[nextIndex].id, true);
        });
    }

    const lessonNotes = document.getElementById('lesson-notes');
    const lessonNotesStatus = document.getElementById('lesson-notes-status');
    if (lessonNotes && lessonNotesStatus) {
        const notesKey = 'madar:lesson-notes:' + @json(auth()->id()) + ':' + @json($lesson->id);
        let notesSaveTimer;
        let notesDirty = false;

        try {
            lessonNotes.value = localStorage.getItem(notesKey) || '';
        } catch (error) {
            console.error('تعذر تحميل ملاحظات المحاضرة:', error);
            lessonNotesStatus.textContent = 'تعذر تحميل الملاحظات المحفوظة من هذا الجهاز.';
        }

        const saveLessonNotes = () => {
            window.clearTimeout(notesSaveTimer);
            if (!notesDirty) return;

            try {
                localStorage.setItem(notesKey, lessonNotes.value);
                notesDirty = false;
                lessonNotesStatus.textContent = 'تم حفظ ملاحظاتك على هذا الجهاز.';
            } catch (error) {
                console.error('تعذر حفظ ملاحظات المحاضرة:', error);
                lessonNotesStatus.textContent = 'تعذر حفظ الملاحظات. تحقّق من مساحة التخزين في المتصفح.';
            }
        };

        lessonNotes.addEventListener('input', () => {
            notesDirty = true;
            lessonNotesStatus.textContent = 'جارٍ حفظ الملاحظات...';
            window.clearTimeout(notesSaveTimer);
            notesSaveTimer = window.setTimeout(saveLessonNotes, 250);
        });
        lessonNotes.addEventListener('blur', saveLessonNotes);
        window.addEventListener('pagehide', saveLessonNotes);
    }

    const themeToggle = document.getElementById('lesson-theme-toggle');
    themeToggle?.setAttribute(
        'aria-pressed',
        String(document.documentElement.dataset.theme === 'dark'),
    );
    themeToggle?.addEventListener('click', async () => {
        themeToggle.disabled = true;
        const root = document.documentElement;
        const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = nextTheme;
        themeToggle.setAttribute('aria-pressed', String(nextTheme === 'dark'));

        const preferenceSaved = window.madarThemePreference.save(nextTheme, true);
        if (!preferenceSaved) {
            window.madarToast('تم تغيير المظهر، لكن تعذّر حفظه على هذا الجهاز.', 'error');
        }

        try {
            const response = await fetch(@json(route('appearance.update')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ theme: nextTheme }),
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            window.madarThemePreference.markSynced();
            window.madarToast(`تم تفعيل الوضع ${nextTheme === 'dark' ? 'الداكن' : 'الفاتح'}.`);
        } catch (error) {
            console.error('تعذر مزامنة تفضيل المظهر:', error);
            window.madarToast('تم تغيير المظهر مؤقتًا، لكن تعذّر مزامنته مع حسابك.', 'error');
        } finally {
            themeToggle.disabled = false;
        }
    });

    const lessonVideo = document.getElementById('lesson-video');
    const videoWatermark = document.querySelector('.video-watermark');

    if (lessonVideo && videoWatermark) {
        lessonVideo.addEventListener('contextmenu', (event) => event.preventDefault());

        window.setInterval(() => {
            const maxTop = Math.max(0, lessonVideo.clientHeight - videoWatermark.offsetHeight);
            const maxLeft = Math.max(0, lessonVideo.clientWidth - videoWatermark.offsetWidth);

            videoWatermark.style.top = `${Math.random() * maxTop}px`;
            videoWatermark.style.left = `${Math.random() * maxLeft}px`;
        }, 10000);
    }

    if (lessonVideo?.dataset.progressUrl) {
        const saveStatus = document.getElementById('progress-save-status');
        const csrfToken = @json(csrf_token());
        let lastSavedPosition = -1;
        let lastSavedAt = 0;
        let saveInProgress = null;

        const saveProgress = async (force = false) => {
            if (!Number.isFinite(lessonVideo.duration) || lessonVideo.duration <= 0) {
                return false;
            }

            const position = Math.floor(lessonVideo.currentTime);

            if (saveInProgress) {
                if (!force) return false;
                const previousSaveSucceeded = await saveInProgress;
                if (!previousSaveSucceeded) return false;
                return saveProgress(true);
            }

            if (!force && (
                position === lastSavedPosition ||
                Date.now() - lastSavedAt < 10000
            )) {
                return false;
            }

            saveStatus.textContent = 'جارٍ حفظ تقدم المشاهدة...';
            saveInProgress = (async () => {
                try {
                const response = await fetch(lessonVideo.dataset.progressUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ position }),
                    keepalive: true,
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const savedProgress = await response.json();
                lastSavedPosition = savedProgress.position;
                lastSavedAt = Date.now();
                saveStatus.textContent = 'تم حفظ تقدم المشاهدة.';
                    return savedProgress;
                } catch (error) {
                    console.error('تعذر حفظ تقدم المحاضرة:', error);
                    saveStatus.textContent = 'تعذر حفظ التقدم. سيُعاد المحاولة تلقائيًا.';
                    return false;
                }
            })();

            const saved = await saveInProgress;
            saveInProgress = null;
            return saved;
        };

        lessonVideo.addEventListener('loadedmetadata', () => {
            const startPosition = Number(lessonVideo.dataset.startPosition);

            if (startPosition > 0 && startPosition < lessonVideo.duration) {
                lessonVideo.currentTime = startPosition;
            }
            updateCompleteButton();
        }, { once: true });

        const completeButton = document.getElementById('complete-lesson-button');
        const completeForm = document.getElementById('complete-lesson-form');
        const updateCompleteButton = () => {
            if (completeButton) {
                completeButton.disabled =
                    lessonVideo.currentTime / lessonVideo.duration < 0.95;
            }
        };

        lessonVideo.addEventListener('timeupdate', updateCompleteButton);
        lessonVideo.addEventListener('pause', () => saveProgress(true));
        lessonVideo.addEventListener('seeked', () => saveProgress(true));
        lessonVideo.addEventListener('timeupdate', () => saveProgress());
        completeForm?.addEventListener('submit', async event => {
            event.preventDefault();
            if (lessonVideo.currentTime / lessonVideo.duration < 0.95) {
                saveStatus.textContent = 'شاهد 95% على الأقل من الفيديو قبل إكمال المحاضرة.';
                return;
            }

            completeButton.disabled = true;
            const savedProgress = await saveProgress(true);
            if (!savedProgress) {
                completeButton.disabled = false;
                return;
            }

            if (savedProgress.watched_seconds < Math.ceil(savedProgress.duration * 0.95)) {
                saveStatus.textContent = 'استمر في مشاهدة الفيديو؛ لا يكفي الوصول إلى نهاية الفيديو فقط.';
                completeButton.disabled = false;
            } else {
                completeForm.submit();
            }
        });
        window.addEventListener('pagehide', () => saveProgress(true));
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                saveProgress(true);
            }
        });
    }


</script>


</body>

</html>
