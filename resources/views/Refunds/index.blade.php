<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طلبات الاسترداد</title>
    @include('components.assistant-assets')
    <style>
        body{margin:0;background:#0c1513;color:#e7f0ed;font:16px Tahoma,Arial,sans-serif;line-height:1.7}main{width:min(1000px,calc(100% - 28px));margin:28px auto}.card{margin:14px 0;padding:18px;border:1px solid #243732;border-radius:12px;background:#13201d}.muted{color:#8aa29b}textarea,select,button{padding:9px;border:1px solid #31433e;border-radius:8px;background:#101b19;color:inherit;font:inherit}textarea{display:block;width:100%;min-height:65px;margin:8px 0;box-sizing:border-box}button{cursor:pointer}
    </style>
    @include('components.design-system')
</head>
<body>
<main>
    <a href="{{ route('admin.dashboard') }}">العودة للوحة المشرف</a>
    <h1>طلبات الاسترداد</h1>
    @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
    @forelse ($requests as $refund)
        <article class="card">
            <h2>{{ $refund->enrollment->course->title }}</h2>
            <p>{{ $refund->user->name }} · {{ number_format($refund->enrollment->amount_cents / 100, 2) }} جنيه</p>
            <p class="muted">سبب الطلب: {{ $refund->reason }}</p>
            <p class="muted">الحالة: {{ $refund->status }} @if ($refund->reviewer) · راجعها {{ $refund->reviewer->name }} @endif</p>
            @if (in_array($refund->status, ['pending', 'approved'], true))
                <form method="POST" action="{{ route('refunds.update', $refund) }}">
                    @csrf
                    @method('PATCH')
                    <label for="status-{{ $refund->id }}">الإجراء</label>
                    <select id="status-{{ $refund->id }}" name="status" required>
                        @if ($refund->status === 'pending')
                            <option value="approved">موافقة (سيظل رد المبلغ يدويًا)</option>
                            <option value="rejected">رفض</option>
                        @else
                            <option value="completed">تأكيد إتمام رد المبلغ يدويًا</option>
                        @endif
                    </select>
                    <textarea name="admin_note" maxlength="2000" placeholder="ملاحظة للطالب (اختياري)"></textarea>
                    <button type="submit">حفظ القرار</button>
                </form>
            @endif
        </article>
    @empty
        <p class="muted">لا توجد طلبات استرداد.</p>
    @endforelse
    {{ $requests->links() }}
</main>
</body>
</html>
