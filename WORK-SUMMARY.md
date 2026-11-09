# 📋 ملخص كل الشغل اللي اتعمل في مشروع منصة مدار

> تاريخ: أكتوبر 2026 — إعادة تصميم كاملة للواجهة الأمامية (Front-End Redesign)
> المشروع: منصة مدار التعليمية — Laravel 12 + Blade

---

## 🎯 الهدف العام

إعادة بناء الواجهة الأمامية بالكامل: توحيد الألوان في نظام واحد، ترتيب الصفحات، وإضافة موشنات احترافية — مع الحفاظ التام على منطق الـ backend والـ routes.

---

## المرحلة ١ — نظام التصميم الموحد (Design System)

### `public/css/appearance.css` (أُعيد بناؤه بالكامل)

| القيمة              | فاتح                                      | داكن      |
| ------------------- | ----------------------------------------- | --------- |
| الخلفية             | `#f4f7f5`                                 | `#101a17` |
| النص                | `#172b28`                                 | `#e4efea` |
| اللون الأساسي       | `#147866`                                 | `#69d2ae` |
| الخطوط الفاصلة      | `#e1eae5`                                 | `#2b4038` |
| ذهبي (نجوم/تنبيهات) | `#d38c19`                                 | `#f2c879` |
| أخطاء               | `#b3261e`                                 | `#ff9d91` |
| بنر أخضر            | `linear-gradient(125deg,#0d5b4d,#18826c)` | أغمق      |

**موشنات مشتركة (Motion Utilities) أُضيفت:**

- `md-rise` / `md-fade` / `md-pop` — أنيميشن دخول
- `md-float` — طفو عايم للبادجات
- `md-spin` / `md-shimmer` — دوران ولمعة
- `.md-reveal` — ظهور تدريجي عند التمرير (IntersectionObserver)
- `.md-stagger` — دخول متتابع للعناصر
- `.md-lift` / `.md-btn` — hover احترافي
- `.md-fill` — امتلاء سلس لشرائط التقدم
- احترام كامل لـ `prefers-reduced-motion`

---

## المرحلة ٢ — إعادة بناء الصفحات

### 1. الصفحة الرئيسية `welcome.blade.php` (إعادة بناء كاملة)

- Header ثابت بـ blur + خط تحت الروابط عند hover
- Hero: orbit دوّار، glow، بطاقة فيديو مايلة -2°، بطاقات عايمة
- عدّاد أرقام متحرك في الإحصائيات (count-up بـ easing)
- 6 بطاقات مميزات بأيقونات بتدور عند hover
- شبكة كورسات، خطوات مرقّمة بأرقام دوارة، مدرسون، آراء، FAQ بفتح ناعم
- CTA بـ shimmer متحرك ودائرة دوّارة
- فوتر بروابط تنزلق عند hover

### 2. لوحة المدير `users/dashboard.blade.php`

- بنر بزخارف دوائر، كروت إحصائيات ترفع عند hover، صفوف تفاعلية، motion-reveal

### 3. لوحة المدرس `Courses/teacher-dashboard.blade.php`

- توحيد كامل للألوان من الـ palette الجديدة، hover على الجداول والكروت

### 4. كورسات `Courses/index.blade.php`

- نفس الـ palette، مستويات بألوان موحدة (مبتدئ أخضر / متوسط أزرق / متقدم ذهبي)

### 5. كورساتي `Courses/my.blade.php`

- تحويل من داكن مفروض → فاتح/داكن حسب اختيار المستخدم، دخول متتابع للكروت

### 6. صفحة كورس `Courses/show.blade.php`

- نفس المعالجة، صفوف محاضرات تنزلق، بطاقات شراء تفاعلية

### 7. نشاطي `users/profile.blade.php`

- توحيد + hover على كل الكروت، حلقة تقدم تلمع

---

## المرحلة ٣ — ترتيب صفحات التسجيل (Auth Pages)

### `users/login.blade.php` (كان أسوأ صفحة — أُعيد بناؤها بالكامل)

**قبل:** إنجليزي بالكامل (`lang="en"`, "Welcome back", "Login", "Your App") بخط Manrope وبدون RTL
**بعد:**

- عربي RTL كامل بخط IBM Plex Sans Arabic
- لوحة علامة خضراء: لوجو مدار SVG + شبكة خطوط متحركة (grid drift) + هالة ضوئية
- **بطاقة التنبيهات الأمنية** (تحت اللوجو وفوق القفل — طلب صريح):
    1. تأكد إنك على الموقع الرسمي والعنوان يبدأ بـ https
    2. مدار ما يطلبش كلمة المرور أو رمز التحقق برسالة أو مكالمة أبدًا
    3. متشاركش بيانات حسابك مع أي حد، ولا تسجل دخول من جهاز عام
    4. لقيت حاجة غريبة؟ غيّر كلمة المرور فورًا وبلّغ الدعم
- قفل يفتح عند صحة البيانات (شackle يرتفع ويدور)
- رسالة خطأ السيرفر بتعمل اهتزاز (md-shake)
- زر إظهار كلمة المرور بخلفية واضحة

### `users/create.blade.php` (التسجيل — ٣ خطوات wizard)

- نفس لوحة العلامة الموحدة، ٣ نقاط مميزات تحت الوصف
- نفس القفل اللي بيتحقق مع كل تغيير في أي حقل (كان بيستنى الخطوة ٣ فقط)
- زر "إنشاء الحساب" في الخطوة الأخيرة بيعمل نبضة ضوء (pulseGlow)
- ٤ نقاط تدخل بأنيميشن متتابع

### `users/forgot-password.blade.php`

- بطاقة مركزية، لوجو مدار أعلى البطاقة، نفس الألوان، تحقق فوري من البريد

### `users/reset-password.blade.php`

- نفس المعالجة بالظبط

### `users/verify-email.blade.php`

- كانت داكنة مفروضة دايمًا → دلوقتي بتتبع تفضيل المستخدم، ونفس البطاقة الموحدة

### `users/update.blade.php` (تعديل مستخدم — أدمن)

- توحيد الألوان من الـ palette

---

## 🧪 التحقق الفعلي (كل مرحلة اتاخدت screenshots)

كل تعديل اتحقق منه بـ headless browser قبل ما يقال "خلص":

| الاختبار             | النتيجة                                   |
| -------------------- | ----------------------------------------- |
| console errors       | **0** في كل الصفحات                       |
| failed requests      | **0**                                     |
| الوضع الفاتح/الداكن  | شغالين في الكل                            |
| Reveal عند التمرير   | 0 عناصر مخفية (كلها بتظهر)                |
| معالج التسجيل wizard | شغال — الخطوة ١→٢ انتقلت بنجاح بعد التحقق |
| view:cache           | بينجح بدون أخطاء syntax                   |

**مجموعة صور نهائية (9 صور) في `_shots/`:**

```
01-home-light-top.png     05-login.png
02-home-light-full.png    06-register-step1.png
03-home-dark-top.png      07-register-step2.png
04-home-dark-full.png     08-forgot-password.png
                          10-guide.png
```

---

## 📁 الملفات اللي اتعدلت (12 ملف)

```
public/css/appearance.css                        ← إعادة كتابة كاملة
resources/views/welcome.blade.php                ← إعادة بناء كاملة
resources/views/users/login.blade.php            ← إعادة بناء كاملة
resources/views/users/create.blade.php           ← توحيد + موشنات
resources/views/users/dashboard.blade.php        ← توحيد + موشنات
resources/views/users/profile.blade.php          ← توحيد + موشنات
resources/views/users/forgot-password.blade.php  ← توحيد
resources/views/users/reset-password.blade.php   ← توحيد
resources/views/users/verify-email.blade.php     ← توحيد + إصلاح الثيم
resources/views/users/update.blade.php           ← توحيد
resources/views/Courses/index.blade.php          ← توحيد
resources/views/Courses/my.blade.php             ← توحيد + إصلاح الثيم
resources/views/Courses/show.blade.php           ← توحيد + موشنات
resources/views/Courses/teacher-dashboard.blade.php ← توحيد + موشنات
```

## ✅ القواعد اللي اتحفظت في كل مرحلة

- **صفر تغيير** في أي controller، route، middleware، أو migration
- كل الصفحات شغالة بالـ data-theme الحالي (فاتح/داكن حسب اختيار المستخدم)
- RTL مضبوط بـ logical properties (`inset-inline-start`, `padding-inline`) مش left/right
- كل الأنيميشن بتحترم `prefers-reduced-motion`
- Accessibility: aria-labels, focus-visible outlines, role attributes
