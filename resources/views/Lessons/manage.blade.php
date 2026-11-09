<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المحاضرات | {{ $course->title }}</title>
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; --bg:#0c1513; --surface:#13201d; --ink:#e7f0ed; --muted:#8aa29b; --line:#243732; --accent:#3cc3a6; --soft:#16352f; --brand:#0f8a73; --danger:#ff9288; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:var(--bg); color:var(--ink); font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif; line-height:1.7; }
        main { width:min(900px,calc(100% - 28px)); margin:0 auto; padding:30px 0 50px; }
        a { color:inherit; text-decoration:none; }
        :focus-visible { outline:3px solid var(--accent); outline-offset:3px; }
        .breadcrumb { color:var(--muted); font-size:.88rem; margin-bottom:8px; }
        .breadcrumb a { color:var(--accent); }
        header,.row,.row-actions,.actions { display:flex; align-items:center; justify-content:space-between; gap:14px; }
        header { margin-bottom:20px; }
        h1,h2,h3,p { margin-top:0; }
        h1 { margin-bottom:3px; font-size:clamp(1.5rem,4vw,2rem); }
        header p { margin-bottom:0; color:var(--muted); }
        .button { display:inline-flex; min-height:42px; align-items:center; justify-content:center; padding:8px 14px; border:1px solid var(--line); border-radius:10px; background:transparent; color:var(--ink); font:inherit; font-weight:600; cursor:pointer; }
        .button.primary { border-color:transparent; background:var(--brand); color:#fff; }
        .button:hover { background:var(--soft); }
        .button.primary:hover { background:#0b7360; }
        .notice { margin-bottom:14px; padding:11px 14px; border:1px solid #26594d; border-radius:10px; background:var(--soft); color:var(--accent); }
        .card { padding:18px; border:1px solid var(--line); border-radius:14px; background:var(--surface); }
        .row { justify-content:flex-start; padding:14px 0; border-bottom:1px solid var(--line); }
        .row:last-child { border-bottom:0; padding-bottom:0; }
        .number { width:38px; height:38px; flex:none; display:grid; place-items:center; border-radius:10px; background:var(--soft); color:var(--accent); font-weight:700; }
        .info { min-width:0; flex:1; }
        .info h2 { margin:0; overflow-wrap:anywhere; font-size:1rem; }
        .info p { margin:3px 0 0; overflow:hidden; color:var(--muted); font-size:.82rem; text-overflow:ellipsis; white-space:nowrap; }
        .row-actions { flex:none; }
        .row-actions form { margin:0; }
        .delete { border-color:#633b39; color:var(--danger); background:transparent; }
        .empty { padding:38px 20px; border:1px dashed #26594d; border-radius:14px; background:var(--surface); color:var(--muted); text-align:center; }
        .empty strong { display:block; color:var(--ink); margin-bottom:5px; }
        @media(max-width:620px) {
            main { padding-top:20px; }
            header { align-items:flex-start; flex-direction:column; }
            .row { align-items:flex-start; flex-wrap:wrap; }
            .row-actions { width:100%; justify-content:flex-end; }
        }
    </style>
    @include('components.appearance')
</head>
<body class="teacher-management">
@include('components.management-navigation')
<main>
    <div class="breadcrumb">
        <a href="{{ route('teacher.dashboard') }}">لوحة المدرس</a> /
        <a href="{{ route('courses.edit', $course) }}">{{ $course->title }}</a> /
        إدارة المحاضرات
    </div>
    <header>
        <div>
            <h1>محاضرات {{ $course->title }}</h1>
            <p>{{ $course->lessons->count() }} محاضرة · يمكنك تعديل المحتوى وترتيب العرض.</p>
        </div>
        <a class="button primary" href="{{ route('lessons.create-for-course', $course) }}">إضافة محاضرة</a>
    </header>

    @if (session('success'))
        <div class="notice" role="status">{{ session('success') }}</div>
    @endif

    @if ($course->lessons->isNotEmpty())
        <section class="card" aria-label="قائمة المحاضرات">
            @foreach ($course->lessons as $lesson)
                <article class="row">
                    <span class="number">{{ $lesson->order }}</span>
                    <div class="info">
                        <h2>{{ $lesson->title }}</h2>
                        <p>
                            {{ $lesson->content ?: 'لا يوجد وصف للمحاضرة.' }} ·
                            @if ($lesson->isVideoProcessing())
                                الفيديو قيد التجهيز، حدّث الصفحة بعد قليل لمتابعة الحالة
                            @else
                                {{ $lesson->video ? 'يوجد فيديو' : 'بدون فيديو' }}
                            @endif
                        </p>
                    </div>
                    <div class="row-actions">
                        <a class="button" href="{{ route('lessons.edit', [$course, $lesson]) }}">تعديل</a>
                        <form method="POST" action="{{ route('lessons.destroy', [$course, $lesson]) }}" onsubmit="return confirm('حذف المحاضرة؟ سيُحذف معها تقدم الطلاب المسجل لهذه المحاضرة.');">
                            @csrf
                            @method('DELETE')
                            <button class="button delete" type="submit">حذف</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="empty">
            <strong>لسه مفيش محاضرات في الكورس</strong>
            <p>أضف أول محاضرة وابدأ تجهيز محتوى الكورس.</p>
            <a class="button primary" href="{{ route('lessons.create-for-course', $course) }}">إضافة أول محاضرة</a>
        </section>
    @endif
</main>
</body>
</html>
