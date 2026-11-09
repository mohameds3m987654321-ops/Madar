<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة كورس</title>
    <link rel="stylesheet" href="{{ asset('css/media-upload-progress.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f1f5f4;--card:#fff;--ink:#0f2622;--mut:#64807a;--line:#e0e9e6;--field:#f7faf9;
            --acc:#0f6b5c;--acc2:#e2f3ef;--err:#b3261e;--err2:#fdecea;
            --shadow:0 1px 2px rgba(15,38,34,.06),0 10px 30px rgba(15,38,34,.07);
        }
        @media (prefers-color-scheme:dark){:root{
            --bg:#0b1614;--card:#13221f;--ink:#e7f2ef;--mut:#8aa6a0;--line:#24403a;--field:#0f1d1a;
            --acc:#3fc2a8;--acc2:#16332d;--err:#ff8a80;--err2:#3a1a18;--shadow:none;
        }}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.6}
        main{max-width:1060px;margin:0 auto;padding:36px 20px 60px}
        :focus-visible{outline:3px solid var(--acc);outline-offset:2px}
        svg{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

        .hero{padding:26px 32px;margin-bottom:24px;border-radius:22px;color:#fff;
              background:radial-gradient(900px 300px at 100% 0,rgba(255,255,255,.18),transparent 60%),linear-gradient(135deg,#0f6b5c,#0a3f4a 70%,#14305a)}
        .hero h1{margin:0;font-size:1.9rem;letter-spacing:-.3px}
        .hero p{margin:4px 0 0;opacity:.85}

        .alert{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:14px;margin-bottom:20px;background:var(--acc2);color:var(--acc)}
        .layout{display:grid;grid-template-columns:1.25fr 1fr;gap:24px;align-items:start}

        form{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:28px;box-shadow:var(--shadow)}
        .field{margin-bottom:20px}
        label.l{display:block;font-weight:700;margin-bottom:8px}
        .req{color:var(--err)}
        input[type=text],input[type=number],textarea,select{width:100%;font:inherit;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:var(--field);color:var(--ink);transition:border-color .15s,box-shadow .15s}
        input:focus,textarea:focus,select:focus{outline:0;border-color:var(--acc);box-shadow:0 0 0 4px var(--acc2)}
        textarea{min-height:130px;resize:vertical}
        .invalid{border-color:var(--err)!important}
        .error{color:var(--err);font-size:.85rem;margin-top:6px}
        .hint{color:var(--mut);font-size:.82rem;margin-top:6px;display:flex;justify-content:space-between}
        .price-wrap{position:relative}
        .price-wrap span{position:absolute;top:50%;inset-inline-end:14px;transform:translateY(-50%);color:var(--mut);pointer-events:none}
        .price-wrap input{padding-inline-end:56px}

        .drop{position:relative;display:flex;flex-direction:column;align-items:center;gap:6px;padding:26px 16px;text-align:center;border:2px dashed var(--line);border-radius:16px;background:var(--field);color:var(--mut);cursor:pointer;transition:border-color .15s,background .15s}
        .drop:hover,.drop.over,.drop:focus-within{border-color:var(--acc);background:var(--acc2);color:var(--acc)}
        .drop svg{width:30px;height:30px}
        .drop input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
        .drop b{color:var(--ink)}

        .actions{display:flex;gap:12px;margin-top:6px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font:inherit;font-weight:500;cursor:pointer;border-radius:12px;padding:12px 24px;text-decoration:none;border:1px solid transparent;transition:transform .15s}
        .btn:hover{transform:translateY(-1px)}
        .btn.primary{background:var(--acc);color:#fff;flex:1}
        @media (prefers-color-scheme:dark){.btn.primary{color:#062019}}
        .btn.ghost{background:transparent;border-color:var(--line);color:var(--ink)}

        .side{position:sticky;top:20px}
        .side h2{font-size:.95rem;color:var(--mut);font-weight:500;margin:0 0 10px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:20px;overflow:hidden;box-shadow:var(--shadow)}
        .cover{position:relative;aspect-ratio:16/10;overflow:hidden;background:linear-gradient(135deg,#0f6b5c,#0a3f4a)}
        .cover img{width:100%;height:100%;object-fit:cover;display:none}
        .cover.has img{display:block}
        .ph{position:absolute;inset:0;display:grid;place-items:center;font-size:3.6rem;font-weight:700;color:rgba(255,255,255,.9)}
        .cover.has .ph{display:none}
        .price{position:absolute;bottom:12px;inset-inline-end:12px;padding:4px 14px;border-radius:12px;font-weight:700;background:rgba(10,25,22,.78);color:#fff}
        .body{padding:18px 20px 22px}
        .t{margin:0 0 6px;font-size:1.12rem;font-weight:700;word-break:break-word}
        .d{margin:0;color:var(--mut);font-size:.9rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word}

        @media (max-width:880px){.layout{grid-template-columns:1fr}.side{position:static;order:-1}}
        @media (prefers-reduced-motion:reduce){*{transition:none!important}}
    </style>
    @include('components.appearance')
</head>
<body class="teacher-management">
@include('components.management-navigation')
<main>
    <header class="hero">
        <h1>إضافة كورس</h1>
        <p>املأ بيانات الكورس ثم احفظه، وشاهد شكله مباشرة قبل النشر.</p>
    </header>

    @if (session('success'))
        <div class="alert" role="status">
            <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="layout">
        <form method="POST" action="{{ route('courses.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Title --}}
            <div class="field">
                <label class="l" for="title">اسم الكورس <span class="req">*</span></label>
                <input id="title" name="title" type="text" maxlength="255" value="{{ old('title') }}"
                       class="@error('title') invalid @enderror" placeholder="مثال: أساسيات Laravel" required>
                @error('title') <div class="error">{{ $message }}</div> @enderror
            </div>

            {{-- Description --}}
            <div class="field">
                <label class="l" for="description">وصف الكورس <span class="req">*</span></label>
                <textarea id="description" name="description" maxlength="1000"
                          class="@error('description') invalid @enderror"
                          placeholder="اكتب نبذة عن محتوى الكورس وما سيتعلمه الطالب" required>{{ old('description') }}</textarea>
                <div class="hint"><span>وصف واضح يزيد من إقبال الطلاب.</span><span><b id="cnt">0</b>/1000</span></div>
                @error('description') <div class="error">{{ $message }}</div> @enderror
            </div>

            {{-- Price --}}
            <div class="field">
                <label class="l" for="price">السعر <span class="req">*</span></label>
                <div class="price-wrap">
                    <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price', 0) }}"
                           class="@error('price') invalid @enderror" required>
                    <span>ج.م</span>
                </div>
                <div class="hint"><span>اكتب 0 لو الكورس مجاني.</span></div>
                @error('price') <div class="error">{{ $message }}</div> @enderror
            </div>

            {{-- Education targeting --}}
            <div class="field">
                <label class="l" for="education_stage">المرحلة التعليمية</label>
                <select id="education_stage" name="education_stage">
                    <option value="" @selected(old('education_stage') === '')>مخصصة لكل الطلاب</option>
                    <option value="preparatory" @selected(old('education_stage') === 'preparatory')>الإعدادية</option>
                    <option value="secondary" @selected(old('education_stage') === 'secondary')>الثانوية العامة</option>
                </select>
                @error('education_stage') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field" id="educationGradeField" hidden>
                <label class="l" for="education_grade">الصف الدراسي <span class="req">*</span></label>
                <select id="education_grade" name="education_grade">
                    <option value="">اختر الصف الدراسي</option>
                </select>
                @error('education_grade') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label class="l" for="education_type">نوع التعليم <span class="req">*</span></label>
                <select id="education_type" name="education_type">
                    <option value="">اختر نوع التعليم</option>
                    <option value="general" @selected(old('education_type') === 'general')>عام</option>
                    <option value="azhar" @selected(old('education_type') === 'azhar')>أزهري</option>
                </select>
                @error('education_type') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field" id="educationSystemField" hidden>
                <label class="l" for="education_system">نظام الثانوية <span class="req">*</span></label>
                <select id="education_system" name="education_system">
                    <option value="">اختر نظام الثانوية</option>
                    <option value="baccalaureate" @selected(old('education_system') === 'baccalaureate')>بكالوريا</option>
                    <option value="old" @selected(old('education_system') === 'old')>نظام قديم</option>
                </select>
                @error('education_system') <div class="error">{{ $message }}</div> @enderror
            </div>

            {{-- Image --}}
            <div class="field">
                <label class="l" for="image">صورة الكورس</label>
                <div class="drop" id="drop">
                    <svg viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                    <span><b>اضغط لاختيار صورة</b> أو اسحبها هنا</span>
                    <small id="fname">PNG أو JPG حتى 2 ميجا</small>
                    <input id="image" name="image" type="file" accept="image/*">
                </div>
                @error('image') <div class="error">{{ $message }}</div> @enderror
            </div>

            @include('components.upload-progress')

            <div class="actions">
                <button type="submit" class="btn primary">
                    <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    حفظ الكورس
                </button>
                <a class="btn ghost" href="{{ route('courses.index') }}">إلغاء</a>
            </div>
        </form>

        {{-- Live preview --}}
        <aside class="side" aria-label="معاينة مباشرة">
            <h2>معاينة مباشرة</h2>
            <div class="card">
                <div class="cover" id="cover">
                    <img id="pimg" alt="">
                    <div class="ph" id="pph">ك</div>
                    <span class="price" id="pprice">مجاني</span>
                </div>
                <div class="body">
                    <h3 class="t" id="ptitle">اسم الكورس</h3>
                    <p class="d" id="pdesc">سيظهر وصف الكورس هنا.</p>
                </div>
            </div>
        </aside>
    </div>
</main>

<script>
    const $ = id => document.getElementById(id);
    const title = $('title'), desc = $('description'), price = $('price'), file = $('image'), drop = $('drop');

    function sync() {
        const t = title.value.trim();
        $('ptitle').textContent = t || 'اسم الكورس';
        $('pph').textContent = (t || 'ك').charAt(0);
        $('pdesc').textContent = desc.value.trim() || 'سيظهر وصف الكورس هنا.';
        $('cnt').textContent = desc.value.length;
        const p = parseFloat(price.value);
        $('pprice').textContent = p > 0 ? p.toLocaleString('ar-EG') + ' ج.م' : 'مجاني';
    }

    file.addEventListener('change', () => {
        const f = file.files[0];
        if (!f) { $('cover').classList.remove('has'); $('fname').textContent = 'PNG أو JPG حتى 2 ميجا'; return; }
        $('pimg').src = URL.createObjectURL(f);
        $('cover').classList.add('has');
        $('fname').textContent = f.name;
    });

    const educationStage = $('education_stage');
    const educationGrade = $('education_grade');
    const educationType = $('education_type');
    const educationSystem = $('education_system');
    const gradeField = $('educationGradeField');
    const systemField = $('educationSystemField');
    const previousGrade = @json(old('education_grade', ''));

    function updateEducationOptions() {
        const stage = educationStage.value;
        const oldGrade = educationGrade.value || previousGrade;
        const gradeNames = stage === 'secondary'
            ? ['الصف الأول الثانوي', 'الصف الثاني الثانوي', 'الصف الثالث الثانوي']
            : ['الصف الأول الإعدادي', 'الصف الثاني الإعدادي', 'الصف الثالث الإعدادي'];

        educationGrade.replaceChildren(new Option('اختر الصف الدراسي', ''));
        gradeNames.forEach((label, index) => {
            educationGrade.add(new Option(label, String(index + 1), false, oldGrade === String(index + 1)));
        });
        educationGrade.disabled = stage === '';
        gradeField.hidden = stage === '';

        educationSystem.disabled = stage !== 'secondary';
        systemField.hidden = stage !== 'secondary';
        if (stage !== 'secondary') {
            educationSystem.value = '';
        }
    }

    educationStage.addEventListener('change', updateEducationOptions);
    updateEducationOptions();

    ['dragenter', 'dragover'].forEach(e => drop.addEventListener(e, () => drop.classList.add('over')));
    ['dragleave', 'drop'].forEach(e => drop.addEventListener(e, () => drop.classList.remove('over')));

    [title, desc, price].forEach(el => el.addEventListener('input', sync));
    sync();
</script>
<script src="{{ asset('js/media-upload-progress.js') }}" defer></script>
</body>
</html>
