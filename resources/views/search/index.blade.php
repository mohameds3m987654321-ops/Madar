<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث | منصة مدار</title>
    <link rel="stylesheet" href="{{ asset('css/global-search.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:dark;--bg:#0c1513;--surface:#13201d;--ink:#e7f0ed;--muted:#8aa29b;--line:#243732;--accent:#3cc3a6;--soft:#16352f}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.7}
        main{width:min(980px,calc(100% - 32px));margin:0 auto;padding:36px 0 60px}
        h1{margin:0;font-size:1.8rem}
        .intro{margin:4px 0 22px;color:var(--muted)}
        .results{display:grid;gap:12px;margin:22px 0}
        .result{padding:17px 19px;border:1px solid var(--line);border-radius:13px;background:var(--surface)}
        .result h2{margin:0;font-size:1.08rem}
        .result p{margin:4px 0 0;color:var(--muted)}
        .result a{color:var(--accent);text-decoration:none}
        .result a:hover{text-decoration:underline}
        .meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:7px;color:var(--muted);font-size:.88rem}
        .courses{display:flex;flex-wrap:wrap;gap:7px 14px;margin-top:9px}
        .empty{padding:24px;border:1px dashed var(--line);border-radius:13px;color:var(--muted)}
        .pages{margin-top:20px}
        @media(max-width:560px){main{padding-top:22px}.global-search{display:grid;grid-template-columns:1fr 1fr}.global-search__query{grid-column:1/-1}}
    </style>
    @include('components.appearance')
</head>
<body>
<main>
    <h1>البحث</h1>
    <p class="intro">ابحث ضمن المحتوى المتاح حسب صلاحية حسابك.</p>

    @include('components.global-search')

    @if ($query !== '')
        @if ($results->count())
            <p>نتائج البحث عن «{{ $query }}» ({{ $results->total() }} نتيجة)</p>
            <section class="results">
                @foreach ($results as $result)
                    @if ($type === 'courses')
                        <article class="result">
                            <h2><a href="{{ route('courses.show', $result) }}">{{ $result->title }}</a></h2>
                            <p>{{ \Illuminate\Support\Str::limit($result->description, 200) }}</p>
                            <div class="meta">
                                <span>المدرس: {{ $result->teacher?->name }}</span>
                                <span>{{ $result->status === 'published' ? 'منشور' : 'مسودة' }}</span>
                                <span>{{ (float) $result->price > 0 ? number_format($result->price, 2) . ' ج.م' : 'مجاني' }}</span>
                            </div>
                        </article>
                    @elseif ($type === 'teachers')
                        <article class="result">
                            <h2>{{ $result->name }}</h2>
                            @if ($result->courses->isNotEmpty())
                                <p>كورسات منشورة:</p>
                                <div class="courses">
                                    @foreach ($result->courses as $course)
                                        <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
                                    @endforeach
                                </div>
                            @else
                                <p>لا توجد كورسات منشورة حاليًا.</p>
                            @endif
                        </article>
                    @else
                        <article class="result">
                            <h2><a href="{{ route('users.edit', $result) }}">{{ $result->name }}</a></h2>
                            <div class="meta">
                                <span>{{ $result->email }}</span>
                                <span>{{ ['student' => 'طالب', 'teacher' => 'مدرس', 'admin' => 'مدير'][$result->role] ?? $result->role }}</span>
                            </div>
                        </article>
                    @endif
                @endforeach
            </section>
            <div class="pages">{{ $results->links() }}</div>
        @else
            <div class="empty">مفيش نتائج مطابقة لعبارة البحث.</div>
        @endif
    @endif
</main>
</body>
</html>
