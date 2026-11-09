# دليل تسليم مشروع منصة مدار

## حدود المراجعة

هذا الدليل مبني على قراءة ملفات Laravel وواجهات Blade وملفات migrations والاختبارات الموجودة في المشروع. لم أفتح `.env` أو أنسخ أي قيمة سرية منه. ولم أتمكن من تصوير الواجهات من المتصفح: العنوان المحلي الذي جُرّب أعاد `404` من Apache، لذلك الصور الحقيقية تحتاج تشغيل المشروع على عنوان يمكن للمتصفح الوصول إليه، وبيانات تجريبية للأدوار المختلفة.

التقنيات الظاهرة في المشروع: Laravel 12 وPHP 8.2+، Blade، Tailwind/Vite، قاعدة بيانات عبر Eloquent، وPaymob للدفع. نقطة بداية التطبيق هي `routes/web.php`، تسجيل الخدمات في `app/Providers`، والواجهات في `resources/views`.

## خريطة استخدام المنصة

- **زائر:** الصفحة الرئيسية، دليل الاستخدام، تسجيل الدخول/التسجيل، والتحقق العام من الشهادات.
- **طالب:** الحساب والتقدم، تصفح الكورسات والاشتراك، الدروس، الاختبارات، الشهادات، سجل المدفوعات وطلبات الاسترداد.
- **مدرّس:** لوحة المدرّس، إنشاء وإدارة الكورسات والدروس، الاختبار النهائي والكوبونات والتقارير.
- **مدير:** لوحة الإدارة، إدارة المستخدمين والأدوار، فتح قفل تسجيل الدخول، ومراجعة طلبات الاسترداد.

الأدوار المخزنة في قاعدة البيانات هي `student` و`teacher` و`admin`. معظم المسارات معرفة في [routes/web.php](./routes/web.php)، والتحقق من الأدوار في [bootstrap/app.php](./bootstrap/app.php) وملفات Middleware وPolicy.

## الصفحات وما تفعله وحقولها

العدد هنا لصفحات Blade الفعلية، وليس للمكوّنات المشتركة؛ توجد أيضًا أربعة مكونات مشتركة تحت `resources/views/components`.

| الصفحة / المسار | لمن؟ | المحتوى والحقول/الإجراءات |
|---|---|---|
| `/` — [welcome.blade.php](./resources/views/welcome.blade.php) | الجميع | واجهة المنصة، أحدث الكورسات والمدرّسين والتقييمات وإحصاءات مختصرة. لا يوجد نموذج إدخال أساسي. |
| `/guide` — [guide/index.blade.php](./resources/views/guide/index.blade.php) | الجميع | دليل إرشادي ومحتوى الصفحات؛ النصوص من `config/page_guide.php`. |
| `/login` — [login.blade.php](./resources/views/users/login.blade.php) | الجميع | تسجيل الدخول: `email` و`password`، واختيار `remember` لتذكر الجلسة. |
| `/register` — [create.blade.php](./resources/views/users/create.blade.php) | الجميع | إنشاء طالب: الاسم، البريد، هاتف الطالب، هاتف ولي الأمر، المرحلة، الصف، النظام الدراسي (للمرحلة الثانوية)، النوع التعليمي، كلمة المرور وتأكيدها. بعض القوائم تعتمد على اختيار المرحلة. |
| `/email/verify` — [verify-email.blade.php](./resources/views/users/verify-email.blade.php) | مستخدم غير مؤكّد | إعادة إرسال رابط التأكيد، تعديل البريد بحقل `email`، أو تسجيل الخروج. |
| `/profile` — [profile.blade.php](./resources/views/users/profile.blade.php) | مستخدم مسجل ومؤكد (والمدير مستثنى من التأكيد) | لوحة الحساب: الكورسات المسجل بها، استكمال آخر درس، نسب التقدم، وقت التعلم، النشاط والإشعارات. تفضيل المظهر light/dark يُحفظ محليًا ويُزامن للحساب. |
| `/courses` — [index.blade.php](./resources/views/Courses/index.blade.php) | مستخدم مسجل | كتالوج الكورسات المنشورة، بحث، بطاقات وأسعار/تقييمات؛ روابط إدارة للمدرس/المدير وكورساتي للطالب. |
| `/courses/{id}` — [show.blade.php](./resources/views/Courses/show.blade.php) | مستخدم مسجل | تفاصيل الكورس والدروس والتقدم والتقييمات. الطالب المؤهل يستطيع تقييم الكورس، التسجيل المجاني أو بدء الدفع؛ نموذج الدفع يستخدم `payment_method` و`phone_number` و`coupon_code`. التقييم يستخدم درجة من 1 إلى 5 ونص مراجعة. |
| `/my-courses` — [my.blade.php](./resources/views/Courses/my.blade.php) | طالب | الكورسات المدفوعة، تقدم كل كورس، الدرس المقترح للاستكمال وحالة الإكمال. |
| `/courses/create` و`/courses/{id}` للتحرير — [create.blade.php](./resources/views/Courses/create.blade.php)، [edit.blade.php](./resources/views/Courses/edit.blade.php) | مدرس/مدير بحسب Policy والمسار | إنشاء/تعديل عنوان الكورس، الوصف، السعر والصورة. الصورة حتى 2MB وبأنواع صور محددة؛ الكورس الجديد يبدأ كمسودة. |
| `/teacher/dashboard` — [teacher-dashboard.blade.php](./resources/views/Courses/teacher-dashboard.blade.php) | مدرس | مؤشرات الكورسات والطلاب والدخل والتقدم، تقارير شهرية، تصدير قوائم الطلاب والمدفوعات، وروابط إدارة الكورسات. حقل التقرير `month`. |
| `/courses/{course}/lessons` — [manage.blade.php](./resources/views/Lessons/manage.blade.php) | مالك الكورس من المدرسين | قائمة المحاضرات وترتيبها؛ إضافة، تعديل أو حذف محاضرة. |
| إنشاء/تعديل محاضرة — [form.blade.php](./resources/views/Lessons/form.blade.php) | مالك الكورس | العنوان `title`، المحتوى `content`، الترتيب `order`، وملف فيديو اختياري `video` (حتى 20MB وبامتدادات فيديو مسموحة). |
| `/lessons/create` — [create.blade.php](./resources/views/Lessons/create.blade.php) | مدرس | نموذج إدخال محاضرة بالطريقة القديمة: اختيار الكورس، العنوان، المحتوى، الفيديو والترتيب. |
| `/lessons/{id}` — [show.blade.php](./resources/views/Lessons/show.blade.php) | طالب مشترك أو مالك الكورس/مدير | مشغل الدرس، حفظ الموضع والتقدم، إكمال الدرس، أسئلة وتعليقات وردود. حقول التعليق `body`، والرد يضيف `parent_id`. |
| `/courses/{course}/quiz/edit` — [edit.blade.php](./resources/views/Quizzes/edit.blade.php) | المدرس المالك | إعداد عنوان الاختبار، درجة النجاح، أقصى محاولات، مدة الاختبار، ثم أسئلة ديناميكية؛ لكل سؤال نص وأربعة اختيارات وتحديد الإجابة الصحيحة. |
| `/courses/{course}/quiz` — [show.blade.php](./resources/views/Quizzes/show.blade.php) | طالب مستوفٍ لشروط الكورس | الاختبار بترتيب أسئلة عشوائي ومؤقت؛ الإجابات اختيار واحد لكل سؤال، مع رقم جلسة مخفي لربط المحاولة. |
| نتيجة الاختبار — [result.blade.php](./resources/views/Quizzes/result.blade.php) | الطالب | النتيجة، النجاح/الرسوب، تفاصيل المحاولة والشهادة إن استحقها. |
| `/courses/{course}/certificate` — [certificate.blade.php](./resources/views/Courses/certificate.blade.php) | طالب مؤهل | عرض شهادة إتمام الكورس ورمز التحقق. |
| `/certificates/verify/{certificateNumber}` — [verify.blade.php](./resources/views/Certificates/verify.blade.php) | الجميع | تحقق عام من رقم الشهادة ذي صيغة UUID. |
| `/payments` — [index.blade.php](./resources/views/Payments/index.blade.php) | الطالب صاحب المدفوعات | سجل الدفع والفواتير وحالة الاسترداد. طلب الاسترداد يتطلب `reason` من 10 إلى 2000 حرف. |
| `/payments/{enrollment}/invoice` — [invoice.blade.php](./resources/views/Payments/invoice.blade.php) | صاحب الاشتراك | تفاصيل فاتورة اشتراك مدفوع أو مسترد. |
| `/courses/{course}/coupons` — [index.blade.php](./resources/views/Coupons/index.blade.php) | مدرس الكورس | إنشاء كوبون: `code`, `type` (نسبة/قيمة ثابتة), `value`, حد الاستخدامات اختياري، تاريخ بداية ونهاية اختياريان؛ ويمكن إيقاف/إعادة تفعيل الكوبون. |
| `/admin` — [dashboard.blade.php](./resources/views/users/dashboard.blade.php) | مدير | إحصاءات المنصة وآخر المستخدمين والكورسات وروابط الإدارة. |
| `/users` — [index.blade.php](./resources/views/users/index.blade.php) | مدير | قائمة وإحصاءات المستخدمين، تصفية حسب الدور، بحث بالاسم/البريد، وتحرير الدور/فتح القفل/الحذف. حقل البحث `q`. |
| `/users/{id}/edit` — [update.blade.php](./resources/views/users/update.blade.php) | مدير | تعديل الاسم والبريد والدور وكلمة المرور وتأكيدها. لا يسمح النموذج بتغيير دور المدير لنفسه. |
| `/StudentToTecher/{id}` — [show.blade.php](./resources/views/users/show.blade.php) | مدير | عرض بيانات مستخدم وإجراء ترقية الطالب إلى مدرس. |
| `/admin/refunds` — [index.blade.php](./resources/views/Refunds/index.blade.php) | مدير | مراجعة طلبات الاسترداد؛ تغيير الحالة إلى موافق/مرفوض/مكتمل وملاحظة إدارية اختيارية `admin_note`. |
| `/search` — [index.blade.php](./resources/views/search/index.blade.php) | مستخدم مسجل ومؤكد | بحث بالكلمة `q` ونوع البحث `type`: كورسات؛ المدرسون للطالب؛ المستخدمون للمدير فقط. |

الصفحات لا تعتمد على Layout واحد؛ معظم ملفات Blade تحتوي التصميم الخاص بها. المكونات المشتركة هي البحث، شريط الإدارة، حفظ المظهر، ومؤشر رفع الملفات داخل `resources/views/components`.

## أهم مسارات الإجراءات

في [routes/web.php](./routes/web.php):

- الحساب: `POST /login`, `POST /users`, `POST /logout`، وتأكيد البريد وإعادة إرساله وتعديله.
- الكورسات: عرض/إنشاء/تعديل/نشر/حذف، تسجيل مجاني أو بدء دفع.
- الدفع: callback لـ Paymob، سجل المدفوعات، الفواتير، وطلب الاسترداد.
- التعلم: الدروس، بث الفيديو، حفظ التقدم وإكمال الدرس، التعليقات.
- الاختبارات والشهادات: تحرير الاختبار، بدء/تسليم محاولة، النتيجة، الشهادة والتحقق منها.
- الإدارة: المستخدمون والأدوار وفتح القفل والاستردادات.
- المدرّس: لوحة المدرّس، التقارير والتصدير، الدروس والكوبونات.

## قاعدة البيانات: الجداول والأنواع والعلاقات

المصدر المعتمد هو ملفات `database/migrations`؛ نوع `id` و`foreignId` في Laravel عادةً رقم صحيح كبير غير موقّع، والأنواع الفعلية النهائية تتأثر بمحرك قاعدة البيانات المختار. `timestamps` تعني `created_at` و`updated_at`.

### جداول المنصة

| الجدول | الحقول والأنواع المهمة | العلاقات والغرض |
|---|---|---|
| `users` | `id`، `name/email/password/role/image` نصوص، `email_verified_at/login_locked_at` timestamps، `failed_login_attempts` unsigned tiny int، `preferred_theme/student_phone/parent_phone/education_stage/education_grade/education_system/education_type` نصوص اختيارية، `remember_token`، timestamps | الحسابات والأدوار وبيانات الطالب وحالة الدخول. البريد فريد. |
| `courses` | `id`, `teacher_id` FK, `title/status/image` strings، `description` text، `price` decimal(10,2)، timestamps | كل كورس يتبع مدرسًا؛ له دروس واشتراكات وتقييمات واختبار وشهادات وكوبونات. |
| `lessons` | `id`, `course_id` FK, `title/video` strings، `content` text nullable، `order` integer، timestamps | دروس الكورس؛ حذف الكورس يحذف دروسه. |
| `course_enrollments` | `user_id/course_id` FKs، `payment_method/status` strings، `amount_cents/discount_cents` unsigned big ints، معرفات Paymob نصوص، `coupon_id` FK nullable، `paid_at` timestamp nullable، timestamps | اشتراك/دفع طالب لكورس؛ فريد لكل طالب وكورس. |
| `lesson_progress` | `user_id/lesson_id` FKs، الموضع/المدة/الثواني أعداد unsigned، `progress` unsigned tiny int، `completed` boolean، أوقات البداية والإكمال timestamps nullable، timestamps | سجل تقدم واحد لكل طالب ولكل درس. |
| `course_quizzes` | `course_id` FK فريد، `title` string، `passing_score/max_attempts` unsigned tiny ints، `time_limit_minutes` unsigned small int، timestamps | اختبار نهائي واحد لكل كورس. |
| `quiz_questions` | `course_quiz_id` FK، `text` text، `position` unsigned small int، timestamps | أسئلة الاختبار. |
| `quiz_options` | `quiz_question_id` FK، `text` string، `is_correct` boolean، `position` unsigned tiny int، timestamps | أربعة اختيارات لكل سؤال وفق التحقق الحالي. |
| `quiz_sessions` | `course_quiz_id/user_id` FKs، `question_order` JSON، `expires_at` timestamp، `submitted_at` nullable، `status` string، timestamps | جلسة اختبار بترتيب الأسئلة والمهلة. |
| `quiz_attempts` | `course_quiz_id/user_id` FKs، `score` unsigned tiny int، أعداد الإجابات unsigned small ints، `passed` boolean، `results` JSON، `submitted_at` timestamp، `quiz_session_id` FK nullable وفريد، timestamps | نتيجة محاولة طالب. |
| `course_certificates` | `course_id/user_id` FKs، `certificate_number` UUID فريد، `issued_at` timestamp، timestamps | شهادة واحدة لكل طالب ولكورس. |
| `course_reviews` | `course_id/user_id` FKs، `rating` unsigned tiny int، `review` text، timestamps | تقييم واحد لكل طالب ولكورس. |
| `lesson_comments` | `lesson_id/user_id` FKs، `parent_id` FK ذاتي nullable، `body` text، timestamps | أسئلة وردود متداخلة على الدرس. |
| `coupons` | `course_id` FK، `code/type` strings، `value` decimal(10,2)، البداية/النهاية timestamps nullable، `max_redemptions` unsigned int nullable، `is_active` boolean، timestamps | كوبونات لكل كورس؛ الكود فريد ضمن الكورس. |
| `coupon_redemptions` | `coupon_id` و`course_enrollment_id` FKs، `status` string، timestamps | حجز/تأكيد استخدام كوبون؛ لكل اشتراك سجل واحد. |
| `refund_requests` | `course_enrollment_id/user_id` FKs، `reviewed_by` FK nullable، `reason/admin_note` نصوص، `status` string، `reviewed_at` timestamp nullable، timestamps | طلب استرداد واحد لكل اشتراك. |
| `teacher_reports` | `teacher_id` FK، `year` unsigned small int، `month` unsigned tiny int، `status` string، `data` JSON nullable، `generated_at` timestamp nullable، timestamps | تقرير شهري فريد لكل مدرس. |
| `notifications` | `id` UUID، `type` string، أعمدة polymorphic للمستلم، `data` text، `read_at` timestamp nullable، timestamps | إشعارات Laravel للمستخدمين. |

### جداول Laravel التشغيلية

- `password_reset_tokens`: البريد مفتاح نصي، token نصي، وتاريخ الإنشاء nullable. **وجود الجدول لا يعني وجود واجهة استعادة كلمة مرور مفعّلة**؛ Fortify لا يفعّل ميزة reset ضمن `features` الحالية.
- `sessions`: معرّف نصي، مستخدم nullable، IP نصي حتى 45، user-agent/payload نص، آخر نشاط integer.
- `cache` و`cache_locks`: مفاتيح نصية، قيمة cache نص متوسط، مالك القفل نص، وانتهاء integer.
- `jobs`, `job_batches`, `failed_jobs`: طوابير Laravel، بيانات payload/exception نصوص طويلة، محاولات/أوقات/عدادات أعداد صحيحة.
- `migrations`: سجل Laravel القياسي لدفعات migrations.

العلاقات الرئيسية: `User → Courses/Enrollments/Progress/Reviews/Certificates`؛ `Course → Lessons/Enrollments/Quiz/Reviews/Coupons/Certificates`؛ `CourseQuiz → Questions/Sessions/Attempts`؛ `Question → Options`؛ `Lesson → Progress/Comments`؛ والاشتراك يرتبط بالكوبون والاسترداد.

راجع التعريفات المصدرية في [database/migrations](./database/migrations) ونماذج Eloquent في [app/Models](./app/Models).

## الأمان الموجود في الكود

- **الدخول:** تحقق من البريد وكلمة المرور، حدّ لمحاولات الطلب على مسار الدخول، قفل حساب بعد خمس كلمات مرور خاطئة ثم فتحه من لوحة المدير، وإعادة إنشاء session بعد النجاح. تسجيل الخروج يبطل الجلسة ويعيد CSRF token. كلمات المرور تُخزّن عبر Laravel hashed cast.
- **البريد:** المستخدم غير المدير يحتاج تأكيد البريد للمناطق المحمية؛ رابط التأكيد موقّع ومحدود المعدل، وإعادة الإرسال وتغيير البريد محدودا المعدل.
- **الصلاحيات:** Middleware منفصل للمدير والمدرس، وسياسات Course/Lesson للتحقق من ملكية المدرس، وحالة النشر والاشتراك المدفوع. عمليات الإدارة موجودة ضمن مجموعة المدير.
- **الفيديو:** الفيديوهات تُرفع إلى تخزين خاص وتُقدّم من خلال Route تتحقق من Policy والاشتراك؛ الاستجابة تمنع التخزين المؤقت وتضيف `nosniff`. تعطيل التحميل/العلامة المائية في المتصفح رادع فقط، وليس DRM ولا يمنع تسجيل الشاشة.
- **الدفع:** استثناء CSRF مخصص callback الخاص بـ Paymob فقط؛ التحقق من HMAC بـ SHA-512 ومقارنته بـ `hash_equals`، ثم مطابقة المبلغ والعملة ومعرّف التكامل والطلب، مع قفل معاملة قاعدة البيانات لمنع التحديث المتزامن.
- **النماذج والرفع:** Form Requests تتحقق من البيانات والأنواع والأطوال؛ الصور حتى 2MB والفيديو حتى 20MB. ملفات الرفع تمر بمرحلة staging ثم job لإتمام التخزين.
- **التسجيل والمراقبة:** توجد حزم Sentry وActivity Log وBackup، ومهام backup وcleanup مجدولة في `routes/console.php`.

### ملاحظات ومخاطر تستحق الانتباه

1. **المصادقة الثنائية غير جاهزة للتشغيل بثقة.** `REQUIRE_2FA` افتراضيًا false، وUser model لا يستخدم trait الخاص بـ Fortify ولا توجد migration لحقول سر 2FA/رموز الاسترداد؛ الموجود Middleware يفحص `two_factor_confirmed_at` فقط. لا تعتبر 2FA حماية فعالة قبل إكمال الحقول والـ trait وتجربة دورة التفعيل والتحدي فعليًا.
2. **إعداد النسخ الاحتياطي يحتاج مراجعة قبل الإنتاج.** [config/backup.php](./config/backup.php) يشمل `base_path()` كاملًا بلا استثناءات صريحة (وقد يحتوي `.env`)، ويحدد قاعدة البيانات باسم `sqlite`، بينما دليل المشروع يذكر MySQL/Redis للإنتاج. راجع تضمين الأسرار، قرص النسخ الاحتياطي، ومحرك قاعدة البيانات قبل الاعتماد على النسخ.
3. **حساسية أسماء مجلدات الواجهات على Linux.** بعض Controllers تستدعي views بصيغة `courses.*` و`lessons.show` بينما المجلدات على القرص `Courses` و`Lessons`. هذا ينجح عادةً على Windows، لكنه قد يفشل على Linux الإنتاجي بسبب حساسية حالة الأحرف. يلزم توحيد الحالة أو اختبار بناء وتشغيل على Linux.
4. **إجراء مستخدم قديم بلا صفحة مرتبطة.** `UserController::search` يعيد `users.search`، لكن لا يظهر ملف Blade بهذا الاسم في `resources/views` ولا مسار route له في `routes/web.php`. يبدو كودًا متروكًا، وإذا تم استدعاؤه مستقبلًا سيحتاج view أو إزالة الإجراء.
5. **قيمة الإعدادات الفعلية لم تُراجع.** يجب فحص إعدادات الإنتاج من لوحة الاستضافة: `APP_DEBUG=false`، HTTPS، مفاتيح Paymob والبريد، وسياسة حفظ `.env`. لم أقرأ `.env` كي لا أكرر أسرارًا في التقرير.

## صور الواجهات

لم تُنتج صور في هذه الجولة؛ المتصفح لم يصل إلى المشروع عبر `http://localhost/return/login` أو `http://localhost/return/public/` وأعاد Apache `404`. لعمل لقطات حقيقية بنفس التصميم، شغّل Laravel على عنوان محلي قابل للوصول (مثل `php artisan serve`) ثم أحتاج روابط/حسابات تجريبية لكل دور وبيانات تجريبية؛ لا ترسل كلمات مرور حقيقية أو أسرار الإنتاج. يمكن بعدها التقاط صورة لكل شاشة قابلة للعرض.

## اختبارات ومصادر للتعمق

يوجد 27 ملف اختبار ضمن `tests/Feature` و`tests/Unit`، منها تسجيل الدخول، تأكيد البريد، الدروس، الفيديو، الاختبارات، المدفوعات والاسترداد. نتيجة 3 اختبارات مذكورة في المحادثة السابقة كانت لاختبارات auth فقط، وليست إثباتًا لنجاح المشروع كاملًا. ملفات المصدر الأهم: [routes/web.php](./routes/web.php)، [app/Http/Controllers](./app/Http/Controllers)، [app/Http/Requests](./app/Http/Requests)، [app/Models](./app/Models)، [database/migrations](./database/migrations)، و[tests/Feature](./tests/Feature).
