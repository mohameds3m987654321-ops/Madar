<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كلمة مرور جديدة | منصة مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <style>
        :root { --mint: #dff3ec; }

        * {
            box-sizing: border-box;
            margin: 0
        }

        body {
            font-family: "IBM Plex Sans Arabic", Tahoma, sans-serif;
            color: var(--ink);
            background: var(--bg);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px
        }

        .card {
            width: 100%;
            max-width: 430px;
            background: var(--surface, #fff);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 34px;
            box-shadow: var(--shadow);
            animation: md-rise .6s var(--motion-ease) both
        }

        .brand-top {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-bottom: 22px;
            color: var(--brand);
            font-weight: 800;
            font-size: 1.2rem;
            text-decoration: none;
            transition: transform .25s var(--motion-ease)
        }

        .brand-top:hover { transform: scale(1.04) }

        .brand-top svg {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--brand);
            color: #fff;
            padding: 7px;
            box-shadow: 0 6px 16px rgba(13, 91, 77, .24)
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 8px
        }

        .sub {
            color: var(--muted);
            margin-bottom: 26px;
            line-height: 1.7
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px
        }

        input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            font: inherit;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            background: var(--field, #fff);
            color: var(--ink);
            transition: border-color .2s, box-shadow .2s
        }

        input:hover { border-color: var(--brand-line) }

        input:focus {
            outline: 0;
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-soft)
        }

        .field { margin-bottom: 20px }

        .field.invalid input { border-color: var(--red) }

        .msg {
            min-height: 18px;
            margin-top: 6px;
            font-size: 13px;
            color: var(--red)
        }

        button {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            background: var(--brand);
            color: var(--paper, #fff);
            font: inherit;
            font-weight: 700;
            font-size: 16px;
            transition: background .2s, transform .18s var(--motion-ease), box-shadow .2s
        }

        button:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 91, 77, .2)
        }

        button:active { transform: translateY(0) scale(.98) }

        button[disabled] {
            opacity: .75;
            cursor: wait
        }

        .error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 10px;
            background: var(--red-soft);
            color: var(--red);
            font-size: 14px
        }

        .hint {
            color: var(--muted);
            font-size: .82rem;
            margin-top: 6px
        }

        .alt {
            margin-top: 20px;
            text-align: center;
            color: var(--muted);
            font-size: 14px
        }

        .alt a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none
        }

        .alt a:hover { text-decoration: underline }

        html[data-theme="dark"] button { color: #10211b }
        html[data-theme="dark"] .brand-top svg { color: #10211b }
    </style>
    @include('components.appearance')
</head>

<body>
    <div class="card">
        <a class="brand-top" href="{{ url('/') }}">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            مدار
        </a>
        <h1>تعيين كلمة مرور جديدة</h1>
        <p class="sub">اختر كلمة مرور قوية لا تستخدمها في مواقع أخرى.</p>

        @if (session('error'))
            <div class="error" role="alert">{{ session('error') }}</div>
        @endif
        @error('token')
            <div class="error" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="field @error('email') invalid @enderror">
                <label for="email_display">البريد الإلكتروني</label>
                <input type="email" id="email_display" value="{{ $email }}" disabled>
            </div>

            <div class="field @error('password') invalid @enderror">
                <label for="password">كلمة المرور الجديدة</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required>
                <div class="hint">٨ أحرف على الأقل.</div>
                @error('password')
                    <div class="msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="field @error('password_confirmation') invalid @enderror">
                <label for="password_confirmation">تأكيد كلمة المرور</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    autocomplete="new-password" required>
                @error('password_confirmation')
                    <div class="msg">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit">حفظ كلمة المرور</button>
        </form>

        <p class="alt"><a href="{{ route('login') }}">الرجوع إلى تسجيل الدخول</a></p>
    </div>
</body>

</html>
