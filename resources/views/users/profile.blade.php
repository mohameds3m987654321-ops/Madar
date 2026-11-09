<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نشاطي | منصة مدار</title>
    <link rel="stylesheet" href="{{ asset('css/global-search.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root[data-theme="light"], :root:not([data-theme="dark"]) {
            color-scheme: light;
            --bg: #f4f7f5;
            --body-glow: rgba(20, 120, 102, .09);
            --surface: #fff;
            --surface-raised: #edf6f3;
            --surface-solid: rgba(255, 255, 255, .96);
            --row: #f7faf9;
            --track: #dce9e5;
            --ink: #172b28;
            --subtle: #435a54;
            --muted: #657a75;
            --line: #e1eae5;
            --brand: #147866;
            --accent: #147866;
            --brand-soft: #e2f3ed;
            --brand-line: #a9d6c8;
            --button-hover: #d4eee5;
            --primary-hover: #0e564a;
            --cover-gradient: linear-gradient(135deg, #d9f1e9, #b8dfd2);
            --continue-gradient: linear-gradient(135deg, #e8f6f1, #fff);
            --shadow: 0 12px 28px rgba(23, 37, 34, .08);
            --shadow-up: 0 20px 44px rgba(23, 37, 34, .13);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 90% 0%, var(--body-glow), transparent 34rem),
                var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.7;
        }

        a { color: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        :focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }

        main { width: min(1120px, calc(100% - 36px)); margin: 0 auto; padding: 34px 0 56px; }

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 26px;
        }

        .brand { color: var(--accent); font-size: .9rem; font-weight: 700; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 4px; font-size: clamp(1.65rem, 4vw, 2.25rem); line-height: 1.35; }
        h2 { margin-bottom: 4px; font-size: 1.15rem; }
        .muted { color: var(--muted); }

        .account-menu > summary { display: none; }
        .head-links { display: flex; flex-wrap: wrap; gap: 9px; }
        .button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 15px;
            border: 1px solid var(--brand-line);
            border-radius: 10px;
            background: var(--brand-soft);
            color: var(--accent);
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            transition: background .16s, transform .16s;
        }
        .button:hover { background: var(--button-hover); transform: translateY(-2px); box-shadow: var(--shadow); }
        .button:active { transform: translateY(0) scale(.97); }
        .button.primary { border-color: transparent; background: var(--brand); color: #fff; }
        .button.primary:hover { background: var(--primary-hover); }

        .welcome, .panel, .stat {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface-solid);
            box-shadow: var(--shadow);
        }

        .stat, .panel {
            transition: transform .28s var(--motion-ease), box-shadow .28s;
        }
        .stat:hover, .panel:hover { transform: translateY(-3px); box-shadow: var(--shadow-up); }

        .welcome {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            padding: 24px;
            margin-bottom: 18px;
        }
        .welcome::before {
            content: "";
            position: absolute;
            inset-inline-end: -60px;
            top: -80px;
            width: 210px;
            aspect-ratio: 1;
            border-radius: 50%;
            background: radial-gradient(circle, var(--brand-soft), transparent 68%);
            pointer-events: none;
        }
        .identity { display: flex; align-items: center; gap: 17px; min-width: 0; }
        .avatar {
            width: 72px;
            height: 72px;
            flex: none;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 2px solid var(--brand-line);
            border-radius: 50%;
            background: var(--brand-soft);
            color: var(--accent);
            font-size: 1.7rem;
            font-weight: 700;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .welcome p { margin: 3px 0 0; color: var(--muted); }
        .welcome .since { margin-top: 5px; color: var(--subtle); font-size: .85rem; }
        .welcome-progress { width: 190px; flex: none; }
        .welcome-progress > div:first-child { display: flex; justify-content: space-between; gap: 12px; font-size: .86rem; }
        .welcome-progress strong { color: var(--accent); }

        .track { height: 8px; margin-top: 8px; overflow: hidden; border-radius: 99px; background: var(--track); }
        .fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--brand), var(--accent)); transition: width 1.1s var(--motion-ease); }

        .stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .stat { padding: 17px 19px; }
        .stat-label { color: var(--muted); font-size: .87rem; }
        .stat-value { display: block; margin-top: 4px; color: var(--ink); font-size: 1.55rem; font-weight: 700; }
        .stat-value small { color: var(--subtle); font-size: .82rem; font-weight: 500; }
        .stat-value.time-value { font-size: 1.18rem; }

        .columns { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(280px, .85fr); gap: 18px; align-items: start; }
        .stack { display: grid; gap: 18px; }
        .panel { padding: 21px; }
        .panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .panel-heading p { margin: 2px 0 0; color: var(--muted); font-size: .88rem; }
        .text-link { color: var(--accent); font-size: .88rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
        .text-link:hover { text-decoration: underline; }

        .continue-card {
            padding: 18px;
            border: 1px solid var(--brand-line);
            border-radius: 13px;
            background: var(--continue-gradient);
        }
        .eyebrow { color: var(--accent); font-size: .8rem; font-weight: 600; }
        .continue-card h3 { margin: 7px 0 0; font-size: 1.08rem; }
        .continue-card p { margin: 3px 0 15px; color: var(--muted); font-size: .88rem; }

        .course-list, .activity-list { display: grid; gap: 12px; }
        .course-row {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            align-items: center;
            gap: 13px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--row);
            transition: transform .25s var(--motion-ease), box-shadow .25s, border-color .25s;
        }
        .course-row:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--brand-line); }
        .course-cover {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 10px;
            background: var(--cover-gradient);
            color: var(--accent);
            font-weight: 700;
        }
        .course-cover img { width: 100%; height: 100%; object-fit: cover; }
        .course-info { min-width: 0; }
        .course-info h3 { overflow: hidden; margin: 0; font-size: .98rem; text-overflow: ellipsis; white-space: nowrap; }
        .course-info p { margin: 2px 0 7px; color: var(--muted); font-size: .82rem; }
        .course-info .track { height: 6px; margin-top: 5px; }
        .course-percent { color: var(--accent); font-size: .85rem; font-weight: 700; }

        .activity {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            align-items: center;
            gap: 11px;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
        }
        .activity:last-child { padding-bottom: 0; border-bottom: 0; }
        .activity-mark {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--brand-soft);
            color: var(--accent);
        }
        .activity h3 { margin: 0; font-size: .9rem; }
        .activity p { margin: 2px 0 0; color: var(--muted); font-size: .79rem; }
        .activity time { color: var(--muted); font-size: .76rem; white-space: nowrap; }
        .notification-count {
            display: inline-flex;
            min-width: 26px;
            height: 26px;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--accent);
            font-size: .8rem;
            font-weight: 700;
        }
        .notification-list { display: grid; gap: 10px; }
        .notifications-more { width: 100%; margin-top: 12px; cursor: pointer; }
        .notification-link {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            align-items: center;
            gap: 11px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--row);
            text-decoration: none;
        }
        .notification-link:hover { border-color: var(--brand-line); background: var(--surface-raised); }
        .notification-link.unread { border-color: var(--brand-line); }
        .notification-link h3 { margin: 0; font-size: .9rem; }
        .notification-link p { margin: 2px 0 0; color: var(--muted); font-size: .8rem; }
        .notification-link time { color: var(--muted); font-size: .75rem; white-space: nowrap; }
        .notification-unread-mark { color: var(--accent); font-size: .75rem; font-weight: 700; }
        .progress-chart-summary {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px;
            margin-bottom: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--row);
        }
        .progress-ring {
            width: 76px;
            height: 76px;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: conic-gradient(var(--brand) 0 var(--progress), var(--track) var(--progress) 100%);
        }
        .progress-ring span {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--surface);
            color: var(--ink);
            font-weight: 700;
        }
        .progress-ring { transition: filter .3s; }
        .progress-ring:hover { filter: drop-shadow(0 4px 12px rgba(20, 120, 102, .25)); }
        .progress-chart-summary strong { display: block; }
        .progress-chart-summary p { margin: 3px 0 0; color: var(--muted); font-size: .85rem; }
        .course-chart-link { color: inherit; text-decoration: none; }
        .course-chart-link:hover { color: var(--accent); }
        .theme-toggle { font: inherit; cursor: pointer; }
        .theme-status {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .empty {
            padding: 24px 16px;
            border: 1px dashed var(--brand-line);
            border-radius: 12px;
            color: var(--muted);
            text-align: center;
        }
        .empty strong { display: block; margin-bottom: 4px; color: var(--ink); }
        .empty p { margin: 0 0 13px; }

        @media (max-width: 780px) {
            .columns { grid-template-columns: 1fr; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            main { width: min(100% - 24px, 1120px); padding-top: 22px; }
            .page-head { align-items: flex-start; flex-direction: column; }
            .account-menu { position: relative; width: 100%; }
            .account-menu > summary {
                display: flex;
                min-height: 44px;
                align-items: center;
                justify-content: space-between;
                padding: 8px 14px;
                border: 1px solid var(--brand-line);
                border-radius: 10px;
                background: var(--brand-soft);
                color: var(--accent);
                cursor: pointer;
                font-weight: 600;
                list-style: none;
            }
            .account-menu > summary::-webkit-details-marker { display: none; }
            .account-menu > summary::after {
                content: "⌄";
                font-size: 1.2rem;
                transition: transform .35s cubic-bezier(.22, 1, .36, 1);
            }
            .account-menu[open] > summary::after { transform: rotate(180deg); }
            .account-menu .head-links {
                position: absolute;
                z-index: 20;
                top: calc(100% + 8px);
                inset-inline: 0;
                display: flex;
                flex-direction: column;
                max-height: 0;
                overflow: hidden;
                padding: 0 12px;
                border: 1px solid var(--line);
                border-radius: 12px;
                background: var(--surface-solid);
                box-shadow: var(--shadow-up);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translateY(-12px) scale(.98);
                transform-origin: top;
                transition: max-height .45s cubic-bezier(.22, 1, .36, 1),
                    padding .4s cubic-bezier(.22, 1, .36, 1),
                    opacity .3s ease, transform .4s cubic-bezier(.22, 1, .36, 1),
                    visibility 0s linear .45s;
            }
            .account-menu[open] .head-links {
                max-height: 600px;
                padding: 12px;
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: translateY(0) scale(1);
                transition-delay: 0s, 0s, 0s, 0s, 0s;
            }
            .account-menu[open] .head-links > .button,
            .account-menu[open] .head-links form,
            .account-menu[open] .head-links form .button { width: 100%; }
            .welcome { align-items: flex-start; flex-direction: column; padding: 18px; }
            .welcome-progress { width: 100%; }
            .avatar { width: 60px; height: 60px; }
            .panel { padding: 16px; }
            .course-row { grid-template-columns: 48px minmax(0, 1fr); }
            .course-cover { width: 48px; height: 48px; }
            .course-percent { grid-column: 2; }
            .activity { grid-template-columns: 36px minmax(0, 1fr); }
            .activity time { grid-column: 2; }
            .progress-chart-summary { align-items: flex-start; }
        }
        @media (max-width: 560px) and (prefers-reduced-motion: reduce) {
            .account-menu > summary::after,
            .account-menu .head-links { transition: none; }
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.appearance')
</head>
<body>
<main>
    @include('components.global-search')

    <header class="page-head">
        <div>
            <div class="brand">منصة مدار التعليمية</div>
            <h1>نشاطي التعليمي</h1>
            <div class="muted">تابع رحلتك وتقدمك في التعلم من مكان واحد.</div>
        </div>
        <details class="account-menu">
            <summary>قائمة الحساب</summary>
            <div class="head-links">
                <button class="button theme-toggle" id="theme-toggle" type="button"
                        aria-label="تبديل إلى الوضع الفاتح">تبديل إلى الوضع الفاتح</button>
                <span id="theme-status" class="theme-status" role="status" aria-live="polite"></span>
                @if ($user->role === 'teacher')
                    <a class="button" href="{{ route('teacher.dashboard') }}">لوحة المدرس</a>
                @elseif ($user->role === 'admin')
                    <a class="button" href="{{ route('admin.dashboard') }}">لوحة المدير</a>
                @else
                    <a class="button" href="{{ route('courses.my') }}">كورساتي</a>
                    <a class="button" href="{{ route('payments.index') }}">المدفوعات والفواتير</a>
                @endif
                <a class="button primary" href="{{ route('courses.index') }}">اكتشف كورسات</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button" type="submit">تسجيل الخروج</button>
                </form>
            </div>
        </details>
    </header>

    <section class="welcome" aria-label="ملخص الحساب">
        <div class="identity">
            <div class="avatar">
                @if ($user->image)
                    <img src="{{ asset('storage/' . $user->image) }}" alt="صورة {{ $user->name }}">
                @else
                    {{ mb_substr($user->name, 0, 1) }}
                @endif
            </div>
            <div>
                <h2>أهلًا، {{ $user->name }}</h2>
                <p>دي لمحة سريعة عن نشاطك وتقدمك في التعلم.</p>
                <div class="since">عضو منذ {{ optional($user->created_at)->translatedFormat('F Y') }}</div>
            </div>
        </div>
        <div class="welcome-progress">
            <div><span>متوسط تقدمك</span><strong>{{ $overallProgress }}%</strong></div>
            <div class="track" role="progressbar" aria-label="متوسط تقدم الكورسات" aria-valuenow="{{ $overallProgress }}" aria-valuemin="0" aria-valuemax="100">
                <div class="fill" style="width: {{ $overallProgress }}%"></div>
            </div>
        </div>
    </section>

    <section class="stats" aria-label="إحصائيات التعلم">
        <article class="stat">
            <div class="stat-label">كورساتي النشطة</div>
            <strong class="stat-value">{{ $courses->count() }}</strong>
        </article>
        <article class="stat">
            <div class="stat-label">محاضرات مكتملة</div>
            <strong class="stat-value">{{ $completedLessons }}</strong>
        </article>
        <article class="stat">
            <div class="stat-label">محاضرات بدأت مشاهدتها</div>
            <strong class="stat-value">{{ $startedLessons }}</strong>
        </article>
        <article class="stat">
            <div class="stat-label">متوسط تقدمك</div>
            <strong class="stat-value">{{ $overallProgress }}<small>%</small></strong>
        </article>
        <article class="stat">
            <div class="stat-label">وقت التعلم المسجل</div>
            <strong class="stat-value time-value">
                @if ($learningHours > 0)
                    {{ $learningHours }} س {{ $learningMinutes }} د
                @else
                    {{ $learningMinutes }} دقيقة
                @endif
            </strong>
        </article>
    </section>

    <div class="columns">
        <div class="stack">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>تابع التعلم</h2>
                        <p>ارجع لآخر محاضرة أو ابدأ محاضرة جديدة.</p>
                    </div>
                </div>
                @if ($continueActivity)
                    <div class="continue-card">
                        <span class="eyebrow">استكمال التعلم</span>
                        <h3>{{ $continueActivity['lesson']->title }}</h3>
                        <p>{{ $continueActivity['course']->title }}</p>
                        <a class="button primary" href="{{ route('lessons.show', $continueActivity['lesson']->id) }}">افتح المحاضرة</a>
                    </div>
                @elseif ($courses->isEmpty())
                    <div class="empty">
                        <strong>لسه ما اشتركتش في أي كورس</strong>
                        <p>اختار كورس وابدأ رحلتك التعليمية.</p>
                        <a class="button primary" href="{{ route('courses.index') }}">تصفح الكورسات</a>
                    </div>
                @else
                    <div class="empty">
                        <strong>أكملت كل محاضراتك 🎉</strong>
                        <p>تقدر ترجع لأي كورس للمراجعة أو تستكشف كورس جديد.</p>
                        <a class="button" href="{{ route('courses.my') }}">عرض كورساتي</a>
                    </div>
                @endif
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>مخطط تقدم الكورسات</h2>
                        <p>نظرة مرئية على إنجازك في كل كورس.</p>
                    </div>
                    @if ($courses->isNotEmpty())
                        <a class="text-link" href="{{ route('courses.my') }}">عرض الكل</a>
                    @endif
                </div>

                @if ($courses->isNotEmpty())
                    <div class="progress-chart-summary">
                        <div class="progress-ring" role="img"
                             aria-label="متوسط التقدم في جميع الكورسات {{ $overallProgress }}%"
                             style="--progress: {{ $overallProgress }}%">
                            <span>{{ $overallProgress }}%</span>
                        </div>
                        <div>
                            <strong>متوسط إنجازك في الكورسات</strong>
                            <p>كل شريط بالأسفل يوضح تقدمك في كورس محدد.</p>
                        </div>
                    </div>
                    <div class="course-list">
                        @foreach ($courses as $courseActivity)
                            @php($course = $courseActivity['course'])
                            <article class="course-row">
                                <div class="course-cover">
                                    @if ($course->image)
                                        <img src="{{ asset('storage/' . $course->image) }}" alt="">
                                    @else
                                        {{ mb_substr($course->title, 0, 1) }}
                                    @endif
                                </div>
                                <div class="course-info">
                                    <h3><a class="course-chart-link" href="{{ route('courses.show', $course) }}">{{ $course->title }}</a></h3>
                                    <p>{{ $course->teacher?->name ?? 'مدرس المنصة' }} · {{ $courseActivity['completedLessons'] }} من {{ $courseActivity['lessonsCount'] }} محاضرة مكتملة</p>
                                    <div class="track" role="progressbar" aria-label="التقدم في {{ $course->title }}" aria-valuenow="{{ $courseActivity['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="fill" style="width: {{ $courseActivity['progress'] }}%"></div>
                                    </div>
                                    @if ($courseActivity['resumeLesson'])
                                        <p>
                                            الدرس التالي:
                                            <a class="course-chart-link" href="{{ route('lessons.show', $courseActivity['resumeLesson']->id) }}">
                                                {{ $courseActivity['resumeLesson']->title }}
                                            </a>
                                        </p>
                                    @endif
                                </div>
                                <span class="course-percent">{{ $courseActivity['progress'] }}%</span>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty">هتظهر كورساتك هنا بعد الاشتراك فيها.</div>
                @endif
            </section>
        </div>

        <div class="stack">
            <section class="panel" aria-labelledby="notifications-title">
                <div class="panel-heading">
                    <div>
                        <h2 id="notifications-title">الإشعارات</h2>
                        <p>آخر التحديثات المتعلقة بحسابك وكورساتك.</p>
                    </div>
                    @if ($unreadNotificationsCount > 0)
                        <span class="notification-count" aria-label="{{ $unreadNotificationsCount }} إشعار غير مقروء">
                            {{ $unreadNotificationsCount }}
                        </span>
                    @endif
                </div>

                @if ($notifications->isNotEmpty())
                    <div class="notification-list">
                        @foreach ($notifications as $notification)
                            <a class="notification-link {{ $notification->read_at ? '' : 'unread' }}"
                               @if ($loop->index >= 4) hidden @endif
                               href="{{ route('notifications.open', $notification->id) }}">
                                <span class="activity-mark" aria-hidden="true">
                                    @switch($notification->data['event'] ?? '')
                                        @case('lesson_added') ▶ @break
                                        @case('quiz_available') ؟ @break
                                        @case('certificate_issued') 🎓 @break
                                        @case('new_user_registered') 👤 @break
                                        @case('student_enrolled') ✚ @break
                                        @default •
                                    @endswitch
                                </span>
                                <span>
                                    <h3>{{ $notification->data['title'] ?? 'إشعار جديد' }}</h3>
                                    <p>{{ $notification->data['message'] ?? '' }}</p>
                                </span>
                                @if (!$notification->read_at)
                                    <span class="notification-unread-mark">جديد</span>
                                @else
                                    <time datetime="{{ $notification->created_at?->toISOString() }}">
                                        {{ $notification->created_at?->diffForHumans() }}
                                    </time>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    @if ($notifications->count() > 4)
                        <button class="button notifications-more" id="notifications-more" type="button">
                            عرض المزيد
                        </button>
                    @endif
                @else
                    <div class="empty">
                        <strong>مفيش إشعارات حاليًا</strong>
                        <p>هنبلغك هنا لما يحصل تحديث جديد.</p>
                    </div>
                @endif
            </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>آخر نشاط</h2>
                    <p>آخر المحاضرات التي بدأت مشاهدتها.</p>
                </div>
            </div>

            @if ($latestActivities->isNotEmpty())
                <div class="activity-list">
                    @foreach ($latestActivities as $activity)
                        <article class="activity">
                            <div class="activity-mark">{{ $activity['progress']->completed ? '✓' : '▶' }}</div>
                            <div>
                                <h3>{{ $activity['lesson']->title }}</h3>
                                <p>{{ $activity['course']->title }} · {{ $activity['progress']->completed ? 'مكتملة' : $activity['progress']->progress . '% من المحاضرة' }}</p>
                            </div>
                            <time datetime="{{ $activity['progress']->updated_at?->toISOString() }}">
                                {{ $activity['progress']->updated_at?->diffForHumans() }}
                            </time>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty">
                    <strong>مفيش نشاط لسه</strong>
                    <p>أول ما تبدأ مشاهدة محاضرة هتلاقي نشاطك ظاهر هنا.</p>
                </div>
            @endif
        </section>
        </div>
    </div>
</main>
<script>
    if (document.documentElement.classList.contains('motion-ready')) {
        const mdTargets = document.querySelectorAll('.welcome, .stat, .panel, .course-row, .notification-link, .continue-card, .page-head');
        mdTargets.forEach((el) => el.classList.add('md-reveal'));
        mdTargets.forEach((el) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(24px)';
            el.style.transition = 'opacity .7s cubic-bezier(.2,.7,.2,1), transform .7s cubic-bezier(.2,.7,.2,1)';
        });
        let mdBatch = 0;
        const mdObserver = new IntersectionObserver((entries, obs) => {
            entries.filter((e) => e.isIntersecting).forEach((entry) => {
                entry.target.style.transitionDelay = `${Math.min((mdBatch++ % 6) * 70, 350)}ms`;
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'none';
                obs.unobserve(entry.target);
            });
        }, { threshold: .1, rootMargin: '0px 0px -30px 0px' });
        mdTargets.forEach((el) => mdObserver.observe(el));
    }

    const notificationsMore = document.getElementById('notifications-more');
    if (notificationsMore) {
        const hiddenNotifications = Array.from(
            document.querySelectorAll('.notification-link[hidden]')
        );
        const batchSize = 4;

        notificationsMore.addEventListener('click', () => {
            hiddenNotifications
                .filter((notification) => notification.hidden)
                .slice(0, batchSize)
                .forEach((notification) => {
                    notification.hidden = false;
                });

            if (hiddenNotifications.every((notification) => !notification.hidden)) {
                notificationsMore.remove();
            }
        });
    }

    const themeToggle = document.getElementById('theme-toggle');
    const themeStatus = document.getElementById('theme-status');
    const updateThemeToggleLabel = () => {
        const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
        const label = nextTheme === 'light' ? 'تبديل إلى الوضع الفاتح' : 'تبديل إلى الوضع الداكن';
        themeToggle.textContent = label;
        themeToggle.setAttribute('aria-label', label);
    };

    updateThemeToggleLabel();
    themeToggle.addEventListener('click', async () => {
        const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
        document.documentElement.dataset.theme = nextTheme;
        updateThemeToggleLabel();
        themeStatus.textContent = '';
        const savedLocally = window.madarThemePreference.save(nextTheme, true);

        try {
            const response = await fetch(@json(route('appearance.update')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ theme: nextTheme }),
            });

            if (!response.ok) {
                throw new Error(`Theme preference save failed with status ${response.status}.`);
            }

            window.madarThemePreference.markSynced();
            themeStatus.textContent = 'تم حفظ الوضع المفضل في حسابك.';
        } catch (error) {
            themeStatus.textContent = savedLocally
                ? 'تعذر حفظ اختيارك في الحساب، وتم الاحتفاظ به على هذا الجهاز.'
                : 'تعذر حفظ اختيارك. حاول مرة أخرى.';
            console.error('Could not save the theme preference.', error);
        }
    });
</script>
</body>
</html>
