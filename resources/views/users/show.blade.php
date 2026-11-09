<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغيير صلاحية المستخدم</title>
    <style>
        :root{--bg:#eef2f1;--card:#fff;--ink:#14302b;--mut:#5d7570;--line:#d5e0dd;--acc:#0f6b5c;--acc2:#e3f2ee}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:16px;background:var(--bg);color:var(--ink);font-family:Tahoma,Arial,sans-serif}
        .card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:28px}
        h1{font-size:1.3rem;margin:0 0 20px}
        dl{margin:0 0 24px}
        dt{color:var(--mut);font-size:.85rem}
        dd{margin:0 0 12px;font-weight:700}
        dd.mail{direction:ltr;text-align:right;font-weight:400}
        .badge{display:inline-block;padding:2px 12px;border-radius:99px;font-size:.85rem;font-weight:400;background:var(--bg);color:var(--mut)}
        .badge.teacher{background:var(--acc2);color:var(--acc);font-weight:700}
        button{width:100%;font:inherit;cursor:pointer;border-radius:8px;padding:12px;border:1px solid var(--acc);background:var(--acc);color:#fff}
        button:focus-visible{outline:3px solid var(--acc);outline-offset:2px}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;background:var(--acc2);color:var(--acc)}
        .note{margin:0;text-align:center;color:var(--mut)}
    </style>
    @include('components.appearance')
</head>
<body>
<div class="card">
    <h1>تغيير صلاحية المستخدم</h1>

    @if (session('success'))
        <div class="alert" role="status">{{ session('success') }}</div>
    @endif

    <dl>
        <dt>الاسم</dt>
        
        <dd>{{ $user->name }}</dd>

        <dt>البريد الإلكتروني</dt>
        <dd class="mail">{{ $user->email }}</dd>

        <dt>الصلاحية الحالية</dt>
        <dd>
            <span class="badge {{ $user->role }}">
                {{ ['student' => 'طالب', 'teacher' => 'مدرّس', 'admin' => 'مدير'][$user->role] ?? $user->role }}
            </span>
        </dd>
    </dl>

    @if ($user->role === 'student')
        <form method="POST" action="{{ route('make-teacher', $user->id) }}"
              onsubmit="return confirm('تعيين {{ $user->name }} كمدرّس؟')">
            @csrf
            <button type="submit">تعيين كمدرّس</button>
        </form>
    @else
        <p class="note">هذا المستخدم ليس طالبًا، فلا يمكن تعيينه كمدرّس من هنا.</p>
    @endif
</div>
</body>
</html>
