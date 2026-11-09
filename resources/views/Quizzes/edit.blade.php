<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد الاختبار | {{ $course->title }}</title>
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:dark;--bg:#0c1513;--surface:#13201d;--field:#101b19;--ink:#e7f0ed;--muted:#8aa29b;--line:#243732;--accent:#3cc3a6;--soft:#16352f;--brand:#0f8a73;--danger:#ff9288}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.7}
        main{width:min(850px,calc(100% - 28px));margin:0 auto;padding:28px 0 50px}a{color:var(--accent);text-decoration:none}:focus-visible{outline:3px solid var(--accent);outline-offset:3px}
        .crumb{margin-bottom:8px;color:var(--muted);font-size:.86rem}h1{margin:0 0 4px;font-size:1.8rem}header p{margin:0 0 20px;color:var(--muted)}
        .card,.question{border:1px solid var(--line);border-radius:14px;background:var(--surface)}.card{padding:20px}.question{padding:16px;margin-top:14px}
        .field{margin-bottom:15px}label{display:block;margin-bottom:6px;font-weight:600}input,textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:var(--field);color:var(--ink);font:inherit}
        input:focus,textarea:focus{outline:0;border-color:var(--accent)}textarea{min-height:74px;resize:vertical}        .top-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .question-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.question-head h2{margin:0;font-size:1rem}
        .options{display:grid;gap:8px}.option{display:grid;grid-template-columns:22px 1fr;align-items:center;gap:8px}.option input[type=radio]{accent-color:var(--accent);width:18px;height:18px}.option input[type=text]{min-width:0}
        .hint,.error{font-size:.82rem}.hint{color:var(--muted);margin-top:4px}.error{color:var(--danger);margin-top:5px}.notice{margin-bottom:14px;padding:10px 13px;border:1px solid #26594d;border-radius:9px;background:var(--soft);color:var(--accent)}
        .button{display:inline-flex;min-height:42px;align-items:center;justify-content:center;padding:8px 14px;border:1px solid var(--line);border-radius:9px;background:transparent;color:var(--ink);font:inherit;font-weight:600;cursor:pointer}
        .button.primary{border-color:transparent;background:var(--brand);color:#fff}.button.remove{color:var(--danger);border-color:#633b39}.button:hover{background:var(--soft)}
        .actions{display:flex;gap:9px;margin-top:18px}.actions .primary{flex:1}
        @media(max-width:520px){main{padding-top:18px}.card{padding:15px}.top-fields{grid-template-columns:1fr}.question{padding:12px}.actions .button{padding-inline:10px}}
    </style>
    @include('components.appearance')
</head>
<body class="teacher-management">
@include('components.management-navigation')
<main>
    <div class="crumb"><a href="{{ route('teacher.dashboard') }}">لوحة المدرس</a> / <a href="{{ route('courses.edit', $course) }}">{{ $course->title }}</a> / إعداد الاختبار</div>
    <header>
        <h1>الاختبار النهائي</h1>
        <p>أضف الأسئلة وحدد الإجابة الصحيحة؛ الطالب سيرى نتيجته فور التسليم.</p>
    </header>

    @if (session('success')) <div class="notice" role="status">{{ session('success') }}</div> @endif

    @php
        $questionValues = old('questions', $quiz
            ? $quiz->questions->map(fn ($question) => [
                'text' => $question->text,
                'options' => $question->options->pluck('text')->all(),
                'correct' => max(0, (int) $question->options->search(fn ($option) => $option->is_correct)),
            ])->all()
            : [['text' => '', 'options' => ['', '', '', ''], 'correct' => 0]]);
    @endphp

    <form class="card" method="POST" action="{{ route('quizzes.save', $course) }}" id="quiz-form">
        @csrf
        @method('PUT')
        <div class="top-fields">
            <div class="field">
                <label for="title">اسم الاختبار</label>
                <input id="title" name="title" maxlength="255" value="{{ old('title', $quiz?->title ?? 'الاختبار النهائي') }}" required>
                @error('title') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="passing_score">درجة النجاح (%)</label>
                <input id="passing_score" name="passing_score" type="number" min="1" max="100" value="{{ old('passing_score', $quiz?->passing_score ?? 70) }}" required>
                @error('passing_score') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="max_attempts">الحد الأقصى للمحاولات</label>
                <input id="max_attempts" name="max_attempts" type="number" min="1" max="20" value="{{ old('max_attempts', $quiz?->max_attempts ?? 3) }}" required>
                @error('max_attempts') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="time_limit_minutes">مدة الاختبار (بالدقائق)</label>
                <input id="time_limit_minutes" name="time_limit_minutes" type="number" min="1" max="180" value="{{ old('time_limit_minutes', $quiz?->time_limit_minutes ?? 30) }}" required>
                @error('time_limit_minutes') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
        @error('questions') <div class="error">{{ $message }}</div> @enderror

        <div id="questions">
            @foreach ($questionValues as $questionIndex => $questionValue)
                <section class="question" data-question>
                    <div class="question-head">
                        <h2>السؤال <span data-question-number>{{ $loop->iteration }}</span></h2>
                        <button class="button remove" type="button" data-remove-question>حذف السؤال</button>
                    </div>
                    <div class="field">
                        <label for="question-{{ $loop->index }}">نص السؤال</label>
                        <textarea id="question-{{ $loop->index }}" name="questions[{{ $loop->index }}][text]" maxlength="2000" required>{{ $questionValue['text'] ?? '' }}</textarea>
                    </div>
                    <div class="hint">اكتب الاختيارات وحدد دائرة الإجابة الصحيحة.</div>
                    <div class="options">
                        @foreach (($questionValue['options'] ?? ['', '', '', '']) as $optionIndex => $optionValue)
                            <label class="option">
                                <input type="radio" name="questions[{{ $loop->parent->index }}][correct]" value="{{ $optionIndex }}" @checked((int) ($questionValue['correct'] ?? 0) === $optionIndex) required>
                                <input type="text" name="questions[{{ $loop->parent->index }}][options][{{ $optionIndex }}]" value="{{ $optionValue }}" maxlength="255" aria-label="اختيار {{ $optionIndex + 1 }}" required>
                            </label>
                        @endforeach
                    </div>
                    @error("questions.$questionIndex.text") <div class="error">{{ $message }}</div> @enderror
                </section>
            @endforeach
        </div>

        <div class="actions">
            <button class="button" type="button" id="add-question">إضافة سؤال</button>
            <a class="button" href="{{ route('courses.edit', $course) }}">رجوع للكورس</a>
            <button class="button primary" type="submit">حفظ الاختبار</button>
        </div>
    </form>
</main>

<template id="question-template">
    <section class="question" data-question>
        <div class="question-head">
            <h2>السؤال <span data-question-number></span></h2>
            <button class="button remove" type="button" data-remove-question>حذف السؤال</button>
        </div>
        <div class="field">
            <label for="question-__INDEX__">نص السؤال</label>
            <textarea id="question-__INDEX__" name="questions[__INDEX__][text]" maxlength="2000" required></textarea>
        </div>
        <div class="hint">اكتب الاختيارات وحدد دائرة الإجابة الصحيحة.</div>
        <div class="options">
            <label class="option"><input type="radio" name="questions[__INDEX__][correct]" value="0" required><input type="text" name="questions[__INDEX__][options][0]" maxlength="255" aria-label="اختيار 1" required></label>
            <label class="option"><input type="radio" name="questions[__INDEX__][correct]" value="1" required><input type="text" name="questions[__INDEX__][options][1]" maxlength="255" aria-label="اختيار 2" required></label>
            <label class="option"><input type="radio" name="questions[__INDEX__][correct]" value="2" required><input type="text" name="questions[__INDEX__][options][2]" maxlength="255" aria-label="اختيار 3" required></label>
            <label class="option"><input type="radio" name="questions[__INDEX__][correct]" value="3" required><input type="text" name="questions[__INDEX__][options][3]" maxlength="255" aria-label="اختيار 4" required></label>
        </div>
    </section>
</template>
<script>
    const questions = document.getElementById('questions');
    let nextIndex = questions.querySelectorAll('[data-question]').length;

    const refreshQuestionNumbers = () => {
        questions.querySelectorAll('[data-question-number]').forEach((number, index) => {
            number.textContent = index + 1;
        });
    };

    document.getElementById('add-question').addEventListener('click', () => {
        const template = document.getElementById('question-template').innerHTML;
        questions.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex++));
        refreshQuestionNumbers();
        questions.lastElementChild.querySelector('textarea').focus();
    });

    questions.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-question]');
        if (!button) return;
        if (questions.querySelectorAll('[data-question]').length <= 1) return;
        button.closest('[data-question]').remove();
        refreshQuestionNumbers();
    });
</script>
</body>
</html>
