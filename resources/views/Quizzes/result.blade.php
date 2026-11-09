<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتيجة الاختبار | {{ $course->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:dark;--bg:#0c1513;--surface:#13201d;--ink:#e7f0ed;--muted:#8aa29b;--line:#243732;--accent:#3cc3a6;--soft:#16352f;--brand:#0f8a73;--danger:#ff9288}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.7}
        main{width:min(760px,calc(100% - 28px));margin:0 auto;padding:30px 0 50px}a{color:var(--accent);text-decoration:none}
        .card{padding:22px;border:1px solid var(--line);border-radius:15px;background:var(--surface)}h1{margin:0;font-size:1.7rem}
        .summary{text-align:center;margin-bottom:20px;padding:20px;border:1px solid var(--line);border-radius:13px;background:#101b19}
        .score{display:block;color:var(--accent);font-size:3rem;font-weight:700;line-height:1.2}.summary p{margin:8px 0 0;color:var(--muted)}
        .passed{color:var(--accent)!important}.failed{color:var(--danger)!important}
        .answer{padding:13px 0;border-bottom:1px solid var(--line)}.answer:last-child{border:0}.answer h2{margin:0 0 5px;font-size:1rem}
        .answer p{margin:3px 0;color:var(--muted);font-size:.9rem}.correct{color:var(--accent)!important}.wrong{color:var(--danger)!important}
        .actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:20px}.button{display:inline-flex;min-height:42px;align-items:center;justify-content:center;padding:8px 14px;border:1px solid var(--line);border-radius:9px;background:transparent;color:var(--ink);font:inherit;font-weight:600}
        .button.primary{border-color:transparent;background:var(--brand);color:#fff}
        @media(max-width:520px){main{padding-top:18px}.card{padding:15px}.actions{flex-direction:column}.button{width:100%}}
    </style>
    @include('components.appearance')
</head>
<body>
<main>
    <div style="margin-bottom:10px;color:var(--muted)"><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a> / نتيجة الاختبار</div>
    <section class="card">
        <div class="summary">
            <h1>{{ $quiz->title }}</h1>
            <strong class="score">{{ $attempt->score }}%</strong>
            <p class="{{ $attempt->passed ? 'passed' : 'failed' }}">
                {{ $attempt->passed ? 'أحسنت، اجتزت الاختبار.' : 'لم تصل لدرجة النجاح بعد؛ يمكنك إعادة المحاولة.' }}
                ({{ $attempt->correct_answers }} من {{ $attempt->total_questions }})
            </p>
        </div>
        @foreach ($attempt->results as $result)
            <article class="answer">
                <h2>{{ $loop->iteration }}. {{ $result['question'] }}</h2>
                <p class="{{ $result['is_correct'] ? 'correct' : 'wrong' }}">إجابتك: {{ $result['selected'] }}</p>
                @unless ($result['is_correct'])
                    <p class="correct">الإجابة الصحيحة: {{ $result['correct'] }}</p>
                @endunless
            </article>
        @endforeach
        <div class="actions">
            <a class="button primary" href="{{ route('quizzes.show', $course) }}">إعادة الاختبار</a>
            <a class="button" href="{{ route('courses.show', $course) }}">العودة للكورس</a>
            @if ($certificate)
                <a class="button" href="{{ route('courses.certificate', $course) }}">عرض شهادة إتمام الكورس</a>
            @endif
        </div>
    </section>
</main>
</body>
</html>
