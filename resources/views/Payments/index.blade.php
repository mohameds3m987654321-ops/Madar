<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المدفوعات والفواتير</title>
    @include('components.assistant-assets')
    <style>
        body{margin:0;background:#0c1513;color:#e7f0ed;font:16px Tahoma,Arial,sans-serif;line-height:1.7}main{width:min(1000px,calc(100% - 28px));margin:28px auto}a{color:#3cc3a6;text-decoration:none}.card{margin:14px 0;padding:18px;border:1px solid #243732;border-radius:12px;background:#13201d}.row{display:flex;justify-content:space-between;gap:14px;align-items:flex-start}button,textarea{padding:9px;border:1px solid #31433e;border-radius:8px;background:#101b19;color:inherit;font:inherit}button{cursor:pointer}.muted{color:#8aa29b}.error{color:#ff9d91}textarea{width:100%;min-height:70px;box-sizing:border-box}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}@media(max-width:600px){.row{flex-direction:column}}
    </style>
    @include('components.design-system')
</head>
<body>
<main>
    <a href="{{ route('users.profile') }}">العودة إلى حسابي</a>
    <h1>سجل المدفوعات والفواتير</h1>
    @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
    @forelse ($enrollments as $enrollment)
        <article class="card">
            <div class="row">
                <div>
                    <h2>{{ $enrollment->course->title }}</h2>
                    <p class="muted">
                        {{ $enrollment->paid_at?->format('Y/m/d H:i') ?? $enrollment->created_at->format('Y/m/d H:i') }}
                        · @switch($enrollment->status)
                            @case('paid') تم الدفع @break
                            @case('refunded') تم الاسترداد @break
                            @case('pending') قيد الدفع @break
                            @case('failed') فشل الدفع @break
                            @default {{ $enrollment->status }}
                        @endswitch
                        @if ($enrollment->paymob_transaction_id) · رقم العملية {{ $enrollment->paymob_transaction_id }} @endif
                    </p>
                    <p>
                        المبلغ: {{ number_format($enrollment->amount_cents / 100, 2) }} جنيه
                        @if ($enrollment->discount_cents > 0)
                            · خصم {{ number_format($enrollment->discount_cents / 100, 2) }} جنيه
                            @if ($enrollment->coupon) ({{ $enrollment->coupon->code }}) @endif
                        @endif
                    </p>
                    @if ($enrollment->refundRequest)
                        <p class="muted">طلب الاسترداد:
                            @switch($enrollment->refundRequest->status)
                                @case('pending') قيد المراجعة @break
                                @case('approved') تمت الموافقة؛ يجري رد المبلغ يدويًا @break
                                @case('rejected') مرفوض @break
                                @case('completed') تم الاسترداد @break
                            @endswitch
                            @if ($enrollment->refundRequest->admin_note) · {{ $enrollment->refundRequest->admin_note }} @endif
                        </p>
                    @endif
                </div>
                <div class="actions">
                    @if (in_array($enrollment->status, ['paid', 'refunded'], true))
                        <a href="{{ route('payments.invoice', $enrollment) }}">عرض الفاتورة</a>
                    @endif
                </div>
            </div>
            @if ($enrollment->status === 'paid' && $enrollment->amount_cents > 0 && !$enrollment->refundRequest)
                <form method="POST" action="{{ route('refunds.store', $enrollment) }}">
                    @csrf
                    <label for="reason-{{ $enrollment->id }}">طلب استرداد هذا الدفع</label>
                    <textarea id="reason-{{ $enrollment->id }}" name="reason" minlength="10" maxlength="2000" required></textarea>
                    @error('reason')<div class="error">{{ $message }}</div>@enderror
                    <button type="submit">إرسال طلب الاسترداد للمراجعة</button>
                </form>
            @endif
        </article>
    @empty
        <p class="muted">لا توجد مدفوعات مسجلة حتى الآن.</p>
    @endforelse
    {{ $enrollments->links() }}
</main>
</body>
</html>
