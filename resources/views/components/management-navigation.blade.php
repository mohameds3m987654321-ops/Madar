<header class="management-header">
    <div class="management-nav">
        <a class="management-brand" href="{{ url('/') }}">مدار</a>
        <nav class="management-links" aria-label="التنقل الإداري">
            @if (auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>الرئيسية</a>
                <a href="{{ route('users.index') }}" @if (request()->routeIs('users.index', 'users.edit', 'users.update')) aria-current="page" @endif>المستخدمون</a>
                <a href="{{ route('courses.index') }}" @if (request()->routeIs('courses.index')) aria-current="page" @endif>الكورسات</a>
                <a href="{{ route('admin.support-inquiries.index') }}" @if (request()->routeIs('admin.support-inquiries.*')) aria-current="page" @endif>استفسارات الدعم</a>
            @else
                <a href="{{ route('teacher.dashboard') }}" @if (request()->routeIs('teacher.dashboard', 'courses.create', 'courses.edit', 'courses.update', 'courses.publish', 'lessons.*', 'quizzes.edit', 'quizzes.save')) aria-current="page" @endif>لوحة المدرس</a>
                <a href="{{ route('courses.create') }}" @if (request()->routeIs('courses.create')) aria-current="page" @endif>إضافة كورس</a>
                <a href="{{ route('courses.index') }}" @if (request()->routeIs('courses.index')) aria-current="page" @endif>استعراض المنصة</a>
            @endif
            <a href="{{ route('guide') }}" @if (request()->routeIs('guide')) aria-current="page" @endif>دليل الاستخدام</a>
            <a href="{{ route('users.profile') }}">حسابي</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="management-logout" type="submit">تسجيل الخروج</button>
        </form>
    </div>
</header>
