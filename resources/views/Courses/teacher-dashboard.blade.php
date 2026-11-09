<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المدرس | منصة مدار</title>
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-search.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <script>
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ready');
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background: radial-gradient(ellipse 900px 480px at 88% -6%, var(--brand-soft), transparent 62%), var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.65;
        }

        a { color: inherit; }
        :focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }

        main { width: min(1120px, calc(100% - 36px)); margin: 28px auto 60px; }

        .heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .eyebrow { color: var(--brand); font-size: .84rem; font-weight: 600; }
        h1 { margin: 2px 0 0; font-size: clamp(1.65rem, 4vw, 2.15rem); }
        .heading p { margin: 4px 0 0; color: var(--muted); }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .summary-card {
            padding: 16px 19px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            transition: transform .28s var(--motion-ease), box-shadow .28s;
        }
        .summary-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-up); }

        .summary-card span { display: block; color: var(--muted); font-size: .85rem; }
        .summary-card strong { display: block; margin-top: 4px; color: var(--brand); font-size: 1.5rem; }

        .course-list { display: grid; gap: 19px; }

        .course-card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: var(--surface);
            transition: transform .28s var(--motion-ease), box-shadow .28s, border-color .28s;
        }
        .course-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-up); border-color: var(--brand-line); }

        .course-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 21px;
            border-bottom: 1px solid var(--line);
        }

        .course-heading h2 { margin: 0; font-size: 1.12rem; }
        .course-meta { display: flex; flex-wrap: wrap; gap: 7px 15px; margin-top: 5px; color: var(--muted); font-size: .83rem; }

        .course-link {
            flex: 0 0 auto;
            padding: 7px 12px;
            border: 1px solid var(--brand-line);
            border-radius: 9px;
            background: var(--brand-soft);
            color: var(--brand);
            font-size: .83rem;
            text-decoration: none;
            transition: background .2s, transform .2s var(--motion-ease);
        }

        .course-link:hover { background: var(--surface-raised); transform: translateY(-1px); }
        .course-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 12px 21px;
            border-bottom: 1px solid var(--line);
        }
        .course-actions a, .course-actions button {
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--surface-raised);
            color: var(--ink);
            font: inherit;
            font-size: .82rem;
            text-decoration: none;
            cursor: pointer;
        }
        .course-actions a:hover, .course-actions button:hover { border-color: var(--brand); color: var(--brand); transform: translateY(-1px); }
        .course-actions .publish-action { border-color: var(--brand-line); background: var(--brand); color: #fff; }
        .course-actions .publish-action:hover { background: var(--brand-dark); color: #fff; }
        .course-status {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            font-size: .76rem;
        }
        .course-status.published { border-color: var(--brand-line); background: var(--brand-soft); color: var(--brand); }
        .course-status.draft { border-color: var(--gold); background: var(--gold-soft); color: var(--gold); }
        .enrollment-title { margin: 0; padding: 13px 21px 6px; color: var(--muted); font-size: .86rem; font-weight: 600; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: right; }
        th, td { padding: 12px 21px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        th { color: var(--muted); font-size: .78rem; font-weight: 500; white-space: nowrap; }
        td { font-size: .88rem; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr { transition: background .18s; }
        tbody tr:hover { background: var(--surface-soft); }

        .student-name { display: block; font-weight: 600; }
        .student-email { display: block; color: var(--muted); font-size: .78rem; direction: ltr; text-align: right; }

        .status {
            display: inline-flex;
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--accent);
            font-size: .77rem;
            white-space: nowrap;
        }

        .status.pending { background: var(--gold-soft); color: var(--gold); }
        .status.failed { background: var(--red-soft); color: var(--red); }

        .progress-cell { min-width: 145px; }
        .progress-value { display: flex; justify-content: space-between; gap: 9px; font-size: .8rem; }
        .progress-value span { color: var(--muted); }
        .track { height: 6px; margin-top: 6px; overflow: hidden; border-radius: 999px; background: #263833; }
        .fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, #24aa8c, #65d9bd); }

        .empty-list, .empty-page {
            padding: 23px;
            color: var(--muted);
            text-align: center;
        }

        .empty-page {
            padding: 54px 22px;
            border: 1px dashed var(--brand-line);
            border-radius: 17px;
            background: var(--surface);
        }

        .empty-page h2 { margin: 0; color: var(--ink); font-size: 1.2rem; }
        .empty-page p { margin: 7px 0 17px; }
        .create-link {
            display: inline-flex;
            padding: 9px 16px;
            border-radius: 10px;
            background: var(--brand);
            color: var(--paper);
            font-weight: 600;
            text-decoration: none;
            transition: transform .2s var(--motion-ease), box-shadow .2s;
        }
        .create-link:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13, 91, 77, .28); }
        .dashboard-panel {
            margin-bottom: 24px;
            padding: 18px 21px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
        }
        .dashboard-panel h2 { margin: 0 0 12px; font-size: 1.1rem; }
        .dashboard-panel p { margin: 0 0 12px; color: var(--muted); }
        .report-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .report-tools input, .report-tools button, .report-tools a {
            min-height: 38px;
            padding: 7px 11px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--surface-raised);
            color: var(--ink);
            font: inherit;
            text-decoration: none;
        }
        .report-tools button { background: var(--brand); color: #fff; cursor: pointer; }
        .report-tools a { color: var(--accent); }
        .report-list { display: grid; gap: 8px; margin: 16px 0 0; padding: 0; list-style: none; }
        .report-list li { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px 16px; padding-top: 8px; border-top: 1px solid var(--line); }
        .report-list span { color: var(--muted); }
        .notice { margin: 0 0 15px; padding: 10px 14px; border-radius: 9px; background: var(--brand-soft); color: var(--brand); }
        .notice.error { background: var(--red-soft); color: var(--red); }

        /* Reveal motion */
        .motion-ready .md-reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s var(--motion-ease), transform .7s var(--motion-ease);
        }
        .motion-ready .md-reveal.is-visible { opacity: 1; transform: none; }

        @media (max-width: 680px) {
            main { width: calc(100% - 26px); margin-top: 20px; }
            .heading { align-items: flex-start; flex-direction: column; }
            .summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
            .summary-card { padding: 13px; }
            .summary-card strong { font-size: 1.25rem; }
            .course-heading { align-items: flex-start; padding: 15px; }
            .course-link { padding: 6px 9px; font-size: .78rem; }
            th, td { padding: 10px 13px; }
            .course-card table { min-width: 610px; }
        }

        @media (max-width: 390px) {
            .summary { grid-template-columns: 1fr; }
        }
        @media (min-width: 681px) and (max-width: 920px) {
            .summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
    </style>
    <script>
        if (document.documentElement.classList.contains('motion-ready')) {
            const mdTargets = document.querySelectorAll('.heading, .summary-card, .dashboard-panel, .course-card, .empty-page');
            mdTargets.forEach((el) => el.classList.add('md-reveal'));
            let mdBatch = 0;
            const mdObserver = new IntersectionObserver((entries, obs) => {
                entries.filter((e) => e.isIntersecting).forEach((entry) => {
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
<body class="teacher-management">
    @include('components.management-navigation')
    <main>
        <header class="heading md-reveal">
            <div>
                <span class="eyebrow">مساحة المدرس</span>
                <h1>لوحة المدرس</h1>
                <p>أهلًا {{ auth()->user()->name }}، أدر كورساتك وتابع طلابك من مكان واحد.</p>
            </div>
            <a class="create-link" href="{{ route('courses.create') }}">+ إضافة كورس جديد</a>
        </header>

        @include('components.global-search')

        @if (session('success'))
            <p class="notice" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="notice error" role="alert">{{ $errors->first() }}</p>
        @endif

        <section class="summary" aria-label="ملخص التدريس">
            <div class="summary-card">
                <span>كورساتي</span>
                <strong>{{ $summary['courses'] }}</strong>
            </div>
            <div class="summary-card">
                <span>الكورسات المنشورة</span>
                <strong>{{ $summary['published'] }}</strong>
            </div>
            <div class="summary-card">
                <span>المسودات</span>
                <strong>{{ $summary['drafts'] }}</strong>
            </div>
            <div class="summary-card">
                <span>الطلاب المشتركون</span>
                <strong>{{ $summary['students'] }}</strong>
            </div>
            <div class="summary-card">
                <span>اشتراكات بانتظار الدفع</span>
                <strong>{{ $summary['pending'] }}</strong>
            </div>
            <div class="summary-card">
                <span>إجمالي الإيرادات المؤكدة</span>
                <strong>{{ number_format($summary['earnings_total_cents'] / 100, 2) }} ج.م</strong>
            </div>
            <div class="summary-card">
                <span>إيرادات هذا الشهر</span>
                <strong>{{ number_format($summary['earnings_month_cents'] / 100, 2) }} ج.م</strong>
            </div>
        </section>

        <section class="dashboard-panel" aria-labelledby="reports-heading">
            <h2 id="reports-heading">التقارير الشهرية والتصدير</h2>
            <p>التقارير تُجهز في الخلفية، ويمكنك متابعة حالتها هنا بعد إضافتها إلى قائمة الانتظار.</p>
            <div class="report-tools">
                <form class="report-tools" method="POST" action="{{ route('teacher.reports.generate') }}">
                    @csrf
                    <label for="report-month">الشهر</label>
                    <input id="report-month" type="month" name="month" max="{{ now()->format('Y-m') }}"
                           value="{{ old('month', now()->format('Y-m')) }}" required>
                    <button type="submit">إنشاء التقرير</button>
                </form>
                <a href="{{ route('teacher.exports.students') }}">تصدير الطلاب Excel</a>
                <a href="{{ route('teacher.exports.payments') }}">تصدير المدفوعات Excel</a>
            </div>
            @if ($reports->isNotEmpty())
                <ul class="report-list" aria-label="التقارير الشهرية السابقة">
                    @foreach ($reports as $report)
                        <li>
                            <strong>{{ sprintf('%04d-%02d', $report->year, $report->month) }}</strong>
                            @if ($report->status === 'completed')
                                <span>
                                    {{ number_format(($report->data['earnings_cents'] ?? 0) / 100, 2) }} ج.م،
                                    {{ $report->data['payment_count'] ?? 0 }} مدفوعة،
                                    {{ $report->data['student_count'] ?? 0 }} طالبًا
                                </span>
                            @else
                                <span>قيد المعالجة</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="dashboard-panel" aria-labelledby="lesson-stats-heading">
            <h2 id="lesson-stats-heading">الدروس الأكثر تركًا ونسب الإكمال</h2>
            @if ($lessonStats->isNotEmpty())
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">الدرس / الكورس</th>
                                <th scope="col">طلاب بدأوا</th>
                                <th scope="col">تركوا الدرس</th>
                                <th scope="col">نسبة الترك</th>
                                <th scope="col">نسبة الإكمال</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lessonStats as $lessonStat)
                                <tr>
                                    <td>
                                        <strong>{{ $lessonStat->lesson_title }}</strong>
                                        <span class="student-email">{{ $lessonStat->course_title }}</span>
                                    </td>
                                    <td>{{ $lessonStat->started_students }}</td>
                                    <td>{{ $lessonStat->abandoned_students }}</td>
                                    <td>{{ $lessonStat->abandonment_percentage }}%</td>
                                    <td>{{ $lessonStat->completion_percentage }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>ستظهر الإحصائيات بعد إضافة الدروس واشتراك الطلاب في الكورسات.</p>
            @endif
        </section>

        @if ($courses->isNotEmpty())
            <section class="course-list" aria-label="كورسات المدرس">
                @foreach ($courses as $course)
                    <article class="course-card">
                        <div class="course-heading">
                            <div>
                                <h2>{{ $course->title }}</h2>
                                <div class="course-meta">
                                    <span>{{ $course->lessons_count }} محاضرة</span>
                                    <span>{{ $course->enrollments->where('status', 'paid')->count() }} اشتراك نشط</span>
                                    <span class="course-status {{ $course->status === 'published' ? 'published' : 'draft' }}">
                                        {{ $course->status === 'published' ? 'منشور' : 'مسودة' }}
                                    </span>
                                </div>
                            </div>
                            <a class="course-link" href="{{ route('courses.show', $course->id) }}">صفحة الكورس</a>
                        </div>
                        <nav class="course-actions" aria-label="إجراءات {{ $course->title }}">
                            <a href="{{ route('courses.edit', $course->id) }}">تعديل التفاصيل</a>
                            <a href="{{ route('lessons.manage', $course) }}">إدارة الدروس</a>
                            <a href="{{ route('quizzes.edit', $course) }}">إدارة الاختبار</a>
                            <a href="{{ route('courses.community.index', $course) }}">مجموعة الطلاب</a>
                            @if ($course->status !== 'published')
                                <form method="POST" action="{{ route('courses.publish', $course->id) }}">
                                    @csrf
                                    <button class="publish-action" type="submit">نشر الكورس</button>
                                </form>
                            @endif
                        </nav>

                        @if ($course->enrollments->isNotEmpty())
                            <h3 class="enrollment-title">الطلاب والاشتراكات</h3>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th scope="col">الطالب</th>
                                            <th scope="col">حالة الاشتراك</th>
                                            <th scope="col">التقدم</th>
                                            <th scope="col">تاريخ الاشتراك</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($course->enrollments as $enrollment)
                                            @php
                                                $statusLabels = [
                                                    'paid' => 'مشترك',
                                                    'pending' => 'بانتظار الدفع',
                                                    'failed' => 'فشل الدفع',
                                                ];
                                                $statusLabel = $statusLabels[$enrollment->status] ?? $enrollment->status;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="student-name">{{ $enrollment->user->name }}</span>
                                                    <span class="student-email">{{ $enrollment->user->email }}</span>
                                                </td>
                                                <td>
                                                    <span class="status {{ $enrollment->status === 'pending' ? 'pending' : ($enrollment->status === 'failed' ? 'failed' : '') }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td class="progress-cell">
                                                    @if ($enrollment->status === 'paid' && $course->lessons_count > 0)
                                                        <div class="progress-value">
                                                            <span>{{ $enrollment->completed_lessons_count }} من {{ $course->lessons_count }} محاضرة</span>
                                                            <strong>{{ $enrollment->progress_percentage }}%</strong>
                                                        </div>
                                                        <div class="track" role="progressbar"
                                                             aria-valuenow="{{ $enrollment->progress_percentage }}"
                                                             aria-valuemin="0" aria-valuemax="100"
                                                             aria-label="تقدم {{ $enrollment->user->name }} في {{ $course->title }}">
                                                            <div class="fill" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                                        </div>
                                                    @elseif ($enrollment->status === 'paid')
                                                        <span class="student-email">لا توجد محاضرات بعد</span>
                                                    @else
                                                        <span class="student-email">يظهر بعد تأكيد الاشتراك</span>
                                                    @endif
                                                </td>
                                                <td>{{ $enrollment->created_at?->format('Y/m/d') ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-list">لا يوجد طلاب مشتركين في هذا الكورس حتى الآن.</div>
                        @endif
                    </article>
                @endforeach
            </section>
        @else
            <section class="empty-page">
                <h2>لسه ما أضفتش أي كورس</h2>
                <p>أنشئ أول كورس لك لمتابعة الطلاب والاشتراكات من هنا.</p>
                <a class="create-link" href="{{ route('courses.create') }}">إضافة كورس</a>
            </section>
        @endif
    </main>
</body>
</html>
