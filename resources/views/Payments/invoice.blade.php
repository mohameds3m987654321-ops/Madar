<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة {{ $enrollment->id }}</title>
    @include('components.assistant-assets')
    <style>
        body{margin:0;background:#f1f4f2;color:#17231f;font:16px Tahoma,Arial,sans-serif;line-height:1.8}.invoice{width:min(760px,calc(100% - 32px));margin:40px auto;padding:36px;background:white;border:1px solid #dce4e0;border-radius:12px}h1{margin:0}table{width:100%;border-collapse:collapse;margin-top:24px}td,th{padding:12px;border-bottom:1px solid #dce4e0;text-align:right}.total{font-weight:bold}.muted{color:#65756e}.actions{margin-bottom:20px}button,a{padding:9px 14px;border:0;border-radius:7px;background:#0f8a73;color:white;text-decoration:none;font:inherit;cursor:pointer}@media print{body{background:white}.invoice{width:100%;margin:0;border:0;padding:0}.actions{display:none}}
    </style>
    @include('components.design-system')
</head>
<body>
<main class="invoice">
    <div class="actions">
        <a href="{{ route('payments.index') }}">سجل المدفوعات</a>
        <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
    </div>
    <h1>فاتورة سداد</h1>
    <p class="muted">منصة مدار · رقم الفاتورة {{ $enrollment->id }}</p>
    <p>الطالب: <strong>{{ $enrollment->user->name }}</strong></p>
    <p>تاريخ الدفع: {{ $enrollment->paid_at?->format('Y/m/d H:i') ?? '—' }}</p>
    <p>رقم عملية الدفع: {{ $enrollment->paymob_transaction_id ?? '—' }}</p>
    <table>
        <thead><tr><th>الوصف</th><th>المبلغ</th></tr></thead>
        <tbody>
            <tr><td>الاشتراك في {{ $enrollment->course->title }}</td><td>{{ number_format(($enrollment->amount_cents + $enrollment->discount_cents) / 100, 2) }} جنيه</td></tr>
            @if ($enrollment->discount_cents > 0)
                <tr><td>خصم {{ $enrollment->coupon?->code }}</td><td>-{{ number_format($enrollment->discount_cents / 100, 2) }} جنيه</td></tr>
            @endif
            <tr class="total"><td>الإجمالي المدفوع</td><td>{{ number_format($enrollment->amount_cents / 100, 2) }} جنيه</td></tr>
        </tbody>
    </table>
    <p class="muted">حالة الدفع: {{ $enrollment->status === 'refunded' ? 'تم الاسترداد' : 'مدفوع' }}</p>
</main>
</body>
</html>
