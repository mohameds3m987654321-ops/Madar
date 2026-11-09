<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق الثنائي</title>
    @include('components.assistant-assets')
    @include('components.design-system')
</head>
<body>
    <main>
        <h1>التحقق بخطوتين</h1>
        <p>أدخل الرمز الظاهر في تطبيق المصادقة لإكمال تسجيل الدخول.</p>

        @if ($errors->any())
            <div role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf
            <label for="code">رمز المصادقة</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            <button type="submit">تحقق</button>
        </form>

        <h2>استخدام رمز استرداد</h2>
        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf
            <label for="recovery_code">رمز الاسترداد</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" required>
            <button type="submit">تحقق برمز الاسترداد</button>
        </form>
    </main>
</body>
</html>
