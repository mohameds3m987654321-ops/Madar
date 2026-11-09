<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كوبونات الخصم | {{ $course->title }}</title>
    @include('components.assistant-assets')
    <style>
        body{margin:0;background:#0c1513;color:#e7f0ed;font:16px Tahoma,Arial,sans-serif;line-height:1.7}main{width:min(900px,calc(100% - 28px));margin:28px auto}a{color:#3cc3a6;text-decoration:none}.card{margin:16px 0;padding:20px;border:1px solid #243732;border-radius:14px;background:#13201d}h1,h2{margin-top:0}form.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}label{display:grid;gap:5px}input,select,button{padding:10px;border:1px solid #31433e;border-radius:8px;background:#101b19;color:inherit;font:inherit}button{cursor:pointer}.submit{grid-column:1/-1;background:#0f8a73}.error{color:#ff9d91}.coupon{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid #243732;padding:13px 0}.muted{color:#8aa29b}.disabled{color:#ff9d91}@media(max-width:600px){form.grid{grid-template-columns:1fr}.coupon{align-items:flex-start;flex-direction:column}}
    </style>
    @include('components.design-system')
</head>
<body>
<main>
    <a href="{{ route('courses.edit', $course) }}">العودة إلى إدارة الكورس</a>
    <h1>كوبونات الخصم · {{ $course->title }}</h1>
    @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
    <section class="card">
        <h2>إنشاء كوبون</h2>
        <form class="grid" method="POST" action="{{ route('courses.coupons.store', $course) }}">
            @csrf
            <label>الكود<input name="code" value="{{ old('code') }}" maxlength="48" pattern="[A-Za-z0-9_-]+" required></label>
            <label>نوع الخصم
                <select name="type" required>
                    <option value="percentage" @selected(old('type') === 'percentage')>نسبة مئوية</option>
                    <option value="fixed" @selected(old('type') === 'fixed')>مبلغ بالجنيه</option>
                </select>
            </label>
            <label>قيمة الخصم<input name="value" type="number" min="0.01" step="0.01" value="{{ old('value') }}" required></label>
            <label>أقصى عدد استخدامات (اختياري)<input name="max_redemptions" type="number" min="1" value="{{ old('max_redemptions') }}"></label>
            <label>يبدأ في (اختياري)<input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}"></label>
            <label>ينتهي في (اختياري)<input name="expires_at" type="datetime-local" value="{{ old('expires_at') }}"></label>
            @foreach (['code', 'type', 'value', 'max_redemptions', 'starts_at', 'expires_at'] as $field)
                @error($field)<span class="error">{{ $message }}</span>@enderror
            @endforeach
            <button class="submit" type="submit">إنشاء الكوبون</button>
        </form>
    </section>
    <section class="card">
        <h2>الكوبونات الحالية</h2>
        @forelse ($coupons as $coupon)
            <article class="coupon">
                <div>
                    <strong>{{ $coupon->code }}</strong>
                    <div class="muted">
                        {{ $coupon->type === 'percentage' ? $coupon->value . '%' : number_format((float) $coupon->value, 2) . ' جنيه' }}
                        · الاستخدامات {{ $coupon->completed_uses }}{{ $coupon->max_redemptions ? ' / ' . $coupon->max_redemptions : '' }}
                        @if ($coupon->expires_at) · ينتهي {{ $coupon->expires_at->format('Y-m-d H:i') }} @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('courses.coupons.toggle', [$course, $coupon]) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="{{ $coupon->is_active ? '' : 'disabled' }}">
                        {{ $coupon->is_active ? 'إيقاف الكوبون' : 'إعادة تفعيل الكوبون' }}
                    </button>
                </form>
            </article>
        @empty
            <p class="muted">لا توجد كوبونات لهذا الكورس.</p>
        @endforelse
    </section>
</main>
</body>
</html>
