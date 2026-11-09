<!DOCTYPE html>
<html lang="ar" dir="rtl"
    data-theme="{{ auth()->check() ? auth()->user()->preferred_theme ?? 'light' : 'light' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد | مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <script>
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ready');
        }
    </script>
    <style>
        :root {
            --mint: #dff3ec;
        }

        * {
            box-sizing: border-box;
            margin: 0;
        }

        body {
            font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
        }

        /* ---------- لوحة العلامة ---------- */
        .brand {
            position: relative;
            overflow: hidden;
            background: var(--green-hero);
            color: #fff;
            padding: clamp(30px, 5vw, 56px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 40px;
        }

        /* شبكة متحركة خلفية بتتحرك ببطء */
        .brand::before {
            content: "";
            position: absolute;
            inset: -60px;
            background-image:
                linear-gradient(rgba(255,255,255,.09) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.09) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(ellipse at 30% 70%, #000 20%, transparent 75%);
            animation: gridDrift 26s linear infinite;
        }

        @keyframes gridDrift {
            from { transform: translate(0, 0); }
            to   { transform: translate(44px, 44px); }
        }

        /* هالة ضوئية عايمة */
        .brand::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            top: 10%;
            inset-inline-start: -130px;
            background: radial-gradient(circle, rgba(255,255,255,.16), transparent 65%);
            animation: md-float 14s ease-in-out infinite alternate;
        }

        @media (prefers-reduced-motion: reduce) {

            .brand::before,
            .brand::after {
                animation: none;
            }
        }

        .logo {
            position: relative;
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -.02em;
            display: flex;
            align-items: center;
            gap: 10px;
            color: inherit;
            text-decoration: none;
            transition: transform .25s var(--motion-ease);
        }
        .logo:hover { transform: scale(1.03); }
        .logo-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: rgba(255, 255, 255, .16);
        }
        .logo-mark svg { width: 24px; height: 24px; }

        .brand h2 {
            position: relative;
            font-size: clamp(1.8rem, 3.4vw, 2.8rem);
            line-height: 1.2;
            letter-spacing: -.03em;
            font-weight: 800;
            max-width: 460px;
        }

        /* المحتوى السفلي ثابت بدون قفزات */
        .brand-copy {
            position: relative;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            max-width: 520px;
        }

        .brand p {
            position: relative;
            margin-top: 18px;
            color: rgba(255,255,255,.78);
            max-width: 380px;
            line-height: 1.7;
        }

        .brand-points {
            position: relative;
            display: grid;
            gap: 10px;
            margin: 22px 0 0;
            padding: 0;
            list-style: none;
        }

        .brand-points li {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            align-items: start;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 14px;
            background: rgba(255, 255, 255, .08);
        }

        .point-number {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 11px;
            background: rgba(255, 255, 255, .16);
            color: #fff;
            font-weight: 800;
        }

        .point-copy {
            display: grid;
            gap: 3px;
            padding-top: 1px;
        }

        .point-copy strong {
            color: #fff;
            font-size: .94rem;
        }

        .point-copy small {
            color: rgba(255, 255, 255, .78);
            font-size: .84rem;
            line-height: 1.6;
        }

        .brand-notice {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            padding: 13px 15px;
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 14px;
            background: rgba(4, 43, 36, .28);
            color: rgba(255, 255, 255, .9);
            font-size: .86rem;
            line-height: 1.7;
        }

        .brand-notice svg {
            width: 20px;
            height: 20px;
            flex: none;
            margin-top: 2px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .brand-notice strong {
            color: #fff;
        }

        /* القفل: يُفتح عند اكتمال البيانات */
        .lock {
            position: relative;
            width: 96px;
            height: 104px;
            margin-bottom: 10px;
            will-change: transform;
        }

        .lock .body {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 96px;
            height: 62px;
            border-radius: 14px;
            background: rgba(255,255,255,.92);
        }

        .lock .body::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 20px;
            width: 14px;
            height: 14px;
            margin-left: -7px;
            border-radius: 50%;
            background: var(--green-hero);
            box-shadow: 0 12px 0 -4px var(--green-hero);
            transition: transform .3s;
        }

        .lock.open .body::after {
            transform: rotate(90deg) translateX(-4px);
        }

        .lock .shackle {
            position: absolute;
            left: 17px;
            top: 4px;
            width: 60px;
            height: 56px;
            border: 10px solid rgba(255,255,255,.92);
            border-bottom: 0;
            border-radius: 34px 34px 0 0;
            transform-origin: 100% 100%;
            transition: transform .45s cubic-bezier(.3, 1.2, .4, 1);
        }

        .lock.open .shackle {
            transform: translateY(-13px) rotate(-24deg);
        }

        /* ---------- لوحة النموذج ---------- */
        .panel {
            display: grid;
            place-items: center;
            padding: 40px 24px;
            animation: md-rise .6s var(--motion-ease) both;
        }

        form {
            width: 100%;
            max-width: 390px;
        }

        h1 {
            font-size: clamp(1.65rem, 4vw, 2rem);
            letter-spacing: -.03em;
            font-weight: 800;
        }

        .sub {
            margin: 8px 0 24px;
            color: var(--muted);
            line-height: 1.7;
        }

        /* Steps indicator */
        .steps {
            display: flex;
            gap: 6px;
            margin-bottom: 28px;
        }

        .steps span {
            flex: 1;
            height: 5px;
            border-radius: 99px;
            background: var(--line);
            transition: background .3s;
        }

        .steps span.active {
            background: var(--brand);
        }

        .step-title {
            margin: -16px 0 24px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        /* Wizard steps: only one visible at a time */
        .wizard-step {
            display: none;
        }

        .wizard-step.current {
            display: block;
            animation: slideIn .3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-8px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .wizard-step.current {
                animation: none;
            }
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .control {
            position: relative;
        }

        input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            font: inherit;
            color: var(--ink);
            background: var(--field, #fff);
            border: 1.5px solid var(--line);
            border-radius: 12px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        select {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            font: inherit;
            color: var(--ink);
            background: var(--field, #fff);
            border: 1.5px solid var(--line);
            border-radius: 12px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        input:hover {
            border-color: var(--brand-line);
        }

        input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-soft);
        }

        select:hover {
            border-color: var(--brand-line);
        }

        select:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-soft);
        }

        .field.invalid input,
        .field.invalid select {
            border-color: var(--red);
        }

        .field.invalid input:focus,
        .field.invalid select:focus {
            box-shadow: 0 0 0 4px var(--red-soft);
        }

        .control input[type="password"],
        .control input.shown {
            padding-left: 84px;
        }

        .toggle {
            position: absolute;
            inset-inline-end: 8px;
            top: 8px;
            height: 34px;
            padding: 0 12px;
            border: 0;
            border-radius: 8px;
            background: var(--brand-soft);
            color: var(--brand);
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s;
        }

        .toggle:hover {
            background: var(--brand-line);
            color: var(--brand-dark);
        }

        .toggle:focus-visible,
        .submit:focus-visible,
        .back:focus-visible {
            outline: 3px solid var(--brand-line);
            outline-offset: 2px;
        }

        .msg {
            min-height: 18px;
            margin-top: 6px;
            font-size: 13px;
            color: var(--red);
        }

        .nav-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        button.submit {
            flex: 1;
            height: 52px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            background: var(--brand);
            color: var(--paper, #fff);
            font: inherit;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background .2s, transform .18s var(--motion-ease), box-shadow .2s;
        }

        button.submit:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 91, 77, .22);
        }

        button.submit:active {
            transform: translateY(0) scale(.98);
        }

        button.submit[disabled] {
            opacity: .75;
            cursor: wait;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(255, 255, 255, .35);
            border-top-color: #fff;
            border-radius: 50%;
            display: none;
            animation: spin .7s linear infinite;
        }

        .loading .spinner {
            display: block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .back {
            display: none;
            height: 52px;
            padding: 0 18px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            cursor: pointer;
            background: transparent;
            color: var(--ink);
            font: inherit;
            font-weight: 600;
            transition: background .2s, border-color .2s;
        }

        .back:hover {
            background: var(--brand-soft);
            border-color: var(--brand-line);
        }

        .nav-actions.has-back .back {
            display: block;
        }

        .server-error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 10px;
            background: var(--red-soft);
            color: var(--red);
            font-size: 14px;
            animation: md-shake .4s var(--motion-ease) both;
        }

        @keyframes md-shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
        }

        .login-link {
            margin-top: 22px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .login-link a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        html[data-theme="dark"] .brand { color: #eafff7; }
        html[data-theme="dark"] button.submit { color: #10211b; }

        @media (max-width: 860px) {
            body {
                grid-template-columns: 1fr;
            }

            .brand {
                padding: 22px 24px;
                gap: 16px;
            }

            .lock {
                display: none;
            }

            .brand-copy {
                min-height: 0;
                max-width: none;
            }

            .brand p {
                margin-top: 8px;
                font-size: .9rem;
            }

            .brand h2 {
                font-size: 1.6rem;
            }

            .brand-points {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                margin-top: 14px;
            }

            .brand-points li {
                grid-template-columns: 28px minmax(0, 1fr);
                gap: 8px;
                padding: 10px;
            }

            .point-number {
                width: 28px;
                height: 28px;
                border-radius: 9px;
                font-size: .85rem;
            }

            .point-copy strong {
                font-size: .82rem;
            }

            .point-copy small {
                font-size: .75rem;
            }

            .brand-notice {
                margin-top: 10px;
                padding: 10px 12px;
                font-size: .82rem;
            }

            .panel {
                padding: 28px 20px;
            }
        }

        @media (max-width: 520px) {
            .brand {
                padding: 18px 16px;
            }

            .logo {
                font-size: 1.2rem;
            }

            .logo-mark {
                width: 34px;
                height: 34px;
            }

            .brand h2 {
                font-size: 1.35rem;
            }

            .brand-points li {
                display: block;
                padding: 9px 8px;
            }

            .point-number {
                margin-bottom: 7px;
            }

            .point-copy small {
                line-height: 1.45;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .lock .shackle,
            .spinner {
                transition: none;
                animation-duration: 2s;
            }
        }
    </style>
    @include('components.appearance')
</head>

<body>
    <aside class="brand" aria-label="إرشادات إنشاء الحساب">
        <a class="logo" href="{{ url('/') }}">
            <span class="logo-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            مدار
        </a>
        <div class="brand-copy">
            <div class="lock" id="lock" aria-hidden="true">
                <div class="shackle"></div>
                <div class="body"></div>
            </div>
            <h2>قبل ما تبدأ، جهّز بياناتك.</h2>
            <p>التسجيل في ٣ خطوات واضحة، وهنوضح لك المطلوب في كل خطوة.</p>
            <ol class="brand-points" aria-label="خطوات التسجيل">
                <li>
                    <span class="point-number" aria-hidden="true">١</span>
                    <span class="point-copy">
                        <strong>بيانات التواصل</strong>
                        <small>اسمك، بريد تقدر تفتحه، ورقمك ورقم ولي الأمر.</small>
                    </span>
                </li>
                <li>
                    <span class="point-number" aria-hidden="true">٢</span>
                    <span class="point-copy">
                        <strong>بيانات الدراسة</strong>
                        <small>اختار المرحلة والصف ونوع التعليم. للثانوية اختار النظام كمان.</small>
                    </span>
                </li>
                <li>
                    <span class="point-number" aria-hidden="true">٣</span>
                    <span class="point-copy">
                        <strong>كلمة المرور</strong>
                        <small>٨ أحرف على الأقل، وبعدها اكتبها مرة تانية للتأكيد.</small>
                    </span>
                </li>
            </ol>
            <div class="brand-notice" role="note">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 11v5M12 8h.01"></path>
                </svg>
                <span><strong>تنبيه مهم:</strong> هنبعت رمز تأكيد من 6 أرقام على بريدك بعد التسجيل. راجع بريدك، وما تشاركش كلمة مرورك مع أي حد.</span>
            </div>
        </div>
    </aside>

    <main class="panel">
        <form method="POST" action="{{ route('users.store') }}" id="regForm" novalidate>
            @csrf
            <h1>إنشاء حساب</h1>
            <p class="sub">أدخل بياناتك على ٣ خطوات بسيطة.</p>

            @if ($errors->any())
                <div class="server-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="steps" aria-hidden="true">
                <span data-step-ind="1" class="active"></span>
                <span data-step-ind="2"></span>
                <span data-step-ind="3"></span>
            </div>
            <p class="step-title" id="stepTitle">الخطوة ١ من ٣ — البيانات الأساسية</p>

            {{-- Step 1: Basic info --}}
            <section class="wizard-step current" data-step="1">
                <div class="field" id="nameField">
                    <label for="name">الاسم الكامل</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                        placeholder="اكتب اسمك الكامل" autocomplete="name" required autofocus>
                    <div class="msg" id="nameMsg"></div>
                </div>

                <div class="field" id="emailField">
                    <label for="email">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        placeholder="you@example.com" autocomplete="email" required>
                    <div class="msg" id="emailMsg"></div>
                </div>

                <div class="field" id="studentPhoneField">
                    <label for="student_phone">رقم هاتف الطالب</label>
                    <input type="tel" id="student_phone" name="student_phone" value="{{ old('student_phone') }}"
                        placeholder="رقم هاتف الطالب" autocomplete="tel" required>
                    <div class="msg" id="studentPhoneMsg"></div>
                </div>

                <div class="field" id="parentPhoneField">
                    <label for="parent_phone">رقم هاتف ولي الأمر</label>
                    <input type="tel" id="parent_phone" name="parent_phone" value="{{ old('parent_phone') }}"
                        placeholder="رقم هاتف ولي الأمر" required>
                    <div class="msg" id="parentPhoneMsg"></div>
                </div>
            </section>

            {{-- Step 2: Education --}}
            <section class="wizard-step" data-step="2">
                <div class="field" id="educationStageField">
                    <label for="education_stage">المرحلة التعليمية</label>
                    <select id="education_stage" name="education_stage" required>
                        <option value="">اختر المرحلة التعليمية</option>
                        <option value="preparatory" @selected(old('education_stage') === 'preparatory')>الإعدادية</option>
                        <option value="secondary" @selected(old('education_stage') === 'secondary')>الثانوية العامة</option>
                    </select>
                    <div class="msg" id="educationStageMsg"></div>
                </div>

                <div class="field" id="educationGradeField" hidden>
                    <label for="education_grade">الصف الدراسي</label>
                    <select id="education_grade" name="education_grade" required disabled>
                        <option value="">اختر الصف الدراسي</option>
                    </select>
                    <div class="msg" id="educationGradeMsg"></div>
                </div>

                <div class="field" id="educationTypeField">
                    <label for="education_type">نوع التعليم</label>
                    <select id="education_type" name="education_type" required>
                        <option value="">اختر نوع التعليم</option>
                        <option value="general" @selected(old('education_type') === 'general')>عام</option>
                        <option value="azhar" @selected(old('education_type') === 'azhar')>أزهري</option>
                    </select>
                    <div class="msg" id="educationTypeMsg"></div>
                </div>

                <div class="field" id="educationSystemField" hidden>
                    <label for="education_system">نظام الثانوية</label>
                    <select id="education_system" name="education_system" disabled>
                        <option value="">اختر نظام الثانوية</option>
                        <option value="baccalaureate" @selected(old('education_system') === 'baccalaureate')>بكالوريا</option>
                        <option value="old" @selected(old('education_system') === 'old')>نظام قديم</option>
                    </select>
                    <div class="msg" id="educationSystemMsg"></div>
                </div>
            </section>

            {{-- Step 3: Password --}}
            <section class="wizard-step" data-step="3">
                <div class="field" id="passField">
                    <label for="password">كلمة المرور</label>
                    <div class="control">
                        <input type="password" id="password" name="password" placeholder="٨ أحرف على الأقل"
                            autocomplete="new-password" required>
                    </div>
                    <div class="msg" id="passMsg"></div>
                </div>

                <div class="field" id="passConfirmField">
                    <label for="password_confirmation">تأكيد كلمة المرور</label>
                    <div class="control">
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            placeholder="أعد كتابة كلمة المرور" autocomplete="new-password" required>
                        <button type="button" class="toggle" id="toggle"
                            aria-label="إظهار كلمة المرور">إظهار</button>
                    </div>
                    <div class="msg" id="passConfirmMsg"></div>
                </div>
            </section>

            <div class="nav-actions" id="navActions">
                <button type="button" class="back" id="backBtn">السابق</button>
                <button type="button" class="submit" id="nextBtn">
                    <span class="spinner" aria-hidden="true"></span><span id="btnText" aria-live="polite">التالي</span>
                </button>
            </div>

            <p class="login-link">لديك حساب بالفعل؟ <a href="{{ route('login') }}">سجّل الدخول</a></p>
        </form>
    </main>

    <script>
        const form = document.getElementById('regForm');
        const nameInput = document.getElementById('name');
        const email = document.getElementById('email');
        const studentPhone = document.getElementById('student_phone');
        const parentPhone = document.getElementById('parent_phone');
        const educationStage = document.getElementById('education_stage');
        const educationGrade = document.getElementById('education_grade');
        const educationSystem = document.getElementById('education_system');
        const previousEducationGrade = @json(old('education_grade', ''));
        const educationType = document.getElementById('education_type');
        const pass = document.getElementById('password');
        const passConfirm = document.getElementById('password_confirmation');
        const lock = document.getElementById('lock');
        const nextBtn = document.getElementById('nextBtn');
        const backBtn = document.getElementById('backBtn');
        const navActions = document.getElementById('navActions');
        const btnText = document.getElementById('btnText');
        const stepTitle = document.getElementById('stepTitle');
        let registrationSubmitting = false;

        const stepTitles = {
            1: 'الخطوة ١ من ٣ — البيانات الأساسية',
            2: 'الخطوة ٢ من ٣ — المرحلة التعليمية',
            3: 'الخطوة ٣ من ٣ — كلمة المرور',
        };
        let currentStep = 1;
        const totalSteps = 3;

        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function check(input, fieldId, msgId, message) {
            const ok = message === '';
            document.getElementById(fieldId).classList.toggle('invalid', !ok);
            document.getElementById(msgId).textContent = message;
            return ok;
        }
        const validateRequired = (input, fieldId, msgId, message) => check(input, fieldId, msgId,
            input.value.trim() !== '' ? '' : message);
        const validateName = () => check(nameInput, 'nameField', 'nameMsg',
            nameInput.value.trim().length >= 2 ? '' : 'اكتب اسمك الكامل.');
        const validateEmail = () => check(email, 'emailField', 'emailMsg',
            emailRe.test(email.value.trim()) ? '' : 'اكتب بريدًا إلكترونيًا صحيحًا.');
        const validateEducationStage = () => validateRequired(educationStage, 'educationStageField', 'educationStageMsg',
            'اختر المرحلة التعليمية.');
        const validateEducationGrade = () => validateRequired(educationGrade, 'educationGradeField', 'educationGradeMsg',
            'اختر الصف الدراسي.');
        const validateEducationSystem = () => educationStage.value !== 'secondary' ||
            validateRequired(educationSystem, 'educationSystemField', 'educationSystemMsg', 'اختر نظام الثانوية.');
        const validateEducationType = () => validateRequired(educationType, 'educationTypeField', 'educationTypeMsg',
            'اختر نوع التعليم.');
        const validatePass = () => check(pass, 'passField', 'passMsg',
            pass.value.length >= 8 ? '' : 'يجب ألا تقل كلمة المرور عن ٨ أحرف.');
        const validatePassConfirm = () => check(passConfirm, 'passConfirmField', 'passConfirmMsg',
            passConfirm.value === pass.value && passConfirm.value !== '' ? '' : 'كلمتا المرور غير متطابقتين.');

        // Per-step validation
        const stepValidators = {
            1: () => {
                const n = validateName();
                const a = validateEmail();
                const s = validateRequired(studentPhone, 'studentPhoneField', 'studentPhoneMsg',
                    'اكتب رقم هاتف الطالب.');
                const p = validateRequired(parentPhone, 'parentPhoneField', 'parentPhoneMsg',
                    'اكتب رقم هاتف ولي الأمر.');
                return n && a && s && p;
            },
            2: () => {
                const stage = validateEducationStage();
                const grade = validateEducationGrade();
                const system = validateEducationSystem();
                const type = validateEducationType();
                return stage && grade && system && type;
            },
            3: () => {
                const p1 = validatePass();
                const p2 = validatePassConfirm();
                return p1 && p2;
            },
        };

        function showStep(step) {
            currentStep = step;
            document.querySelectorAll('.wizard-step').forEach((section) => {
                section.classList.toggle('current', section.dataset.step === String(step));
            });
            document.querySelectorAll('[data-step-ind]').forEach((indicator) => {
                indicator.classList.toggle('active', Number(indicator.dataset.stepInd) <= step);
            });
            stepTitle.textContent = stepTitles[step];
            navActions.classList.toggle('has-back', step > 1);
            btnText.textContent = step === totalSteps ? 'إنشاء الحساب' : 'التالي';

            const firstInput = document.querySelector(
                `.wizard-step[data-step="${step}"] input, .wizard-step[data-step="${step}"] select`);
            if (firstInput && !firstInput.disabled) firstInput.focus({
                preventScroll: true
            });

            syncLock();
        }

        function syncLock() {
            // The lock unlocks fully once the user reaches the last step
            lock.classList.toggle('open', currentStep === totalSteps &&
                nameInput.value.trim().length >= 2 &&
                emailRe.test(email.value.trim()) &&
                studentPhone.value.trim() !== '' &&
                parentPhone.value.trim() !== '' &&
                educationStage.value !== '' &&
                educationGrade.value !== '' &&
                (educationStage.value !== 'secondary' || educationSystem.value !== '') &&
                educationType.value !== '' &&
                pass.value.length >= 8 &&
                passConfirm.value === pass.value);
        }

        function submitRegistration() {
            if (registrationSubmitting) return;

            registrationSubmitting = true;
            nextBtn.disabled = true;
            nextBtn.classList.add('loading');
            nextBtn.setAttribute('aria-busy', 'true');
            backBtn.disabled = true;
            btnText.textContent = 'جاري التسجيل...';
            form.submit();
        }

        function firstInvalidInStep(step) {
            const sections = {
                1: [nameInput, email, studentPhone, parentPhone],
                2: [educationStage, educationGrade, educationSystem, educationType],
                3: [pass, passConfirm]
            };
            const invalid = (sections[step] || []).find((input) => input.closest('.field').classList.contains('invalid'));
            if (invalid) invalid.focus();
        }

        nextBtn.addEventListener('click', () => {
            if (!stepValidators[currentStep]()) {
                firstInvalidInStep(currentStep);
                return;
            }
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
                return;
            }
            submitRegistration();
        });

        backBtn.addEventListener('click', () => {
            if (currentStep > 1) showStep(currentStep - 1);
        });

        // Enter key advances to the next step instead of submitting early
        form.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                e.preventDefault();
                nextBtn.click();
            }
        });

        // Show / hide password (both fields together)
        const toggle = document.getElementById('toggle');
        toggle.addEventListener('click', () => {
            const show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            passConfirm.type = pass.type;
            pass.classList.toggle('shown', show);
            passConfirm.classList.toggle('shown', show);
            toggle.textContent = show ? 'إخفاء' : 'إظهار';
            toggle.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
            toggle.classList.toggle('active', show);
        });

        /* حركة نبض خفيفة على زر التالي في الخطوة الأخيرة */
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulseGlow {
                0%, 100% { box-shadow: 0 0 0 0 rgba(13, 91, 77, 0); }
                50% { box-shadow: 0 0 0 6px rgba(13, 91, 77, .12); }
            }
            .submit.pulse { animation: pulseGlow 1.8s ease-in-out infinite; }
        `;
        document.head.appendChild(style);

        // Watch for the last step to add a gentle pulse on the final button
        const pulseObserver = new MutationObserver(() => {
            nextBtn.classList.toggle('pulse', currentStep === totalSteps);
        });
        pulseObserver.observe(document.getElementById('stepTitle'), { childList: true, characterData: true, subtree: true });

        function updateEducationOptions(restorePreviousGrade = false) {
            const stage = educationStage.value;
            const gradeField = document.getElementById('educationGradeField');
            const systemField = document.getElementById('educationSystemField');
            const oldGrade = educationGrade.value || (restorePreviousGrade ? previousEducationGrade : '');
            const gradeNames = stage === 'secondary' ? ['الصف الأول الثانوي', 'الصف الثاني الثانوي',
                'الصف الثالث الثانوي'] : ['الصف الأول الإعدادي', 'الصف الثاني الإعدادي', 'الصف الثالث الإعدادي'];

            educationGrade.replaceChildren(new Option('اختر الصف الدراسي', ''));
            gradeNames.forEach((label, index) => {
                educationGrade.add(new Option(label, String(index + 1), false, oldGrade === String(index + 1)));
            });
            educationGrade.disabled = stage === '';
            gradeField.hidden = stage === '';

            educationSystem.disabled = stage !== 'secondary';
            systemField.hidden = stage !== 'secondary';
            if (stage !== 'secondary') {
                educationSystem.value = '';
                systemField.classList.remove('invalid');
                document.getElementById('educationSystemMsg').textContent = '';
            }

            syncLock();
        }

        educationStage.addEventListener('change', () => {
            educationGrade.value = '';
            educationSystem.value = '';
            updateEducationOptions(true);
            validateEducationStage();
        });
        educationGrade.addEventListener('change', validateEducationGrade);
        educationSystem.addEventListener('change', validateEducationSystem);
        updateEducationOptions();

        nameInput.addEventListener('blur', validateName);
        email.addEventListener('blur', validateEmail);
        studentPhone.addEventListener('blur', () => validateRequired(studentPhone, 'studentPhoneField', 'studentPhoneMsg',
            'اكتب رقم هاتف الطالب.'));
        parentPhone.addEventListener('blur', () => validateRequired(parentPhone, 'parentPhoneField', 'parentPhoneMsg',
            'اكتب رقم هاتف ولي الأمر.'));
        educationType.addEventListener('change', validateEducationType);
        pass.addEventListener('blur', validatePass);
        passConfirm.addEventListener('blur', validatePassConfirm);

        // Restore the correct step after a server-side validation error
        const restoredStep = (() => {
            const errorFields = @json($errors?->keys() ?? []);
            if (errorFields.length === 0) return 1;
            if (['password', 'password_confirmation'].some((f) => errorFields.includes(f))) return 3;
            if (['education_stage', 'education_grade', 'education_system', 'education_type'].some((f) => errorFields
                    .includes(f))) return 2;
            return 1;
        })();
        if (restoredStep !== 1) showStep(restoredStep);
    </script>
</body>

</html>
