<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التحقق من الشهادة | مدار</title>
    @include('components.assistant-assets')
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0c1513;color:#e7f0ed;font:16px Tahoma,Arial,sans-serif;line-height:1.8}.card{width:min(700px,calc(100% - 32px));padding:32px;border:1px solid #243732;border-radius:16px;background:#13201d;text-align:center}h1{margin-top:0;color:#3cc3a6}.invalid{color:#ff9d91}.number{overflow-wrap:anywhere;color:#8aa29b}
    </style>
    @include('components.design-system')
</head>
<body>
<main class="card">
    @if ($certificate)
        <h1>شهادة صحيحة</h1>
        <p>تم التحقق من صدور هذه الشهادة من منصة مدار.</p>
        <p><strong>الطالب:</strong> {{ $certificate->user->name }}</p>
        <p><strong>الكورس:</strong> {{ $certificate->course->title }}</p>
        <p><strong>المدرس:</strong> {{ $certificate->course->teacher?->name ?? 'منصة مدار' }}</p>
        <p><strong>تاريخ الإصدار:</strong> {{ $certificate->issued_at->translatedFormat('d F Y') }}</p>
        <p class="number"><strong>كود التحقق:</strong> {{ $certificate->certificate_number }}</p>
    @else
        <h1 class="invalid">تعذر التحقق من الشهادة</h1>
        <p>لا يوجد سجل شهادة مطابق لكود التحقق.</p>
    @endif
    <a href="{{ route('home') }}" style="color:#3cc3a6">العودة إلى منصة مدار</a>
</main>
</body>
</html>
