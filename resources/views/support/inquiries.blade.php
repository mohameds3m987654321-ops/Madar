@php
    $statusMap = [
        'all' => 'الكل',
        'new' => 'جديدة',
        'in_progress' => 'قيد المعالجة',
        'resolved' => 'محلولة',
    ];

    $selectedStatus = request('status', 'all');
    $filteredInquiries = $inquiries->getCollection();

    $selectedId = (int) request('inquiry', $filteredInquiries->first()->id ?? 0);
    $selectedInquiry = $filteredInquiries->firstWhere('id', $selectedId) ?? $filteredInquiries->first();

    $messages = is_array($selectedInquiry->conversation ?? null)
        ? $selectedInquiry->conversation
        : [];
    if ($selectedInquiry && $messages === [] && filled($selectedInquiry->question)) {
        $messages[] = [
            'role' => 'user',
            'text' => $selectedInquiry->question,
            'created_at' => $selectedInquiry->created_at,
        ];
    }

    $statusColors = [
        'new' => 'status-new',
        'in_progress' => 'status-open',
        'resolved' => 'status-resolved',
    ];

    $statusLabel = fn ($status) => [
        'new' => 'جديدة',
        'in_progress' => 'قيد المعالجة',
        'resolved' => 'محلولة',
    ][$status ?? 'new'] ?? 'جديدة';

    $messageLabel = function ($role) {
        return match ($role) {
            'user' => 'الطالب',
            'support' => 'فريق الدعم',
            'assistant' => 'المساعد',
            'note' => 'ملاحظة داخلية',
            'system' => 'النظام',
            default => 'رسالة',
        };
    };

    $formatTime = function ($value) {
        if (empty($value)) {
            return 'الآن';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->translatedFormat('H:i');
        } catch (\Throwable $exception) {
            return 'الآن';
        }
    };

    $isLate = function ($inquiry) {
        if (($inquiry->status ?? 'new') === 'resolved') {
            return false;
        }

        return $inquiry->created_at
            && $inquiry->created_at->lt(now()->subMinutes(120));
    };

    $supportReplyCanned = [
        'استلام الاستفسار' => 'أهلاً بك، تم استلام استفسارك وسيتم الرد عليك خلال ساعتين في أيام العمل.',
        'تحويل للمدرّس' => 'تم تحويل استفسارك إلى المدرّس المختص، وسيرد عليك في أقرب وقت ممكن.',
        'إعادة فتح الاختبار' => 'تمت إعادة فتح الاختبار لك، يمكنك المحاولة مجدداً من صفحة الاختبارات.',
        'طلب تفاصيل' => 'لمساعدتك بشكل أسرع، يرجى إرسال اسم الدرس ولقطة شاشة للخطأ إن وجدت.',
        'إصدار الشهادة' => 'تم إصدار شهادتك، وهي متاحة الآن في صفحة الشهادات لتحميلها بصيغة PDF.',
    ];
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة دعم الطلاب</title>
    <style>
        :root {
            --soft: var(--surface-soft);
            --primary: var(--brand);
            --primary-alt: var(--brand-dark);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            background: var(--bg);
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, sans-serif;
        }

        body {
            direction: rtl;
            line-height: 1.6;
        }

        button, input, select, textarea {
            font: inherit;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem max(1.5rem, calc((100vw - 1480px) / 2));
            background: var(--surface-solid, var(--surface));
            border-bottom: 1px solid var(--line);
            box-shadow: 0 1px 0 rgba(148, 163, 184, 0.04);
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .logo {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-alt));
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 12px 26px -18px var(--primary);
        }

        .brand-copy {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .brand-copy strong {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .brand-copy small {
            color: var(--muted);
            font-size: .75rem;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .ghost-button {
            border: 1px solid var(--line);
            background: transparent;
            color: var(--muted);
            border-radius: 10px;
            padding: .7rem 1rem;
            cursor: pointer;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: .7rem;
            font-weight: 700;
            color: var(--ink);
        }

        .avatar-mini {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--soft);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .stats {
            display: grid;
            width: min(1480px, calc(100% - 3rem));
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin: 0 auto;
            padding: 1.25rem 0 0;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1rem 1.2rem;
            min-height: 100px;
        }

        .stat-card b {
            display: block;
            font-size: 1.8rem;
            line-height: 1.15;
            margin-bottom: .2rem;
        }

        .stat-card small {
            color: var(--muted);
            font-weight: 600;
        }

        .stat-card.danger b {
            color: var(--danger);
        }

        .layout {
            display: grid;
            width: min(1480px, calc(100% - 3rem));
            grid-template-columns: minmax(270px, 310px) minmax(0, 1fr) minmax(260px, 290px);
            gap: 1rem;
            margin: 0 auto;
            padding: 1rem 0 1.5rem;
            height: min(800px, calc(100dvh - 245px));
            min-height: 560px;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            min-height: 0;
        }

        .inbox-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            color: var(--ink);
            font-size: 1rem;
            font-weight: 800;
        }

        .inbox-panel {
            display: flex;
            flex-direction: column;
        }

        .inbox-tools {
            padding: 1rem;
            border-bottom: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            gap: .8rem;
        }

        .inbox-tools form {
            display: flex;
            gap: .5rem;
        }

        .search-box {
            flex: 1;
            min-width: 0;
            background: var(--soft);
            border: 1px solid transparent;
            border-radius: 12px;
            padding: .82rem .9rem;
            font-size: .9rem;
            color: var(--ink);
        }

        .search-submit {
            border: 0;
            border-radius: 12px;
            padding: .65rem .85rem;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--brand-line);
            background: var(--surface);
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .tab-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            border-radius: 999px;
            background: var(--soft);
            color: var(--muted);
            padding: .4rem .8rem;
            border: 1px solid transparent;
            font-size: .78rem;
            font-weight: 700;
        }

        .tab-item.active {
            border-color: var(--brand-line);
            background: var(--brand-soft);
            color: var(--brand);
        }

        .list {
            flex: 1;
            overflow-y: auto;
            padding: .75rem;
        }

        .inquiry-item {
            display: block;
            width: 100%;
            padding: .8rem .8rem .7rem;
            border: 1px solid transparent;
            border-radius: 12px;
            background: transparent;
            margin-bottom: .55rem;
            transition: background .15s ease, border-color .15s ease;
        }

        .inquiry-item:hover {
            background: var(--soft);
        }

        .inquiry-item.active {
            background: var(--soft);
            border-color: var(--brand-line);
        }

        .item-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .25rem;
        }

        .item-name {
            font-weight: 800;
            font-size: 1rem;
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-time {
            color: var(--muted);
            font-size: .72rem;
            white-space: nowrap;
        }

        .item-course {
            color: var(--primary);
            font-size: .75rem;
            font-weight: 700;
            margin-bottom: .25rem;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-summary {
            color: var(--muted);
            font-size: .8rem;
            margin-bottom: .6rem;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .item-contact {
            display: block;
            margin: -.25rem 0 .55rem;
            color: var(--muted);
            font-size: .7rem;
            overflow-wrap: anywhere;
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .status-pill,
        .meta-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            line-height: 1;
        }

        .status-new { background: color-mix(in srgb, var(--brand) 13%, var(--surface)); color: var(--brand); }
        .status-open { background: var(--gold-soft); color: var(--gold); }
        .status-resolved { background: color-mix(in srgb, var(--success) 13%, var(--surface)); color: var(--success); }
        .status-waiting { background: var(--blue-soft); color: var(--blue); }
        .late-pill { background: color-mix(in srgb, var(--danger) 12%, var(--surface)); color: var(--danger); }

        .thread-panel {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .thread-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--line);
        }

        .thread-title {
            display: flex;
            flex-direction: column;
            gap: .1rem;
            min-width: 0;
        }

        .thread-title h2 {
            margin: 0;
            font-size: 1.08rem;
        }

        .thread-title small {
            color: var(--muted);
            font-size: .74rem;
        }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .message {
            max-width: 72%;
            padding: .8rem .9rem;
            border-radius: 16px;
            border-top-left-radius: 6px;
            background: var(--soft);
            align-self: flex-start;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.05);
            animation: message-in .22s ease-out both;
        }

        @keyframes message-in {
            from { opacity: 0; transform: translateY(8px) scale(.99); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .message.pending {
            opacity: .65;
        }

        .message p {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: .93rem;
        }

        .message small {
            display: block;
            margin-top: .35rem;
            color: var(--muted);
            font-size: .7rem;
        }

        .message.support,
        .message.assistant {
            align-self: flex-end;
            background: color-mix(in srgb, var(--brand) 10%, var(--surface));
            border: 1px solid color-mix(in srgb, var(--brand) 18%, var(--line));
            border-top-left-radius: 16px;
            border-top-right-radius: 6px;
        }

        .message.note {
            max-width: 92%;
            align-self: center;
            background: var(--gold-soft);
            border: 1px dashed color-mix(in srgb, var(--gold) 50%, var(--line));
        }

        .message.system {
            max-width: 92%;
            align-self: center;
            background: transparent;
            border: none;
            box-shadow: none;
            text-align: center;
            padding: .15rem .6rem;
            color: var(--muted);
        }

        .message.system p,
        .message.system small {
            display: none;
        }

        .composer {
            padding: 1rem 1.1rem;
            border-top: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }

        .canned-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .canned-btn {
            border: 1px dashed rgba(148, 163, 184, 0.8);
            background: transparent;
            color: var(--muted);
            border-radius: 999px;
            padding: .32rem .7rem;
            cursor: pointer;
            font-size: .74rem;
        }

        .composer-form {
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }

        .composer-form textarea {
            width: 100%;
            min-height: 92px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--soft);
            padding: .8rem .9rem;
            color: var(--ink);
            resize: vertical;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .send-button,
        .action-button,
        .danger-button {
            border: none;
            cursor: pointer;
            border-radius: 12px;
            font-weight: 800;
            transition: filter .15s ease;
        }

        .search-submit,
        .send-button,
        .action-button {
            font-family: inherit;
        }

        .send-button {
            background: var(--brand);
            color: white;
            padding: .8rem 1.25rem;
            box-shadow: 0 12px 20px -14px var(--primary);
        }

        .action-button {
            background: var(--soft);
            color: var(--ink);
            border: 1px solid var(--line);
            padding: .7rem 1rem;
        }

        .danger-button {
            background: color-mix(in srgb, var(--danger) 10%, var(--surface));
            color: var(--danger);
            border: 1px solid color-mix(in srgb, var(--danger) 30%, var(--line));
            padding: .7rem 1rem;
        }

        .danger-button.full { width: 100%; }

        .send-button:hover,
        .action-button:hover,
        .danger-button:hover,
        .ghost-button:hover,
        .canned-btn:hover {
            filter: brightness(0.98);
        }

        .details-panel {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: .9rem;
            overflow-y: auto;
        }

        .details-heading {
            margin: 0;
            padding-bottom: .75rem;
            border-bottom: 1px solid var(--line);
            font-size: 1rem;
        }

        .detail-actions {
            display: flex;
            gap: .5rem;
            margin-top: .8rem;
        }

        .detail-student {
            background: var(--soft);
            border-radius: 14px;
            padding: .9rem;
            border: 1px solid var(--line);
            overflow-wrap: anywhere;
        }

        .student-head {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-bottom: .6rem;
        }

        .student-head .avatar-mini {
            width: 42px;
            height: 42px;
            font-size: 1rem;
        }

        .student-head strong {
            display: block;
            font-size: 1rem;
        }

        .student-head small {
            color: var(--muted);
        }

        .detail-course {
            font-weight: 700;
            margin: .25rem 0 .45rem;
            color: var(--ink);
        }

        .detail-foot {
            color: var(--muted);
            font-size: .76rem;
            margin-top: .45rem;
        }

        .pagination {
            padding: .5rem .75rem 1rem;
        }

        .pagination nav > div:first-child {
            color: var(--muted);
            font-size: .78rem;
        }

        .pagination nav > div:last-child {
            display: flex;
            justify-content: center;
        }

        .pagination nav > div:last-child > span,
        .pagination nav > div:last-child > a {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .pagination nav a,
        .pagination nav span[aria-current="page"] span,
        .pagination nav span[aria-disabled="true"] span {
            padding: .35rem .65rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--muted);
            font-size: .8rem;
        }

        .pagination nav span[aria-current="page"] span {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .field label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--muted);
        }

        .field select {
            width: 100%;
            border-radius: 12px;
            padding: .72rem .8rem;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
        }

        .contact-detail {
            display: grid;
            gap: .3rem;
            color: var(--muted);
            font-size: .82rem;
            overflow-wrap: anywhere;
        }

        .flash-message {
            width: min(1480px, calc(100% - 3rem));
            margin: 1rem auto 0;
            padding: .75rem 1rem;
            border-radius: 12px;
            background: color-mix(in srgb, var(--success) 12%, var(--surface));
            color: var(--success);
            font-size: .9rem;
        }

        .flash-message.error {
            background: color-mix(in srgb, var(--danger) 10%, var(--surface));
            color: var(--danger);
        }

        .reply-feedback {
            display: none;
            padding: .55rem .75rem;
            border-radius: 10px;
            background: color-mix(in srgb, var(--success) 12%, var(--surface));
            color: var(--success);
            font-size: .82rem;
        }

        .reply-feedback.visible { display: block; }
        .reply-feedback.error {
            background: color-mix(in srgb, var(--danger) 10%, var(--surface));
            color: var(--danger);
        }

        .sla-box {
            border-radius: 12px;
            padding: .8rem .9rem;
            background: var(--brand-soft);
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.7;
        }

        .sla-box.warning {
            background: color-mix(in srgb, var(--danger) 10%, var(--surface));
            color: var(--danger);
            font-weight: 700;
        }

        .empty-state {
            padding: 1.5rem 1rem;
            text-align: center;
            color: var(--muted);
            border: 1px dashed var(--line);
            border-radius: 12px;
            margin: 1rem;
        }

        .empty-thread {
            display: grid;
            min-height: 100%;
            place-items: center;
        }

        .section-label {
            margin: 0;
            font-size: .88rem;
            font-weight: 800;
        }

        @media (max-width: 1100px) {
            .layout {
                grid-template-columns: minmax(240px, 300px) minmax(0, 1fr);
                height: auto;
            }
            .details-panel {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: start;
            }
            .details-heading,
            .details-panel > form:last-of-type { grid-column: 1 / -1; }
            .list {
                max-height: 520px;
            }
            .thread-panel {
                min-height: 560px;
            }
        }

        @media (max-width: 760px) {
            .topbar {
                padding: .75rem 1rem;
            }
            .stats,
            .layout,
            .flash-message {
                width: calc(100% - 2rem);
            }
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .7rem;
            }
            .layout {
                grid-template-columns: 1fr;
                gap: .8rem;
                padding: .8rem 0 1.25rem;
            }
            .inbox-panel { min-height: 320px; max-height: 440px; }
            .thread-panel { min-height: 620px; }
            .details-panel { display: flex; grid-column: auto; }
            .brand-copy small {
                display: none;
            }
            .message {
                max-width: 92%;
            }
        }

        @media (max-width: 480px) {
            .topbar { align-items: flex-start; }
            .brand { gap: .55rem; }
            .topbar-actions { gap: .45rem; }
            .user-badge > span:last-child { display: none; }
            .ghost-button { padding: .55rem .7rem; font-size: .82rem; }
            .stats,
            .layout,
            .flash-message { width: calc(100% - 1.25rem); }
            .stat-card { min-height: 82px; padding: .8rem; }
            .stat-card b { font-size: 1.5rem; }
            .thread-head { align-items: flex-start; padding: .85rem; }
            .thread-title small { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .messages,
            .composer { padding: .85rem; }
            .form-actions { align-items: stretch; flex-direction: column; }
            .send-button { width: 100%; }
        }
    </style>
    @include('components.design-system')
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <span class="logo" aria-hidden="true">م</span>
            <div class="brand-copy">
                <strong>لوحة دعم الطلاب</strong>
                <small>استفسارات الطلاب والمدرّسين</small>
            </div>
        </div>

        <div class="topbar-actions">
            <a class="ghost-button" href="{{ route('admin.support-inquiries.index') }}">إعادة تحميل</a>
            <div class="user-badge">
                <span class="avatar-mini">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name ?? 'د', 0, 1)) }}</span>
                <span>{{ auth()->user()->name ?? 'فريق الدعم' }}</span>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="flash-message" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-message error" role="alert">{{ session('error') }}</div>
    @endif

    <div class="stats">
        <div class="stat-card">
            <b data-stat="new_count">{{ $stats->new_count ?? 0 }}</b>
            <small>جديدة</small>
        </div>
        <div class="stat-card">
            <b data-stat="in_progress_count">{{ $stats->in_progress_count ?? 0 }}</b>
            <small>قيد المعالجة</small>
        </div>
        <div class="stat-card danger">
            <b data-stat="late_count">{{ $stats->late_count ?? 0 }}</b>
            <small>متأخرة</small>
        </div>
        <div class="stat-card">
            <b data-stat="resolved_count">{{ $stats->resolved_count ?? 0 }}</b>
            <small>محلولة</small>
        </div>
    </div>

    <main class="layout">
        <aside class="panel inbox-panel" aria-label="القائمة">
            <div class="inbox-tools">
                <h2 class="inbox-heading">صندوق الاستفسارات</h2>
                <form method="GET" action="{{ route('admin.support-inquiries.index') }}">
                    <input
                        class="search-box"
                        type="search"
                        name="search"
                        value="{{ request('search', '') }}"
                        placeholder="بحث باسم الطالب أو البريد أو السؤال…"
                        autocomplete="off"
                    >
                    <button class="search-submit" type="submit">بحث</button>
                </form>

                <div class="tabs">
                    @foreach ($statusMap as $value => $label)
                        @php
                            $query = request()->except(['status', 'page', 'inquiry']);
                            $query['status'] = $value;
                        @endphp
                        <a
                            href="{{ route('admin.support-inquiries.index', $query) }}"
                            class="tab-item {{ $selectedStatus === $value ? 'active' : '' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="list">
                @forelse ($filteredInquiries as $inquiry)
                    @php
                        $messageText = is_array($inquiry->conversation ?? null)
                            ? collect($inquiry->conversation)->first(fn ($m) => ($m['role'] ?? 'user') === 'user')['text'] ?? $inquiry->question
                            : $inquiry->question;
                        $itemTime = optional($inquiry->created_at)->diffForHumans();
                        $lateFlag = $isLate($inquiry);
                        $itemQuery = request()->except(['inquiry']);
                        $itemQuery['inquiry'] = $inquiry->id;
                    @endphp
                    <a
                        href="{{ route('admin.support-inquiries.index', $itemQuery) }}"
                        data-inquiry-id="{{ $inquiry->id }}"
                        class="inquiry-item {{ ($selectedInquiry && $selectedInquiry->id === $inquiry->id) ? 'active' : '' }}"
                    >
                        <div class="item-head">
                            <span class="item-name">{{ $inquiry->contact_name ?: 'زائر' }}</span>
                            <span class="item-time">{{ $itemTime }}</span>
                        </div>
                        <span class="item-course">استفسار #{{ $inquiry->id }}</span>
                        <div class="item-summary">{{ Str::limit(strip_tags($messageText), 120) }}</div>
                        @if ($inquiry->contact_email || $inquiry->contact_phone)
                            <small class="item-contact">
                                {{ $inquiry->contact_email }}
                                @if ($inquiry->contact_email && $inquiry->contact_phone) · @endif
                                {{ $inquiry->contact_phone }}
                            </small>
                        @endif
                        <div class="pill-row">
                            <span class="status-pill inquiry-status-pill {{ $statusColors[$inquiry->status] ?? 'status-new' }}">{{ $statusLabel($inquiry->status) }}</span>
                            @if ($lateFlag)
                                <span class="status-pill late-pill">متأخرة</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="empty-state">لا توجد استفسارات مطابقة للبحث الحالي.</div>
                @endforelse
            </div>
            @if ($inquiries->hasPages())
                <div class="pagination">{{ $inquiries->links() }}</div>
            @endif
        </aside>

        <section class="panel thread-panel" aria-live="polite">
            @if ($selectedInquiry)
                @php
                    $selectedLabel = $statusLabel($selectedInquiry->status ?? 'new');
                @endphp
                <div class="thread-head">
                    <div class="thread-title">
                        <h2>سجل المحادثة · #{{ $selectedInquiry->id }}</h2>
                        <small>{{ $selectedInquiry->question }} · منذ {{ optional($selectedInquiry->created_at)->diffForHumans() }}</small>
                    </div>
                    <span class="status-pill current-status-pill {{ $statusColors[$selectedInquiry->status] ?? 'status-new' }}">{{ $selectedLabel }}</span>
                </div>

                <div class="messages" aria-live="polite" aria-relevant="additions">
                    @forelse ($messages as $message)
                        @php
                            $role = $message['role'] ?? 'user';
                            $text = $message['text'] ?? '';
                            $time = $formatTime($message['created_at'] ?? $selectedInquiry->created_at);
                        @endphp
                        <div class="message {{ $role }}">
                            <p>{{ $text }}</p>
                            <small>
                                {{ $messageLabel($role) }} · {{ $time }}
                                @if ($role === 'support' && !empty($message['email_sent']))
                                    · أُرسل بالبريد
                                @endif
                            </small>
                        </div>
                    @empty
                        <div class="empty-state">لا توجد رسائل في هذه المحادثة.</div>
                    @endforelse
                </div>

                <div class="composer">
                    <div class="canned-wrap">
                        @foreach ($supportReplyCanned as $label => $reply)
                            <button type="button" class="canned-btn" data-reply="{{ $reply }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.support-inquiries.respond', $selectedInquiry) }}"
                          class="composer-form" data-inquiry-id="{{ $selectedInquiry->id }}">
                        @csrf
                        <textarea name="message" maxlength="2000" placeholder="اكتب ردك للطالب…">{{ old('message') }}</textarea>
                        <div class="form-actions">
                            <small class="detail-foot">سيتم حفظ الرد وإرساله إلى الزائر عند توفر بريده الإلكتروني.</small>
                            <button class="send-button" type="submit">إرسال الرد</button>
                        </div>
                    </form>
                    <div class="reply-feedback" role="status" aria-live="polite"></div>
                </div>
            @else
                <div class="empty-state empty-thread">اختر استفساراً من القائمة لعرض المحادثة.</div>
            @endif
        </section>

        <aside class="panel details-panel" aria-label="تفاصيل الاستفسار">
            @if ($selectedInquiry)
                <h3 class="section-label details-heading">تفاصيل الاستفسار</h3>
                <h3 class="section-label">بيانات التواصل</h3>
                <div class="detail-student">
                    <div class="student-head">
                        <span class="avatar-mini">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($selectedInquiry->contact_name ?: 'ز', 0, 1)) }}</span>
                        <div>
                            <strong>{{ $selectedInquiry->contact_name ?: 'زائر' }}</strong>
                            <small>رقم الاستفسار #{{ $selectedInquiry->id }}</small>
                        </div>
                    </div>
                    <div class="contact-detail">
                        <span>{{ $selectedInquiry->contact_email ?: 'لا يوجد بريد إلكتروني' }}</span>
                        @if ($selectedInquiry->contact_phone)
                            <span>{{ $selectedInquiry->contact_phone }}</span>
                        @endif
                        <span>تاريخ الإرسال: {{ optional($selectedInquiry->created_at)->translatedFormat('Y/m/d H:i') }}</span>
                    </div>
                </div>

                <div class="field">
                    <label>الاستفسار</label>
                    <div class="detail-student">{{ $selectedInquiry->question }}</div>
                </div>

                <form method="POST" action="{{ route('admin.support-inquiries.update', $selectedInquiry) }}">
                    @csrf
                    @method('PATCH')
                    <div class="field">
                        <label>الحالة</label>
                        <select name="status">
                            <option value="new" {{ $selectedInquiry->status === 'new' ? 'selected' : '' }}>جديدة</option>
                            <option value="in_progress" {{ $selectedInquiry->status === 'in_progress' ? 'selected' : '' }}>قيد المعالجة</option>
                            <option value="resolved" {{ $selectedInquiry->status === 'resolved' ? 'selected' : '' }}>محلولة</option>
                        </select>
                    </div>
                    <div class="detail-actions">
                        <button class="action-button" type="submit">تحديث الحالة</button>
                    </div>
                </form>

                <div class="sla-box {{ $isLate($selectedInquiry) ? 'warning' : '' }}">
                    @if (($selectedInquiry->status ?? 'new') === 'resolved')
                        تم حل هذا الاستفسار.
                    @elseif ($isLate($selectedInquiry))
                        ⚠️ تجاوزت الاستجابة المستهدفة لأكثر من ساعتين.
                    @else
                        الاستجابة الأولى المستهدفة: خلال ساعتين.
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.support-inquiries.destroy', $selectedInquiry) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الاستفسار؟');">
                    @csrf
                    @method('DELETE')
                    <button class="danger-button full" type="submit">حذف الاستفسار</button>
                </form>
            @else
                <div class="empty-state">لا يوجد استفسار محدد.</div>
            @endif
        </aside>
    </main>

    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.canned-btn');
            if (!button) return;
            const text = button.getAttribute('data-reply');
            const textarea = document.querySelector('.composer-form textarea');
            if (!textarea) return;
            textarea.value = text;
            textarea.focus();
        });

        const replyForm = document.querySelector('.composer-form');
        if (replyForm) {
            const messages = document.querySelector('.messages');
            const textarea = replyForm.querySelector('textarea[name="message"]');
            const submitButton = replyForm.querySelector('button[type="submit"]');
            const feedback = document.querySelector('.reply-feedback');
            const statusLabels = {
                new: 'جديدة',
                in_progress: 'قيد المعالجة',
                resolved: 'محلولة',
            };
            const statusClasses = {
                new: 'status-new',
                in_progress: 'status-open',
                resolved: 'status-resolved',
            };

            function showFeedback(message, isError) {
                feedback.textContent = message;
                feedback.classList.toggle('error', isError);
                feedback.classList.add('visible');
            }

            function updateStatus(status) {
                const currentStatus = document.querySelector('.current-status-pill');
                if (currentStatus) {
                    currentStatus.classList.remove('status-new', 'status-open', 'status-resolved');
                    currentStatus.classList.add(statusClasses[status] || 'status-new');
                    currentStatus.textContent = statusLabels[status] || statusLabels.new;
                }

                const statusSelect = document.querySelector('select[name="status"]');
                if (statusSelect) statusSelect.value = status;

                const inquiryItem = document.querySelector(
                    `[data-inquiry-id="${replyForm.dataset.inquiryId}"]`
                );
                const itemStatus = inquiryItem?.querySelector('.inquiry-status-pill');
                if (itemStatus) {
                    itemStatus.classList.remove('status-new', 'status-open', 'status-resolved');
                    itemStatus.classList.add(statusClasses[status] || 'status-new');
                    itemStatus.textContent = statusLabels[status] || statusLabels.new;
                }
            }

            function updateStats(stats) {
                Object.entries(stats || {}).forEach(([key, value]) => {
                    const counter = document.querySelector(`[data-stat="${key}"]`);
                    if (counter) counter.textContent = value ?? 0;
                });
            }

            function createMessage(text, time, emailSent, pending) {
                const bubble = document.createElement('div');
                bubble.className = `message support${pending ? ' pending' : ''}`;
                const body = document.createElement('p');
                body.textContent = text;
                const meta = document.createElement('small');
                meta.textContent = `فريق الدعم · ${time}${emailSent ? ' · أُرسل بالبريد' : ''}`;
                bubble.append(body, meta);
                return bubble;
            }

            replyForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (submitButton.disabled) return;

                const replyText = textarea.value.trim();
                if (!replyText) {
                    textarea.focus();
                    return;
                }

                feedback.classList.remove('visible', 'error');
                const shouldFollow = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 100;
                const pending = createMessage(replyText, 'جارٍ الإرسال…', false, true);
                messages.append(pending);
                if (shouldFollow) messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });

                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
                const originalButtonText = submitButton.textContent;
                submitButton.textContent = 'جارٍ الإرسال…';

                try {
                    const response = await fetch(replyForm.action, {
                        method: 'POST',
                        body: new FormData(replyForm),
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': replyForm.querySelector('input[name="_token"]').value,
                        },
                    });
                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        throw new Error(response.status === 419
                            ? 'انتهت الجلسة. حدّث الصفحة وسجّل الدخول مرة أخرى.'
                            : 'تعذر إرسال الرد. تحقق من اتصالك وحاول مرة أخرى.');
                    }
                    const result = await response.json();

                    if (!response.ok) {
                        const validationMessage = Object.values(result.errors || {}).flat()[0];
                        throw new Error(validationMessage || result.message || 'تعذر إرسال الرد. حاول مرة أخرى.');
                    }

                    const time = new Intl.DateTimeFormat('ar-EG', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }).format(new Date(result.reply.created_at));
                    pending.replaceWith(createMessage(
                        result.reply.text,
                        time,
                        result.email_sent,
                        false
                    ));
                    textarea.value = '';
                    updateStatus(result.status);
                    updateStats(result.stats);
                    showFeedback(result.message, result.email_failed);
                    if (shouldFollow) messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });
                } catch (error) {
                    pending.remove();
                    showFeedback(
                        error instanceof Error ? error.message : 'تعذر إرسال الرد. تحقق من اتصالك وحاول مرة أخرى.',
                        true
                    );
                } finally {
                    submitButton.disabled = false;
                    submitButton.removeAttribute('aria-busy');
                    submitButton.textContent = originalButtonText;
                }
            });
        }
    </script>
</body>
</html>
