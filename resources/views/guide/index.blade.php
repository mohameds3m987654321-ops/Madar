
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f7f5">
    <meta name="description" content="ملخص صفحات منصة مدار ونصوص إرشادية مقترحة لشرح طريقة استخدامها.">
    <title>دليل استخدام مدار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/page-guide.css') }}">
    @include('components.appearance')
</head>
<body>
<header class="guide-site-header">
    <div class="guide-site-nav">
        <a class="guide-site-brand" href="{{ route('home') }}">مدار</a>
        <nav class="guide-site-links" aria-label="التنقل">
            <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>الرئيسية</a>
            <a href="{{ route('courses.index') }}" @if (request()->routeIs('courses.index')) aria-current="page" @endif>الدورات</a>
            <a href="{{ route('guide') }}" @if (request()->routeIs('guide')) aria-current="page" @endif>دليل الاستخدام</a>
            @auth
                <a href="{{ route('users.profile') }}">حسابي</a>
            @else
                <a href="{{ route('login') }}">تسجيل الدخول</a>
            @endauth
        </nav>
        <button class="guide-theme-toggle" id="theme-toggle" type="button" aria-label="التبديل إلى الوضع الداكن" title="التبديل إلى الوضع الداكن">
            <svg class="theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"/></svg>
            <svg class="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
        </button>
        <span id="theme-status" class="theme-status" role="status" aria-live="polite"></span>
    </div>
</header>
<main class="guide-page">


    <div class="guide-container">
        <a class="guide-home-link" href="{{ route('home') }}">العودة إلى منصة مدار</a>
        <header class="guide-heading">
            <h1>دليل استخدام منصة مدار</h1>
            <p>ملخص وظيفة كل صفحة، وأهم الأدوات الموجودة فيها، ونص تعليق مقترح لتسجيل شرحها.</p>
            <p class="guide-notice">هذه الصفحة توفر النصوص والملخصات داخل المنصة. ملفات الفيديو نفسها غير مُنشأة أو مُسجّلة؛ استخدم نص كل صفحة أثناء تسجيل الشاشة والصوت.</p>
        </header>

        @foreach (collect($guidePages)->groupBy('category') as $category => $pages)
            <section class="guide-category" aria-label="{{ $category }}">
                <h2>{{ $category }}</h2>
                <div class="guide-grid">
                    @foreach ($pages as $routeName => $page)
                        <article class="guide-card">
                            <h3>{{ $page['title'] }}</h3>
                            <p>{{ $page['summary'] }}</p>
                            <h4>أهم ما تتيحه الصفحة</h4>
                            <ul>
                                @foreach ($page['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <details>
                                <summary>نص مقترح لتعليق الفيديو</summary>
                                <p>{{ $page['script'] }}</p>
                            </details>
                            @if (\Illuminate\Support\Facades\Route::has($routeName) && count(\Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName)->parameterNames()) === 0)
                                <a class="guide-card-link" href="{{ route($routeName) }}">فتح الصفحة</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</main>
<script>
    const themeToggle = document.getElementById('theme-toggle');
    const themeStatus = document.getElementById('theme-status');
    const themeIcons = {
        dark: document.querySelector('.theme-icon-dark'),
        light: document.querySelector('.theme-icon-light'),
    };

    function updateThemeControl(theme) {
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        const label = nextTheme === 'dark' ? 'التبديل إلى الوضع الداكن' : 'التبديل إلى الوضع الفاتح';
        if (!themeToggle) return;
        themeToggle.setAttribute('aria-label', label);
        themeToggle.setAttribute('title', label);
        if (themeIcons.dark) themeIcons.dark.hidden = theme === 'dark';
        if (themeIcons.light) themeIcons.light.hidden = theme !== 'dark';
        const themeColorMeta = document.querySelector('meta[name="theme-color"]');
        if (themeColorMeta) {
            themeColorMeta.content = theme === 'dark' ? '#101a17' : '#f4f7f5';
        }
    }

    const initialTheme = document.documentElement.dataset.theme || 'light';
    updateThemeControl(initialTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', async () => {
            const currentTheme = document.documentElement.dataset.theme || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            updateThemeControl(nextTheme);
            if (themeStatus) themeStatus.textContent = '';
            const savedLocally = window.madarThemePreference.save(nextTheme, hasThemeAccount);

            @auth
                try {
                    const response = await fetch(@json(route('appearance.update')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ theme: nextTheme }),
                    });

                    if (!response.ok) {
                        throw new Error(`Theme preference save failed with status ${response.status}.`);
                    }

                    window.madarThemePreference.markSynced();
                    if (themeStatus) themeStatus.textContent = 'تم حفظ الوضع المفضل في حسابك.';
                } catch (error) {
                    if (themeStatus) {
                        themeStatus.textContent = savedLocally
                            ? 'تعذر حفظ اختيارك في الحساب، وتم الاحتفاظ به على هذا الجهاز.'
                            : 'تعذر حفظ اختيارك. حاول مرة أخرى.';
                    }
                    console.error('Could not save the theme preference.', error);
                }
            @else
                if (themeStatus) {
                    themeStatus.textContent = savedLocally
                        ? 'تم حفظ الوضع المفضل على هذا الجهاز.'
                        : 'تعذر حفظ اختيارك. حاول مرة أخرى.';
                }
            @endauth
        });
    }
</script>
<style>
    .theme-status { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>

</body>
</html>
