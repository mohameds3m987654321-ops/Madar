<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد المصادقة الثنائية</title>
    @include('components.assistant-assets')
    @include('components.design-system')
</head>
<body>
    <main>
        <h1>إعداد المصادقة الثنائية</h1>

        @if (session('error'))
            <p role="alert">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif

        @if (! $user->two_factor_secret)
            <p>فعّل المصادقة الثنائية باستخدام تطبيق مثل Google Authenticator أو Microsoft Authenticator.</p>
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <button type="submit">إنشاء إعداد المصادقة</button>
            </form>
        @elseif (! $user->two_factor_confirmed_at)
            <p>امسح رمز QR بتطبيق المصادقة، ثم أدخل الرمز الظاهر فيه للتأكيد.</p>
            <img
                src="data:image/svg+xml;base64,{{ base64_encode($user->twoFactorQrCodeSvg()) }}"
                alt="رمز QR لإعداد المصادقة الثنائية"
            >
            <p>أو أدخل المفتاح يدويًا: <code>{{ $secret }}</code></p>
            <form method="POST" action="{{ route('two-factor.confirm') }}">
                @csrf
                <label for="code">رمز المصادقة</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required>
                <button type="submit">تأكيد وتفعيل</button>
            </form>
        @else
            <p>تم تفعيل المصادقة الثنائية بنجاح.</p>
            <a href="{{ route('users.profile') }}">العودة إلى حسابي</a>
        @endif
    </main>
</body>
</html>
