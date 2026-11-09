<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الكورس | منصة مدار</title>
    <link rel="stylesheet" href="{{ asset('css/media-upload-progress.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 90% 0%, color-mix(in srgb, var(--brand) 9%, transparent), transparent 34rem),
                var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 1.7;
        }
        a { color: inherit; }
        :focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
        main { width: min(1040px, calc(100% - 36px)); margin: 0 auto; padding: 32px 0 56px; }

        .breadcrumb { margin-bottom: 8px; color: var(--muted); font-size: .88rem; }
        .breadcrumb a { color: var(--accent); text-decoration: none; }
        .heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }
        .heading-copy { min-width: 0; }
        .heading-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; }
        h1, h2, p { margin-top: 0; }
        h1 { margin-bottom: 4px; font-size: clamp(1.55rem, 4vw, 2rem); line-height: 1.4; }
        .heading p { margin-bottom: 0; color: var(--muted); }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border: 1px solid var(--brand-line);
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--accent);
            font-size: .82rem;
            white-space: nowrap;
        }

        .layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(260px, 320px); gap: 20px; align-items: start; }
        .card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }
        .form-card { min-width: 0; padding: clamp(18px, 3vw, 28px); }
        .section-heading { margin: 22px 0 16px; padding-top: 18px; border-top: 1px solid var(--line); }
        .section-heading:first-child { margin-top: 0; padding-top: 0; border-top: 0; }
        .section-heading h2 { margin-bottom: 3px; font-size: 1.1rem; }
        .section-heading p { margin-bottom: 0; color: var(--muted); font-size: .87rem; }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
        .field-grid .field { min-width: 0; }
        .field-grid .field[hidden] { display: none; }
        .field { margin-bottom: 17px; }
        label { display: block; margin-bottom: 7px; font-weight: 600; }
        .optional { color: var(--muted); font-size: .82rem; font-weight: 400; }
        input[type="text"], input[type="number"], textarea, select {
            display: block;
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--field, var(--surface-soft));
            color: var(--ink);
            font: inherit;
            padding: 11px 13px;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus, textarea:focus, select:focus { border-color: var(--accent); outline: 0; box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
        textarea { min-height: 150px; resize: vertical; }
        input::placeholder, textarea::placeholder { color: var(--muted); }
        .hint { margin-top: 5px; color: var(--muted); font-size: .8rem; }
        .error { margin-top: 5px; color: var(--danger); font-size: .83rem; }
        .invalid { border-color: var(--danger) !important; }
        .price-wrap { position: relative; }
        .price-wrap input { padding-inline-end: 52px; }
        .currency { position: absolute; top: 50%; inset-inline-end: 13px; transform: translateY(-50%); color: var(--muted); pointer-events: none; }

        .file-input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; clip-path: inset(50%); }
        .file-picker {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border: 1px dashed var(--brand-line);
            border-radius: 11px;
            background: var(--surface-soft);
        }
        .file-picker span { min-width: 0; overflow: hidden; color: var(--muted); font-size: .86rem; text-overflow: ellipsis; white-space: nowrap; }
        .file-picker label {
            flex: none;
            margin: 0;
            padding: 7px 12px;
            border: 1px solid var(--brand-line);
            border-radius: 8px;
            background: var(--brand-soft);
            color: var(--accent);
            cursor: pointer;
            font-size: .82rem;
        }

        .actions { display: flex; gap: 10px; padding-top: 18px; border-top: 1px solid var(--line); }
        .button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 9px 17px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--ink-2);
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, transform .15s;
        }
        .button:hover { transform: translateY(-1px); background: var(--surface-soft); }
        .button.primary { flex: 1; border-color: transparent; background: var(--brand); color: #fff; }
        .button.primary:hover { background: var(--brand-dark); }

        .notice { margin-bottom: 16px; padding: 11px 14px; border: 1px solid var(--brand-line); border-radius: 10px; background: var(--brand-soft); color: var(--accent); }
        .preview-cover { display: block; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; background: linear-gradient(135deg, var(--brand-dark), var(--brand)); }
        .preview-body { padding: 17px; }
        .preview-label { color: var(--accent); font-size: .78rem; font-weight: 600; }
        .preview-body h2 { margin: 5px 0 7px; font-size: 1.1rem; line-height: 1.5; overflow-wrap: anywhere; }
        .preview-body p { margin-bottom: 15px; color: var(--muted); font-size: .87rem; overflow-wrap: anywhere; }
        .preview-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 12px; border-top: 1px solid var(--line); color: var(--muted); font-size: .83rem; }
        .preview-foot strong { color: var(--accent); font-size: 1.05rem; }
        .meta { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px 10px; margin-bottom: 13px; color: var(--ink-2); font-size: .82rem; }
        .empty-cover { display: grid; place-items: center; color: rgba(255, 255, 255, .85); font-size: 2.3rem; font-weight: 700; }

        @media (max-width: 760px) {
            main { width: min(620px, calc(100% - 24px)); padding-top: 22px; }
            .layout { grid-template-columns: 1fr; }
            .heading { align-items: flex-start; flex-direction: column; }
            .heading-actions { justify-content: flex-start; }
            .preview-card { order: -1; }
        }
        @media (max-width: 480px) {
            .form-card { padding: 17px; }
            .actions .button { padding-inline: 12px; }
            .field-grid { grid-template-columns: 1fr; }
            .heading-actions { width: 100%; }
            .heading-actions .button { flex: 1; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition: none !important; } }
    </style>
    @include('components.appearance')
</head>
<body class="teacher-management">
@include('components.management-navigation')
<main>
    <div class="breadcrumb">
        <a href="{{ route('teacher.dashboard') }}">لوحة المدرس</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('courses.index') }}">الكورسات</a>
        <span aria-hidden="true"> / </span>
        <span>تعديل الكورس</span>
    </div>

    <header class="heading">
        <div class="heading-copy">
            <h1>تعديل الكورس</h1>
            <p>حدّث المعلومات الأساسية التي تظهر للطلاب.</p>
        </div>
        <div class="heading-actions">
            <span class="status">{{ $course->status === 'published' ? 'منشور' : 'مسودة' }}</span>
            <a class="button" href="{{ route('lessons.manage', $course) }}">إدارة المحاضرات</a>
            <a class="button" href="{{ route('courses.coupons.index', $course) }}">كوبونات الخصم</a>
        </div>
    </header>

    @if (session('success'))
        <div class="notice" role="status">{{ session('success') }}</div>
    @endif

    <div class="layout">
        <form class="card form-card" method="POST" action="{{ route('courses.update', $course->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="section-heading">
                <h2>بيانات الكورس</h2>
                <p>عدّل الاسم والوصف والسعر والصورة، ثم احفظ التغييرات.</p>
            </div>

            <div class="field">
                <label for="title">اسم الكورس</label>
                <input
                    id="title"
                    name="title"
                    type="text"
                    maxlength="255"
                    value="{{ old('title', $course->title) }}"
                    class="@error('title') invalid @enderror"
                    required
                >
                @error('title') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="description">وصف الكورس</label>
                <textarea
                    id="description"
                    name="description"
                    class="@error('description') invalid @enderror"
                    required
                >{{ old('description', $course->description) }}</textarea>
                @error('description') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="section-heading">
                <h2>السعر والجمهور المستهدف</h2>
                <p>حدّد سعر الكورس والفئة الدراسية المناسبة.</p>
            </div>
            <div class="field-grid">
                <div class="field">
                    <label for="price">السعر</label>
                    <div class="price-wrap">
                        <input
                            id="price"
                            name="price"
                            type="number"
                            min="0"
                            step="0.01"
                            value="{{ old('price', $course->price) }}"
                            class="@error('price') invalid @enderror"
                            required
                        >
                        <span class="currency">ج.م</span>
                    </div>
                    <div class="hint">اكتب 0 إذا كان الكورس مجانيًا.</div>
                    @error('price') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="education_stage">المرحلة التعليمية</label>
                    <select id="education_stage" name="education_stage">
                        <option value="" @selected(old('education_stage', $course->education_stage) === '')>مخصص لكل الطلاب</option>
                        <option value="preparatory" @selected(old('education_stage', $course->education_stage) === 'preparatory')>الإعدادية</option>
                        <option value="secondary" @selected(old('education_stage', $course->education_stage) === 'secondary')>الثانوية العامة</option>
                    </select>
                    @error('education_stage') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field" id="educationGradeField" hidden>
                    <label for="education_grade">الصف الدراسي</label>
                    <select id="education_grade" name="education_grade">
                        <option value="">اختر الصف الدراسي</option>
                    </select>
                    @error('education_grade') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field" id="educationTypeField" hidden>
                    <label for="education_type">نوع التعليم</label>
                    <select id="education_type" name="education_type">
                        <option value="">اختر نوع التعليم</option>
                        <option value="general" @selected(old('education_type', $course->education_type) === 'general')>عام</option>
                        <option value="azhar" @selected(old('education_type', $course->education_type) === 'azhar')>أزهري</option>
                    </select>
                    @error('education_type') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field" id="educationSystemField" hidden>
                    <label for="education_system">نظام الثانوية</label>
                    <select id="education_system" name="education_system">
                        <option value="">اختر نظام الثانوية</option>
                        <option value="baccalaureate" @selected(old('education_system', $course->education_system) === 'baccalaureate')>بكالوريا</option>
                        <option value="old" @selected(old('education_system', $course->education_system) === 'old')>نظام قديم</option>
                    </select>
                    @error('education_system') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="section-heading">
                <h2>صورة الكورس</h2>
                <p>اختر صورة واضحة تظهر في بطاقة الكورس للطلاب.</p>
            </div>
            <div class="field">
                <label for="image">صورة الكورس <span class="optional">اختياري</span></label>
                <div class="file-picker">
                    <span id="file-name">{{ $course->image ? 'الصورة الحالية محفوظة؛ اختر صورة لاستبدالها' : 'لم يتم اختيار صورة جديدة' }}</span>
                    <label for="image">اختيار صورة</label>
                </div>
                <input
                    class="file-input"
                    id="image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/svg+xml"
                    aria-describedby="image-hint"
                >
                <div class="hint" id="image-hint">JPG أو PNG أو GIF أو SVG، بحد أقصى 2 ميجابايت.</div>
                @error('image') <div class="error">{{ $message }}</div> @enderror
            </div>

            @include('components.upload-progress')

            <div class="actions">
                <a class="button" href="{{ route('courses.index') }}">إلغاء</a>
                <button class="button primary" type="submit">حفظ التعديلات</button>
            </div>
        </form>

        <aside class="card preview-card" aria-label="معاينة الكورس">
            @if ($course->image)
                <img class="preview-cover" id="preview-image" src="{{ asset('storage/' . $course->image) }}" alt="صورة {{ $course->title }}">
            @else
                <div class="preview-cover empty-cover" id="preview-image" aria-label="لا توجد صورة للكورس">
                    {{ mb_substr($course->title, 0, 1) }}
                </div>
            @endif
            <div class="preview-body">
                <span class="preview-label">معاينة</span>
                <h2 id="preview-title">{{ old('title', $course->title) }}</h2>
                <p id="preview-description">{{ old('description', $course->description) }}</p>
                <div class="meta">
                    <span>{{ $course->lessons_count }} محاضرة</span>
                    <span>{{ $course->teacher?->name }}</span>
                </div>
                <div class="preview-foot">
                    <span>سعر الكورس</span>
                    <strong><span id="preview-price">{{ old('price', $course->price) }}</span> ج.م</strong>
                </div>
            </div>
        </aside>
    </div>
</main>
<script>
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const priceInput = document.getElementById('price');
    const imageInput = document.getElementById('image');

    const educationStage = document.getElementById('education_stage');
    const educationGrade = document.getElementById('education_grade');
    const educationTypeField = document.getElementById('educationTypeField');
    const gradeField = document.getElementById('educationGradeField');
    const systemField = document.getElementById('educationSystemField');
    const currentStage = @json(old('education_stage', $course->education_stage));
    const currentGrade = @json(old('education_grade', $course->education_grade));
    let selectedGrade = currentGrade;

    function updateEducationOptions() {
        const stage = educationStage.value;
        const gradeNames = stage === 'secondary'
            ? ['الصف الأول الثانوي', 'الصف الثاني الثانوي', 'الصف الثالث الثانوي']
            : ['الصف الأول الإعدادي', 'الصف الثاني الإعدادي', 'الصف الثالث الإعدادي'];

        educationGrade.replaceChildren(new Option('اختر الصف الدراسي', ''));
        gradeNames.forEach((label, index) => {
            educationGrade.add(new Option(label, String(index + 1), false, selectedGrade === String(index + 1)));
        });
        educationGrade.disabled = stage === '';
        gradeField.hidden = stage === '';

        const showType = stage !== '';
        educationTypeField.hidden = !showType;
        document.getElementById('education_type').disabled = !showType;

        const showSystem = stage === 'secondary';
        systemField.hidden = !showSystem;
        document.getElementById('education_system').disabled = !showSystem;
        if (!showSystem) {
            document.getElementById('education_system').value = '';
        }
    }

    educationStage.addEventListener('change', () => {
        selectedGrade = '';
        updateEducationOptions();
    });
    updateEducationOptions();

    titleInput.addEventListener('input', () => {
        document.getElementById('preview-title').textContent = titleInput.value || 'اسم الكورس';
    });
    descriptionInput.addEventListener('input', () => {
        document.getElementById('preview-description').textContent = descriptionInput.value || 'وصف الكورس';
    });
    priceInput.addEventListener('input', () => {
        document.getElementById('preview-price').textContent = priceInput.value || '0';
    });
    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (!file) return;

        document.getElementById('file-name').textContent = file.name;
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            const previewImage = document.getElementById('preview-image');
            if (previewImage instanceof HTMLImageElement) {
                previewImage.src = reader.result;
                previewImage.alt = `صورة ${titleInput.value}`;
                return;
            }

            const image = document.createElement('img');
            image.id = 'preview-image';
            image.className = 'preview-cover';
            image.src = reader.result;
            image.alt = `صورة ${titleInput.value}`;
            previewImage.replaceWith(image);
        });
        reader.readAsDataURL(file);
    });
</script>
<script src="{{ asset('js/media-upload-progress.js') }}" defer></script>
</body>
</html>
