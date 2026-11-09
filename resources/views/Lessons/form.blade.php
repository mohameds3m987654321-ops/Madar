<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $lesson->exists ? 'تعديل المحاضرة' : 'إضافة محاضرة' }} | منصة مدار</title>
    <link rel="stylesheet" href="{{ asset('css/media-upload-progress.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme:dark; --bg:#0c1513; --surface:#13201d; --field:#101b19; --ink:#e7f0ed; --muted:#8aa29b; --line:#243732; --accent:#3cc3a6; --soft:#16352f; --brand:#0f8a73; --danger:#ff9288; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:var(--bg); color:var(--ink); font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif; line-height:1.7; }
        main { width:min(700px,calc(100% - 28px)); margin:0 auto; padding:30px 0 50px; }
        a { color:var(--accent); text-decoration:none; }
        :focus-visible { outline:3px solid var(--accent); outline-offset:3px; }
        .breadcrumb { margin-bottom:8px; color:var(--muted); font-size:.86rem; }
        h1 { margin:0 0 4px; font-size:1.8rem; }
        header p { margin:0 0 20px; color:var(--muted); }
        .card { padding:22px; border:1px solid var(--line); border-radius:15px; background:var(--surface); }
        .field { margin-bottom:17px; }
        label { display:block; margin-bottom:7px; font-weight:600; }
        input,textarea { width:100%; padding:11px 13px; border:1px solid var(--line); border-radius:10px; background:var(--field); color:var(--ink); font:inherit; }
        input:focus,textarea:focus { outline:0; border-color:var(--accent); box-shadow:0 0 0 3px #3cc3a622; }
        textarea { min-height:130px; resize:vertical; }
        input[type=file] { padding:9px; }
        .hint { margin-top:5px; color:var(--muted); font-size:.82rem; }
        .error { margin-top:5px; color:var(--danger); font-size:.84rem; }
        .invalid { border-color:var(--danger); }
        .actions { display:flex; gap:10px; margin-top:22px; }
        .button { display:inline-flex; min-height:44px; align-items:center; justify-content:center; padding:9px 16px; border:1px solid var(--line); border-radius:10px; background:transparent; color:var(--ink); font:inherit; font-weight:600; cursor:pointer; }
        .button.primary { flex:1; border-color:transparent; background:var(--brand); color:white; }
        .hint.error { color:var(--danger); }
        .attachment-list { margin:12px 0 0; padding:0; list-style:none; }
        .attachment-item { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 0; border-top:1px solid var(--line); }
        .attachment-item form { margin:0; }
        .attachment-name { overflow-wrap:anywhere; }
        .button.danger { color:var(--danger); }
        @media(max-width:500px) { main{padding-top:20px}.card{padding:17px} }
    </style>
    @include('components.appearance')
</head>
<body class="teacher-management">
@include('components.management-navigation')
<main>
    <div class="breadcrumb">
        <a href="{{ route('teacher.dashboard') }}">لوحة المدرس</a> /
        <a href="{{ route('courses.edit', $course) }}">{{ $course->title }}</a> /
        <a href="{{ route('lessons.manage', $course) }}">المحاضرات</a>
    </div>
    <header>
        <h1>{{ $lesson->exists ? 'تعديل المحاضرة' : 'إضافة محاضرة' }}</h1>
        <p>{{ $course->title }}</p>
    </header>
    <form class="card" method="POST"
          action="{{ $lesson->exists ? route('lessons.update', [$course, $lesson]) : route('lessons.store-for-course', $course) }}"
          @unless ($lesson->exists)
              data-chunk-upload-url="{{ route('lessons.video-uploads.start', $course) }}"
          @endunless
          enctype="multipart/form-data">
        @csrf
        @if ($lesson->exists) @method('PUT') @endif

        <div class="field">
            <label for="title">عنوان المحاضرة</label>
            <input id="title" name="title" type="text" maxlength="255" value="{{ old('title', $lesson->title) }}" class="@error('title') invalid @enderror" required>
            @error('title') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="content">وصف أو محتوى المحاضرة <span class="hint">اختياري</span></label>
            <textarea id="content" name="content" class="@error('content') invalid @enderror">{{ old('content', $lesson->content) }}</textarea>
            @error('content') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="order">ترتيب المحاضرة</label>
            <input id="order" name="order" type="number" min="0" max="65535" value="{{ old('order', $lesson->exists ? $lesson->order : (($course->lessons()->max('order') ?? 0) + 1)) }}" required>
            <div class="hint">غيّر الرقم لتحديد مكان المحاضرة في قائمة الكورس.</div>
            @error('order') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="video">فيديو المحاضرة <span class="hint">اختياري، بصيغة MP4. يُرفع على أجزاء لتسهيل الملفات الكبيرة.</span></label>
            @if ($lesson->video)
                <div class="hint">
                    @if ($lesson->isVideoProcessing())
                        الفيديو قيد التجهيز في مساحة آمنة.
                    @else
                        يوجد فيديو مرفوع حاليًا. ارفع ملفًا جديدًا لاستبداله.
                    @endif
                </div>
            @endif
            <input id="video" name="video" type="file" accept=".mp4,video/mp4">
            @error('video') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="video_duration_seconds">مدة الفيديو بالثواني</label>
            <input id="video_duration_seconds" name="video_duration_seconds" type="number" min="1" max="86400" value="{{ old('video_duration_seconds', $lesson->video_duration_seconds) }}" @if ($lesson->video || old('video')) required @endif>
            <div class="hint">تُملأ تلقائيًا عند اختيار فيديو. راجع المدة للتأكد من صحتها.</div>
            <div class="hint" data-video-duration-status role="status" aria-live="polite"></div>
            @error('video_duration_seconds') <div class="error">{{ $message }}</div> @enderror
        </div>

        @include('components.upload-progress')

        <div class="actions">
            <a class="button" href="{{ route('lessons.manage', $course) }}">رجوع</a>
            <button class="button primary" type="submit">{{ $lesson->exists ? 'حفظ المحاضرة' : 'إضافة المحاضرة' }}</button>
        </div>
    </form>

    @if ($lesson->exists)
        <section class="card" aria-labelledby="attachments-title" style="margin-top:20px">
            <h2 id="attachments-title">المرفقات والملفات</h2>
            <p class="hint">ارفع ملفات PDF أو صورًا أو مستندات تعليمية. الحد الأقصى 50 ميجابايت للملف.</p>

            @if ($lesson->attachments->isNotEmpty())
                <ul class="attachment-list">
                    @foreach ($lesson->attachments as $attachment)
                        <li class="attachment-item">
                            <a class="attachment-name" href="{{ route('lessons.attachments.download', [$lesson, $attachment]) }}">
                                {{ $attachment->original_name }}
                            </a>
                            <form method="POST" action="{{ route('lessons.attachments.destroy', [$course, $lesson, $attachment]) }}"
                                  onsubmit="return confirm('حذف هذا الملف المرفق؟');">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit">حذف</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="hint">لم تُضف ملفات لهذه المحاضرة بعد.</p>
            @endif

            <form method="POST" action="{{ route('lessons.attachments.store', [$course, $lesson]) }}"
                  enctype="multipart/form-data" style="margin-top:16px">
                @csrf
                <div class="field">
                    <label for="attachments">إضافة ملفات</label>
                    <input id="attachments" name="attachments[]" type="file" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                           required>
                    <div class="hint">يمكن اختيار حتى 10 ملفات في المرة الواحدة.</div>
                    @error('attachments')
                        <div class="error">{{ $message }}</div>
                    @enderror
                    @foreach ($errors->get('attachments.*') as $messages)
                        @foreach ($messages as $message)
                            <div class="error">{{ $message }}</div>
                        @endforeach
                    @endforeach
                </div>
                <button class="button primary" type="submit">رفع الملفات</button>
            </form>
        </section>
    @endif
</main>
<script src="{{ asset('js/media-upload-progress.js') }}" defer></script>
<script>
    const videoInput = document.getElementById('video');
    const videoDurationInput = document.getElementById('video_duration_seconds');
    const videoDurationStatus = document.querySelector('[data-video-duration-status]');

    videoInput.addEventListener('change', () => {
        const file = videoInput.files?.[0];
        videoDurationInput.required = Boolean(file || {{ $lesson->video ? 'true' : 'false' }});

        if (!file) {
            videoDurationStatus.textContent = '';
            return;
        }

        videoDurationStatus.textContent = 'جارٍ قراءة مدة الفيديو...';
        const video = document.createElement('video');
        const objectUrl = URL.createObjectURL(file);
        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            if (Number.isFinite(video.duration) && video.duration > 0) {
                videoDurationInput.value = String(Math.ceil(video.duration));
                videoDurationStatus.textContent = 'تمت قراءة المدة تلقائيًا. يمكنك تعديلها إذا لزم الأمر.';
            } else {
                videoDurationStatus.textContent = 'تعذرت قراءة مدة الفيديو تلقائيًا. اكتب المدة بالثواني يدويًا.';
            }
            URL.revokeObjectURL(objectUrl);
        };
        video.onerror = () => {
            videoDurationStatus.textContent = 'تعذرت قراءة مدة الفيديو تلقائيًا. اكتب المدة بالثواني يدويًا.';
            URL.revokeObjectURL(objectUrl);
        };
        video.src = objectUrl;
    });

    videoDurationInput.addEventListener('input', () => {
        if (videoDurationInput.value) {
            videoDurationStatus.textContent = '';
        }
    });

    videoDurationInput.addEventListener('invalid', () => {
        if (videoInput.files?.length && !videoDurationInput.value) {
            videoDurationStatus.textContent = 'مدة الفيديو مطلوبة. انتظر القراءة التلقائية أو أدخلها بالثواني.';
        }
    });
</script>
</body>
</html>
