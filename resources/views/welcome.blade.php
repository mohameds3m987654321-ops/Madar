<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="مدار منصة تعليمية تساعدك على اكتساب مهارات جديدة بدورات عملية ومتابعة واضحة لتقدمك.">
    <meta name="theme-color" content="#f4f7f5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مدار — تعلّم يقرّبك من هدفك</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    <script>
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ready');
        }
    </script>
    <style>
        :root {
            color-scheme: light;
            font-family: "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 92px; }
        body { margin: 0; color: var(--ink); background: var(--paper); line-height: 1.75; }
        a { color: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        a:focus-visible, button:focus-visible, summary:focus-visible { outline: 3px solid var(--gold); outline-offset: 4px; }
        .container { width: min(1160px, calc(100% - 40px)); margin-inline: auto; }

        /* ---------- Header ---------- */
        .site-header { position: sticky; z-index: 10; top: 0; border-bottom: 1px solid var(--line); background: var(--surface-solid); backdrop-filter: blur(16px); }
        .nav { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: var(--brand); text-decoration: none; font-size: 1.35rem; font-weight: 800; white-space: nowrap; transition: transform .25s var(--motion-ease); }
        .brand:hover { transform: scale(1.03); }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 13px; color: #fff; background: linear-gradient(135deg, var(--brand), var(--brand-dark)); box-shadow: 0 6px 16px rgba(20, 120, 102, .28); }
        .brand-mark svg { width: 24px; height: 24px; }
        .nav-links { display: flex; align-items: center; justify-content: center; gap: clamp(12px, 2vw, 26px); }
        .nav-links a { position: relative; color: var(--muted); text-decoration: none; font-size: .9rem; font-weight: 600; padding: 6px 2px; transition: color .2s; }
        .nav-links a::after { content: ""; position: absolute; right: 0; bottom: 0; width: 0; height: 2px; border-radius: 2px; background: var(--brand); transition: width .28s var(--motion-ease); }
        .nav-links a:hover { color: var(--brand); }
        .nav-links a:hover::after { width: 100%; }
        .nav-actions { display: flex; align-items: center; gap: 10px; white-space: nowrap; }

        /* ---------- Buttons ---------- */
        .button { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 9px 18px; border: 1px solid transparent; border-radius: 12px; background: var(--brand); color: var(--paper); text-decoration: none; font: inherit; font-size: .92rem; font-weight: 700; cursor: pointer; transition: transform .2s var(--motion-ease), background .2s, box-shadow .2s; }
        .button:hover { transform: translateY(-2px); background: var(--brand-dark); box-shadow: 0 8px 22px rgba(20, 120, 102, .2); }
        .button:active { transform: translateY(0) scale(.97); }
        .button-outline { border-color: var(--line); background: var(--white); color: var(--ink); }
        .button-outline:hover { border-color: var(--brand); background: var(--white); color: var(--brand); box-shadow: var(--shadow); }
        .button-light { background: #fff; color: #0d5b4d; }
        .button-light:hover { background: #eff8f4; color: #0d5b4d; }
        .theme-toggle { width: 44px; min-width: 44px; padding: 9px; }
        .theme-toggle svg { width: 20px; height: 20px; transition: transform .5s var(--motion-ease); }
        .theme-toggle:hover svg { transform: rotate(28deg); }
        .theme-status { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .menu-toggle { display: none; width: 44px; height: 44px; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 12px; background: var(--white); color: var(--ink); cursor: pointer; }
        .menu-toggle svg { width: 22px; height: 22px; }

        /* ---------- Hero ---------- */
        .hero { position: relative; overflow: hidden; padding: 78px 0 72px; }
        .hero::before { content: ""; position: absolute; inset: 0; background: radial-gradient(ellipse 700px 460px at 85% 8%, var(--mint), transparent 62%); opacity: .8; pointer-events: none; }
        .hero::after { content: ""; position: absolute; width: 480px; height: 480px; border-radius: 50%; left: -160px; top: -180px; background: radial-gradient(circle, rgba(20, 120, 102, .07), transparent 68%); pointer-events: none; }
        .hero-grid { position: relative; display: grid; grid-template-columns: 1.02fr .98fr; align-items: center; gap: clamp(34px, 7vw, 88px); }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px; padding: 6px 13px; border: 1px solid var(--line); border-radius: 999px; background: var(--white); color: var(--brand); font-size: .86rem; font-weight: 700; }
        .eyebrow-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--brand); box-shadow: 0 0 0 4px var(--mint); animation: md-float 3s ease-in-out infinite; }
        h1, h2, h3, p { margin-top: 0; }
        .hero h1 { max-width: 620px; margin-bottom: 18px; font-size: clamp(2.55rem, 5vw, 4.55rem); line-height: 1.23; letter-spacing: -.045em; }
        .hero h1 span { position: relative; color: var(--brand); }
        .hero h1 span::after { content: ""; position: absolute; right: 0; bottom: 2px; width: 100%; height: 10px; border-radius: 6px; background: var(--mint); z-index: -1; transform: scaleX(0); transform-origin: right; transition: transform .6s .5s var(--motion-ease); }
        .hero-grid.is-in .hero h1 span::after { transform: scaleX(1); }
        .hero-copy { max-width: 570px; margin-bottom: 28px; color: var(--muted); font-size: clamp(1.03rem, 1.6vw, 1.16rem); }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .hero-actions .button { min-height: 52px; padding-inline: 24px; }
        .hero-note { display: flex; align-items: center; gap: 9px; margin-top: 22px; color: var(--muted); font-size: .88rem; }
        .hero-note svg { width: 19px; height: 19px; color: var(--brand); }

        .hero-art { position: relative; min-height: 400px; display: grid; place-items: center; }
        .art-orbit { position: absolute; width: min(90%, 420px); aspect-ratio: 1; border: 1.5px dashed var(--brand-line); border-radius: 50%; animation: md-spin 42s linear infinite; }
        .art-orbit::before, .art-orbit::after { content: ""; position: absolute; border-radius: 50%; inset: 11%; border: 1px solid var(--line); }
        .art-orbit::after { inset: 22%; }
        .art-glow { position: absolute; width: 68%; aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, var(--mint), transparent 72%); }
        @keyframes md-spin { to { transform: rotate(360deg); } }

        .lesson-window { position: relative; z-index: 1; width: min(100%, 440px); padding: 13px; border: 1px solid var(--line); border-radius: 23px; background: var(--white); box-shadow: 0 28px 75px rgba(13, 91, 77, .16); transform: rotate(-2deg); animation: md-pop .8s var(--motion-ease) both; }
        .lesson-screen { overflow: hidden; aspect-ratio: 1.4; border-radius: 15px; background: linear-gradient(140deg, #0d5b4d, #18826c 55%, #9bd8bd); }
        .lesson-screen video { display: block; width: 100%; height: 100%; object-fit: cover; }
        .lesson-meta { display: flex; justify-content: space-between; gap: 12px; padding: 13px 7px 4px; color: var(--muted); font-size: .83rem; }
        .lesson-meta strong { color: var(--ink); }

        .floating-card { position: absolute; z-index: 2; display: flex; align-items: center; gap: 10px; padding: 12px 15px; border: 1px solid var(--line); border-radius: 15px; background: var(--white); box-shadow: var(--shadow); font-size: .84rem; font-weight: 700; animation: md-pop .7s var(--motion-ease) both, md-float 5s ease-in-out infinite; }
        .floating-card svg { width: 21px; height: 21px; color: var(--brand); }
        .floating-progress { top: 9%; inset-inline-start: -2%; animation-delay: .25s, 1s; }
        .floating-certificate { right: 0; bottom: 10%; animation-delay: .4s, 1.4s; }

        .motion-ready .md-reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s var(--motion-ease), transform .7s var(--motion-ease); }
        .motion-ready .md-reveal.is-visible { opacity: 1; transform: none; }

        /* ---------- Stats ---------- */
        .stats-wrap { padding: 0 0 36px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); overflow: hidden; border: 1px solid var(--line); border-radius: 20px; background: var(--white); box-shadow: var(--shadow); }
        .stat { min-height: 110px; display: grid; align-content: center; justify-items: center; padding: 16px; text-align: center; transition: background .25s; }
        .stat:hover { background: var(--surface-soft); }
        .stat + .stat { border-inline-start: 1px solid var(--line); }
        .stat strong { color: var(--brand); font-size: 1.55rem; line-height: 1.35; }
        .stat span { color: var(--muted); font-size: .87rem; }
        .first-cohort { grid-column: 1 / -1; padding: 21px; color: var(--brand); font-weight: 700; text-align: center; }

        /* ---------- Sections ---------- */
        .section { padding: 80px 0; }
        .section-soft { position: relative; background: var(--mint); }
        .section-soft::before { content: ""; position: absolute; inset: 0; background: radial-gradient(ellipse 620px 320px at 12% 100%, rgba(20, 120, 102, .08), transparent 62%); pointer-events: none; }
        .section-head { position: relative; max-width: 670px; margin: 0 auto 40px; text-align: center; }
        .section-kicker { margin-bottom: 7px; color: var(--brand); font-size: .9rem; font-weight: 800; letter-spacing: .01em; }
        .section-head h2 { margin-bottom: 10px; font-size: clamp(1.85rem, 3.5vw, 2.7rem); line-height: 1.35; letter-spacing: -.03em; }
        .section-head p { margin-bottom: 0; color: var(--muted); }

        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .feature-card { min-height: 210px; padding: 25px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); transition: transform .3s var(--motion-ease), box-shadow .3s var(--motion-ease), border-color .3s; }
        .feature-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-up); border-color: var(--brand-line); }
        .feature-icon { width: 48px; height: 48px; display: grid; place-items: center; margin-bottom: 17px; border-radius: 15px; background: var(--mint); color: var(--brand); transition: transform .3s var(--motion-ease), background .3s; }
        .feature-card:hover .feature-icon { transform: scale(1.12) rotate(-6deg); background: var(--brand); color: var(--paper); }
        .feature-icon svg { width: 24px; height: 24px; }
        .feature-card h3 { margin-bottom: 7px; font-size: 1.08rem; }
        .feature-card p { margin-bottom: 0; color: var(--muted); font-size: .92rem; }

        /* ---------- Courses ---------- */
        .course-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
        .course-card { display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--line); border-radius: 18px; background: var(--white); transition: transform .3s var(--motion-ease), box-shadow .3s var(--motion-ease), border-color .3s; }
        .course-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-up); border-color: var(--brand-line); }
        .course-cover { position: relative; overflow: hidden; aspect-ratio: 16 / 10; background: linear-gradient(135deg, var(--mint), var(--paper)); }
        .course-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s var(--motion-ease); }
        .course-card:hover .course-cover img { transform: scale(1.05); }
        .course-placeholder { height: 100%; display: grid; place-items: center; color: var(--brand); }
        .course-placeholder svg { width: 54px; height: 54px; }
        .course-price { position: absolute; top: 12px; inset-inline-start: 12px; padding: 4px 12px; border-radius: 999px; background: var(--white); color: var(--brand); font-size: .8rem; font-weight: 800; box-shadow: var(--shadow); }
        .course-body { flex: 1; padding: 19px 20px 12px; }
        .course-teacher { margin-bottom: 7px; color: var(--brand); font-size: .82rem; font-weight: 700; }
        .course-body h3 { margin-bottom: 7px; font-size: 1.1rem; line-height: 1.5; }
        .course-body p { min-height: 3.2em; margin-bottom: 0; color: var(--muted); font-size: .88rem; }
        .course-meta { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 15px; color: var(--muted); font-size: .82rem; }
        .course-meta span { display: inline-flex; align-items: center; gap: 6px; }
        .course-meta svg { width: 16px; height: 16px; color: var(--brand); }
        .course-foot { padding: 0 20px 20px; }
        .course-foot .button { width: 100%; }
        .section-action { margin-top: 32px; text-align: center; }
        .empty-card { grid-column: 1 / -1; padding: 38px 22px; border: 1px dashed var(--brand-line); border-radius: 18px; background: var(--white); color: var(--muted); text-align: center; }
        .empty-card strong { display: block; margin-bottom: 6px; color: var(--brand); font-size: 1.08rem; }

        /* ---------- Steps ---------- */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; counter-reset: steps; }
        .step { position: relative; padding: 5px 68px 12px 12px; counter-increment: steps; transition: transform .3s var(--motion-ease); }
        .step:hover { transform: translateY(-4px); }
        .step::before { content: "0" counter(steps); position: absolute; top: 0; right: 0; width: 50px; height: 50px; display: grid; place-items: center; border: 1px solid var(--brand-line); border-radius: 16px; background: var(--white); color: var(--brand); font-size: 1.1rem; font-weight: 800; box-shadow: var(--shadow); transition: transform .3s var(--motion-ease), background .3s, color .3s; }
        .step:hover::before { transform: scale(1.1) rotate(-4deg); background: var(--brand); color: var(--paper); }
        .step h3 { margin-bottom: 8px; font-size: 1.05rem; }
        .step p { margin-bottom: 0; color: var(--muted); font-size: .92rem; }

        /* ---------- Teachers & reviews ---------- */
        .teacher-grid, .review-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .teacher-card { padding: 24px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); text-align: center; transition: transform .3s var(--motion-ease), box-shadow .3s; }
        .teacher-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-up); }
        .avatar { width: 74px; height: 74px; display: grid; place-items: center; overflow: hidden; margin: 0 auto 13px; border: 4px solid var(--mint); border-radius: 50%; background: var(--brand); color: var(--paper); font-size: 1.25rem; font-weight: 800; transition: transform .3s var(--motion-ease), border-color .3s; }
        .teacher-card:hover .avatar { transform: scale(1.07); border-color: var(--brand-line); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .teacher-card h3 { margin-bottom: 2px; font-size: 1.06rem; }
        .teacher-card p { margin-bottom: 0; color: var(--muted); font-size: .88rem; }
        .teacher-count { display: inline-block; margin-top: 13px; padding: 4px 12px; border-radius: 999px; background: var(--mint); color: var(--brand); font-size: .8rem; font-weight: 700; }
        .review-card { padding: 23px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); transition: transform .3s var(--motion-ease), box-shadow .3s; }
        .review-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-up); }
        .review-stars { margin-bottom: 12px; color: var(--gold); letter-spacing: 2px; }
        .review-text { min-height: 4.7em; margin-bottom: 18px; color: var(--ink-2); }
        .review-person { display: flex; align-items: center; gap: 10px; }
        .review-person .avatar { width: 42px; height: 42px; margin: 0; border-width: 2px; font-size: .9rem; }
        .review-person strong, .review-person span { display: block; }
        .review-person strong { font-size: .9rem; }
        .review-person span { color: var(--muted); font-size: .78rem; }

        /* ---------- FAQ ---------- */
        .faq-list { max-width: 820px; display: grid; gap: 12px; margin: auto; }
        .faq-list details { padding: 18px 21px; border: 1px solid var(--line); border-radius: 15px; background: var(--white); transition: border-color .25s, box-shadow .25s, transform .25s var(--motion-ease); }
        .faq-list details:hover { border-color: var(--brand-line); box-shadow: var(--shadow); }
        .faq-list details[open] { border-color: var(--brand-line); box-shadow: var(--shadow); }
        .faq-list summary { position: relative; padding-inline-end: 30px; color: var(--ink); font-weight: 700; cursor: pointer; list-style: none; }
        .faq-list summary::-webkit-details-marker { display: none; }
        .faq-list summary::after { content: "+"; position: absolute; top: -2px; inset-inline-start: 0; color: var(--brand); font-size: 1.4rem; line-height: 1; transition: transform .3s var(--motion-ease); }
        .faq-list details[open] summary::after { content: "−"; transform: rotate(180deg); }
        .faq-list details p { margin: 11px 0 0; color: var(--muted); font-size: .93rem; animation: md-rise .4s var(--motion-ease) both; }

        /* ---------- CTA ---------- */
        .final-cta { padding: 0 0 80px; }
        .cta-panel { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 25px; padding: clamp(30px, 5vw, 54px); border-radius: 24px; background: var(--green-hero); color: var(--paper); }
        .cta-panel::after { content: ""; position: absolute; width: 310px; aspect-ratio: 1; left: 19%; top: -70%; border: 1px solid rgba(255,255,255,.14); border-radius: 50%; box-shadow: 0 0 0 36px rgba(255,255,255,.035), 0 0 0 76px rgba(255,255,255,.025); animation: md-spin 60s linear infinite; }
        .cta-panel::before { content: ""; position: absolute; inset: 0; background: linear-gradient(115deg, transparent 42%, rgba(255,255,255,.09) 50%, transparent 58%); background-size: 220% 100%; animation: md-shimmer 4.5s linear infinite; pointer-events: none; }
        .cta-panel h2 { position: relative; z-index: 1; margin-bottom: 7px; font-size: clamp(1.65rem, 3.5vw, 2.35rem); }
        .cta-panel p { position: relative; z-index: 1; margin-bottom: 0; color: rgba(255,255,255,.82); }
        .cta-panel .button { position: relative; z-index: 1; flex: none; }

        /* ---------- Footer ---------- */
        .site-footer { padding: 48px 0 20px; border-top: 1px solid var(--line); background: var(--white); }
        .footer-grid { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 35px; padding-bottom: 28px; }
        .footer-about p { max-width: 350px; margin: 12px 0 0; color: var(--muted); font-size: .91rem; }
        .footer-column h3 { margin-bottom: 12px; font-size: .96rem; }
        .footer-links { display: grid; justify-items: start; gap: 7px; }
        .footer-links a { position: relative; color: var(--muted); text-decoration: none; font-size: .88rem; transition: color .2s, padding .25s var(--motion-ease); }
        .footer-links a::before { content: "◂"; position: absolute; right: -12px; top: 1px; color: var(--brand); opacity: 0; transition: opacity .2s; }
        .footer-links a:hover { color: var(--brand); padding-inline-start: 10px; }
        .footer-links a:hover::before { opacity: 1; }
        .footer-note { color: var(--muted); font-size: .88rem; }
        .footer-legal { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding-top: 17px; border-top: 1px solid var(--line); color: var(--muted); font-size: .83rem; }
        .legal-details { margin-top: 8px; }

        html[data-theme="dark"] .nav-links.is-open { background: var(--paper); }
        html[data-theme="dark"] .lesson-window { box-shadow: 0 28px 75px rgba(0, 0, 0, .4); }
        html[data-theme="dark"] .button:not(.button-light):not(.button-outline) { color: #10211b; }
        html[data-theme="dark"] .button:not(.button-light):not(.button-outline):hover { background: var(--brand-dark); }
        html[data-theme="dark"] .stats { box-shadow: none; }
        html[data-theme="dark"] .cta-panel { color: #eafff7; }

        @media (prefers-reduced-motion: reduce) {
            .motion-ready .md-reveal { opacity: 1; transform: none; transition: none; }
            .art-orbit, .eyebrow-dot, .floating-card, .cta-panel::after, .cta-panel::before { animation: none !important; }
        }
        @media (max-width: 980px) {
            .hero-grid { gap: 30px; }
            .hero-art { min-height: 330px; }
        }
        @media (max-width: 780px) {
            .nav { min-height: 68px; }
            .menu-toggle { display: inline-flex; }
            .nav-links { position: absolute; top: calc(100% + 1px); inset-inline: 0; display: none; align-items: stretch; padding: 12px 20px 18px; border-bottom: 1px solid var(--line); background: var(--paper); box-shadow: var(--shadow); }
            .nav-links.is-open { display: grid; animation: md-rise .3s var(--motion-ease) both; }
            .nav-links a { padding: 7px 0; font-size: .94rem; }
            .nav-actions .button { min-height: 40px; padding: 7px 11px; font-size: .82rem; }
            .theme-toggle { min-width: 40px !important; width: 40px; height: 40px; padding: 8px !important; }
            .hero { padding: 52px 0 50px; }
            .hero-grid { grid-template-columns: 1fr; }
            .hero-art { width: min(100%, 500px); min-height: 340px; margin: 4px auto 0; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .course-grid, .teacher-grid, .review-grid { grid-template-columns: repeat(2, 1fr); }
            .steps { gap: 16px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-about { grid-column: 1 / -1; }
        }
        @media (max-width: 540px) {
            .container { width: min(100% - 30px, 1160px); }
            .brand { font-size: 1.16rem; }
            .brand-mark { width: 34px; height: 34px; }
            .nav-actions { gap: 6px; }
            .nav-actions .button-outline { display: none; }
            .nav-actions .theme-toggle { display: inline-flex; }
            .hero { padding-top: 42px; }
            .hero h1 { font-size: clamp(2.35rem, 11vw, 3.25rem); }
            .hero-actions { display: grid; grid-template-columns: 1fr 1fr; }
            .hero-actions .button { padding-inline: 12px; font-size: .88rem; }
            .hero-art { min-height: 290px; }
            .floating-card { padding: 9px 11px; font-size: .75rem; }
            .floating-progress { top: 0; inset-inline-start: 0; }
            .floating-certificate { bottom: 0; right: 0; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .stat { min-height: 90px; }
            .stat:nth-child(3) { border-inline-start: 0; border-top: 1px solid var(--line); }
            .stat:nth-child(4) { border-top: 1px solid var(--line); }
            .section { padding: 58px 0; }
            .feature-grid, .course-grid, .teacher-grid, .review-grid { grid-template-columns: 1fr; }
            .feature-card { min-height: auto; }
            .course-body p, .review-text { min-height: 0; }
            .steps { grid-template-columns: 1fr; gap: 20px; }
            .cta-panel { align-items: flex-start; flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 25px 15px; }
            .footer-about { grid-column: 1 / -1; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; }
        }
    </style>
    @include('components.appearance')
</head>
<body>
<header class="site-header">
    <nav class="nav container" aria-label="التنقل الرئيسي">
        <a class="brand" href="{{ url('/') }}" aria-label="مدار، الصفحة الرئيسية">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            مدار
        </a>
        <button class="menu-toggle" type="button" aria-label="فتح القائمة" aria-expanded="false" aria-controls="main-nav">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="nav-links" id="main-nav">
            <a href="#home">الرئيسية</a>
            <a href="#courses">الدورات</a>
            <a href="#how">كيف نبدأ</a>
            <a href="#teachers">المدرسون</a>
            <a href="#about">عن مدار</a>
            <a href="#faq">الأسئلة الشائعة</a>
            <a href="{{ route('guide') }}">دليل الاستخدام</a>
        </div>
        <div class="nav-actions">
            <button class="button button-outline theme-toggle" type="button" aria-label="التبديل إلى الوضع الداكن" title="التبديل إلى الوضع الداكن">
                <svg class="theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"/></svg>
                <svg class="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
            </button>
            <span class="theme-status" role="status" aria-live="polite"></span>
            @auth
                <a class="button button-outline" href="{{ route('users.profile') }}">حسابي</a>
            @else
                <a class="button button-outline" href="{{ route('login') }}">تسجيل الدخول</a>
                <a class="button" href="{{ route('users.create') }}">إنشاء حساب مجاني</a>
            @endauth
        </div>
    </nav>
</header>

<main>
    <section class="hero" id="home">
        <div class="hero-grid container md-reveal" id="hero-reveal">
            <div>
                <div class="eyebrow"><span class="eyebrow-dot"></span> تعلّم على خطوتك</div>
                <h1>ابنِ مستقبلك خطوة بخطوة <span>مع مدار</span></h1>
                <p class="hero-copy">منصة تعليمية تأخذك من الصفر إلى الاحتراف، بدورات مصممة بعناية، ومتابعة مستمرة لتقدمك، وتعلّم يناسب وقتك.</p>
                <div class="hero-actions">
                    @auth
                        <a class="button" href="{{ route('courses.index') }}">ابدأ التعلّم</a>
                    @else
                        <a class="button" href="{{ route('users.create') }}">ابدأ مجانًا</a>
                    @endauth
                    <a class="button button-outline" href="#courses">استعرض الدورات</a>
                </div>
                <p class="hero-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                    تعلّم من أي جهاز، وتقدّم بالوتيرة المناسبة لك.
                </p>
            </div>
            <div class="hero-art" aria-label="فيديو تعريفي بمنصة مدار">
                <div class="art-orbit" aria-hidden="true"></div>
                <div class="art-glow" aria-hidden="true"></div>
                <div class="lesson-window">
                    <div class="lesson-screen">
                        <video autoplay muted loop playsinline preload="metadata" aria-label="فيديو تعريفي بمنصة مدار">
                            <source src="{{ asset('videos/madar-intro.mp4') }}" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                    </div>
                    <div class="lesson-meta"><span><strong>تعلّم بتركيز</strong></span><span>تقدّمك في مكان واحد</span></div>
                </div>
                <div class="floating-card floating-progress">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7"/></svg>
                    تابع تقدّمك بسهولة
                </div>
                <div class="floating-card floating-certificate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="m8.2 13-1 8 4.8-2.7 4.8 2.7-1-8"/></svg>
                    إنجازاتك تستحق التوثيق
                </div>
            </div>
        </div>
    </section>

    <section class="stats-wrap container" aria-label="إحصاءات مدار">
        <div class="stats md-reveal">
            @if ($stats['students'] > 0)
                <div class="stat"><strong data-count="{{ $stats['students'] }}">+{{ number_format($stats['students']) }}</strong><span>طالب بدأ رحلة التعلّم</span></div>
            @else
                <div class="first-cohort">انضم لأول دفعة من طلاب مدار وابدأ رحلتك التعليمية.</div>
            @endif
            <div class="stat"><strong data-count="{{ $stats['courses'] }}">{{ number_format($stats['courses']) }}</strong><span>دورة منشورة</span></div>
            <div class="stat"><strong data-count="{{ $stats['teachers'] }}">{{ number_format($stats['teachers']) }}</strong><span>مدرس يشارك خبرته</span></div>
            @if ($stats['reviews'] > 0)
                <div class="stat"><strong>{{ number_format((float) $stats['rating'], 1) }} / 5</strong><span>متوسط {{ number_format($stats['reviews']) }} تقييم حقيقي</span></div>
            @else
                <div class="stat"><strong>خطوتك</strong><span>هي بداية حكاية نجاح جديدة</span></div>
            @endif
        </div>
    </section>

    <section class="section" id="about">
        <div class="container">
            <div class="section-head md-reveal">
                <p class="section-kicker">تعلّم له معنى</p>
                <h2>ليه مدار؟ لأن رحلتك تستحق طريقًا واضحًا</h2>
                <p>تعلّم عملي ومنظّم يساعدك تعرف أين أنت، وما خطوتك التالية.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card md-reveal">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M8 7h8M8 11h6"/></svg></span>
                    <h3>محتوى مُعد باحترافية</h3><p>دروس مبسطة وتطبيقات عملية تربط ما تتعلمه بالواقع.</p>
                </article>
                <article class="feature-card md-reveal">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <h3>تعلّم بإيقاعك</h3><p>ارجع إلى المحتوى من جهازك وفي الوقت الذي يناسبك.</p>
                </article>
                <article class="feature-card md-reveal">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7"/></svg></span>
                    <h3>متابعة واضحة للتقدم</h3><p>تابع إنجاز الدروس واعرف أين وصلت في رحلتك.</p>
                </article>
                <article class="feature-card md-reveal">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="m8.2 13-1 8 4.8-2.7 4.8 2.7-1-8"/></svg></span>
                    <h3>شهادات إتمام</h3><p>احصل على شهادة عند استيفاء متطلبات الدورة وإتمامها.</p>
                </article>
                <article class="feature-card md-reveal">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg></span>
                    <h3>مساحة للأسئلة والنقاش</h3><p>شارك أسئلتك على الدروس وتواصل مع مجتمع التعلّم.</p>
                </article>
                <article class="feature-card md-reveal">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 22h8m-4-4v4M7 9h10M7 13h6"/></svg></span>
                    <h3>تجربة تناسب أجهزتك</h3><p>واجهة مرنة لتتابع دروسك من الهاتف أو الكمبيوتر.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-soft" id="courses">
        <div class="container">
            <div class="section-head md-reveal">
                <p class="section-kicker">ابدأ من هنا</p>
                <h2>اكتشف الدورة التي تناسب هدفك</h2>
                <p>تصفّح الدورات المنشورة حاليًا، واختر خطوتك التعليمية التالية.</p>
            </div>
            <div class="course-grid">
                @forelse ($courses as $course)
                    <article class="course-card md-reveal">
                        <div class="course-cover">
                            @if ($course->image)
                                <img src="{{ asset('storage/'.$course->image) }}" alt="{{ $course->title }}" loading="lazy">
                            @else
                                <div class="course-placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M8 7h8M8 11h6"/></svg></div>
                            @endif
                            <span class="course-price">{{ (float) $course->price > 0 ? number_format((float) $course->price, 2).' ج.م' : 'مجانية' }}</span>
                        </div>
                        <div class="course-body">
                            <div class="course-teacher">{{ $course->teacher?->name ?? 'مدرس مدار' }}</div>
                            <h3>{{ $course->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($course->description, 115) }}</p>
                            <div class="course-meta">
                                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>{{ $course->lessons_count }} درس</span>
                                @if ($course->reviews_count > 0)
                                    <span>★ {{ number_format((float) $course->reviews_avg_rating, 1) }} ({{ $course->reviews_count }})</span>
                                @endif
                            </div>
                        </div>
                        <div class="course-foot">
                            @auth
                                <a class="button" href="{{ route('courses.show', $course->id) }}">ابدأ الآن</a>
                            @else
                                <a class="button" href="{{ route('users.create') }}">ابدأ الآن</a>
                            @endauth
                        </div>
                    </article>
                @empty
                    <div class="empty-card md-reveal">
                        <strong>دورات مدار قيد التجهيز</strong>
                        تابعنا قريبًا لاكتشاف أولى الدورات المنشورة.
                    </div>
                @endforelse
            </div>
            @if ($courses->isNotEmpty())
                <div class="section-action md-reveal">
                    @auth
                        <a class="button button-outline" href="{{ route('courses.index') }}">عرض كل الدورات</a>
                    @else
                        <a class="button button-outline" href="{{ route('users.create') }}">أنشئ حسابًا لاستعراض كل الدورات</a>
                    @endauth
                </div>
            @endif
        </div>
    </section>

    <section class="section" id="how">
        <div class="container">
            <div class="section-head md-reveal">
                <p class="section-kicker">بخطوات بسيطة</p>
                <h2>كيف تبدأ رحلتك مع مدار؟</h2>
            </div>
            <div class="steps">
                <article class="step md-reveal"><h3>أنشئ حسابك في دقيقة</h3><p>سجّل ببياناتك الأساسية وابدأ بناء مساحة تعلّمك.</p></article>
                <article class="step md-reveal"><h3>اختر الدورة المناسبة</h3><p>تصفّح الدورات المتاحة واختر ما يتوافق مع هدفك.</p></article>
                <article class="step md-reveal"><h3>تعلّم وتابع إنجازك</h3><p>أكمل الدروس ومتطلبات الدورة لتحصل على شهادة الإتمام عند استحقاقها.</p></article>
            </div>
        </div>
    </section>

    <section class="section section-soft" id="teachers">
        <div class="container">
            <div class="section-head md-reveal">
                <p class="section-kicker">أهل الخبرة يشاركونك المعرفة</p>
                <h2>تعلّم من مدرّسي مدار</h2>
                <p>تعرّف على المدرسين الذين لديهم دورات منشورة على المنصة.</p>
            </div>
            <div class="teacher-grid">
                @forelse ($teachers as $teacher)
                    <article class="teacher-card md-reveal">
                        <div class="avatar">
                            @if ($teacher->image)
                                <img src="{{ asset('storage/'.$teacher->image) }}" alt="" loading="lazy">
                            @else
                                {{ \Illuminate\Support\Str::substr($teacher->name, 0, 1) }}
                            @endif
                        </div>
                        <h3>{{ $teacher->name }}</h3>
                        <p>مدرس في مدار</p>
                        <span class="teacher-count">{{ $teacher->courses_count }} {{ $teacher->courses_count === 1 ? 'دورة منشورة' : 'دورات منشورة' }}</span>
                    </article>
                @empty
                    <div class="empty-card md-reveal"><strong>مدرسونا قادمون قريبًا</strong>سيظهر هنا المدرسون أصحاب الدورات المنشورة.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section" id="reviews">
        <div class="container">
            <div class="section-head md-reveal">
                <p class="section-kicker">آراء من خاضوا التجربة</p>
                <h2>ماذا يقول طلابنا؟</h2>
                <p>نعرض التقييمات التي كتبها الطلاب فعلًا بعد تجربتهم.</p>
            </div>
            <div class="review-grid">
                @forelse ($reviews as $review)
                    <article class="review-card md-reveal">
                        <div class="review-stars" aria-label="التقييم {{ $review->rating }} من 5">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</div>
                        <p class="review-text">{{ $review->review }}</p>
                        <div class="review-person">
                            <div class="avatar">
                                @if ($review->user?->image)
                                    <img src="{{ asset('storage/'.$review->user->image) }}" alt="" loading="lazy">
                                @else
                                    {{ \Illuminate\Support\Str::substr($review->user?->name ?? 'طالب', 0, 1) }}
                                @endif
                            </div>
                            <div><strong>{{ $review->user?->name ?? 'طالب مدار' }}</strong><span>{{ $review->course?->title }}</span></div>
                        </div>
                    </article>
                @empty
                    <div class="empty-card md-reveal"><strong>آراء الطلاب ستظهر هنا</strong>لا توجد تقييمات منشورة بعد؛ ننتظر تجارب الطلاب الحقيقية.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section section-soft" id="faq">
        <div class="container">
            <div class="section-head md-reveal">
                <p class="section-kicker">إجابات تهمك</p>
                <h2>الأسئلة الشائعة</h2>
            </div>
            <div class="faq-list">
                <details class="md-reveal"><summary>هل الدورات مجانية؟</summary><p>توجد دورات مجانية وأخرى مدفوعة، ويظهر سعر كل دورة في صفحتها.</p></details>
                <details class="md-reveal"><summary>هل أقدر أتعلم من الموبايل؟</summary><p>نعم، صُممت المنصة لتناسب الشاشات المختلفة، ويمكنك متابعة الدروس من الهاتف أو الكمبيوتر.</p></details>
                <details class="md-reveal"><summary>هل أحصل على شهادة؟</summary><p>تتوفر شهادة إتمام عند استيفاء متطلبات الدورة وإكمالها.</p></details>
                <details class="md-reveal"><summary>ما سياسة استرداد المبلغ؟</summary><p>سياسة الاسترداد لم تُحدد على المنصة حتى الآن. قبل تفعيل الدفع، يجب على إدارة مدار نشر الشروط المعتمدة بوضوح.</p></details>
                <details class="md-reveal"><summary>هل الدفع الإلكتروني متاح الآن؟</summary><p>يحتاج الدفع إلى إعداد حساب بوابة الدفع الخاصة بمالك المنصة؛ لا تُكمل أي عملية دفع ما لم تكن وسائل الدفع مفعّلة ومعلنة في صفحة الدورة.</p></details>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="container">
            <div class="cta-panel md-reveal">
                <div><h2>جاهز تبدأ رحلتك؟ مدار في انتظارك</h2><p>اختَر هدفك، وخلي أول خطوة تقرّبك منه.</p></div>
                @auth
                    <a class="button button-light" href="{{ route('courses.index') }}">استعرض الدورات</a>
                @else
                    <a class="button button-light" href="{{ route('users.create') }}">ابدأ مجانًا</a>
                @endauth
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid md-reveal">
            <div class="footer-about">
                <a class="brand" href="{{ url('/') }}">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    مدار
                </a>
                <p>مساحة تعلّم تساعدك تكتسب مهارات جديدة، وتتابع تقدمك، وتقترب من أهدافك خطوة بخطوة.</p>
            </div>
            <div class="footer-column">
                <h3>روابط سريعة</h3>
                <div class="footer-links"><a href="#about">عن مدار</a><a href="#courses">الدورات</a><a href="#teachers">المدرسون</a><a href="#faq">الأسئلة الشائعة</a><a href="{{ route('guide') }}">دليل الاستخدام</a></div>
            </div>
            <div class="footer-column">
                <h3>معلومات المنصة</h3>
                <div class="footer-links">
                    <a href="#terms">الشروط والأحكام</a>
                    <a href="#privacy">سياسة الخصوصية</a>
                    <span class="footer-note">قنوات التواصل ستُضاف عند تجهيزها.</span>
                </div>
            </div>
        </div>
        <div class="footer-legal md-reveal">
            <span>© {{ now()->year }} مدار. جميع الحقوق محفوظة.</span>
            <span>نسخة تعريفية للمنصة.</span>
        </div>
        <details class="legal-details" id="terms"><summary>الشروط والأحكام</summary><p class="footer-note">الشروط التفصيلية قيد الإعداد، ويجب نشرها قبل إتاحة التسجيل والدفع للجمهور.</p></details>
        <details class="legal-details" id="privacy"><summary>سياسة الخصوصية</summary><p class="footer-note">سياسة الخصوصية التفصيلية قيد الإعداد، ويجب توضيح كيفية التعامل مع بيانات الحساب قبل إتاحة التسجيل للجمهور.</p></details>
    </div>
</footer>

<script>
    /* ---------- Reveal on scroll (single observer, deferred batch reveal) ---------- */
    if (document.documentElement.classList.contains('motion-ready')) {
        const revealTargets = document.querySelectorAll('.md-reveal');
        let lastVisible = 0;
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry, index) => {
                entry.target.style.transitionDelay = `${Math.min(index * 70, 350)}ms`;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealTargets.forEach((element) => revealObserver.observe(element));

        /* Hero gets its entrance immediately */
        requestAnimationFrame(() => document.getElementById('hero-reveal')?.classList.add('is-visible'));

        /* Count-up numbers in stats */
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const target = entry.target;
                const end = parseInt(target.dataset.count, 10) || 0;
                const prefix = target.textContent.trim().startsWith('+') ? '+' : '';
                if (end === 0) { observer.unobserve(target); return; }
                const duration = 1200;
                const start = performance.now();
                const tick = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    target.textContent = prefix + Math.round(end * eased).toLocaleString('en-US');
                    if (progress < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
                observer.unobserve(target);
            });
        }, { threshold: 0.5 });
        document.querySelectorAll('[data-count]').forEach((el) => counterObserver.observe(el));
    }

    /* ---------- Header controls ---------- */
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('#main-nav');
    const themeToggle = document.querySelector('.theme-toggle');
    const themeStatus = document.querySelector('.theme-status');
    const themeIcons = {
        dark: document.querySelector('.theme-icon-dark'),
        light: document.querySelector('.theme-icon-light'),
    };

    function updateThemeControl(theme) {
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        const label = nextTheme === 'dark' ? 'التبديل إلى الوضع الداكن' : 'التبديل إلى الوضع الفاتح';

        themeToggle.setAttribute('aria-label', label);
        themeToggle.setAttribute('title', label);
        themeIcons.dark.hidden = theme === 'dark';
        themeIcons.light.hidden = theme !== 'dark';
        document.querySelector('meta[name="theme-color"]').content = theme === 'dark' ? '#101a17' : '#f4f7f5';
    }

    const initialTheme = document.documentElement.dataset.theme;
    updateThemeControl(initialTheme);

    themeToggle.addEventListener('click', async () => {
        const currentTheme = document.documentElement.dataset.theme;
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = nextTheme;
        updateThemeControl(nextTheme);
        themeStatus.textContent = '';
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
                themeStatus.textContent = 'تم حفظ الوضع المفضل في حسابك.';
            } catch (error) {
                themeStatus.textContent = savedLocally
                    ? 'تعذر حفظ اختيارك في الحساب، وتم الاحتفاظ به على هذا الجهاز.'
                    : 'تعذر حفظ اختيارك. حاول مرة أخرى.';
                console.error('Could not save the theme preference.', error);
            }
        @else
            if (savedLocally) {
                themeStatus.textContent = 'تم حفظ الوضع المفضل على هذا الجهاز.';
            } else {
                themeStatus.textContent = 'تم تغيير الوضع لهذه الزيارة، لكن تعذر حفظه على هذا الجهاز.';
            }
        @endauth
    });

    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        menuToggle.setAttribute('aria-label', isOpen ? 'فتح القائمة' : 'إغلاق القائمة');
        mainNav.classList.toggle('is-open', !isOpen);
    });

    mainNav.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'فتح القائمة');
            mainNav.classList.remove('is-open');
        }
    });
</script>
</body>
</html>
