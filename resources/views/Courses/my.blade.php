<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كورساتي | منصة مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root[data-theme="light"], :root:not([data-theme="dark"]) {
            color-scheme: light;
            /* ثيم فاتح موحّد */
            --bg: #f4f7f5;
            --surface: #fff;
            --tint: #edf6f3;
            --ink: #172b28;
            --ink-2: #435a54;
            --muted: #657a75;
            --line: #e1eae5;
            --brand: #147866;
            --brand-dark: #0e564a;
            --accent: #147866;
            --brand-soft: #e2f3ed;
            --brand-line: #a9d6c8;
            --shadow: 0 1px 2px rgba(23, 37, 34, .05), 0 10px 26px rgba(23, 37, 34, .08);
            --shadow-hover: 0 4px 8px rgba(23, 37, 34, .07), 0 20px 44px rgba(23, 37, 34, .13);
        }

        :root[data-theme="dark"] {
            color-scheme: dark;
            --bg: #101a17;
            --surface: #182521;
            --tint: #20332d;
            --ink: #e4efea;
            --ink-2: #b8cac4;
            --muted: #a3b7ae;
            --line: #2b4038;
            --brand: #69d2ae;
            --brand-dark: #9be4c9;
            --accent: #69d2ae;
            --brand-soft: #203a32;
            --brand-line: #356052;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 8px 22px rgba(0, 0, 0, .35);
            --shadow-hover: 0 2px 4px rgba(0, 0, 0, .5), 0 14px 30px rgba(0, 0, 0, .5);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.7;
        }

        a { color: inherit; }
        :focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; border-radius: 8px; }

        /* ---------- الهيدر ---------- */
        /* ---------- الصفحة ---------- */
        main { width: min(1120px, calc(100% - 40px)); margin: 22px auto 48px; }

        .intro {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        h1 { margin: 0; font-size: clamp(1.5rem, 3.5vw, 2rem); line-height: 1.4; }
        .intro p { margin: 2px 0 0; color: var(--muted); }

        .count {
            flex: none;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--brand-soft);
            border: 1px solid var(--brand-line);
            color: var(--accent);
            font-size: .88rem;
            font-weight: 600;
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }

        /* ---------- الكارت ---------- */
        .card {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: var(--shadow);
            animation: md-rise .55s var(--motion-ease) both;
            animation-delay: calc(var(--i, 0) * 60ms);
            transition: transform .25s var(--motion-ease), box-shadow .25s, border-color .25s;
        }
        @keyframes md-rise { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
        .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); border-color: var(--brand-line); }

        .cover {
            position: relative;
            height: 150px;
            overflow: hidden;
            background: var(--brand-soft);
        }

        .cover img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .cover-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: rgba(255, 255, 255, .85);
            font-size: 3rem;
            font-weight: 700;
        }

        .cover-status {
            position: absolute;
            top: 12px;
            inset-inline-start: 12px;
            padding: 3px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .92);
            border: 1px solid var(--brand-line);
            color: var(--brand);
            font-size: .78rem;
            font-weight: 600;
        }
        .cover-status.done { background: var(--brand); border-color: var(--brand); color: var(--paper, #fff); }

        .card-content { display: flex; flex-direction: column; flex: 1; padding: 16px 18px 18px; }

        .card h2 {
            margin: 0;
            font-size: 1.08rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .teacher { margin: 2px 0 12px; color: var(--muted); font-size: .86rem; }

        .meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-bottom: 14px;
            color: var(--ink-2);
            font-size: .83rem;
        }
        .meta span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .progress-label { display: flex; justify-content: space-between; gap: 10px; font-size: .84rem; }
        .progress-label span { color: var(--muted); }
        .progress-label strong { color: var(--accent); }

        .track { height: 8px; margin-top: 8px; margin-bottom: 16px; overflow: hidden; border-radius: 99px; background: var(--track); }
        .fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--brand), var(--accent)); transition: width 1s var(--motion-ease); }

        .action {
            width: 100%;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
            padding: 9px 15px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: var(--brand);
            color: var(--paper, #fff);
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .18s, transform .2s var(--motion-ease), box-shadow .2s;
        }
        .action:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13, 91, 77, .2); }
        .action.secondary { background: var(--brand-soft); color: var(--brand); border-color: var(--brand-line); }
        .action.secondary:hover { background: var(--tint); }

        /* ---------- لا توجد كورسات ---------- */
        .empty {
            padding: 48px 24px;
            border: 1px dashed var(--brand-line);
            border-radius: 16px;
            background: var(--surface);
            text-align: center;
        }

        .empty-mark {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            margin: 0 auto 12px;
            border-radius: 16px;
            background: var(--brand-soft);
            color: var(--accent);
            font-size: 1.4rem;
        }

        .empty h2 { margin: 0; font-size: 1.2rem; }
        .empty p { margin: 6px 0 18px; color: var(--muted); }
        .empty .action { width: auto; min-width: 180px; }

        @media (max-width: 560px) {
            main { width: calc(100% - 28px); margin-top: 16px; }
            .intro { align-items: flex-start; flex-direction: column; gap: 10px; }
            .grid { grid-template-columns: 1fr; gap: 14px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; }
            .card:hover { transform: none; }
        }
    </style>
    @include('components.appearance')
</head>
<body>

    <main>
        <div class="intro">
            <div>
                <h1>كورساتي</h1>
                <p>تابع تقدمك وكمّل التعلم من حيث توقفت.</p>
            </div>
            <span class="count">{{ $courses->count() }} كورس</span>
        </div>

        @if ($courses->isNotEmpty())
            <section class="grid" aria-label="الكورسات المشترك بها">
                @foreach ($courses as $item)
                    @php($course = $item['course'])
                    <article class="card" style="--i: {{ $loop->index }}">
                        <div class="cover">
                            @if ($course->image)
                                <img src="{{ asset('storage/' . $course->image) }}" alt="{{ $course->title }}" loading="lazy">
                            @else
                                <div class="cover-placeholder" aria-hidden="true">{{ mb_substr($course->title, 0, 1) }}</div>
                            @endif
                            <span class="cover-status {{ $item['isComplete'] ? 'done' : '' }}">{{ $item['isComplete'] ? 'مكتمل' : 'مشترك' }}</span>
                        </div>
                        <div class="card-content">
                            <h2>{{ $course->title }}</h2>
                            <p class="teacher">المدرس: {{ optional($course->teacher)->name ?? 'غير محدد' }}</p>
                            <div class="meta">
                                <span>{{ $course->lessons->count() }} محاضرة</span>
                                @if ($item['isComplete'])
                                    <span>أكملت جميع المحاضرات</span>
                                @elseif ($item['resumeLesson'])
                                    <span title="{{ $item['resumeLesson']->title }}">التالي: {{ $item['resumeLesson']->title }}</span>
                                @else
                                    <span>لا توجد محاضرات بعد</span>
                                @endif
                            </div>
                            <div class="progress-label">
                                <span>نسبة التقدم</span>
                                <strong>{{ $item['progress'] }}%</strong>
                            </div>
                            <div class="track" role="progressbar" aria-valuenow="{{ $item['progress'] }}"
                                 aria-valuemin="0" aria-valuemax="100" aria-label="نسبة التقدم في {{ $course->title }}">
                                <div class="fill" style="width: {{ $item['progress'] }}%"></div>
                            </div>

                            @if ($item['isComplete'])
                                <a class="action secondary" href="{{ route('courses.show', $course->id) }}">مراجعة الكورس</a>
                            @elseif ($item['resumeLesson'])
                                <a class="action" href="{{ route('lessons.show', $item['resumeLesson']->id) }}">
                                    {{ $item['progress'] > 0 ? 'كمّل التعلّم' : 'ابدأ التعلّم' }}
                                </a>
                            @else
                                <a class="action secondary" href="{{ route('courses.show', $course->id) }}">عرض تفاصيل الكورس</a>
                            @endif
                            <a class="action secondary" href="{{ route('courses.community.index', $course) }}">مجموعة الكورس</a>
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <section class="empty">
                <div class="empty-mark" aria-hidden="true">▤</div>
                <h2>لسه ما اشتركتش في أي كورس</h2>
                <p>استخدم زر «تصفح الكورسات» بالأعلى للاشتراك في كورس جديد.</p>
            </section>
        @endif
    </main>
</body>
</html>
