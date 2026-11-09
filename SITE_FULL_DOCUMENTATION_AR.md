# 📘 التوثيق الكامل لمنصة مدار — كل حاجة صغيرة وكبيرة

> تاريخ التوثيق: 2026-11-01
> المشروع: منصة مدار التعليمية — Laravel 12 + Blade + Tailwind/Vite + Paymob
> جذر المشروع: `d:\xampp\htdocs\return`

---

## 1) نظرة عامة على المنصة

منصة كورسات عربية موجّهة للتعليم المصري، فيها **3 أدوار**:

| الدور                  | ماذا يعمل                                                                                                                      |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| 👨‍🎓 **طالب (student)**  | يتصفح الكاتالوج، يشترك (مجاني/كوبون/Paymob)، يتابع فيديوهات الدروس، يخوض الاختبار النهائي، يقيّم، ويحصل على شهادة موثّقة بـ QR |
| 👨‍🏫 **مدرّس (teacher)** | ينشئ كورسات ودروس ويديرها، يجهّز الاختبار، ينشئ كوبونات، يشاهد تقاريره ويصدّر Excel                                            |
| 🛡️ **مدير (admin)**    | لوحة إدارة كاملة: مستخدمون، أدوار، فتح قفل الحسابات، طلبات الاسترداد                                                           |

**التقنيات:** Laravel 12 · PHP 8.2+ · Blade · MySQL/SQLite · Redis (cache/queue) · Horizon · Fortify · Sentry · Spatie Activity Log & Backup · maatwebsite/excel · bacon-qr-code · Paymob.

---

## 2) الصفحات بالكامل (36 ملف Blade)

### صفحات عامة (زائر)

| المسار                                     | الملف                             | الوظيفة والحقول                                                                                                                                                                                                                                       |
| ------------------------------------------ | --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/`                                        | `welcome.blade.php`               | الصفحة الرئيسية: Hero بفيديو تعريفي، عدّادات إحصائية متحركة (طلاب/كورسات/مدرسين/تقييمات)، 6 بطاقات مزايا، أحدث 3 كورسات، 3 خطوات "كيف تبدأ"، مدرسون، آراء طلاب حقيقية، FAQ، CTA، فوتر بالشروط والخصوصية. كاش 5 دقائق (`Course::HOME_PAGE_CACHE_KEY`). |
| `/guide`                                   | `guide/index.blade.php`           | دليل الاستخدام التفاعلي — النصوص كاملة من `config/page_guide.php` (19 صفحة موثّقة بأقسامها ومميزاتها وسيناريو الاستخدام).                                                                                                                             |
| `/login`                                   | `users/login.blade.php`           | تسجيل دخول: `email` + `password` + `remember`. بطاقة تنبيهات أمنية، قفل متحرك يفتح عند صحة البيانات، حد محاولات `throttle:15,1`.                                                                                                                      |
| `/register`                                | `users/create.blade.php`          | إنشاء حساب طالب (معالج 3 خطوات wizard): الاسم، البريد، هاتف الطالب، هاتف ولي الأمر، المرحلة، الصف، النظام الدراسي (للثانوية)، النوع التعليمي، كلمة المرور وتأكيدها.                                                                                   |
| `/forgot-password`                         | `users/forgot-password.blade.php` | طلب رابط إعادة تعيين كلمة المرور (حقل `email`، `throttle:5,1`).                                                                                                                                                                                       |
| `/reset-password/{token}`                  | `users/reset-password.blade.php`  | إعادة تعيين كلمة المرور الفعلية.                                                                                                                                                                                                                      |
| `/certificates/verify/{certificateNumber}` | `Certificates/verify.blade.php`   | تحقق **عام** من رقم شهادة (UUID) مع QR.                                                                                                                                                                                                               |
| `/search`                                  | `search/index.blade.php`          | بحث موحّد حسب الدور: `q` + `type` — الطالب: كورسات ومدرسون؛ المدرس: كورسات + مسوداته؛ المدير: كل الكورسات والمستخدمين (والبريد ظاهر للأدمن فقط).                                                                                                      |

### صفحات الحساب (مسجّل)

| المسار                           | الملف                          | الوظيفة والحقول                                                                                                                                                             |
| -------------------------------- | ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/email/verify`                  | `users/verify-email.blade.php` | شاشة تأكيد البريد: إعادة إرسال الرابط (`verification.send`)، تعديل البريد (`email`)، أو خروج.                                                                               |
| `/profile`                       | `users/profile.blade.php`      | "نشاطي وحسابي": الكورسات المسجّل بها، استكمال آخر درس، حلقات تقدم، وقت التعلم، النشاط والإشعارات، تفضيل الثيم light/dark يُحفظ محليًا ويُزامَن للحساب (`POST /appearance`). |
| `/payments`                      | `Payments/index.blade.php`     | سجل المدفوعات والفواتير وحالة الاسترداد. طلب استرداد: `reason` (10–2000 حرف).                                                                                               |
| `/payments/{enrollment}/invoice` | `Payments/invoice.blade.php`   | فاتورة اشتراك (مدفوع أو مسترد).                                                                                                                                             |

### صفحات الكورسات والتعلم

| المسار                                      | الملف                           | الوظيفة والحقول                                                                                                                                                                                                                                                      |
| ------------------------------------------- | ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/courses`                                  | `Courses/index.blade.php`       | كتالوج الكورسات المنشورة: بحث، بطاقات، أسعار، تقييمات، تصفية حسب المرحلة/الصف/النظام، وروابط إدارة للمدرس/الأدمن.                                                                                                                                                    |
| `/courses/{id}`                             | `Courses/show.blade.php`        | تفاصيل الكورس: الدروس، التقدم، التقييمات. الالتحاق المجاني (`POST /courses/{course}/enroll`) أو الدفع (`POST /courses/{course}/checkout` بحقول `payment_method` و`phone_number` و`coupon_code`). التقييم: `rating` 1–5 + نص `review` (يتاح بعد إكمال 70% من الكورس). |
| `/my-courses`                               | `Courses/my.blade.php`          | الكورسات المدفوعة، تقدم كل كورس، الدرس المقترح للاستكمال، وحالة الإكمال.                                                                                                                                                                                             |
| `/courses/{course}/certificate`             | `Courses/certificate.blade.php` | شهادة الإتمام باسم الطالب والدورة + رقم تحقق UUID، مع زر طباعة.                                                                                                                                                                                                      |
| `/lessons/{id}`                             | `Lessons/show.blade.php`        | مشغّل الدرس: فيديو خاص (private disk) بعلامة مائية متحركة مرتبطة بالحساب، حفظ الموضع والتقدم (`PATCH /lessons/{lesson}/progress`)، إكمال الدرس (`POST /lessons/{lesson}/complete`)، تعليقات وردود متداخلة (`body` + `parent_id`).                                    |
| `/courses/{course}/quiz`                    | `Quizzes/show.blade.php`        | الاختبار النهائي: ترتيب أسئلة عشوائي (محفوظ في JSON بجلسة الاختبار)، مؤقّت، اختيار واحد لكل سؤال.                                                                                                                                                                    |
| `/courses/{course}/quiz/attempts/{attempt}` | `Quizzes/result.blade.php`      | النتيجة، نجاح/رسوب، تفاصيل الإجابات، ورابط الشهادة عند الاستحقاق.                                                                                                                                                                                                    |

### صفحات المدرّس

| المسار                                      | الملف                                                 | الوظيفة والحقول                                                                                                                                                                                                            |
| ------------------------------------------- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/teacher/dashboard`                        | `Courses/teacher-dashboard.blade.php`                 | مؤشرات: الكورسات، الطلاب، الدخل المؤكد، نسب الإكمال، دروس لم تكتمل. تقارير شهرية (`month`) عبر `POST /teacher/reports`، وتصدير Excel: قوائم الطلاب (`/teacher/exports/students`) والمدفوعات (`/teacher/exports/payments`). |
| `/courses/create` + `/courses-edit/{id}`    | `Courses/create.blade.php` / `Courses/edit.blade.php` | إنشاء/تعديل: العنوان، الوصف، السعر، الصورة (حتى 2MB)، المستوى، بيانات المرحلة التعليمية. الجديد يبدأ **مسودة**، والنشر عبر `POST /courses/{id}/publish`.                                                                   |
| `/courses/{course}/lessons`                 | `Lessons/manage.blade.php`                            | إدارة دروس الكورس: قائمة، ترتيب، إضافة، تعديل، حذف.                                                                                                                                                                        |
| `/courses/{course}/lessons/create` + `edit` | `Lessons/form.blade.php`                              | حقول الدرس: `title`، `content`، `order`، وفيديو اختياري `video` (حتى 20MB، امتدادات مسموحة). الفيديو يُرفع لقرص خاص عبر staging ثم job `FinalizeMediaUpload`.                                                              |
| `/lessons/create`                           | `Lessons/create.blade.php`                            | النموذج القديم لإضافة درس باختيار الكورس يدويًا.                                                                                                                                                                           |
| `/courses/{course}/quiz/edit`               | `Quizzes/edit.blade.php`                              | إعداد الاختبار: العنوان، درجة النجاح، أقصى محاولات، المدة بالدقائق، ثم أسئلة ديناميكية — لكل سؤال نص وأربعة اختيارات وتحديد الإجابة الصحيحة (`PUT /courses/{course}/quiz`).                                                |
| `/courses/{course}/coupons`                 | `Coupons/index.blade.php`                             | كوبونات الكورس: `code`، `type` (نسبة/قيمة ثابتة)، `value`، حد استخدامات اختياري، تاريخ بداية/نهاية اختياري، إيقاف/تفعيل (`PATCH`).                                                                                         |

### صفحات المدير

| المسار                  | الملف                       | الوظيفة والحقول                                                                                            |
| ----------------------- | --------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `/admin`                | `users/dashboard.blade.php` | لوحة المدير: إحصاءات المنصة، آخر المستخدمين والكورسات، روابط الإدارة.                                      |
| `/users`                | `users/index.blade.php`     | قائمة المستخدمين: تصفية بالدور، بحث `q` بالاسم/البريد، تحرير الدور، فتح القفل، حذف.                        |
| `/users/{id}/edit`      | `users/update.blade.php`    | تعديل: الاسم، البريد، الدور، كلمة المرور وتأكيدها. لا يمكن للأدمن تغيير دوره لنفسه.                        |
| `/StudentToTecher/{id}` | `users/show.blade.php`      | تفاصيل مستخدم + ترقية طالب إلى مدرّس (`POST /make-teacher/{id}`) أو العكس (`POST /TeacherToStudent/{id}`). |
| `/admin/refunds`        | `Refunds/index.blade.php`   | مراجعة طلبات الاسترداد: الحالة (approved/rejected/completed) + ملاحظة إدارية اختيارية `admin_note`.        |

### مكونات مشتركة (`resources/views/components`)

- `appearance.blade.php` — حفظ/مزامنة الثيم الفاتح والداكن.
- `global-search.blade.php` — البحث الموحّد.
- `management-navigation.blade.php` — شريط إدارة (أدمن/مدرس).
- `upload-progress.blade.php` — مؤشر تقدم رفع الملفات (صور/فيديو).

---

## 3) المسارات (routes/web.php) — 60+ مسار

### عامة

- `GET /` (home) · `GET /guide` · `GET /certificates/verify/{certificateNumber}`
- `GET|POST /login` · `GET /register` · `POST /users`
- استرداد كلمة المرور: `GET/POST /forgot-password`، `GET /reset-password/{token}`، `POST /reset-password`
- `GET|POST /payments/paymob/callback` (بلا CSRF — مخصص لـ Paymob)

### مسجّل (auth + verified-or-admin)

- `POST /appearance` — تفضيل الثيم
- تأكيد البريد: `GET /email/verify`، `GET /email/verify/{id}/{hash}` (signed)، `POST /email/verification-notification`، `PATCH /email/verification-email`
- `POST /logout` · `GET /search`
- `GET/PUT /profile` · `GET /notifications/{notification}/open`
- `GET /payments` · `GET /payments/{enrollment}/invoice` · `POST /payments/{enrollment}/refund-requests`

### كورسات وتعلم (مسجّل)

- `GET /courses` · `GET /my-courses` · `GET /courses/{id}`
- `POST /courses/{course}/enroll` (مجاني) · `POST /courses/{course}/checkout` (دفع)
- `POST /courses/{course}/reviews`
- `POST /lessons/{lesson}/complete` · `PATCH /lessons/{lesson}/progress` · `GET /lessons/{lesson}/video` (محتوى خاص)
- `POST /lessons/{lesson}/comments`
- `GET /courses/{course}/certificate` · `GET /courses/{course}/quiz` · `POST /courses/{course}/quiz/attempts` · `GET /courses/{course}/quiz/attempts/{attempt}`
- `GET /lessons/create` (مدرس) · `GET /lessons/{id}`

### الأدمن (auth + admin + two-factor)

- `GET /admin` · `GET /admin/refunds` · `PATCH /admin/refunds/{refundRequest}`
- `GET /users` · `GET /users/{id}/edit` · `PUT /users/{id}` · `DELETE /users/{id}`
- `POST /users/{user}/login-unlock`
- `GET /StudentToTecher/{id}` · `POST /TeacherToStudent/{id}` · `POST /make-teacher/{id}`

### المدرّس (auth + teacher + two-factor)

- `GET /teacher/dashboard` · `POST /teacher/reports` · `GET /teacher/exports/students` · `GET /teacher/exports/payments`
- `GET/POST /courses` · `PUT /courses/{id}` · `POST /courses/{id}/publish` · `DELETE /courses/{id}`
- `GET/PUT /courses/{course}/quiz/edit|save`
- كوبونات: `GET/POST /courses/{course}/coupons` · `PATCH .../coupons/{coupon}`
- دروس: `GET /courses/{course}/lessons` + create/store/edit/update/destroy
- `GET|POST /lessons/create` · `GET /lessons`

---

## 4) قاعدة البيانات (19+ جدول)

### جداول المنصة

| الجدول                | أهم الحقول                                                                                                                                                                                                             | الغرض والعلاقات                                          |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `users`               | name, email (فريد), password, role, image, email_verified_at, login_locked_at, failed_login_attempts, preferred_theme, student_phone, parent_phone, education_stage, education_grade, education_system, education_type | الحسابات والأدوار وبيانات الطالب وقفل الدخول والثيم      |
| `courses`             | teacher_id FK, title, status (draft/published), image, description, price decimal(10,2), حقول تعليمية (stage/grade/system/type)                                                                                        | الكورس: دروس، اشتراكات، تقييمات، اختبار، شهادات، كوبونات |
| `lessons`             | course_id FK, title, video, content, order                                                                                                                                                                             | دروس الكورس (cascade delete)                             |
| `course_enrollments`  | user_id, course_id, payment_method, status, amount_cents, discount_cents, معرّفات Paymob, coupon_id, paid_at                                                                                                           | اشتراك/دفع — فريد لكل (طالب، كورس)                       |
| `lesson_progress`     | user_id, lesson_id, position, duration, seconds, watched_seconds, progress tinyint, completed bool, started/completed_at                                                                                               | تقدم واحد لكل (طالب، درس)                                |
| `course_quizzes`      | course_id (فريد), title, passing_score, max_attempts, time_limit_minutes                                                                                                                                               | اختبار واحد لكل كورس                                     |
| `quiz_questions`      | course_quiz_id, text, position                                                                                                                                                                                         | أسئلة الاختبار                                           |
| `quiz_options`        | quiz_question_id, text, is_correct, position                                                                                                                                                                           | أربعة اختيارات لكل سؤال                                  |
| `quiz_sessions`       | course_quiz_id, user_id, question_order JSON, expires_at, submitted_at, status                                                                                                                                         | جلسة اختبار (ترتيب + مهلة)                               |
| `quiz_attempts`       | course_quiz_id, user_id, score, counts, passed, results JSON, submitted_at, quiz_session_id (فريد)                                                                                                                     | نتيجة المحاولة                                           |
| `course_certificates` | course_id, user_id, certificate_number UUID فريد, issued_at                                                                                                                                                            | شهادة واحدة لكل (طالب، كورس)                             |
| `course_reviews`      | course_id, user_id, rating tinyint, review text                                                                                                                                                                        | تقييم واحد لكل (طالب، كورس)                              |
| `lesson_comments`     | lesson_id, user_id, parent_id ذاتي nullable, body                                                                                                                                                                      | أسئلة وردود متداخلة                                      |
| `coupons`             | course_id, code (فريد ضمن الكورس), type, value, starts_at, ends_at, max_redemptions, is_active                                                                                                                         | كوبونات الخصم                                            |
| `coupon_redemptions`  | coupon_id, course_enrollment_id, status                                                                                                                                                                                | حجز/تأكيد استخدام الكوبون — سجل واحد لكل اشتراك          |
| `refund_requests`     | course_enrollment_id, user_id, reviewed_by, reason, admin_note, status, reviewed_at                                                                                                                                    | طلب استرداد واحد لكل اشتراك                              |
| `teacher_reports`     | teacher_id, year, month (فريد لكل مدرس), status, data JSON, generated_at                                                                                                                                               | التقرير الشهري للمدرّس                                   |
| `notifications`       | UUID, type, notifiable polymorphic, data, read_at                                                                                                                                                                      | إشعارات Laravel                                          |

### جداول Laravel التشغيلية

- `password_reset_tokens` — التوكنات (الاسترداد مفعّل فعليًا في هذا الإصدار عبر `PasswordResetController`).
- `sessions` · `cache` + `cache_locks` · `jobs` + `job_batches` + `failed_jobs` · `migrations` · `activity_log`.

**العلاقات الرئيسية:** `User → Courses/Enrollments/Progress/Reviews/Certificates` · `Course → Lessons/Enrollments/Quiz/Reviews/Coupons/Certificates` · `CourseQuiz → Questions/Sessions/Attempts` · `Question → Options` · `Lesson → Progress/Comments` · `Enrollment → Coupon/Refund`.

---

## 5) المزايا الكاملة — صغيرة وكبيرة

### 🔐 الحساب والأمان

1. تسجيل بمعالج 3 خطوات + تحقق فوري أثناء الكتابة.
2. تسجيل دخول مع `remember`، وحد محاولات (throttle 15/دقيقة).
3. **قفل تلقائي للحساب** بعد 5 كلمات مرور خاطئة + فتح من لوحة المدير.
4. تأكيد البريد الإلكتروني إلزامي (باستثناء الأدمن) بروابط موقّعة، وإمكانية تعديل البريد قبل التأكيد.
5. استرداد كلمة المرور بالبريد (throttle 5/دقيقة).
6. مصادقة ثنائية (2FA) للأدمن والمدرّس عبر Fortify (`REQUIRE_2FA`) — ملاحظة: التفعيل الفعلي يحتاج إكمال الحقول في قاعدة البيانات.
7. Middleware للأدوار: `AdminMiddleware`، `TeacherMiddleware`، `VerifyEmailForNonAdmins`، `RequireTwoFactorAuthentication`.
8. Policies لملكية الكورس والدرس + Global Scopes.
9. جلسات آمنة، إعادة توليد session بعد الدخول، تسجيل خروج يبطل الجلسة.

### 💳 الدفع والمال (Paymob)

10. Checkout متزامن لـ **Vodafone Cash** و**Fawry**.
11. تحقق HMAC بـ SHA-512 + مقارنة `hash_equals`.
12. مطابقة المبلغ والعملة ومعرّف التكامل والطلب، مع `lockForUpdate` لمنع سباق المعاملات.
13. Callback يدعم GET وPOST.
14. كورسات مجانية + كوبونات 100% = اشتراك بلا دفع.
15. سجل مدفوعات + فواتير لكل اشتراك.
16. **طلبات استرداد** بم سبب إلزامي ومراجعة أدمن (موافقة/رفض/إكمال) بملاحظة إدارية.

### 🎓 التعلم والمحتوى

17. كتالوج كورسات مع بحث وتصفية حسب المرحلة/الصف/النظام/النوع.
18. دروس مرتبة (order) مع إدارة كاملة (إضافة/تعديل/حذف/ترتيب).
19. **مشغّل فيديو تفاعلي**: حفظ الموضع (position/seconds)، نسبة تقدم، ثواني مشاهدة، إكمال الدرس.
20. **فيديوهات خاصة** (private disk) تُقدّم من مسار يتحقق من الاشتراك + علامة مائية متحركة مرتبطة بالحساب + منع التحميل (رادع، ليس DRM) + أمر `php artisan media:secure-lesson-videos` لتأمين الملفات القديمة.
21. تعليقات وأسئلة متداخلة (سؤال → رد) على كل درس.
22. اختبار نهائي: عنوان، درجة نجاح، محاولات، مؤقّت، أسئلة عشوائية الترتيب لكل محاولة (JSON في الجلسة).
23. نتيجة مفصّلة + إعادة المحاولة وفق الحد.
24. **شهادة إتمام تلقائية** برقم UUID + **QR للتحقق العام** من أي شخص.
25. **تقييم الكورس** (1–5 + مراجعة نصية) — متاح بعد إكمال 70% (config `courses.review_completion_percentage`).

### 🛠️ أدوات المدرّس

26. لوحة مدرّس بمؤشرات: كورسات، طلاب، دخل مؤكد، نسب إكمال، دروس متروكة.
27. تقارير شهرية تُولّد في الطابور (`GenerateReport` job) — فريدة لكل شهر/مدرّس.
28. **تصدير Excel** (maatwebsite/excel): قوائم الطلاب والمدفوعات.
29. إدارة كوبونات كاملة (نسبة/قيمة، حدود، تواريخ، تفعيل/إيقاف).
30. حالة الكورس: مسودة → منشور، مع Policy للملكية.

### 🔔 الإشعارات والأتمتة

31. Observers: `CourseObserver`، `UserObserver`، `CourseEnrollmentObserver` — إشعارات موقّعة (queued + afterCommit) عبر `CourseNotificationService`.
32. تنبيه الأدمن عند تسجيل طالب جديد، وتنبيه المدرّس عند اشتراك مدفوع.
33. رفع وسائط عبر **staging** ثم `FinalizeMediaUpload` job في الطابور.

### ⚡ الأداء

34. كاش الصفحة الرئيسية والكاتالوج 5 دقائق مع **إبطال ذكي** عند أي تغيير (courses/lessons/reviews/enrollments/users).
35. استعلامات مجمّعة (الرئيسية 2 استعلام بدل 5، myCourses بـ 2 بدل 22) + `completionStatsForUser`.
36. فهارس مركّبة (migration أداء مخصصة) + eager loading.
37. Redis للكاش والطابور (Horizon على Linux / `queue:listen` على Windows).
38. OPcache و Debugbar للتطوير فقط.

### 🧰 البنية التحتية والمراقبة

39. **Sentry** لتتبع الأخطاء.
40. **Activity Log** (Spatie) مع تنظيف مجدول.
41. **نسخ احتياطية يومية 2:00 ص** (Spatie Backup) + تنظيف مجدول — انتبه: راجع `config/backup.php` قبل الإنتاج (استثناء `.env` واسم قاعدة البيانات).
42. سجلات يومية بمدة احتفاظ 14 يوم.
43. فحص جودة: **PHPStan/Larastan** نظيف (baseline) + **Pint** للتنسيق.
44. **27 ملف اختبار** (Feature + Unit) تشمل الدخول، البريد، الدروس، الفيديو، الاختبارات، المدفوعات، الاسترداد.

### 🎨 الواجهة والتصميم

45. نظام تصميم موحّد (`public/css/appearance.css`): palette فاتح/داكن كامل.
46. RTL كامل بخط IBM Plex Sans Arabic + logical properties.
47. موشنات مشتركة: `md-rise/fade/pop/float/spin/shimmer/stagger/lift/fill` + reveal بالتمرير + احترام `prefers-reduced-motion`.
48. وضع داكن/فاتح يُحفظ محليًا ويُزامَن للحساب.
49. صفحة تسجيل الدخول ببطاقة تنبيهات أمنية وقفل متحرك.
50. Accessibility: aria-labels، focus-visible، role attributes.

---

## 6) رحلة الطالب من التسجيل للشهادة

```
تسجيل + تحقق البريد → تصفّح الكاتالوج → اشتراك (مجاني/كوبون/Paymob)
→ مشاهدة + تتبع تقدم → اختبار الكورس → تقييم (بعد 70%) → شهادة QR موثّقة 🎓
```

---

## 7) الإعداد والتشغيل

```bash
composer install                # على Windows: --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # الـ seeder يطلب ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD في .env
npm install && npm run build
php artisan serve               # أو: composer run dev (server + queue + pail + vite)
php artisan queue:listen        # على Windows بدل Horizon
php artisan horizon             # على Linux الإنتاج
```

**Paymob** (`PAYMOB_SETUP.md`): عيّن `PAYMOB_SECRET_KEY` و`PAYMOB_PUBLIC_KEY` و`PAYMOB_HMAC_SECRET` ومعرّفي تكامل Vodafone Cash وFawry، ووجّه الـ callback إلى `/payments/paymob/callback`. لا الدفع الفعلي لا يُكمل قبل تفعيل بيانات التاجر.

**البريد:** للتجربة `MAIL_MAILER=log`، وللإنتاج SMTP حقيقي (Gmail يقدّر App Password).

---

## 8) ملاحظات ومخاطر قبل الإنتاج

1. **2FA** غير جاهزة بثقة: لا توجد migration لحقول سر 2FA ولا استخدام trait في User — راجع قبل اعتبارها حماية فعلية.
2. **config/backup.php** يشمل `base_path()` كاملًا بلا استثناءات وحدّد قاعدة `sqlite` — راجع قبل الإنتاج.
3. **حساسية حالة الأحرف على Linux**: controllers تستدعي `courses.*` بينما المجلدات `Courses` — ينجح على Windows وقد يفشل على Linux؛ وحّد الحالة.
4. كود متروك: `UserController::search` يعيد view غير موجود.
5. راجع إعدادات الإنتاج: `APP_DEBUG=false`، HTTPS، مفاتيح Paymob/البريد، وعدم رفع `.env`.

---

## 9) أرقام سريعة

| البند             | العدد                                                           |
| ----------------- | --------------------------------------------------------------- |
| ملفات Blade       | 36 (32 صفحة + 4 مكونات)                                         |
| Controllers       | 16                                                              |
| Models (Eloquent) | 17                                                              |
| Migrations        | 23                                                              |
| مسارات web        | +60                                                             |
| ملفات اختبار      | 27 (136 اختبار ناجح / 788 assertion حسب لوحة المشروع)           |
| Services          | 6 (Paymob، Coupon، Certificate، QR، Notifications، MediaStager) |
| Jobs              | 2 (FinalizeMediaUpload، GenerateReport)                         |
| Console commands  | 1 (media:secure-lesson-videos)                                  |
