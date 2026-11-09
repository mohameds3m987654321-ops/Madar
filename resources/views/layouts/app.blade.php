<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'مدار — تعلّم يقرّبك من هدفك')</title>
    
    @stack('styles')
    
    @include('components.appearance')
    
    <script>
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ready');
        }
    </script>
</head>
<body class="motion-ready">
    <header class="site-header">
        <nav class="nav container" aria-label="التنقل الرئيسي">
            <a class="brand" href="{{ url('/') }}">
                <span class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                مدار
            </a>
            
            <div class="nav-links">
                <a href="{{ url('/') }}">الرئيسية</a>
                <a href="{{ route('courses.index') }}">الدورات</a>
                @auth
                    <a href="{{ route('courses.my') }}">كورساتي</a>
                @endauth
            </div>

            <div class="nav-actions">
                <button class="theme-toggle md-btn" type="button" id="global-theme-toggle" aria-label="تبديل الوضع">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                </button>
                @auth
                    <a class="button button-outline" href="{{ route('users.profile') }}">حسابي</a>
                @else
                    <a class="button button-outline" href="{{ route('login') }}">دخول</a>
                    <a class="button" href="{{ route('users.create') }}">ابدأ مجاناً</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="container py-main">
        @if (session('success'))
            <div class="alert-success md-reveal">{{ session('success') }}</div>
        @endif
        
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-legal">
                <span>© {{ now()->year }} مدار. جميع الحقوق محفوظة.</span>
                <div class="footer-links">
                    <a href="{{ route('guide') }}">دليل الاستخدام</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Global Theme Toggle
        const themeBtn = document.getElementById('global-theme-toggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', async () => {
                const currentTheme = document.documentElement.dataset.theme;
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.dataset.theme = nextTheme;
                window.madarThemePreference.save(nextTheme, @json(auth()->check()));
                
                @auth
                    fetch(@json(route('appearance.update')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ theme: nextTheme }),
                    });
                @endauth
            });
        }

        // Reveal on scroll
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.md-reveal').forEach((el) => revealObserver.observe(el));
    </script>
    @stack('scripts')
</body>
</html>
