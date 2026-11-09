<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد البريد الإلكتروني | منصة مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 20px;
            background: radial-gradient(ellipse 700px 400px at 85% -8%, var(--brand-soft), transparent 62%), var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.8;
        }
        main {
            width: min(100%, 520px);
            padding: 32px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--surface, #fff);
            box-shadow: var(--shadow);
            animation: md-rise .6s var(--motion-ease) both;
        }
        h1 { margin: 0 0 8px; font-size: 1.55rem; }
        p { color: var(--ink-2, var(--ink)); }
        .email { color: var(--brand); overflow-wrap: anywhere; }
        .message { margin: 14px 0; padding: 10px 13px; border-radius: 9px; background: var(--brand-soft); color: var(--brand); }
        .message.error { background: #fff0ef; color: var(--red); }
        .error { color: var(--red); font-size: .88rem; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input {
            width: 100%;
            min-height: 46px;
            padding: 9px 12px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--field, #fff);
            color: var(--ink);
            font: inherit;
            direction: ltr;
            text-align: left;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus { outline: 0; border-color: var(--brand); box-shadow: 0 0 0 4px var(--brand-soft); }
        button, .logout {
            min-height: 44px;
            border: 0;
            border-radius: 9px;
            padding: 8px 15px;
            background: var(--brand);
            color: var(--paper, #fff);
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .18s var(--motion-ease), box-shadow .2s;
        }
        button:hover, .logout:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13,91,77,.2); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .secondary { border: 1px solid var(--brand-line); background: var(--brand-soft); color: var(--brand); }
        .secondary:hover { background: var(--brand-line); }
        html[data-theme="dark"] button:not(.secondary) { color: #10211b; }
        .section { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--line); }
        form { margin: 0; }
        .email-form { display: grid; gap: 8px; }
        @media (max-width: 480px) {
            main { padding: 22px; }
            .actions { display: grid; }
            button, .logout { width: 100%; text-align: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition-duration: .01ms !important; }
        }
    </style>
    @include('components.appearance')
</head>
<body>
    <main>
        <h1>أكد بريدك الإلكتروني</h1>
        @if (config('mail.default') === 'log')
            <p role="status">إعداد البريد الحالي للتطوير ولا يرسل رسائل إلى صندوق الوارد. رمز التأكيد محفوظ في <strong dir="ltr">storage/logs/laravel.log</strong>. لإرسال الرمز فعليًا، اضبط SMTP في ملف <strong dir="ltr">.env</strong>.</p>
        @else
            <p>أرسلنا رمز تأكيد إلى <strong class="email">{{ auth()->user()->email }}</strong>. أدخل الرمز لتفعيل حسابك ومتابعة استخدام المنصة. الرمز صالح لمدة 10 دقائق. لو لم تجد الرسالة، راجع مجلد الرسائل غير المرغوب فيها.</p>
        @endif

        @if (session('status'))
            <div class="message" role="status">{{ session('status') }}</div>
        @endif
        @if (session('email_error'))
            <div class="message error" role="alert">{{ session('email_error') }}</div>
        @endif

        <form class="email-form" method="POST" action="{{ route('verification.code') }}">
            @csrf
            <label for="code">رمز التحقق المكوّن من 6 أرقام</label>
            <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" dir="ltr" required autofocus>
            @error('code')
                <div class="error" role="alert">{{ $message }}</div>
            @enderror
            <button type="submit">تأكيد البريد الإلكتروني</button>
        </form>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="secondary">إرسال رمز جديد</button>
        </form>

        <section class="section">
            <p>كتبت البريد الإلكتروني بشكل غير صحيح؟ غيّره وسنرسل رمزًا جديدًا.</p>
            <form class="email-form" method="POST" action="{{ route('verification.email.update') }}">
                @csrf
                @method('PATCH')
                <label for="email">البريد الإلكتروني</label>
                <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email">
                @error('email')
                    <div class="error" role="alert">{{ $message }}</div>
                @enderror
                <button type="submit">تحديث البريد وإرسال الرمز</button>
            </form>
        </section>

        <div class="actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="secondary">تسجيل الخروج</button>
            </form>
        </div>
    </main>
</body>
</html>
