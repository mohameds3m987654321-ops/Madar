<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ auth()->check() ? (auth()->user()->preferred_theme ?? 'light') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكورسات</title>
    <link rel="stylesheet" href="{{ asset('css/appearance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-search.css') }}">
    @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'teacher'], true))
        <link rel="stylesheet" href="{{ asset('css/management-navigation.css') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f4f7f5;--card:#fff;--ink:#172b28;--mut:#657a75;--line:#e1eae5;
            --acc:#147866;--acc2:#e2f3ed;--err:#b3261e;--err2:#fdecea;
            --brand-soft:#e2f3ed;--brand-line:#a9d6c8;
            --shadow:0 1px 2px rgba(15,38,34,.06),0 10px 30px rgba(15,38,34,.07);
            --shadow-up:0 2px 4px rgba(15,38,34,.06),0 22px 44px rgba(15,38,34,.14);
        }
        @media (prefers-color-scheme:dark){:root{
            --bg:#101a17;--card:#182521;--ink:#e4efea;--mut:#a3b7ae;--line:#2b4038;
            --acc:#69d2ae;--acc2:#203a32;--err:#ff8a80;--err2:#3a1a18;--shadow:none;--shadow-up:0 20px 40px rgba(0,0,0,.4);
            --brand-soft:#203a32;--brand-line:#356052;
        }}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",Tahoma,Arial,sans-serif;line-height:1.6}
        main{max-width:1160px;margin:0 auto;padding:36px 20px 60px}
        :focus-visible{outline:3px solid var(--acc);outline-offset:2px}
        svg{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

        .hero{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;padding:28px 32px;margin-bottom:24px;border-radius:22px;color:#fff;
              background:radial-gradient(900px 300px at 100% 0,rgba(255,255,255,.18),transparent 60%),var(--green-hero);position:relative;overflow:hidden}
        .hero::after{content:"";position:absolute;width:220px;aspect-ratio:1;left:8%;top:-120%;border:1px solid rgba(255,255,255,.16);border-radius:50%;box-shadow:0 0 0 32px rgba(255,255,255,.045),0 0 0 66px rgba(255,255,255,.03);pointer-events:none}
        .hero h1{margin:0;font-size:2rem;letter-spacing:-.3px}
        .hero p{margin:4px 0 0;opacity:.85}
        .count{display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:4px 14px;border-radius:99px;background:rgba(255,255,255,.16);font-size:.9rem}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font:inherit;font-weight:500;cursor:pointer;border-radius:12px;padding:10px 20px;text-decoration:none;border:1px solid transparent;transition:transform .2s var(--motion-ease),background .2s,box-shadow .2s}
        .btn:hover{transform:translateY(-2px)}
        .btn.light{background:#fff;color:#0d5b4d;box-shadow:0 6px 18px rgba(0,0,0,.18)}
        .btn.primary{background:var(--acc);color:#fff}
        @media (prefers-color-scheme:dark){.btn.primary{color:#062019}}
        .btn.soft{background:var(--acc2);color:var(--acc)}
        .btn.soft:hover{background:var(--acc);color:#fff}
        .btn.danger{background:transparent;color:var(--err);border-color:var(--line)}
        .btn.danger:hover{background:var(--err2);border-color:var(--err)}

        .alert{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:14px;margin-bottom:20px;background:var(--acc2);color:var(--acc)}
        .search{position:relative;max-width:420px;margin-bottom:26px}
        .search svg{position:absolute;top:50%;inset-inline-start:14px;transform:translateY(-50%);color:var(--mut)}
        .search input{width:100%;font:inherit;padding:12px 16px 12px 16px;padding-inline-start:44px;border:1px solid var(--line);border-radius:14px;background:var(--card);color:var(--ink);box-shadow:var(--shadow)}
        .search-error{margin:-18px 0 20px;color:var(--err);font-size:.88rem}

        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:22px}
        .card{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--line);border-radius:20px;overflow:hidden;box-shadow:var(--shadow);
              transition:transform .28s var(--motion-ease),box-shadow .28s,border-color .28s;animation:rise .55s var(--motion-ease) both;animation-delay:calc(var(--i) * 55ms)}
        .card:hover{transform:translateY(-6px);box-shadow:var(--shadow-up);border-color:var(--brand-line)}
        @keyframes rise{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
        .cover{position:relative;aspect-ratio:16/10;overflow:hidden}
        .cover img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
        .card:hover .cover img{transform:scale(1.06)}
        .ph{width:100%;height:100%;display:grid;place-items:center;font-size:3.6rem;font-weight:700;color:rgba(255,255,255,.9)}
        .lvl{position:absolute;top:12px;inset-inline-start:12px;padding:3px 12px;border-radius:99px;font-size:.8rem;font-weight:500;backdrop-filter:blur(8px);background:rgba(255,255,255,.88);color:#0f2622}
        .lvl.beginner{color:#0b7a4a}.lvl.intermediate{color:#3966bd}.lvl.advanced{color:#d38c19}
        .price{position:absolute;bottom:12px;inset-inline-end:12px;padding:4px 14px;border-radius:12px;font-weight:700;background:rgba(10,25,22,.78);color:#fff;backdrop-filter:blur(8px)}
        .body{padding:18px 20px 8px;flex:1}
        .t{margin:0 0 6px;font-size:1.12rem;font-weight:700}
        .d{margin:0;color:var(--mut);font-size:.9rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.9em}
        .meta{display:flex;gap:16px;margin-top:14px;color:var(--mut);font-size:.85rem}
        .meta span{display:inline-flex;align-items:center;gap:6px}
        .rating-summary{display:flex;align-items:center;gap:7px;margin-top:10px;color:var(--mut);font-size:.85rem}
        .rating-summary strong{color:var(--ink)}
        .rating-stars{color:#f5b942;letter-spacing:2px}
        .foot{display:flex;gap:10px;padding:16px 20px 20px}
        .foot form{margin:0}
        .foot .btn{padding:9px 16px;font-size:.92rem}
        .foot .edit{flex:1}

        .foot .buy-form{flex:1}
        .foot .buy-form .btn{width:100%}
        .course-link{display:block;color:inherit;text-decoration:none;flex:1}
        .course-link:focus-visible{outline:3px solid var(--acc);outline-offset:-3px}
        .empty{padding:64px 20px;text-align:center;color:var(--mut);background:var(--card);border:2px dashed var(--line);border-radius:20px}
        .pages{margin-top:28px}
        @media (max-width:560px){.hero{padding:22px}.hero h1{font-size:1.6rem}}
        @media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
    </style>
    @include('components.appearance')
</head>
<body @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'teacher'], true)) class="{{ auth()->user()->role === 'admin' ? 'admin' : 'teacher' }}-management" @endif>
    @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'teacher'], true))
        @include('components.management-navigation')
    @endif
<main>
    <header class="hero">
        <div>
            <h1>الكورسات</h1>
            <p>أدر كورساتك: أضف، عدّل، أو احذف بسهولة.</p>
            <span class="count">إجمالي الكورسات: {{ $courses->total() }}</span>
        </div>
        @if (auth()->check() && (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin'))
        <a class="btn light" href="{{ route('courses.create') }}">
            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            إضافة كورس
        </a>
        @endif
        @if (auth()->check() && auth()->user()->role === 'student')
        <a class="btn light" href="{{ route('courses.my') }}">كورساتي</a>
        @endif
    </header>

    @if (session('success'))
        <div class="alert" role="status">
            <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @include('components.global-search')
    @error('q') <p class="search-error" role="alert">{{ $message }}</p> @enderror

    @php $levels = ['beginner' => 'مبتدئ', 'intermediate' => 'متوسط', 'advanced' => 'متقدم']; @endphp

    @if ($courses->count())
        <section class="grid">
            @foreach ($courses as $course)
                <article class="card" style="--i: {{ $loop->index }}">
                    <a class="course-link" href="{{ route('courses.show', $course->id) }}">
                    <div class="cover">
                        @if ($course->image)
                            <img src="{{ asset('storage/'.$course->image) }}" alt="{{ $course->title }}" loading="lazy">
                        @else
                            <div class="ph" aria-hidden="true"
                                 style="background:linear-gradient(135deg,hsl({{ ($course->id * 47) % 360 }} 60% 42%),hsl({{ ($course->id * 47 + 50) % 360 }} 65% 30%))">
                                {{ mb_substr($course->title, 0, 1) }}
                            </div>
                        @endif
                        <span class="lvl {{ $course->level }}">{{ $levels[$course->level] ?? $course->level }}</span>
                        <span class="price">{{ $course->price > 0 ? number_format($course->price, 0).' ج.م' : 'مجاني' }}</span>
                    </div>

                    <div class="body">
                        <h2 class="t">{{ $course->title }}</h2>
                        <p class="d">{{ $course->description }}</p>
                        <div class="meta">
                            <span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $course->duration }} ساعة</span>
                            <span><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>{{ $course->created_at->format('Y/m/d') }}</span>
                        </div>
                        @if ($course->reviews_count > 0)
                            <div class="rating-summary" aria-label="تقييم الكورس">
                                <span class="rating-stars">★</span>
                                <strong>{{ number_format($course->reviews_avg_rating, 1) }}</strong>
                                <span>({{ $course->reviews_count }} تقييم)</span>
                            </div>
                        @endif
                    </div>
                    </a>

                    @can('update', $course)
                    <div class="foot">
                        <a class="btn soft edit" href="{{ route('courses.edit', $course->id) }}">
                            <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            تعديل
                        </a>
                        @can('delete', $course)
                        <form method="POST" action="{{ route('courses.destroy', $course->id) }}"
                              onsubmit="return confirm('حذف كورس {{ $course->title }}؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn danger" type="submit" aria-label="حذف {{ $course->title }}">
                                <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>
                                حذف
                            </button>
                        </form>
                        @endcan
                    </div>
                    @else
                    <div class="foot">
                        <a class="btn primary buy" style="width:100%" href="{{ route('courses.show', $course->id) }}">
                            تفاصيل الكورس والاشتراك
                        </a>
                    </div>
                    @endif
                </article>
            @endforeach
        </section>
    @else
        <div class="empty">
            @if (request('q'))
                لا توجد كورسات مطابقة للبحث.
            @else
                لا توجد كورسات بعد. ابدأ بإضافة أول كورس.
            @endif
        </div>
    @endif

    @if ($courses instanceof \Illuminate\Pagination\AbstractPaginator && $courses->hasPages())
        <div class="pages">{{ $courses->links() }}</div>
    @endif
</main>
</body>
</html>
