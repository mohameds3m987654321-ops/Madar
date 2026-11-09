<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة المدير | مدار</title>
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-search.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <script>
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ready');
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: radial-gradient(ellipse 900px 480px at 88% -6%, var(--brand-soft), transparent 62%), var(--bg); color: var(--ink); font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif; line-height: 1.65; }
        @media (prefers-reduced-motion: reduce) { body { background: var(--bg); } }
        a { color: inherit; }
        :focus-visible { outline: 3px solid var(--brand); outline-offset: 3px; }
        main { width: min(1160px, calc(100% - 36px)); margin: 32px auto 56px; }
        .welcome { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 22px; padding: 26px 30px; border-radius: 20px; background: var(--green-hero); color: #fff; }
        .welcome::before { content: ""; position: absolute; width: 240px; aspect-ratio: 1; left: 12%; top: -140%; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; box-shadow: 0 0 0 34px rgba(255,255,255,.045), 0 0 0 70px rgba(255,255,255,.03); }
        .welcome p { margin: 3px 0 0; color: rgba(255,255,255,.8); }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 0; font-size: clamp(1.65rem, 4vw, 2.2rem); }
        .welcome-actions { display: flex; flex-wrap: wrap; gap: 9px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 8px 15px; border: 1px solid transparent; border-radius: 10px; background: #fff; color: #0d5b4d; font: inherit; font-weight: 700; text-decoration: none; transition: transform .2s var(--motion-ease), box-shadow .2s; }
        .button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, .16); }
        .button.secondary { border-color: rgba(255,255,255,.5); background: transparent; color: #fff; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        .stat, .panel { border: 1px solid var(--line); border-radius: 16px; background: var(--surface); box-shadow: var(--shadow); }
        .stat { padding: 18px 20px; transition: transform .28s var(--motion-ease), box-shadow .28s; }
        .stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-up); }
        .stat strong { color: var(--brand); }
        .stat span { display: block; color: var(--muted); font-size: .88rem; }
        .stat strong { display: block; margin-top: 4px; font-size: 1.8rem; }
        .stat small { color: var(--muted); font-size: .82rem; }
        .panels { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start; }
        .panel { overflow: hidden; transition: transform .28s var(--motion-ease), box-shadow .28s; }
        .panel:hover { transform: translateY(-3px); box-shadow: var(--shadow-up); }
        .list-row { transition: background .2s, padding .25s var(--motion-ease); }
        .list-row:hover { background: var(--surface-soft); }
        .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 17px 20px; border-bottom: 1px solid var(--line); }
        .panel-head h2 { margin: 0; font-size: 1.05rem; }
        .panel-head a { color: var(--brand); font-size: .87rem; font-weight: 700; text-decoration: none; }
        .list { display: grid; }
        .list-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 20px; border-bottom: 1px solid var(--line); }
        .list-row:last-child { border-bottom: 0; }
        .primary-text { overflow-wrap: anywhere; font-weight: 700; }
        .secondary-text { color: var(--muted); font-size: .82rem; }
        .tag { flex: none; padding: 3px 9px; border-radius: 999px; background: var(--brand-soft); color: var(--brand); font-size: .78rem; font-weight: 700; }
        .tag.teacher { background: var(--blue-soft); color: var(--blue); }
        .tag.admin { background: var(--amber-soft); color: var(--amber); }
        .tag.draft { background: var(--amber-soft); color: var(--amber); }
        .empty { padding: 28px 20px; color: var(--muted); text-align: center; }
        @media (max-width: 850px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .panels { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            main { width: calc(100% - 24px); margin-top: 20px; }
            .welcome { align-items: flex-start; flex-direction: column; padding: 21px; }
            .stats { gap: 9px; }
            .stat { padding: 13px; }
            .stat strong { font-size: 1.5rem; }
            .list-row { padding: 12px 14px; }
        }
    </style>
    <script>
        if (document.documentElement.classList.contains('motion-ready')) {
            const mdTargets = document.querySelectorAll('.welcome, .stats, .panels, .panel');
            mdTargets.forEach((el) => el.classList.add('md-reveal'));
            let mdBatch = 0;
            const mdObserver = new IntersectionObserver((entries, obs) => {
                entries.filter((e) => e.isIntersecting).forEach((entry, i) => {
                    entry.target.style.transitionDelay = `${Math.min((mdBatch++ % 6) * 70, 350)}ms`;
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                });
            }, { threshold: .1, rootMargin: '0px 0px -30px 0px' });
            mdTargets.forEach((el) => mdObserver.observe(el));
        }
    </script>
    @include('components.appearance')
</head>
<body class="admin-management">
    @include('components.management-navigation')
    <main>
        <header class="welcome md-reveal">
            <div>
                <h1>أهلًا {{ auth()->user()->name }}</h1>
                <p>من هنا تتابع المنصة وتصل بسرعة لأهم مهام الإدارة.</p>
            </div>
            <div class="welcome-actions">
                <a class="button" href="{{ route('users.index') }}">إدارة المستخدمين</a>
                <a class="button secondary" href="{{ route('refunds.index') }}">طلبات الاسترداد</a>
                <a class="button secondary" href="{{ route('admin.support-inquiries.index') }}">استفسارات الدعم</a>
                <a class="button secondary" href="{{ route('courses.index') }}">استعراض الكورسات</a>
            </div>
        </header>

        @include('components.global-search')

        <section class="stats" aria-label="ملخص المنصة">
            <article class="stat"><span>كل المستخدمين</span><strong>{{ number_format($stats['users']) }}</strong><small>{{ $stats['students'] }} طالب · {{ $stats['teachers'] }} مدرس</small></article>
            <article class="stat"><span>الكورسات المنشورة</span><strong>{{ number_format($stats['published_courses']) }}</strong><small>{{ $stats['draft_courses'] }} مسودة</small></article>
            <article class="stat"><span>الاشتراكات المدفوعة</span><strong>{{ number_format($stats['paid_enrollments']) }}</strong><small>إجمالي اشتراكات المنصة</small></article>
            <article class="stat"><span>بانتظار الدفع</span><strong>{{ number_format($stats['pending_enrollments']) }}</strong><small>اشتراكات لم يكتمل دفعها</small></article>
        </section>

        <div class="panels">
            <section class="panel">
                <header class="panel-head"><h2>أحدث المستخدمين</h2><a href="{{ route('users.index') }}">كل المستخدمين</a></header>
                <div class="list">
                    @forelse ($latestUsers as $user)
                        <div class="list-row">
                            <div><div class="primary-text">{{ $user->name }}</div><div class="secondary-text">{{ $user->email }}</div></div>
                            <span class="tag {{ $user->role }}">{{ ['student' => 'طالب', 'teacher' => 'مدرس', 'admin' => 'مدير'][$user->role] ?? $user->role }}</span>
                        </div>
                    @empty
                        <div class="empty">لا يوجد مستخدمون حتى الآن.</div>
                    @endforelse
                </div>
            </section>
            <section class="panel">
                <header class="panel-head"><h2>أحدث الكورسات</h2><a href="{{ route('courses.index') }}">استعراض الكورسات</a></header>
                <div class="list">
                    @forelse ($latestCourses as $course)
                        <div class="list-row">
                            <div><div class="primary-text">{{ $course->title }}</div><div class="secondary-text">{{ $course->teacher?->name ?? 'مدرس غير متاح' }}</div></div>
                            <span class="tag {{ $course->status === 'draft' ? 'draft' : '' }}">{{ $course->status === 'published' ? 'منشور' : 'مسودة' }}</span>
                        </div>
                    @empty
                        <div class="empty">لا توجد كورسات حتى الآن.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</body>
</html>
