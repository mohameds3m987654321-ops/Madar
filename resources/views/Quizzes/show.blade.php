<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }} | منصة مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:dark;--bg:#0c1513;--surface:#13201d;--field:#101b19;--ink:#e7f0ed;--muted:#8aa29b;--line:#243732;--accent:#3cc3a6;--soft:#16352f;--brand:#0f8a73}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.7}
        main{width:min(780px,calc(100% - 28px));margin:0 auto;padding:28px 0 50px}a{color:var(--accent);text-decoration:none}:focus-visible{outline:3px solid var(--accent);outline-offset:3px}
        .crumb{margin-bottom:8px;color:var(--muted);font-size:.86rem}header{margin-bottom:18px}h1{margin:0 0 4px;font-size:1.8rem}header p{margin:0;color:var(--muted)}
        .card,.question{border:1px solid var(--line);border-radius:14px;background:var(--surface)}.card{padding:20px}.question{padding:16px;margin-bottom:13px}.question h2{margin:0 0 12px;font-size:1rem}
        .option{display:flex;align-items:center;gap:10px;padding:10px 12px;margin-top:8px;border:1px solid var(--line);border-radius:9px;background:var(--field);cursor:pointer}
        .option input{accent-color:var(--accent);width:18px;height:18px}.actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px}
        .button{display:inline-flex;min-height:42px;align-items:center;justify-content:center;padding:8px 14px;border:1px solid var(--line);border-radius:9px;background:transparent;color:var(--ink);font:inherit;font-weight:600;cursor:pointer}
        .button.primary{border-color:transparent;background:var(--brand);color:#fff}.history{margin-top:15px;padding-top:12px;border-top:1px solid var(--line);color:var(--muted);font-size:.86rem}
        .history a{margin-inline-start:8px}.timer{margin:12px 0;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:var(--field);font-weight:700}.timer.expired{color:#ff9288}
        @media(max-width:520px){main{padding-top:18px}.card{padding:14px}.question{padding:13px}.actions{align-items:stretch;flex-direction:column}}
    </style>
    @include('components.appearance')
</head>
<body>
<main>
    <div class="crumb"><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a> / الاختبار النهائي</div>
    <header>
        <h1>{{ $quiz->title }}</h1>
        <p>{{ $quiz->questions->count() }} سؤال · درجة النجاح {{ $quiz->passing_score }}% · المحاولات {{ $attemptsUsed }}/{{ $quiz->max_attempts }}.</p>
    </header>
    <form class="card" id="quiz-form" method="POST" action="{{ route('quizzes.submit', $course) }}">
        @csrf
        <input type="hidden" name="quiz_session" value="{{ $quizSession->id }}">
        @error('answers') <div role="alert">{{ $message }}</div> @enderror
        <div class="timer" id="quiz-timer" data-expires-at="{{ $quizSession->expires_at->timestamp }}" role="timer" aria-live="polite">
            الوقت المتبقي: <span id="quiz-time"></span>
        </div>
        @foreach ($quiz->questions as $question)
            <fieldset class="question" style="border-style:solid">
                <legend><h2>{{ $loop->iteration }}. {{ $question->text }}</h2></legend>
                @foreach ($question->options as $option)
                    <label class="option">
                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" @checked(old("answers.{$question->id}") == $option->id)>
                        <span>{{ $option->text }}</span>
                    </label>
                @endforeach
            </fieldset>
        @endforeach
        <div class="actions">
            <a class="button" href="{{ route('courses.show', $course) }}">العودة للكورس</a>
            <button class="button primary" type="submit">تسليم الاختبار وعرض النتيجة</button>
        </div>
        @if ($attempts->isNotEmpty())
            <div class="history">
                محاولاتك السابقة:
                @foreach ($attempts as $attempt)
                    <a href="{{ route('quizzes.result', [$course, $attempt]) }}">{{ $attempt->score }}% · {{ $attempt->submitted_at->format('Y/m/d') }}</a>
                @endforeach
            </div>
        @endif
    </form>
</main>
<script>
    (() => {
        const timer = document.getElementById('quiz-timer');
        const display = document.getElementById('quiz-time');
        const form = document.getElementById('quiz-form');
        const deadline = Number(timer.dataset.expiresAt) * 1000;

        const updateTimer = () => {
            const secondsLeft = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            display.textContent = `${Math.floor(secondsLeft / 60)}:${String(secondsLeft % 60).padStart(2, '0')}`;
            if (secondsLeft === 0) {
                timer.classList.add('expired');
                form.requestSubmit();
                return;
            }
            window.setTimeout(updateTimer, 1000);
        };

        updateTimer();
    })();
</script>
</body>
</html>
