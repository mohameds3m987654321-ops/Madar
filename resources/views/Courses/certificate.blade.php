<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة إتمام | {{ $course->title }}</title>
    <style>
        @page { size: landscape; margin: 12mm; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:28px; background:#0c1513; color:#e7f0ed; font-family:Tahoma,Arial,sans-serif; }
        .certificate { position:relative; width:min(1100px,100%); min-height:690px; display:flex; flex-direction:column; align-items:center; justify-content:center; overflow:hidden; padding:64px 48px; border:1px solid #3cc3a6; outline:9px solid #13201d; outline-offset:-21px; background:radial-gradient(ellipse at 50% 0%,#16352f,transparent 60%),#13201d; text-align:center; }
        .certificate:before,.certificate:after { content:""; position:absolute; width:190px; height:190px; border:1px solid #26594d; transform:rotate(45deg); }
        .certificate:before { top:-120px; right:-100px; }.certificate:after { bottom:-120px; left:-100px; }
        .brand { color:#3cc3a6; font-weight:700; letter-spacing:2px; }
        h1 { margin:18px 0 6px; font-size:clamp(2rem,5vw,3.5rem); }
        .lead { color:#b8cac4; font-size:1.1rem; }
        .student { margin:22px 0 10px; color:#3cc3a6; font-size:clamp(2rem,5vw,3.1rem); font-weight:700; }
        .course { max-width:850px; margin:0; font-size:clamp(1.3rem,3vw,2rem); }
        .details { display:flex; justify-content:center; gap:44px; margin-top:42px; color:#8aa29b; font-size:.9rem; }
        .details strong { display:block; margin-top:4px; color:#e7f0ed; font-size:.95rem; }
        .verification { display:flex; align-items:center; gap:16px; margin-top:24px; text-align:right; }
        .verification img { width:112px; height:112px; padding:6px; border-radius:8px; background:#fff; }
        .verification a { color:#3cc3a6; overflow-wrap:anywhere; }
        .actions { position:fixed; top:16px; inset-inline-end:16px; display:flex; gap:8px; }
        .actions a,.actions button { padding:9px 14px; border:1px solid #26594d; border-radius:8px; background:#16352f; color:#3cc3a6; font:inherit; text-decoration:none; cursor:pointer; }
        @media(max-width:600px){body{padding:14px}.certificate{min-height:80vh;padding:40px 24px}.details{gap:18px;flex-wrap:wrap}}
        @media print { body{display:block;min-height:0;padding:0;background:#fff;color:#10211d;print-color-adjust:exact;-webkit-print-color-adjust:exact}.certificate{width:100%;min-height:180mm;border-color:#26594d;outline-color:#fff}.actions{display:none} }
    </style>
    @include('components.appearance')
</head>
<body>
<nav class="actions" aria-label="خيارات الشهادة">
    <a href="{{ route('courses.show', $course) }}">العودة للكورس</a>
    <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
</nav>
<main class="certificate">
    <div class="brand">مَدار · منصة التعلم</div>
    <h1>شهادة إتمام</h1>
    <div class="lead">تُمنح هذه الشهادة إلى</div>
    <div class="student">{{ $user->name }}</div>
    <p class="lead">لإتمام جميع محاضرات الكورس</p>
    <h2 class="course">{{ $course->title }}</h2>
    <div class="details">
        <div>المدرس<strong>{{ $course->teacher?->name ?? 'منصة مدار' }}</strong></div>
        <div>تاريخ الإتمام<strong>{{ $certificate->issued_at->translatedFormat('d F Y') }}</strong></div>
        <div>رقم الشهادة<strong>{{ $certificate->certificate_number }}</strong></div>
    </div>
    <div class="verification">
        <img src="{{ $certificateQrCode }}" alt="رمز QR للتحقق من الشهادة">
        <div>
            <div>امسح الرمز للتحقق من صحة الشهادة</div>
            <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
        </div>
    </div>
</main>
</body>
</html>
