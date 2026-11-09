<!DOCTYPE html>
<html lang="ar" dir="rtl"
    data-theme="{{ auth()->check() ? auth()->user()->preferred_theme ?? 'light' : 'light' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول | منصة مدار</title>
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
        * {
            box-sizing: border-box;
            margin: 0;
        }

        body {
            font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif;
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

        .brand::before {
            content: "";
            position: absolute;
            inset: -60px;
            background-image:
                linear-gradient(rgba(255, 255, 255, .09) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .09) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(ellipse at 30% 70%, #000 20%, transparent 75%);
            animation: gridDrift 26s linear infinite;
        }

        .brand::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            top: 10%;
            inset-inline-start: -130px;
            background: radial-gradient(circle, rgba(255, 255, 255, .16), transparent 65%);
            animation: md-float 14s ease-in-out infinite alternate;
        }

        @keyframes gridDrift {
            from {
                transform: translate(0, 0);
            }

            to {
                transform: translate(44px, 44px);
            }
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

        .logo:hover {
            transform: scale(1.03);
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: rgba(255, 255, 255, .16);
        }

        .logo-mark svg {
            width: 24px;
            height: 24px;
        }

        .brand-copy {
            position: relative;
            display: grid;
            gap: 16px;
            max-width: 520px;
        }

        .brand h2 {
            font-size: clamp(1.8rem, 3.4vw, 2.8rem);
            line-height: 1.2;
            letter-spacing: -.03em;
            font-weight: 800;
            max-width: 460px;
        }

        .brand p {
            margin-top: -6px;
            color: rgba(255, 255, 255, .78);
            max-width: 380px;
            line-height: 1.7;
        }

        .brand-tips {
            position: relative;
            display: grid;
            gap: 8px;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 14px;
            background: rgba(255, 255, 255, .08);
            backdrop-filter: blur(6px);
        }

        .brand-tips-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 2px;
            font-size: .88rem;
            font-weight: 700;
            color: #ffe9b8;
        }

        .brand-tips-title svg {
            width: 17px;
            height: 17px;
            flex: none;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .brand-tips ul { margin: 0; padding: 0; display: grid; gap: 7px; }

        .brand-tips li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: rgba(255, 255, 255, .92);
            font-size: .82rem;
            line-height: 1.65;
            list-style: none;
        }

        .brand-tips li svg {
            width: 14px;
            height: 14px;
            flex: none;
            margin-top: 4px;
            stroke: #ffe9b8;
            fill: none;
            stroke-width: 2.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .brand-points {
            position: relative;
            display: grid;
            gap: 10px;
            margin-top: 26px;
        }

        .brand-points span {
            display: flex;
            align-items: center;
            gap: 9px;
            color: rgba(255, 255, 255, .9);
            font-size: .92rem;
        }

        .brand-points svg {
            width: 18px;
            height: 18px;
            flex: none;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* القفل الذي يُفتح عند صحة البيانات */
        .lock {
            position: relative;
            width: 96px;
            height: 104px;
            margin-bottom: 6px;
        }

        .lock .body {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 96px;
            height: 62px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .92);
            transition: transform .35s var(--motion-ease);
        }

        .lock.open .body {
            transform: translateY(2px);
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
        }

        .lock .shackle {
            position: absolute;
            left: 17px;
            top: 4px;
            width: 60px;
            height: 56px;
            border: 10px solid rgba(255, 255, 255, .92);
            border-bottom: 0;
            border-radius: 34px 34px 0 0;
            transform-origin: 100% 100%;
            transition: transform .5s cubic-bezier(.3, 1.3, .4, 1);
        }

        .lock.open .shackle {
            transform: translateY(-13px) rotate(-26deg);
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
            margin: 8px 0 26px;
            color: var(--muted);
            line-height: 1.7;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: .92rem;
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

        input:hover {
            border-color: var(--brand-line);
        }

        input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-soft);
        }

        .field.invalid input {
            border-color: var(--red);
        }

        .field.invalid input:focus {
            box-shadow: 0 0 0 4px var(--red-soft);
        }

        .control input[type="password"],
        .control input.shown {
            padding-left: 84px;
            padding-right: 16px;
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
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s;
        }

        .toggle:hover {
            background: var(--brand-line);
            color: var(--brand-dark);
        }

        .toggle:focus-visible,
        .submit:focus-visible {
            outline: 3px solid var(--brand-line);
            outline-offset: 2px;
        }

        .msg {
            min-height: 18px;
            margin-top: 6px;
            font-size: .84rem;
            color: var(--red);
        }

        .submit {
            width: 100%;
            height: 52px;
            margin-top: 8px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            background: var(--brand);
            color: var(--paper, #fff);
            font: inherit;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background .2s, transform .18s var(--motion-ease), box-shadow .2s;
        }

        .submit:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 91, 77, .22);
        }

        .submit:active {
            transform: translateY(0) scale(.98);
        }

        .submit[disabled] {
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

        .server-error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 10px;
            background: var(--red-soft);
            color: var(--red);
            font-size: .92rem;
            animation: md-shake .4s var(--motion-ease) both;
        }

        @keyframes md-shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(5px);
            }

            75% {
                transform: translateX(-5px);
            }
        }

        .alt {
            margin-top: 22px;
            text-align: center;
            color: var(--muted);
            font-size: .92rem;
        }

        .alt a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
            transition: color .2s;
        }

        .alt a:hover {
            text-decoration: underline;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: -6px 0 20px;
            font-size: .92rem;
        }

        .row label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }

        .row input[type=checkbox] {
            width: 18px;
            height: 18px;
            accent-color: var(--brand);
        }

        .row a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
        }

        .row a:hover {
            text-decoration: underline;
        }

        html[data-theme="dark"] .brand {
            color: #eafff7;
        }

        html[data-theme="dark"] .submit {
            color: #10211b;
        }

        @media (prefers-reduced-motion: reduce) {
            .brand-tips { animation: none; }
        }

        @media (max-width: 860px) {
            body {
                grid-template-columns: 1fr;
            }

            .brand {
                padding: 22px 24px;
                gap: 18px;
            }

            .lock,
            .brand-points {
                display: none;
            }

            .brand-copy {
                gap: 12px;
                max-width: none;
            }

            .brand h2 {
                font-size: 1.6rem;
            }

            .brand p {
                margin-top: -6px;
                font-size: .9rem;
            }

            .brand-tips {
                padding: 11px 13px;
                gap: 6px;
            }

            .brand-tips li {
                font-size: .78rem;
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

            .brand-tips {
                padding: 10px 11px;
            }

            .brand-tips li {
                font-size: .76rem;
                line-height: 1.55;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .lock .shackle,
            .spinner,
            .brand::before,
            .brand::after {
                transition: none;
                animation-duration: 2s;
            }
        }
    </style>
    @include('components.appearance')
</head>

<body>
    <aside class="brand" aria-label="معلومات وإرشادات تسجيل الدخول">
        <a class="logo" href="{{ url('/') }}">
            <span class="logo-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            مدار
        </a>
        <div class="brand-copy">
            <div class="lock md-float" id="lock" aria-hidden="true">
                <div class="shackle"></div>
                <div class="body"></div>
            </div>
            <h2>أهلًا بعودتك 👋</h2>
            <p>كمّل من حيث وقفت، وقدمك وإنجازاتك مستنيك في حسابك.</p>
            <div class="brand-points">
                <span><svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m8 12 3 3 5-6" />
                    </svg> تابع محاضراتك في أي وقت</span>
                <span><svg viewBox="0 0 24 24">
                        <path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7" />
                    </svg> شوف تقدمك لحظة بلحظة</span>
                <span><svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="6" />
                        <path d="m8.2 13-1 8 4.8-2.7 4.8 2.7-1-8" />
                    </svg> شهادات موثقة بنهاية كل كورس</span>
            </div>
            <div class="brand-tips" role="note" aria-label="إرشادات مهمة لتسجيل الدخول">
                <p class="brand-tips-title">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    قبل ما تسجّل دخول
                </p>
                <ul>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg> اتأكد إنك على الموقع الرسمي قبل ما تدخل بياناتك.</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg> ما تشاركش كلمة المرور أو رمز التحقق مع أي حد.</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg> لو حسابك جديد، فعّل بريدك من رسالة التحقق. تكرار كلمة مرور خاطئة قد يوقف الدخول مؤقتًا.</li>
                </ul>
            </div>
        </div>
    </aside>

    <main class="panel">
        <form method="POST" action="{{ route('login.store') }}" id="loginForm" novalidate>
            @csrf
            <h1>تسجيل الدخول</h1>
            <p class="sub">اكتب بريدك وكلمة المرور لما تكمل تعلّمك.</p>

            @if ($errors->any())
                <div class="server-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="field" id="emailField">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    placeholder="you@example.com" autocomplete="email" dir="ltr" style="text-align:right" required
                    autofocus>
                <div class="msg" id="emailMsg"></div>
            </div>

            <div class="field" id="passField">
                <label for="password">كلمة المرور</label>
                <div class="control">
                    <input type="password" id="password" name="password" placeholder="اكتب كلمة المرور"
                        autocomplete="current-password" required>
                    <button type="button" class="toggle" id="toggle" aria-label="إظهار كلمة المرور">إظهار</button>
                </div>
                <div class="msg" id="passMsg"></div>
            </div>

            <div class="row">
                <label><input type="checkbox" name="remember"> تذكرني</label>
                <span><a href="{{ route('password.request') }}">نسيت كلمة المرور؟</a></span>
            </div>

            <button type="submit" class="submit" id="submitBtn">
                <span class="spinner"></span><span id="btnText">دخول</span>
            </button>

            <p class="alt">لسه جديد هنا؟ <a href="{{ route('users.create') }}">أنشئ حساب مجاني</a></p>
        </form>
    </main>

    <script>
        const form = document.getElementById('loginForm');
        const email = document.getElementById('email');
        const pass = document.getElementById('password');
        const lock = document.getElementById('lock');
        const btn = document.getElementById('submitBtn');

        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        /* القفل يُفتح عندما تكون البيانات صحيحة */
        function syncLock() {
            lock.classList.toggle('open', emailRe.test(email.value.trim()) && pass.value.length > 0);
        }
        email.addEventListener('input', syncLock);
        pass.addEventListener('input', syncLock);
        syncLock();

        /* إظهار / إخفاء كلمة المرور */
        const toggle = document.getElementById('toggle');
        toggle.addEventListener('click', () => {
            const show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            pass.classList.toggle('shown', show);
            toggle.textContent = show ? 'إخفاء' : 'إظهار';
            toggle.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        });

        /* التحقق الفوري */
        function check(input, fieldId, msgId, message) {
            const ok = message === '';
            document.getElementById(fieldId).classList.toggle('invalid', !ok);
            document.getElementById(msgId).textContent = message;
            return ok;
        }
        const validateEmail = () => check(email, 'emailField', 'emailMsg',
            emailRe.test(email.value.trim()) ? '' : 'اكتب بريدًا إلكترونيًا صحيحًا مثل name@example.com.');
        const validatePass = () => check(pass, 'passField', 'passMsg',
            pass.value.length > 0 ? '' : 'اكتب كلمة المرور.');

        email.addEventListener('blur', validateEmail);
        pass.addEventListener('blur', validatePass);

        form.addEventListener('submit', (e) => {
            const a = validateEmail(),
                b = validatePass();
            if (!a || !b) {
                e.preventDefault();
                (!a ? email : pass).focus();
                return;
            }
            btn.classList.add('loading');
            btn.disabled = true;
            document.getElementById('btnText').textContent = 'جاري الدخول';
        });
    </script>
</body>

</html>
