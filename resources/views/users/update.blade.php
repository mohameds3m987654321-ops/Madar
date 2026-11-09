<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المستخدم</title>
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f4f7f5;--card:#fff;--ink:#172b28;--mut:#657a75;--line:#e1eae5;--field:#f7faf9;
            --acc:#147866;--acc2:#e2f3ed;--err:#b3261e;--err2:#fdecea;
            --student:#147866;--teacher:#3966bd;--admin:#d38c19;
            --shadow:0 1px 2px rgba(15,38,34,.06),0 10px 30px rgba(15,38,34,.07);
        }
        @media (prefers-color-scheme:dark){:root{
            --bg:#101a17;--card:#182521;--ink:#e4efea;--mut:#a3b7ae;--line:#2b4038;--field:#13211d;
            --acc:#69d2ae;--acc2:#203a32;--err:#ff8a80;--err2:#3a1a18;
            --student:#69d2ae;--teacher:#8fb0ff;--admin:#f2b45c;--shadow:none;
        }}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.6}
        main{max-width:1060px;margin:0 auto;padding:36px 20px 60px}
        :focus-visible{outline:3px solid var(--acc);outline-offset:2px}
        svg{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

        .hero{padding:26px 32px;margin-bottom:24px;border-radius:22px;color:#fff;
              background:radial-gradient(900px 300px at 100% 0,rgba(255,255,255,.18),transparent 60%),var(--green-hero)}
        .hero h1{margin:0;font-size:1.9rem;letter-spacing:-.3px}
        .hero p{margin:4px 0 0;opacity:.85}
        .alert{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:14px;margin-bottom:20px;background:var(--acc2);color:var(--acc)}
        .alert.err{background:var(--err2);color:var(--err)}

        .layout{display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start}
        .panel{background:var(--card);border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow)}

        /* profile card */
        .profile{position:sticky;top:20px;overflow:hidden;text-align:center;--c:var(--student)}
        .profile[data-role=teacher]{--c:var(--teacher)}
        .profile[data-role=admin]{--c:var(--admin)}
        .banner{height:92px;background:linear-gradient(135deg,var(--c),color-mix(in srgb,var(--c) 40%,#0a1f2a));transition:background .3s}
        .avatar{width:96px;height:96px;margin:-48px auto 0;border-radius:50%;display:grid;place-items:center;font-size:2.4rem;font-weight:700;
                color:var(--c);background:var(--card);border:5px solid var(--card);box-shadow:0 8px 24px rgba(0,0,0,.18);transition:color .3s}
        .profile h2{margin:12px 16px 0;font-size:1.25rem;word-break:break-word}
        .profile .em{margin:2px 16px 0;color:var(--mut);font-size:.9rem;direction:ltr;word-break:break-all}
        .rb{display:inline-flex;align-items:center;gap:6px;margin:14px 0;padding:4px 16px;border-radius:99px;font-weight:500;font-size:.9rem;color:var(--c);background:color-mix(in srgb,var(--c) 14%,transparent)}
        .rb::before{content:"";width:8px;height:8px;border-radius:50%;background:currentColor}
        .facts{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid var(--line)}
        .facts div{padding:14px 8px}
        .facts div+div{border-inline-start:1px solid var(--line)}
        .facts small{display:block;color:var(--mut);font-size:.78rem}
        .facts b{font-size:.92rem}

        /* form */
        form.panel{padding:28px}
        fieldset{border:0;padding:0;margin:0 0 26px}
        legend{font-weight:700;font-size:1.05rem;margin-bottom:14px;padding:0}
        .field{margin-bottom:18px}
        label.l{display:block;font-weight:500;margin-bottom:7px}
        .iw{position:relative}
        .iw>svg{position:absolute;top:50%;inset-inline-start:14px;transform:translateY(-50%);color:var(--mut);pointer-events:none}
        input[type=text],input[type=email],input[type=password]{width:100%;font:inherit;padding:12px 14px;padding-inline-start:44px;border:1px solid var(--line);border-radius:12px;background:var(--field);color:var(--ink);transition:border-color .15s,box-shadow .15s}
        input:focus{outline:0;border-color:var(--acc);box-shadow:0 0 0 4px var(--acc2)}
        .invalid{border-color:var(--err)!important}
        .error{color:var(--err);font-size:.85rem;margin-top:6px}
        .hint{color:var(--mut);font-size:.82rem;margin-top:6px}
        .eye{position:absolute;top:50%;inset-inline-end:8px;transform:translateY(-50%);border:0;background:transparent;color:var(--mut);cursor:pointer;padding:6px;border-radius:8px}
        .eye:hover{color:var(--ink)}
        .iw .eye+*{display:none}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}

        /* role cards */
        .roles{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
        .roles input{position:absolute;opacity:0;pointer-events:none}
        .roles label{display:flex;flex-direction:column;gap:4px;padding:16px;border:2px solid var(--line);border-radius:16px;background:var(--field);cursor:pointer;transition:transform .15s,border-color .15s,background .15s;position:relative}
        .roles label:hover{transform:translateY(-2px)}
        .roles label svg{width:26px;height:26px;margin-bottom:4px}
        .roles label b{font-size:1rem}
        .roles label span{color:var(--mut);font-size:.8rem;line-height:1.4}
        .roles .r-student{--c:var(--student)}.roles .r-teacher{--c:var(--teacher)}.roles .r-admin{--c:var(--admin)}
        .roles label svg{color:var(--c)}
        .roles input:checked+label{border-color:var(--c);background:color-mix(in srgb,var(--c) 10%,var(--card))}
        .roles input:checked+label::after{content:"✓";position:absolute;top:10px;inset-inline-end:12px;width:22px;height:22px;display:grid;place-items:center;border-radius:50%;background:var(--c);color:#fff;font-size:.8rem}
        .roles input:focus-visible+label{outline:3px solid var(--acc);outline-offset:2px}
        .roles input:disabled+label{opacity:.55;cursor:not-allowed}
        .roles input:disabled+label:hover{transform:none}

        /* strength */
        .meter{display:flex;gap:6px;margin-top:10px}
        .meter i{flex:1;height:5px;border-radius:5px;background:var(--line);transition:background .2s}
        .meter[data-s="1"] i:nth-child(-n+1){background:#d9382c}
        .meter[data-s="2"] i:nth-child(-n+2){background:#e08a00}
        .meter[data-s="3"] i:nth-child(-n+3){background:#7ab800}
        .meter[data-s="4"] i{background:#0b9d58}
        #slabel{font-size:.82rem;color:var(--mut);margin-top:6px;min-height:1.4em}

        .actions{display:flex;gap:12px;padding-top:6px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font:inherit;font-weight:500;cursor:pointer;border-radius:12px;padding:12px 24px;text-decoration:none;border:1px solid transparent;transition:transform .15s}
        .btn:hover{transform:translateY(-1px)}
        .btn.primary{background:var(--acc);color:#fff;flex:1}
        @media (prefers-color-scheme:dark){.btn.primary{color:#062019}}
        .btn.ghost{background:transparent;border-color:var(--line);color:var(--ink)}

        @media (max-width:900px){.layout{grid-template-columns:1fr}.profile{position:static}}
        @media (max-width:560px){.roles,.grid2{grid-template-columns:1fr}.hero{padding:22px}}
        @media (prefers-reduced-motion:reduce){*{transition:none!important}}
    </style>
    @include('components.appearance')
</head>
<body class="admin-management">
@include('components.management-navigation')
@php
    $labels = ['student' => 'طالب', 'teacher' => 'مدرّس', 'admin' => 'مدير'];
    $current = old('role', $user->role);
    $self = auth()->id() === $user->id;
@endphp
<main>
    <header class="hero">
        <h1>تعديل المستخدم</h1>
        <p>حدّث بيانات الحساب والصلاحية، وشاهد التغييرات مباشرة في بطاقة الملف الشخصي.</p>
    </header>

    @if (session('success'))
        <div class="alert" role="status"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err" role="alert"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>تحقق من الحقول المظللة وأعد المحاولة.</div>
    @endif

    <div class="layout">
        {{-- Live profile --}}
        <aside class="panel profile" id="profile" data-role="{{ $current }}" aria-label="معاينة الملف الشخصي">
            <div class="banner"></div>
            <div class="avatar" id="pav">{{ mb_substr(old('name', $user->name), 0, 1) }}</div>
            <h2 id="pname">{{ old('name', $user->name) }}</h2>
            <div class="em" id="pmail">{{ old('email', $user->email) }}</div>
            <span class="rb" id="prole">{{ $labels[$current] ?? $current }}</span>
            <div class="facts">
                <div><small>رقم المستخدم</small><b>#{{ $user->id }}</b></div>
                <div><small>تاريخ الانضمام</small><b>{{ $user->created_at?->format('Y/m/d') }}</b></div>
            </div>
        </aside>

        {{-- Form --}}
        <form class="panel" method="POST" action="{{ route('users.update', $user->id) }}" novalidate>
    @csrf
    @method('PUT')

            <fieldset>
                <legend>البيانات الأساسية</legend>
                <div class="grid2">
                    <div class="field">
                        <label class="l" for="name">الاسم</label>
                        <div class="iw">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="@error('name') invalid @enderror" required>
                        </div>
                        @error('name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label class="l" for="email">البريد الإلكتروني</label>
                        <div class="iw">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <input id="email" name="email" type="email" dir="ltr" value="{{ old('email', $user->email) }}" class="@error('email') invalid @enderror" style="text-align:right" required>
                        </div>
                        @error('email') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>الصلاحية</legend>
                <div class="roles" role="radiogroup" aria-label="الصلاحية">
                    @if ($self) <input type="hidden" name="role" value="{{ $user->role }}"> @endif

                    <input type="radio" id="r-student" name="role" value="student" @checked($current === 'student') @disabled($self)>
                    <label class="r-student" for="r-student">
                        <svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                        <b>طالب</b><span>يتصفح الكورسات ويشتريها.</span>
                    </label>

                    <input type="radio" id="r-teacher" name="role" value="teacher" @checked($current === 'teacher') @disabled($self)>
                    <label class="r-teacher" for="r-teacher">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                        <b>مدرّس</b><span>يضيف الكورسات ويعدّلها.</span>
                    </label>

                    <input type="radio" id="r-admin" name="role" value="admin" @checked($current === 'admin') @disabled($self)>
                    <label class="r-admin" for="r-admin">
                        <svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg>
                        <b>مدير</b><span>تحكم كامل في المستخدمين.</span>
                    </label>
                </div>
                @if ($self) <div class="hint">لا يمكنك تغيير صلاحية حسابك الحالي حتى لا تفقد الوصول.</div> @endif
                @error('role') <div class="error">{{ $message }}</div> @enderror
            </fieldset>

            <fieldset>
                <legend>كلمة المرور</legend>
                <div class="grid2">
                    <div class="field">
                        <label class="l" for="password">كلمة مرور جديدة</label>
                        <div class="iw">
                            <svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input id="password" name="password" type="password" autocomplete="new-password" class="@error('password') invalid @enderror">
                            <button class="eye" type="button" id="eye" aria-label="إظهار كلمة المرور">
                                <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="meter" id="meter" data-s="0"><i></i><i></i><i></i><i></i></div>
                        <div id="slabel">اتركها فارغة للإبقاء على كلمة المرور الحالية.</div>
                        @error('password') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label class="l" for="password_confirmation">تأكيد كلمة المرور</label>
                        <div class="iw">
                            <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                        </div>
                        <div class="error" id="mismatch" hidden>كلمتا المرور غير متطابقتين.</div>
                    </div>
                </div>
            </fieldset>

            <div class="actions">
                <button type="submit" class="btn primary">
                    <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    حفظ التغييرات
                </button>
                <a class="btn ghost" href="{{ route('users.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
</main>

<script>
    const $ = id => document.getElementById(id);
    const labels = {student:'طالب', teacher:'مدرّس', admin:'مدير'};
    const name = $('name'), email = $('email'), pw = $('password'), pc = $('password_confirmation');

    function sync(){
        const n = name.value.trim();
        $('pname').textContent = n || '—';
        $('pav').textContent = (n || '?').charAt(0);
        $('pmail').textContent = email.value.trim() || '—';
    }
    [name, email].forEach(e => e.addEventListener('input', sync));

    document.querySelectorAll('input[name=role][type=radio]').forEach(r => r.addEventListener('change', () => {
        $('profile').dataset.role = r.value;
        $('prole').textContent = labels[r.value];
    }));

    const msgs = ['', 'ضعيفة', 'متوسطة', 'جيدة', 'قوية جدًا'];
    pw.addEventListener('input', () => {
        const v = pw.value;
        let s = 0;
        if (v.length >= 8) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v) && v.length >= 10) s++;
        if (!v) { $('meter').dataset.s = 0; $('slabel').textContent = 'اتركها فارغة للإبقاء على كلمة المرور الحالية.'; }
        else { $('meter').dataset.s = Math.max(s, 1); $('slabel').textContent = 'قوة كلمة المرور: ' + msgs[Math.max(s, 1)]; }
        check();
    });

    function check(){ $('mismatch').hidden = !pc.value || pw.value === pc.value; }
    pc.addEventListener('input', check);

    $('eye').addEventListener('click', () => {
        const show = pw.type === 'password';
        pw.type = pc.type = show ? 'text' : 'password';
        $('eye').setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
    });
</script>
</body>
</html>