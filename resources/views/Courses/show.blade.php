<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $course->title }} | منصة مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root[data-theme="light"], :root:not([data-theme="dark"]) {
            color-scheme: light;
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
            --danger: #b3261e;
            --danger-bg: #fdecea;
            --radius: 16px;
            --radius-sm: 11px;
            --shadow: 0 1px 2px rgba(23, 37, 34, .05), 0 10px 26px rgba(23, 37, 34, .08);
            --hero-bg: linear-gradient(135deg, #0d5b4d, #18826c);
            --field-bg: #f7faf9;
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
            --danger: #ff9d91;
            --danger-bg: #3c211e;
            --radius: 16px;
            --radius-sm: 11px;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 8px 22px rgba(0, 0, 0, .35);
            --hero-bg: linear-gradient(135deg, #0a4a3f, #14665a);
            --field-bg: #13211d;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.75;
        }

        a { color: inherit; }
        button, input, select { font: inherit; }
        :focus-visible { outline: 3px solid #5fbfa9; outline-offset: 3px; border-radius: 8px; }

        .i { width: 1.15em; height: 1.15em; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

        /* ---------- الهيدر ---------- */
        .site-header {
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 max(20px, calc((100% - 1120px) / 2));
            background: var(--surface);
            border-bottom: 1px solid var(--line);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .brand-mark {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--brand);
            color: #fff;
        }

        .header-link { color: var(--muted); font-size: .92rem; text-decoration: none; }
        .header-link:hover { color: var(--brand); }

        /* ---------- الصفحة ---------- */
        .page { width: min(1120px, calc(100% - 40px)); margin: 16px auto 40px; }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--muted);
            font-size: .88rem;
        }
        .breadcrumb a { text-decoration: none; }
        .breadcrumb a:hover { color: var(--brand); }
        .breadcrumb-current { color: var(--ink-2); font-weight: 500; }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            align-items: start;
            gap: 20px;
        }
        .layout.single { grid-template-columns: minmax(0, 1fr); }
        .main-column { min-width: 0; display: grid; gap: 14px; }

        /* ---------- الغلاف (الصورة جنب العنوان) ---------- */
        .course-hero {
            display: flex;
            align-items: center;
            gap: 22px;
            padding: 18px 22px;
            border-radius: 18px;
            background: var(--hero-bg);
            color: var(--paper, #fff);
            box-shadow: var(--shadow);
        }
        .hero-thumb {
            flex: 0 0 210px;
            width: 210px;
            aspect-ratio: 16 / 10;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, .1);
            display: grid;
            place-items: center;
        }
        .hero-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-thumb .i { width: 38px; height: 38px; opacity: .6; }
        .hero-content { min-width: 0; flex: 1; }
        .course-hero h1 { margin: 0; font-size: clamp(1.3rem, 3vw, 1.8rem); line-height: 1.4; font-weight: 700; }

        .chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            font-size: .85rem;
        }

        /* ---------- الأقسام ---------- */
        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }
        .panel h2 { margin: 0; font-size: 1.15rem; font-weight: 700; }

        .course-description {
            margin: 10px 0 0;
            color: var(--ink-2);
            white-space: pre-line;
            max-width: 72ch;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .meta { display: flex; flex-wrap: wrap; gap: 10px 28px; margin: 14px 0 0; padding-top: 12px; border-top: 1px solid var(--line); }
        .meta div { display: flex; flex-direction: column; }
        .meta dt { color: var(--muted); font-size: .8rem; }
        .meta dd { margin: 0; font-weight: 600; font-size: .95rem; }

        .section-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .lesson-count { color: var(--muted); font-size: .85rem; white-space: nowrap; }

        .lesson-list { display: grid; gap: 8px; max-height: 320px; overflow-y: auto; padding-inline-end: 4px; }

        .lesson-row {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 54px;
            padding: 8px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            text-decoration: none;
            transition: border-color .15s, background .15s;
        }
        a.lesson-row:hover { border-color: var(--brand-line); background: var(--tint); }
        div.lesson-row { background: var(--tint); border-color: transparent; }
        .locked-lesson { cursor: pointer; }
        .locked-lesson:hover, .locked-lesson:focus-visible { border-color: var(--brand-line); background: var(--tint); }

        .lesson-number {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 600;
            font-size: .95rem;
        }
        div.lesson-row .lesson-number { background: var(--track); color: var(--muted); }

        .lesson-info { min-width: 0; flex: 1; }
        .lesson-title { display: block; font-weight: 600; overflow-wrap: anywhere; }
        div.lesson-row .lesson-title { color: var(--ink-2); }
        .lesson-subtitle { display: block; color: var(--muted); font-size: .8rem; }

        .lesson-state { display: inline-flex; align-items: center; gap: 6px; color: var(--brand); font-size: .85rem; font-weight: 500; white-space: nowrap; }
        .lesson-locked { color: var(--muted); font-weight: 400; }

        .empty-lessons { padding: 26px; border: 1px dashed var(--brand-line); border-radius: var(--radius-sm); color: var(--muted); text-align: center; }

        .rating-summary { display: flex; align-items: center; gap: 8px; color: var(--muted); }
        .rating-stars { color: #f5b942; letter-spacing: 2px; direction: ltr; white-space: nowrap; }
        .rating-form { display: grid; gap: 12px; margin: 16px 0 20px; }
        .rating-form label { display: block; color: var(--ink-2); font-weight: 600; }
        .rating-form legend { margin-bottom: 8px; color: var(--ink-2); font-weight: 600; }
        .rating-form textarea {
            width: 100%;
            min-height: 110px;
            resize: vertical;
            padding: 10px 12px;
            border: 1px solid var(--brand-line);
            border-radius: 10px;
            background: var(--field-bg);
            color: var(--ink);
        }
        .rating-choices { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; width: fit-content; }
        .rating-choices input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .rating-choices label { color: var(--muted); font-size: 1.8rem; line-height: 1; cursor: pointer; }
        .rating-choices label:hover, .rating-choices label:hover ~ label,
        .rating-choices input:checked ~ label { color: #f5b942; }
        .rating-choices input:focus-visible + label { outline: 2px solid var(--accent); outline-offset: 2px; }
        .review-list { display: grid; gap: 12px; margin-top: 16px; }
        .review-item { padding: 14px; border: 1px solid var(--line); border-radius: var(--radius-sm); }
        .review-heading { display: flex; justify-content: space-between; gap: 12px; }
        .review-date { color: var(--muted); font-size: .82rem; }
        .review-item p { margin: 8px 0 0; color: var(--ink-2); white-space: pre-line; overflow-wrap: anywhere; }
        .reviews-empty, .review-hint { color: var(--muted); }

        /* ---------- الشريط الجانبي ---------- */
        .sidebar { position: sticky; top: 14px; display: grid; gap: 14px; }

        .purchase-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }
        .purchase-card h2 { margin: 0; font-size: 1.05rem; font-weight: 700; }

        .price { margin: 6px 0 12px;         color: var(--accent); font-size: 1.9rem; font-weight: 700; line-height: 1.4; }
        .price small { color: var(--muted); font-size: .85rem; font-weight: 400; }

        .purchase-note { margin: 0 0 6px; color: var(--muted); font-size: .9rem; }

        .purchase-card label { display: block; margin: 12px 0 6px; color: var(--ink-2); font-size: .88rem; font-weight: 600; }
        .purchase-card input, .purchase-card select {
            width: 100%;
            min-height: 46px;
            padding: 9px 12px;
            border: 1px solid var(--brand-line);
            border-radius: 10px;
            background: var(--field-bg);
            color: var(--ink);
            transition: border-color .15s, box-shadow .15s;
        }
        .purchase-card input:focus, .purchase-card select:focus { outline: 0; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
        .purchase-card input::placeholder { color: var(--muted); }

        .button, .purchase-link {
            width: 100%;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
            padding: 10px 16px;
            border: 0;
            border-radius: var(--radius-sm);
            background: var(--brand);
            color: var(--paper, #fff);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .18s, transform .2s var(--motion-ease), box-shadow .2s;
        }
        .button:hover, .purchase-link:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13, 91, 77, .2); }

        .enrolled-state { display: flex; gap: 10px; padding: 13px 14px; border-radius: var(--radius-sm); background: var(--brand-soft); color: var(--brand); }
        .enrolled-state strong { display: block; font-size: .95rem; }
        .enrolled-state p { margin: 2px 0 0; font-size: .85rem; color: var(--ink-2); }

        .alert { margin: 0 0 16px; padding: 12px 14px; border-radius: 10px; font-size: .9rem; }
        .alert-success { background: var(--brand-soft); color: var(--accent); }
        .alert-error { background: var(--danger-bg); color: var(--danger); }
        .purchase-card .alert { margin: 14px 0 0; }
        .field-error { margin-top: 5px; color: var(--danger); font-size: .82rem; }

        .access-toast {
            position: fixed;
            z-index: 10;
            inset-inline: 20px;
            bottom: 20px;
            width: fit-content;
            max-width: min(440px, calc(100% - 40px));
            margin-inline: auto;
            padding: 12px 18px;
            border: 1px solid var(--brand-line);
            border-radius: 12px;
            background: var(--surface);
            color: var(--ink);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .progress-card { padding: 16px 20px; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); }
        .progress-heading { display: flex; justify-content: space-between; gap: 12px; font-size: .9rem; color: var(--muted); }
        .progress-heading strong { color: var(--brand); }
        .progress-track { height: 8px; margin-top: 10px; overflow: hidden; border-radius: 99px; background: var(--track); }
        .progress-fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--brand), var(--accent)); transition: width 1s var(--motion-ease); }

        /* ---------- Motion ---------- */
        .motion-ready .md-reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s var(--motion-ease), transform .7s var(--motion-ease);
        }
        .motion-ready .md-reveal.is-visible { opacity: 1; transform: none; }
        .panel, .purchase-card, .progress-card, .course-hero { transition: transform .28s var(--motion-ease), box-shadow .28s; }
        .panel:hover, .purchase-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-up); }
        .lesson-row { transition: border-color .18s, background .18s, transform .22s var(--motion-ease); }
        a.lesson-row:hover { transform: translateX(-4px); }

        /* ---------- الشاشات الصغيرة ---------- */
        @media (max-width: 960px) {
            .layout { grid-template-columns: minmax(0, 1fr) 300px; gap: 16px; }
            .hero-thumb { flex-basis: 170px; width: 170px; }
        }

        @media (max-width: 760px) {
            .site-header { height: 52px; }
            .page { width: min(100% - 28px, 600px); margin-top: 12px; }
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; grid-row: 1; }
            .main-column { grid-row: 2; }
            .panel, .purchase-card { padding: 18px; }

            .course-hero { flex-direction: column; align-items: stretch; padding: 14px; gap: 12px; }
            .hero-thumb { flex: none; width: 100%; aspect-ratio: 16 / 7; }
            .lesson-list { max-height: none; }
        }

        @media (max-width: 430px) {
            .header-link { font-size: .84rem; }
            .breadcrumb { font-size: .82rem; }
            .lesson-row { gap: 10px; padding: 8px 10px; }
            .lesson-state { font-size: .78rem; }
            .section-heading { flex-direction: column; align-items: flex-start; gap: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; }
        }

        /* Course overview refresh */
        .page { width: min(1240px, calc(100% - 48px)); margin-top: 24px; }
        .breadcrumb { margin-bottom: 18px; }
        .layout { grid-template-columns: minmax(0, 1fr) 350px; gap: 22px; }
        .course-hero {
            position: relative;
            min-height: 230px;
            margin-bottom: 22px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background:
                radial-gradient(ellipse at 2% 100%, color-mix(in srgb, var(--brand) 13%, transparent), transparent 46%),
                var(--surface);
            color: var(--ink);
            box-shadow: var(--shadow);
        }
        .hero-thumb {
            flex-basis: min(38%, 390px);
            width: min(38%, 390px);
            min-width: 260px;
            aspect-ratio: 16 / 9;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            box-shadow: 0 16px 34px color-mix(in srgb, var(--brand) 18%, transparent);
        }
        .hero-thumb .i { width: 52px; height: 52px; color: #fff; opacity: .8; }
        .hero-content { padding: 10px clamp(4px, 2vw, 22px); }
        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
            color: var(--brand);
            font-size: .86rem;
            font-weight: 700;
        }
        .hero-kicker::before {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand);
            content: "";
        }
        .course-hero h1 {
            max-width: 19ch;
            font-size: clamp(1.7rem, 3vw, 2.45rem);
            line-height: 1.3;
            letter-spacing: -.025em;
        }
        .course-hero .chips { gap: 9px; margin-top: 18px; }
        .course-hero .chip {
            min-height: 36px;
            padding: 6px 12px;
            border: 1px solid var(--line);
            background: var(--surface-soft, var(--surface));
            color: var(--ink-2);
            font-size: .84rem;
        }
        .course-hero .chip .i { color: var(--brand); }
        .course-hero .chip-rating { color: var(--ink); }
        .course-hero .chip-rating .rating-stars { font-size: .92rem; }
        .course-hero .chip-status {
            border-color: var(--brand-line);
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 700;
        }
        .main-column { gap: 18px; }
        .panel, .purchase-card, .progress-card { border-radius: 20px; }
        .panel { padding: 24px; }
        .section-heading { align-items: center; margin-bottom: 16px; }
        .section-heading h2, .panel h2 { font-size: 1.22rem; letter-spacing: -.01em; }
        .lesson-list { max-height: 420px; gap: 10px; }
        .lesson-row {
            min-height: 68px;
            padding: 10px 14px;
            border-radius: 14px;
            background: var(--surface-soft, var(--surface));
        }
        .lesson-number { width: 42px; height: 42px; flex-basis: 42px; border-radius: 13px; font-weight: 700; }
        .lesson-title { font-size: .98rem; }
        .lesson-state { padding: 7px 10px; border-radius: 10px; background: var(--brand-soft); font-size: .8rem; }
        .lesson-locked { background: var(--surface); }
        .sidebar { top: 20px; gap: 16px; }
        .purchase-card { padding: 24px; }
        .purchase-card h2 { font-size: 1.12rem; }
        .price { margin-block: 12px 16px; font-size: 2.25rem; letter-spacing: -.03em; }
        .purchase-note { line-height: 1.75; }
        .purchase-card label { margin-top: 15px; }
        .purchase-card .button, .purchase-card .purchase-link {
            min-height: 52px;
            border-radius: 14px;
            font-weight: 700;
        }
        .purchase-card .enrolled-state { border: 1px solid var(--brand-line); }
        .teacher-actions { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 18px; }
        .teacher-actions .purchase-link {
            width: auto;
            flex: 1 1 150px;
            min-height: 42px;
            margin: 0;
            padding: 8px 12px;
            border: 1px solid var(--brand-line);
            background: var(--brand-soft);
            color: var(--brand);
            font-size: .84rem;
        }
        .teacher-actions .purchase-link:hover { background: var(--tint); }
        .course-about .meta { gap: 14px; }
        .course-about .meta div {
            min-width: 145px;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface-soft, var(--surface));
        }
        .course-about .meta { border: 0; padding-top: 16px; }
        .course-about .course-description {
            display: block;
            max-width: 76ch;
            overflow: visible;
            -webkit-line-clamp: unset;
        }
        .review-item { border-radius: 14px; background: var(--surface-soft, var(--surface)); }
        .review-list { gap: 10px; }

        @media (max-width: 960px) {
            .layout { grid-template-columns: minmax(0, 1fr) 320px; }
            .hero-thumb { flex-basis: 36%; width: 36%; min-width: 230px; }
        }
        @media (max-width: 760px) {
            .page { width: min(100% - 28px, 640px); margin-top: 14px; }
            .layout { display: flex; flex-direction: column; gap: 16px; }
            .sidebar, .main-column { width: 100%; }
            .sidebar { order: 0; }
            .main-column { order: 1; }
            .course-hero { min-height: 0; flex-direction: column; align-items: stretch; margin-bottom: 16px; padding: 14px; gap: 12px; }
            .hero-thumb { flex: none; width: 100%; min-width: 0; aspect-ratio: 16 / 8; }
            .hero-content { padding: 8px 4px 4px; }
            .course-hero h1 { font-size: clamp(1.55rem, 7vw, 2rem); }
            .course-hero .chips { margin-top: 14px; }
            .panel, .purchase-card { padding: 19px; }
            .sidebar { position: static; }
            .lesson-list { max-height: none; }
        }
        @media (max-width: 430px) {
            .course-hero .chip { font-size: .78rem; }
            .lesson-row { align-items: flex-start; }
            .lesson-state { align-self: center; }
            .rating-summary { flex-wrap: wrap; gap: 4px 7px; }
        }
    </style>
    <script>
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ready');
        }
    </script>
    @include('components.appearance')
</head>
<body>
    {{-- أيقونات --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <symbol id="i-book" viewBox="0 0 24 24"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16"/></symbol>
        <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></symbol>
        <symbol id="i-play" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m10 8.5 5 3.5-5 3.5z"/></symbol>
        <symbol id="i-lock" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></symbol>
    </svg>

    <header class="site-header">
        <a class="brand" href="{{ route('courses.index') }}">
            <span class="brand-mark" aria-hidden="true">م</span>
            <span>منصة مدار</span>
        </a>
        <a class="header-link" href="{{ route('courses.index') }}">العودة إلى الكورسات</a>
    </header>

    @php $isStudent = auth()->user()->role === 'student'; @endphp

    <main class="page">
        <nav class="breadcrumb" aria-label="مسار التنقل">
            <a href="{{ route('courses.index') }}">الكورسات</a>
            <span aria-hidden="true">/</span>
            <span class="breadcrumb-current">{{ $course->title }}</span>
        </nav>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error" role="alert">{{ session('error') }}</div>
        @endif

        <section class="course-hero md-reveal" aria-labelledby="course-title">
            <div class="hero-thumb">
                @if ($course->image)
                    <img src="{{ asset('storage/' . $course->image) }}" alt="{{ $course->title }}">
                @else
                    <svg class="i" aria-hidden="true"><use href="#i-book"/></svg>
                @endif
            </div>
            <div class="hero-content">
                <span class="hero-kicker">منصة مدار التعليمية</span>
                <h1 id="course-title">{{ $course->title }}</h1>
                <div class="chips">
                    <span class="chip"><svg class="i"><use href="#i-book"/></svg>{{ $lessons->count() }} محاضرة</span>
                    <span class="chip"><svg class="i"><use href="#i-user"/></svg>{{ optional($course->teacher)->name ?? 'مدرس غير محدد' }}</span>
                    @if ($course->reviews_count > 0)
                        <span class="chip chip-rating">
                            <span class="rating-stars" aria-hidden="true">★</span>
                            {{ number_format($course->reviews_avg_rating ?? 0, 1) }}
                            <span>({{ $course->reviews_count }} تقييم)</span>
                        </span>
                    @else
                        <span class="chip chip-rating">كورس جديد</span>
                    @endif
                    <span class="chip chip-status">
                        @switch($course->status)
                            @case('published') متاح للتعلم @break
                            @case('draft') مسودة @break
                            @case('active') متاح للتعلم @break
                            @default {{ $course->status ?: 'حالة غير محددة' }}
                        @endswitch
                    </span>
                </div>
            </div>
        </section>

        <div class="layout {{ $isStudent ? '' : 'single' }}">
            <div class="main-column">
                <section class="panel course-about md-reveal" aria-labelledby="about-title">
                    <h2 id="about-title">عن الكورس</h2>
                    <p class="course-description">{{ $course->description ?: 'لا يوجد وصف لهذا الكورس حاليًا.' }}</p>
                    @if (auth()->user()->role === 'teacher' && $course->teacher_id === auth()->id())
                        <div class="teacher-actions">
                            <a class="purchase-link" href="{{ route('lessons.manage', $course) }}">إدارة المحاضرات</a>
                            <a class="purchase-link" href="{{ route('quizzes.edit', $course) }}">إعداد الاختبار النهائي</a>
                            <a class="purchase-link" href="{{ route('courses.community.index', $course) }}">مجموعة الطلاب</a>
                        </div>
                    @elseif ($isStudent && $enrollment?->status === 'paid')
                        <div class="teacher-actions">
                            <a class="purchase-link" href="{{ route('courses.community.index', $course) }}">مجموعة الكورس والتواصل</a>
                        </div>
                    @endif
                    <dl class="meta">
                        <div>
                            <dt>حالة الكورس</dt>
                            <dd>
                                @switch($course->status)
                                    @case('published') منشور @break
                                    @case('draft') مسودة @break
                                    @case('active') متاح @break
                                    @default {{ $course->status ?: 'غير محدد' }}
                                @endswitch
                            </dd>
                        </div>
                        <div>
                            <dt>تاريخ الإضافة</dt>
                            <dd>{{ $course->created_at?->format('Y/m/d') ?? 'غير محدد' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="panel course-lessons md-reveal" aria-labelledby="lessons-title">
                    <div class="section-heading">
                        <h2 id="lessons-title">محتوى الكورس</h2>
                        <span class="lesson-count">{{ $lessons->count() }} محاضرة</span>
                    </div>

                    @if ($lessons->isNotEmpty())
                        <div class="lesson-list">
                            @foreach ($lessons as $lesson)
                                @if ($isEnrolled)
                                    <a class="lesson-row" href="{{ route('lessons.show', $lesson->id) }}">
                                @else
                                    <div class="lesson-row locked-lesson" role="button" tabindex="0"
                                         data-access-notice="المحاضرة دي متاحة للمشتركين فقط. اشترك في الكورس علشان تقدر تشوفها.">
                                @endif
                                    <span class="lesson-number">{{ $loop->iteration }}</span>
                                    <span class="lesson-info">
                                        <span class="lesson-title">{{ $lesson->title }}</span>
                                        <span class="lesson-subtitle">المحاضرة {{ $lesson->order }}</span>
                                    </span>
                                    @if ($isEnrolled)
                                        <span class="lesson-state"><svg class="i"><use href="#i-play"/></svg>ابدأ المشاهدة</span>
                                    @else
                                        <span class="lesson-state lesson-locked"><svg class="i"><use href="#i-lock"/></svg>مغلقة</span>
                                    @endif
                                @if ($isEnrolled)
                                    </a>
                                @else
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="empty-lessons">لا توجد محاضرات في هذا الكورس حاليًا.</div>
                    @endif
                </section>

                <section class="panel course-reviews md-reveal" aria-labelledby="reviews-title">
                    <div class="section-heading">
                        <h2 id="reviews-title">تقييمات وآراء الطلاب</h2>
                        <span class="rating-summary">
                            <span class="rating-stars" aria-label="متوسط التقييم">
                                @for ($star = 1; $star <= 5; $star++)
                                    {{ $star <= round($course->reviews_avg_rating ?? 0) ? '★' : '☆' }}
                                @endfor
                            </span>
                            <strong>{{ number_format($course->reviews_avg_rating ?? 0, 1) }}</strong>
                            <span>({{ $course->reviews_count }} تقييم)</span>
                        </span>
                    </div>

                    @if ($canReview)
                        <form class="rating-form" method="POST" action="{{ route('courses.reviews.store', $course) }}">
                            @csrf
                            <fieldset style="border:0;padding:0;margin:0">
                                <legend>تقييمك من 1 إلى 5 نجوم</legend>
                                <div class="rating-choices" role="radiogroup" aria-label="تقييم الكورس">
                                    @for ($rating = 5; $rating >= 1; $rating--)
                                        <input id="rating-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}"
                                               @checked((string) old('rating', $myReview?->rating ?? '') === (string) $rating) required>
                                        <label for="rating-{{ $rating }}" title="{{ $rating }} نجوم" aria-label="{{ $rating }} نجوم">★</label>
                                    @endfor
                                </div>
                            </fieldset>
                            @error('rating')
                                <div class="field-error">{{ $message }}</div>
                            @enderror

                            <div>
                                <label for="review">اكتب رأيك في الكورس</label>
                                <textarea id="review" name="review" minlength="10" maxlength="2000" required
                                          placeholder="شارك تجربتك ليستفيد باقي الطلاب...">{{ old('review', $myReview?->review) }}</textarea>
                                @error('review')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                            <button class="button" type="submit">{{ $myReview ? 'تحديث تقييمي' : 'إرسال التقييم' }}</button>
                        </form>
                    @elseif ($isStudent)
                        @if ($enrollment?->status === 'paid')
                            <p class="review-hint">أكمل {{ $reviewThreshold }}% على الأقل من دروس الكورس لإضافة تقييمك (إنجازك الحالي {{ $completionPercentage }}%).</p>
                        @else
                            <p class="review-hint">اشترك في الكورس لتتمكن من إضافة تقييمك ورأيك.</p>
                        @endif
                    @endif

                    @if ($reviews->isNotEmpty())
                        <div class="review-list">
                            @foreach ($reviews as $review)
                                <article class="review-item">
                                    <div class="review-heading">
                                        <strong>{{ $review->user->name }}</strong>
                                        <span class="review-date">{{ $review->created_at->format('Y/m/d') }}</span>
                                    </div>
                                    <div class="rating-stars" aria-label="{{ $review->rating }} من 5 نجوم">
                                        @for ($star = 1; $star <= 5; $star++)
                                            {{ $star <= $review->rating ? '★' : '☆' }}
                                        @endfor
                                    </div>
                                    <p>{{ $review->review }}</p>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="reviews-empty">لا توجد تقييمات حتى الآن. كن أول من يشارك رأيه.</p>
                    @endif
                </section>
            </div>

            @if ($isStudent)
                <aside class="sidebar" aria-label="الاشتراك والتقدم">
                    <section class="purchase-card md-reveal" aria-labelledby="purchase-title">
                        <h2 id="purchase-title">
                            @if ($isEnrolled)
                                اشتراكك في الكورس
                            @else
                                اشترك وابدأ التعلم
                            @endif
                        </h2>

                        @if ((float) $course->price <= 0)
                            <p class="price">مجاني</p>
                        @else
                            <p class="price">{{ number_format($course->price, 2) }} <small>جنيه مصري</small></p>
                        @endif

                        @if ($isEnrolled)
                            <div class="enrolled-state" role="status">
                                <svg class="i" style="width:1.4em;height:1.4em;margin-top:3px"><use href="#i-check"/></svg>
                                <div>
                                    <strong>أنت مشترك في الكورس</strong>
                                    <p>يمكنك الآن مشاهدة جميع المحاضرات.</p>
                                </div>
                            </div>
                            @if ($lessons->isNotEmpty())
                                <a class="purchase-link" href="{{ route('lessons.show', $lessons->first()->id) }}">ابدأ التعلم</a>
                            @endif
                        @elseif ((float) $course->price <= 0)
                            <p class="purchase-note">سجّل في الكورس مجانًا للوصول إلى جميع المحاضرات.</p>
                            <form method="POST" action="{{ route('courses.enroll', $course->id) }}">
                                @csrf
                                <button class="button" type="submit">اشترك مجانًا</button>
                            </form>
                        @else
                            <p class="purchase-note">اختر وسيلة الدفع المناسبة وأكمل الاشتراك بأمان.</p>
                            <form method="POST" action="{{ route('courses.checkout', $course->id) }}">
                                @csrf
                                <label for="payment_method">طريقة الدفع</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="">اختر طريقة الدفع</option>
                                    <option value="vodafone_cash" @selected(old('payment_method') === 'vodafone_cash')>فودافون كاش</option>
                                    <option value="fawry" @selected(old('payment_method') === 'fawry')>فوري</option>
                                </select>
                                @error('payment_method')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror

                                <label for="phone_number">رقم الهاتف</label>
                                <input id="phone_number" name="phone_number" type="tel"
                                       value="{{ old('phone_number') }}" placeholder="مثال: 01xxxxxxxxx"
                                       required autocomplete="tel" inputmode="tel" dir="ltr">
                                @error('phone_number')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror

                                <label for="coupon_code">كود الخصم (اختياري)</label>
                                <input id="coupon_code" name="coupon_code" type="text" maxlength="48"
                                       value="{{ old('coupon_code') }}" autocomplete="off">
                                @error('coupon_code')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror

                                @error('payment')
                                    <div class="alert alert-error" role="alert">{{ $message }}</div>
                                @enderror
                                <button class="button" type="submit">تابع إلى الدفع</button>
                            </form>
                        @endif
                    </section>

                    <section class="progress-card md-reveal" aria-label="نسبة إنجاز الكورس">
                        <div class="progress-heading">
                            <span>إنجاز الكورس</span>
                            <strong>{{ $courseProgress }}%</strong>
                        </div>
                        <div class="progress-track" role="progressbar" aria-valuenow="{{ $courseProgress }}"
                             aria-valuemin="0" aria-valuemax="100" aria-label="نسبة إنجاز الكورس">
                            <div class="progress-fill" style="width: {{ $courseProgress }}%"></div>
                        </div>
                    </section>

                    @if ($isEnrolled && $allLessonsCompleted)
                        <section class="purchase-card" aria-label="إتمام الكورس">
                            <h2>أكملت جميع المحاضرات 🎉</h2>
                            @if ($quiz && $quiz->questions_count > 0)
                                <a class="purchase-link" href="{{ route('quizzes.show', $course) }}">ابدأ الاختبار النهائي</a>
                            @else
                                <p class="purchase-note">لا يوجد اختبار نهائي لهذا الكورس حاليًا.</p>
                            @endif
                            @if ($certificate)
                                <a class="purchase-link" href="{{ route('courses.certificate', $course) }}">عرض شهادة إتمام الكورس</a>
                            @endif
                        </section>
                    @endif
                </aside>
            @endif
        </div>
    </main>
    <div id="access-toast" class="access-toast" role="status" aria-live="polite"
         data-message="{{ session('access_notice') }}" hidden></div>
    <script>
        if (document.documentElement.classList.contains('motion-ready')) {
            const mdTargets = document.querySelectorAll('.md-reveal, .lesson-row, .review-item');
            mdTargets.forEach((el) => { if (!el.classList.contains('md-reveal')) el.classList.add('md-reveal'); });
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

        (() => {
            const toast = document.getElementById('access-toast');
            let timeout;

            const showNotice = (message) => {
                toast.textContent = message;
                toast.hidden = false;
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    toast.hidden = true;
                }, 4000);
            };

            document.querySelectorAll('[data-access-notice]').forEach((lesson) => {
                lesson.addEventListener('click', () => showNotice(lesson.dataset.accessNotice));
                lesson.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        showNotice(lesson.dataset.accessNotice);
                    }
                });
            });

            if (toast.dataset.message) {
                showNotice(toast.dataset.message);
            }
        })();
    </script>
</body>
</html>
