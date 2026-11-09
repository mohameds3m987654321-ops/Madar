<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المستخدمون</title>
    <link rel="stylesheet" href="{{ asset('css/global-search.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f3f6f5;--card:#fff;--ink:#0f2622;--mut:#64807a;--line:#e1eae7;
            --acc:#0f6b5c;--acc-soft:#e2f3ef;--blue:#2459d6;--blue-soft:#e5ecfc;
            --amber:#a85d00;--amber-soft:#fdf0dc;--dark:#14302b;--err:#b3261e;
            --shadow:0 1px 2px rgba(15,38,34,.05),0 8px 24px rgba(15,38,34,.06);
        }
        @media (prefers-color-scheme:dark){:root{
            --bg:#0c1715;--card:#13221f;--ink:#e7f2ef;--mut:#8aa6a0;--line:#24403a;
            --acc:#3fc2a8;--acc-soft:#16332d;--blue:#8fb0ff;--blue-soft:#182a52;
            --amber:#f2b45c;--amber-soft:#3a2a10;--dark:#e7f2ef;--err:#ff8a80;--shadow:none;
        }}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.6}
        main{max-width:1100px;margin:0 auto;padding:36px 20px 56px}
        :focus-visible{outline:3px solid var(--acc);outline-offset:2px}

        .head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:28px}
        h1{font-size:1.9rem;margin:0;letter-spacing:-.3px}
        .sub{margin:2px 0 0;color:var(--mut)}
        .btn{display:inline-flex;align-items:center;gap:6px;font:inherit;font-weight:500;cursor:pointer;border-radius:10px;padding:9px 18px;text-decoration:none;border:1px solid var(--acc);background:var(--acc);color:#fff}
        @media (prefers-color-scheme:dark){.btn{color:#062019}}
        .btn.ghost{background:transparent;color:var(--acc)}
        .btn.sm{padding:6px 14px;font-size:.88rem}

        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
        .stat{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px 20px;box-shadow:var(--shadow)}
        .stat small{color:var(--mut);display:block}
        .stat strong{display:block;font-size:2rem;line-height:1.2;margin-top:4px}
        .stat i{display:block;height:4px;width:36px;border-radius:4px;margin-top:10px}

        .panel{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow);overflow:hidden}
        .toolbar{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--line)}
        .tabs{display:flex;gap:4px;background:var(--bg);padding:4px;border-radius:12px}
        .tabs a{padding:6px 16px;border-radius:9px;text-decoration:none;color:var(--mut);font-size:.92rem}
        .tabs a.on{background:var(--card);color:var(--ink);font-weight:700;box-shadow:0 1px 3px rgba(0,0,0,.1)}
        .search{display:flex;gap:8px;flex:1;max-width:340px;min-width:200px}
        .search input{flex:1;font:inherit;padding:8px 14px;border:1px solid var(--line);border-radius:10px;background:var(--bg);color:var(--ink)}
        .search-error{color:var(--err);margin:8px 0 0;font-size:.85rem}

        table{width:100%;border-collapse:collapse}
        th{padding:12px 20px;text-align:right;color:var(--mut);font-weight:500;font-size:.85rem;background:var(--bg)}
        td{padding:14px 20px;border-top:1px solid var(--line);vertical-align:middle}
        tbody tr:hover{background:var(--bg)}
        .user{display:flex;align-items:center;gap:14px}
        .av{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;font-weight:700;flex:none;background:var(--acc-soft);color:var(--acc)}
        .av.teacher{background:var(--blue-soft);color:var(--blue)}
        .av.admin{background:var(--amber-soft);color:var(--amber)}
        .nm{font-weight:700}
        .em{color:var(--mut);font-size:.85rem;direction:ltr;text-align:right}
        .badge{display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:99px;font-size:.84rem;font-weight:500;background:var(--acc-soft);color:var(--acc)}
        .badge::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
        .badge.teacher{background:var(--blue-soft);color:var(--blue)}
        .badge.admin{background:var(--amber-soft);color:var(--amber)}
        .badge.blocked{margin-top:5px;background:#fbeaea;color:var(--err)}
        .date{color:var(--mut);font-size:.9rem}
        .act{text-align:left;white-space:nowrap}
        .lock{color:var(--mut);font-size:.85rem}

        .alert{padding:12px 18px;border-radius:12px;margin-bottom:18px;background:var(--acc-soft);color:var(--acc)}
        .empty{padding:56px 20px;text-align:center;color:var(--mut)}
        .pages{padding:16px 20px;border-top:1px solid var(--line)}

        @media (max-width:820px){
            .stats{grid-template-columns:repeat(2,1fr)}
            thead{display:none}
            table,tbody,tr,td{display:block;width:100%}
            tr{padding:14px 20px;border-top:1px solid var(--line)}
            td{border:0;padding:4px 0}
            .act{text-align:right;margin-top:8px}
            .date{display:none}
        }
    </style>
    @include('components.appearance')
</head>
<body class="admin-management">
@include('components.management-navigation')
<main>
    <div class="head">
        <div>
            <h1>المستخدمون</h1>
            <p class="sub">إدارة الطلاب والمدرّسين وصلاحياتهم.</p>
        </div>
        <a class="btn" href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
    </div>

    @include('components.global-search')

    @if (session('success'))
        <div class="alert" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert" role="alert">{{ session('error') }}</div>
    @endif

    <section class="stats" aria-label="إحصائيات">
        <div class="stat"><small>إجمالي المستخدمين</small><strong>{{ $stats['total'] }}</strong><i style="background:var(--dark)"></i></div>
        <div class="stat"><small>الطلاب</small><strong>{{ $stats['student'] }}</strong><i style="background:var(--acc)"></i></div>
        <div class="stat"><small>المدرّسون</small><strong>{{ $stats['teacher'] }}</strong><i style="background:var(--blue)"></i></div>
        <div class="stat"><small>المديرون</small><strong>{{ $stats['admin'] }}</strong><i style="background:var(--amber)"></i></div>
    </section>

    @php
        $labels = ['student' => 'طالب', 'teacher' => 'مدرّس', 'admin' => 'مدير'];
        $role = request('role');
    @endphp

    <div class="panel">
        <div class="toolbar">
            <nav class="tabs" aria-label="تصفية حسب الصلاحية">
                <a href="{{ route('users.index', array_filter(['q' => request('q')])) }}" class="{{ !$role ? 'on' : '' }}">الكل</a>
                @foreach ($labels as $key => $label)
                    <a href="{{ route('users.index', array_filter(['role' => $key, 'q' => request('q')])) }}"
                       class="{{ $role === $key ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <form class="search" method="GET" action="{{ route('users.index') }}">
                @if ($role) <input type="hidden" name="role" value="{{ $role }}"> @endif
                <input type="search" name="q" value="{{ request('q') }}" maxlength="255"
                       placeholder="ابحث بالاسم أو البريد" aria-label="بحث" @error('q') aria-invalid="true" @enderror>
                <button class="btn sm" type="submit">بحث</button>
            </form>
            @error('q') <p class="search-error" role="alert">{{ $message }}</p> @enderror
            @error('role') <p class="search-error" role="alert">{{ $message }}</p> @enderror
        </div>

        @if ($users->count())
            <table>
                <thead>
                    <tr><th>المستخدم</th><th>الصلاحية</th><th>تاريخ الانضمام</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="user">
                                <div class="av {{ $user->role }}" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</div>
                                <div>
                                    <div class="nm">{{ $user->name }}</div>
                                    <div class="em">{{ $user->email }}</div>
                                    @if ($user->login_locked_at)
                                        <span class="badge blocked">محظور من الدخول</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><span class="badge {{ $user->role }}">{{ $labels[$user->role] ?? $user->role }}</span></td>
                        <td class="date">{{ $user->created_at?->format('Y/m/d') }}</td>
                        <td class="act">
                            @if ($user->login_locked_at)
                                <form method="POST" action="{{ route('users.login-unlock', $user) }}" style="display:inline"
                                      onsubmit="return confirm('رفع حظر تسجيل الدخول عن {{ $user->name }}؟')">
                                    @csrf
                                    <button class="btn sm" type="submit">رفع الحظر</button>
                                </form>
                            @endif
                            @if ($user->role === 'student')
                                <form method="POST" action="{{ route('make-teacher', $user->id) }}" style="display:inline"
                                      onsubmit="return confirm('تعيين {{ $user->name }} كمدرّس؟')">
                                    @csrf
                                    <button class="btn sm" type="submit">تعيين كمدرّس</button>
                                </form>
                            @elseif ($user->role === 'teacher')
                                <form method="POST" action="{{ route('users.makeStudent', $user->id) }}" style="display:inline"
                                      onsubmit="return confirm('إرجاع {{ $user->name }} إلى طالب؟')">
                                    @csrf
                                    <button class="btn sm ghost" type="submit">إرجاع إلى طالب</button>
                                </form>
                            @else
                                <span class="lock">محمي</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">لا يوجد مستخدمون مطابقون. جرّب تغيير البحث أو التصفية.</div>
        @endif

        @if ($users->hasPages())
            <div class="pages">{{ $users->links() }}</div>
        @endif
    </div>
</main>
</body>
</html>
