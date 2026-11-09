        <!DOCTYPE html>
        <html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">

            <title>إضافة محاضرة | منصة مدار</title>
            <link rel="stylesheet" href="{{ asset('css/media-upload-progress.css') }}">
            <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">

            <link
                href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap"
                rel="stylesheet"
            >

            <style>
                :root {
                    --bg: #1b2220;
                    --card: #232c29;
                    --text: #e3ebe8;
                    --muted: #8fa09b;
                    --primary: #7fae9f;
                    --primary-dark: #97c4b5;
                    --border: #34413d;
                    --input: #1f2926;
                    --danger: #d89690;
                }

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    background: var(--bg);
                    color: var(--text);
                    font-family: 'Tajawal', sans-serif;
                    line-height: 1.7;
                }

                .container {
                    max-width: 760px;
                    margin: auto;
                    padding: 40px 18px 60px;
                }

                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }

                .header h1 {
                    margin: 0 0 8px;
                    font-size: 30px;
                }

                .header p {
                    margin: 0;
                    color: var(--muted);
                }

                .card {
                    background: var(--card);
                    border: 1px solid var(--border);
                    border-radius: 20px;
                    padding: 25px;
                }

                .card h2 {
                    margin: 0 0 22px;
                    color: var(--primary-dark);
                    font-size: 20px;
                }

                .field {
                    margin-bottom: 18px;
                }

                label {
                    display: block;
                    margin-bottom: 7px;
                    color: var(--muted);
                    font-size: 14px;
                }

                input,
                select,
                textarea {
                    width: 100%;
                    border: 1px solid var(--border);
                    border-radius: 12px;
                    background: var(--input);
                    color: var(--text);
                    padding: 13px 14px;
                    font-family: inherit;
                    font-size: 15px;
                }

                input:focus,
                select:focus,
                textarea:focus {
                    outline: none;
                    border-color: var(--primary);
                }

                textarea {
                    min-height: 150px;
                    resize: vertical;
                }

                input[type="file"] {
                    padding: 10px;
                }

                .error {
                    color: var(--danger);
                    font-size: 13px;
                    margin-top: 6px;
                }

                .button {
                    width: 100%;
                    border: none;
                    border-radius: 12px;
                    padding: 14px;
                    background: var(--primary);
                    color: #fff;
                    font-family: inherit;
                    font-size: 16px;
                    font-weight: 700;
                    cursor: pointer;
                    transition: .2s;
                }

                .button:hover {
                    background: var(--primary-dark);
                }

                .back {
                    display: inline-block;
                    margin-top: 18px;
                    color: var(--muted);
                    text-decoration: none;
                }

                .back:hover {
                    color: var(--primary-dark);
                }

                .success {
                    background: #263b34;
                    border: 1px solid #3e6255;
                    color: #a9d7c5;
                    padding: 12px 15px;
                    border-radius: 12px;
                    margin-bottom: 18px;
                }

                @media (max-width: 600px) {
                    .container {
                        padding: 25px 14px 40px;
                    }

                    .card {
                        padding: 20px;
                    }

                    .header h1 {
                        font-size: 25px;
                    }
                }
            </style>
            @include('components.appearance')
        </head>

        <body class="teacher-management">
        @include('components.management-navigation')
        <div class="container">

            <div class="header">
                <h1>📚 إضافة محاضرة</h1>
                <p>أضف محاضرة جديدة إلى أحد كورساتك</p>
            </div>

            <div class="card">

                <h2>بيانات المحاضرة</h2>

                @if(session('success'))
                    <div class="success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="error">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ route('lessons.store') }}"
                    method="POST"
                    data-chunk-upload-url-template="{{ route('lessons.video-uploads.start', ['course' => '__COURSE_ID__']) }}"
                    enctype="multipart/form-data"
                >

                    @csrf

                    <div class="field">
                        <label for="course_id">
                            الكورس
                        </label>

                        <select
                            id="course_id"
                            name="course_id"
                            required
                        >
                            <option value="">
                                اختر الكورس
                            </option>

                            @foreach($courses as $course)
                                <option
                                    value="{{ $course->id }}"
                                    {{ old('course_id') == $course->id ? 'selected' : '' }}
                                >
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="field">
                        <label for="title">
                            اسم المحاضرة
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="{{ old('title') }}"
                            placeholder="مثال: مقدمة في البرمجة"
                            required
                        >
                    </div>


                    <div class="field">
                        <label for="content">
                            محتوى المحاضرة
                        </label>

                        <textarea
                            id="content"
                            name="content"
                            placeholder="اكتب محتوى المحاضرة هنا..."
                        >{{ old('content') }}</textarea>
                    </div>


                    <div class="field">
                        <label for="video">
                            فيديو المحاضرة <span class="hint">اختياري، بصيغة MP4. يُرفع على أجزاء لتسهيل الملفات الكبيرة.</span>
                        </label>

                        <input
                            type="file"
                            id="video"
                            name="video"
                            accept=".mp4,video/mp4"
                        >
                    </div>

                    <div class="field">
                        <label for="video_duration_seconds">مدة الفيديو بالثواني</label>
                        <input
                            type="number"
                            id="video_duration_seconds"
                            name="video_duration_seconds"
                            min="1"
                            max="86400"
                            value="{{ old('video_duration_seconds') }}"
                        >
                        <div class="hint">تُملأ تلقائيًا عند اختيار فيديو. راجع المدة للتأكد من صحتها.</div>
                    </div>


                    <div class="field">
                        <label for="order">
                            ترتيب المحاضرة
                        </label>

                        <input
                            type="number"
                            id="order"
                            name="order"
                            value="{{ old('order', 0) }}"
                            min="0"
                            required
                        >
                    </div>


                    @include('components.upload-progress')

                    <button
                        type="submit"
                        class="button"
                    >
                        إضافة المحاضرة
                    </button>

                </form>

                <a
                    href="{{ route('lessons.index') }}"
                    class="back"
                >
                    ← العودة إلى المحاضرات
                </a>

            </div>

        </div>

        <script src="{{ asset('js/media-upload-progress.js') }}" defer></script>
        <script>
            const videoInput = document.getElementById('video');
            const videoDurationInput = document.getElementById('video_duration_seconds');

            videoInput.addEventListener('change', () => {
                const file = videoInput.files?.[0];
                videoDurationInput.required = Boolean(file);

                if (!file) return;

                const video = document.createElement('video');
                const objectUrl = URL.createObjectURL(file);
                video.preload = 'metadata';
                video.onloadedmetadata = () => {
                    videoDurationInput.value = String(Math.ceil(video.duration));
                    URL.revokeObjectURL(objectUrl);
                };
                video.onerror = () => URL.revokeObjectURL(objectUrl);
                video.src = objectUrl;
            });
        </script>
        </body>

        </html>
