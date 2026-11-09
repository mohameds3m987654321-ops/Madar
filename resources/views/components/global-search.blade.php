<form class="global-search" action="{{ route('search') }}" method="GET">
    <label class="global-search__query">
        <span class="sr-only">عبارة البحث</span>
        <input type="search" name="q" value="{{ request('q') }}" maxlength="100"
               minlength="2" placeholder="ابحث عن كورس أو مستخدم..." required>
    </label>
    <label class="global-search__type">
        <span class="sr-only">نوع البحث</span>
        <select name="type">
            <option value="courses" @selected(request('type', 'courses') === 'courses')>كورسات</option>
            @if (auth()->user()->role === 'student')
                <option value="teachers" @selected(request('type') === 'teachers')>مدرسون</option>
            @endif
            @if (auth()->user()->role === 'admin')
                <option value="users" @selected(request('type') === 'users')>كل المستخدمين</option>
            @endif
        </select>
    </label>
    <button type="submit">بحث</button>
</form>
