# التقرير الهندسي والتقني لمنصة مدار

**تاريخ المراجعة:** 6 نوفمبر 2026  
**جذر المشروع:** `D:\xampp\htdocs\return`  
**التقنيات الأساسية:** PHP 8.2+، Laravel 12، Blade، Vite، Tailwind CSS 4، Eloquent  
**النتيجة المختصرة:** تطبيق تعليمي متكامل متعدد الأدوار، مع دفع إلكتروني ومحتوى دراسي وتتبع تقدم. تشغيل الاختبارات الكامل في 6 نوفمبر 2026 انتهى بـ **192 ناجحًا و0 فاشل**. جرى تصحيح مراجع الواجهات لتطابق حالة أسماء المجلدات، وإصلاح بنية إعداد النسخ الاحتياطي وإشعاراته. لم ينجح بناء Vite في بيئة الفحص لأن `npm` غير متاح؛ لذلك لا يزال البناء والتحقق من إعدادات خدمات الإنتاج شرطين قبل النشر. هذا التقرير ليس اختبار اختراق خارجيًا أو تدقيقًا للتبعيات أو اختبار حمل متزامنًا.

## حدود التغطية

يشمل التقرير خريطة الملفات التي يملكها المشروع ومراجعة مكونات التطبيق: `app`، المسارات، الإعدادات، الترحيلات، الواجهات، الاختبارات، إعدادات البناء والتشغيل والمستندات. جرى حصر ملفات هذه المكونات وقراءة المراجع الوظيفية ذات الصلة.

لم تُقرأ ملفات `.env` أو تُنسخ قيمها، حفاظًا على الأسرار. ولم تُراجع آلاف ملفات `vendor` مراجعة مصدرية؛ تلك مكتبات خارجية تُدار بواسطة `composer.lock`، وتحتاج فحصًا منفصلًا لسلسلة التوريد. لم تُحلل ملفات الفيديو والصور الثنائية، ولم يُتصل بقاعدة الإنتاج أو حساب Paymob الفعلي. لذلك لا يُعد هذا التقرير إثباتًا لسلامة البنية التشغيلية أو لمقاومة ضغط المستخدمين المتزامنين.

## 1. هيكل المشروع

```text
return/
├── app/                         منطق التطبيق (82 ملف PHP)
│   ├── Console/Commands/        أوامر Artisan المخصصة
│   ├── Exports/                 مصدّرات Excel
│   ├── Http/
│   │   ├── Controllers/         معالجات صفحات وطلبات الويب
│   │   ├── Middleware/          التحقق من الدور والبريد و2FA
│   │   └── Requests/            صلاحيات وتحقق مدخلات النماذج
│   ├── Jobs/                    مهام الطوابير
│   ├── Models/                  نماذج Eloquent وScopes
│   ├── Notifications/           إشعارات البريد والمنصة
│   ├── Observers/               آثار تغييرات النماذج والتنبيهات
│   ├── Providers/               تسجيل الخدمات والسياسات ومحدّدات المعدل
│   ├── Services/                الدفع والكوبونات والشهادات والوسائط
│   ├── CoursePolicy.php
│   └── LessonPolicy.php
├── bootstrap/                   إنشاء تطبيق Laravel ومقدمو الخدمة
├── config/                      إعدادات Laravel والحزم (19 ملف PHP)
├── database/
│   ├── factories/
│   ├── migrations/              23 ترحيلًا
│   └── seeders/
├── public/                      نقطة الدخول وملفات CSS/JS والصور والفيديو
├── resources/
│   ├── css/                     مدخل Tailwind
│   ├── js/                      مدخلا Vite
│   └── views/                   38 قالب Blade و4 مكونات مشتركة
├── routes/                      web.php وconsole.php
├── storage/                     ملفات مرفوعة وبيانات تشغيل محلية
├── tests/
│   ├── Feature/                 30 ملف اختبار ميزات
│   └── Unit/                    اختبار وحدة نموذجي
├── .github/workflows/           CI على GitHub Actions
├── .vscode/                     مهام تطوير وتشغيل للمحرر
├── artisan
├── composer.json / composer.lock
├── package.json / vite.config.js
├── phpunit.xml
└── مستندات المشروع والتسليم والإعداد
```

### الملفات والمجلدات ذات الصلة

- **تشغيل التطبيق:** `artisan`، `bootstrap/app.php`، `bootstrap/providers.php`.
- **التبعيات:** `composer.json` و`composer.lock` لـPHP؛ `package.json` وملف lock الخاص بـnpm إن وُجد لـVite والواجهة.
- **التحقق الآلي:** `phpunit.xml`، `phpstan.neon`، `phpstan-baseline.neon`، وسير العمل `.github/workflows/laravel.yml`.
- **الأصول العامة:** `public/css/appearance.css`، `global-search.css`، `management-navigation.css`، `media-upload-progress.css`، `page-guide.css`، وملفات JavaScript الخاصة بالدليل ورفع الوسائط. مدخلا البناء هما `resources/css/app.css` و`resources/js/app.js` مع `resources/js/bootstrap.js`.
- **المستندات:** `README.md`، `PAYMOB_SETUP.md`، `PROJECT_HANDOVER_AR.md`، `SITE_FULL_DOCUMENTATION_AR.md`، `WORK-SUMMARY.md`، `project-overview.html`، وملاحظات تشغيل/تسليم أخرى في الجذر.
- **بيانات التشغيل المحلية:** `storage/app/public` للصور، و`storage/app/private` للوسائط الخاصة. محتويات `storage` ليست جزءًا من الشيفرة المصدرية، ويجب ألا تُعامل كملفات نشر ثابتة دون مراجعة.
- **مستثنى من جرد الشيفرة:** `.env` و`vendor` وملفات cache/build والملفات الثنائية؛ الأول يحوي أسرارًا، والثاني/الثالث ملفات مولدة أو تبعيات خارجية.

## 2. البنية والوظائف البرمجية

### الخادم والمسارات

التطبيق Laravel أحادي البنية، يعرض صفحات HTML باستخدام Blade بدل SPA منفصلة. `routes/web.php` هو مركز الربط ويحتوي مسارات عامة، ومجموعات مستخدم مسجل، ومدير، ومدرس. `routes/console.php` يعرّف مهام الصيانة المجدولة. `bootstrap/app.php` يعرّف aliases للـmiddleware ويستثني callback Paymob من CSRF؛ سلامة هذا الاستثناء تعتمد على تحقق HMAC داخل المعالج.

| المكوّن | المسؤولية الأساسية |
|---|---|
| `UserController` | لوحة المدير، قائمة المستخدمين والبحث، التسجيل وتسجيل الدخول، إدارة الأدوار والقفل، تحديث الملف الشخصي والثيم. |
| `CourseController` | الكتالوج والبحث/الفلاتر، كورسات الطالب، لوحة المدرس، إنشاء ونشر وتعديل الكورسات، الاشتراك المجاني والدفع، callback Paymob. |
| `LessonController` | إدارة الدروس، صلاحيات عرض الدرس، بث الفيديو، حفظ التقدم وإكمال الدرس. |
| `QuizController` | إعداد الاختبار، بدء الجلسة، تسليم الإجابات، النتيجة، التحقق من أهلية الشهادة وعرضها. |
| `CouponController` / `CouponService` | إنشاء وإيقاف الكوبونات والتحقق من صلاحيتها والحساب الذري للخصم والحجوزات. |
| `PaymentController` | سجل اشتراكات الطالب وفواتيره مع فحص ملكية السجل. |
| `RefundRequestController` | استقبال طلب الاسترداد من الطالب ومراجعته وتغيير حالته إداريًا؛ رد المال نفسه موصوف بأنه يدوي. |
| `EmailVerificationController` / `PasswordResetController` | تأكيد البريد وإعادة إرساله/تغييره، ونماذج وإتمام إعادة تعيين كلمة المرور. |
| `SearchController` | بحث موحد حسب دور المستخدم في الكورسات والمدرسين والمستخدمين. |
| `TeacherReportController` | إنشاء التقارير الشهرية في الخلفية وتنزيل قوائم الطلاب والمدفوعات بصيغة Excel. |
| `CourseReviewController` / `LessonCommentController` | تقييم الكورس والتعليقات/الردود على الدروس بعد فحوص الأهلية. |
| `NotificationController` | فتح إشعار المالك والتحويل إلى وجهته. |
| `CertificateVerificationController` | تحقق عام من رقم الشهادة. |

#### نقاط الدخول الرئيسية

- عامة: `/`، `/guide`، `/login`، `/register`، طلب/إعادة تعيين كلمة المرور، والتحقق العام من الشهادات.
- حساب مسجّل: الملف الشخصي والثيم، التحقق من البريد، الإشعارات، المدفوعات والفواتير، الكتالوج والاشتراكات، الدروس والتقدم، التعليقات والاختبارات والشهادات.
- مدير: لوحة الإدارة، إدارة المستخدمين والأدوار، فتح قفل الدخول، وإدارة طلبات الاسترداد؛ تحمي المجموعة صلاحيات `admin` و2FA عند تفعيلها.
- مدرس: لوحة الأداء، التقارير والصادرات، إدارة الكورسات والدروس والاختبارات والكوبونات؛ الملكية تتحقق بواسطة Policies وفحوص المسارات.
- خارجي: `/payments/paymob/callback` يستقبل GET وPOST من Paymob.

### الحماية والتحقق

- `AdminMiddleware` و`TeacherMiddleware`، و`VerifyEmailForNonAdmins`، و`RequireTwoFactorAuthentication` تضبط الوصول حسب الدور والبريد و2FA.
- `CoursePolicy` و`LessonPolicy` توحدان فحوص رؤية/تعديل/حذف الكورسات والدروس والوصول للفيديو.
- Form Requests تفصل التحقق عن المعالجة: تسجيل/دخول/إعادة تعيين، الكتالوج، الدفع، الكورس، الدرس، التقدم والتعليقات، البحث وتعديل حسابات المستخدمين.
- تسجيل الدخول: حدّ 5 محاولات للدقيقة لكل IP+بريد، و30/IP؛ قفل حساب مؤقت 15 دقيقة افتراضيًا بعد 5 إخفاقات. التسجيل 5/IP/دقيقة، إعادة تأكيد البريد 3 لكل مستخدم/IP كل 10 دقائق، 2FA 5/IP/دقيقة، ومحاولات checkout/الكوبون 10 لكل مستخدم/IP و30/IP.
- لا يدخل `role` ضمن تحقق تعديل الملف الشخصي. تغيير الدور متاح عبر مسارات الإدارة، وتوجد حماية من حذف/خفض صلاحية آخر مدير ومن تغيير المدير لدوره/حسابه بنفسه.

### الكيانات والخدمات

**النماذج (20):** `User`، `Course`، `Lesson`، `CourseEnrollment`، `LessonProgress`، `CourseQuiz`، `QuizQuestion`، `QuizOption`، `QuizSession`، `QuizAttempt`، `CourseCertificate`، `CourseReview`، `LessonComment`، `Coupon`، `CouponRedemption`، `RefundRequest`، `TeacherReport`، `PaymobCallbackLog`، وScopes لـ`PublishedCourses` و`AdminUsers`.

**العلاقات العامة:** المستخدم يملك/يرتبط بدورات واشتراكات وتقدم وتقييمات وشهادات؛ الدورة ترتبط بمدرس ودروس واشتراكات واختبار وكوبونات وشهادات وتقييمات؛ الاختبار يحوي أسئلة وخيارات وجلسات ومحاولات؛ الدرس له تقدم وتعليقات؛ الاشتراك يرتبط بكوبون واسترداد.

**الخدمات:** `PaymobService` لإنشاء جلسة الدفع والتحقق من HMAC؛ `PaymobCallbackAuditService` لتسجيل نتيجة callback؛ `CouponService` لحساب الخصم؛ `CourseCertificateService` لإصدار الشهادة؛ `CertificateQrCodeService` لإنشاء QR؛ `CourseNotificationService` لتوزيع الإشعارات؛ `UploadedMediaStager` لحفظ ملف الرفع مؤقتًا.

**المهام الخلفية:** `FinalizeMediaUpload` لنقل الوسائط من staging مع إعادة محاولات؛ `GenerateReport` لحساب تقرير شهري للمدرس. الإشعارات تستخدم `ShouldQueue`، وتشمل التحقق من البريد وإعادة تعيينه وإشعارات النشاط.

**Observers:** `UserObserver` لتنبيه المدير عند تسجيل طالب وإبطال كاش الصفحة؛ `CourseObserver` لإبطال كاش المحتوى؛ `CourseEnrollmentObserver` لتنبيه المدرس عند الاشتراك المدفوع وإبطال الكاش.

#### خريطة الوظائف العامة في Controllers

هذه خريطة أسماء الوظائف ومسؤولياتها، لتسهيل التنقل بين الملفات؛ ليست بديلًا عن قراءة تنفيذ كل وظيفة عند تعديلها:

| الملف | الوظائف الرئيسية |
|---|---|
| `UserController` | `adminDashboard`, `index`, `show`, `create`, `updateAppearance`, `store`, `login`, `unlockLogin`, `makeTeacher`, `makeStudent`, `edit`, `update`, `destroy`, `updateProfile`, `profile`, `logout`. |
| `CourseController` | `index`, `myCourses`, `teacherDashboard`, `create`, `store`, `publish`, `show`, `enrollFree`, `checkout`, `paymobCallback`, `edit`, `update`, `destroy`؛ و`markCheckoutFailed` لمعالجة فشل البدء. |
| `LessonController` | `create`, `manage`, `createForCourse`, `storeForCourse`, `editForCourse`, `updateForCourse`, `destroyForCourse`, `store` (مسار التوافق القديم)، `show`, `video`, `PessonProgress` (اسم الدالة القديم كما في الكود)، `saveProgress`, `index`؛ مع مساعدي الملكية والوصول. |
| `QuizController` | `edit`, `save`, `show`, `submit`, `result`, `certificate`؛ ومساعدات ملكية الكورس وأهلية الطالب واكتمال الدروس. |
| `EmailVerificationController` | `notice`, `verify`, `resend`, `updateEmail`. |
| `PasswordResetController` | `requestForm`, `sendResetLink`, `resetForm`, `reset`. |
| `SearchController` | `__invoke`؛ بحث منفصل للكورسات والمدرسين والمستخدمين. |
| `RefundRequestController` | `store`, `index`, `update`. |
| `PaymentController` | `index`, `invoice`. |
| `TeacherReportController` | `generate`, `students`, `payments`؛ مع `downloadDate`. |
| `CouponController` | `index`, `store`, `toggle`؛ مع فحص ملكية الكورس. |
| `CourseReviewController` / `LessonCommentController` | `store` لإنشاء تقييم أو تعليق/رد. |
| `NotificationController` | `open`. |
| `CertificateVerificationController` | `show`. |

#### Form Requests

- **Auth:** `LoginRequest`، `RegisterUserRequest`، `ForgotPasswordRequest`، `ResetPasswordRequest`.
- **Courses:** `CourseCatalogRequest`، `CourseFormRequest`، `CheckoutRequest`، `StoreCourseReviewRequest`.
- **Lessons:** `StoreLessonRequest`، `LessonFormRequest`، `SaveLessonProgressRequest`، `StoreLessonCommentRequest`.
- **Quizzes:** `SaveQuizRequest`، `SubmitQuizRequest`.
- **Search:** `SearchRequest`، `SearchUsersRequest`، `UserIndexRequest`.
- **Users:** `UpdateProfileRequest`، `UpdateManagedUserRequest`، `UpdateEmailRequest`.

### الواجهة الأمامية

- 38 قالب Blade: الصفحة الرئيسية، الدخول والتسجيل وإعادة كلمة المرور والتحقق، الملف الشخصي ولوحات الإدارة/المدرس، الكتالوج وصفحة الكورس والكورسات المسجل بها، نماذج وإدارة الدروس ومشغل الدرس، الاختبارات والنتائج والشهادات، المدفوعات والفواتير والاسترداد، الكوبونات والبحث والدليل وإعداد/تحدي 2FA.
- 4 مكونات Blade مشتركة: `appearance`، البحث الشامل، شريط الإدارة، ومؤشر تقدم الرفع.
- الواجهة RTL عربية وتستخدم IBM Plex Sans Arabic، ونظام ألوان فاتح/داكن، وحركات تحترم `prefers-reduced-motion` وميزات وصول مثل `focus-visible` وبعض ARIA.
- الملفات المخصصة في `public` تشمل إدارة قائمة الدليل ومؤشر الرفع وتنسيقات المظهر والتنقل والإرشاد والبحث.
- `vite.config.js` يجمع مدخلي CSS/JS باستخدام Laravel Vite plugin وTailwind 4. كثير من الصفحات تستخدم تنسيقات/سكريبتات inline؛ لا توجد بنية frontend منفصلة عن Laravel.

## 3. التكاملات والبيانات

### قواعد البيانات

المشروع يستخدم Eloquent وترحيلات Laravel؛ إعدادات الاتصال تشمل SQLite وMySQL/MariaDB وPostgreSQL، ويمكن تبديل الاتصال عبر إعدادات البيئة. اختبارات HTTP تستخدم SQLite في الذاكرة لتجنب قاعدة تشغيل حقيقية. الفهارس المركبة تعالج استعلامات الكورسات والاشتراكات والتقدم.

**الجداول الوظيفية:** `users`، `courses`، `lessons`، `course_enrollments`، `lesson_progress`، `course_quizzes`، `quiz_questions`، `quiz_options`، `quiz_sessions`، `quiz_attempts`، `course_certificates`، `course_reviews`، `lesson_comments`، `coupons`، `coupon_redemptions`، `refund_requests`، `teacher_reports`، `paymob_callback_logs`، و`notifications`.

**جداول Laravel/الحزم:** `migrations`، `password_reset_tokens`، `sessions`، `cache` و`cache_locks`، `jobs` و`job_batches` و`failed_jobs`، و`activity_log`.

**القيود والحقول المهمة:** البريد الإلكتروني فريد؛ دور المستخدم `student` أو`teacher` أو`admin`. سعر الدورة `decimal(10,2)` ويُتحقق من نطاقه ومن دقتين عشريتين. الاشتراك فريد لكل مستخدم/دورة ويحفظ `amount_cents` و`discount_cents` وحالة الدفع ومعرفات Paymob وتاريخ الدفع. التقدم فريد لكل طالب/درس ويخزن موضع الفيديو والنسبة والثواني وحالة الإكمال. الاختبار له إعداد واحد لكل دورة، وجلسة تحتوي ترتيب الأسئلة وموعد الانتهاء، ومحاولات مرتبطة بالطالب. الشهادة لها رقم UUID فريد، والتقييم فريد لكل طالب/دورة. الكوبون فريد داخل الدورة ويحتوي النوع والقيمة والتواريخ وحد الاستخدام؛ سجل الاسترداد فريد لكل اشتراك. طلب الاسترداد فريد لكل اشتراك. سجل callback يحفظ معرف الطلب/المعاملة والتكامل وطريقة الطلب/IP وhash للحمولة وحالة صحة التوقيع والنتيجة، وليس نص بيانات البطاقة أو HMAC.

### خدمات خارجية وتشغيل

| الخدمة | الاستخدام ومسار البيانات |
|---|---|
| Paymob Accept | إنشاء intention/checkout بعملة EGP وتكامل Vodafone Cash أو Fawry؛ callback موقّع HMAC-SHA512 يتحقق من المبلغ والعملة والتكامل قبل تغيير اشتراك معلّق إلى مدفوع. استدعاء إنشاء الدفع يملك timeout ويمر عبر HTTP Client. |
| SMTP / Mail | إشعارات تأكيد البريد وإعادة تعيين كلمة المرور؛ إشعار التحقق queued. بيئة الاختبار تستخدم mail array، ويمكن محليًا استخدام log. |
| Redis | مخزن كاش وطوابير/Horizon في إعدادات الإنتاج المقترحة؛ قاعدة بيانات الطوابير متاحة أيضًا. يحتاج Redis وعمّال الطوابير إلى تشغيل ومراقبة حقيقيين. |
| Laravel Horizon | مراقبة وإدارة طابور Redis؛ الوصول محمي بـ`Horizon::auth` للمدير. على Windows توثّق الملفات استخدام `queue:listen` بسبب متطلبات Horizon للنظام. |
| التخزين | قرص `public` لصور الدورات، و`private` للفيديوهات الخاصة؛ مسار الفيديو يصرّح بالوصول ويستخدم استجابة ملف تدعم Range. إعداد S3 موجود، لكن تهيئة disk الخاصة بتخزين الفيديو وتدفقه تحتاج تفعيلًا واختبارًا منفصلين. |
| Sentry | تتبع أخطاء التطبيق عبر إعدادات `config/sentry.php` ومتغيرات بيئة. |
| Spatie Activity Log / Backup | تسجيل النشاط والنسخ الاحتياطي مع جدول جدولة يومي للتنفيذ والتنظيف. |
| Excel وQR | `maatwebsite/excel` لتصدير سجلات المدرس؛ `bacon/bacon-qr-code` لإرفاق QR بتحقق الشهادة. |
| Google Fonts | تحميل IBM Plex Sans Arabic من Google Fonts في بعض القوالب؛ اعتماد خارجي يؤثر في الخصوصية والأداء عند الحجب. |

**تدفق الدفع:** طلب checkout يتحقق من الطالب والكورس والاشتراك والكوبون، يحفظ مبلغًا وخصمًا snapshot بالسنتات، ثم ينشئ جلسة Paymob. Callback مكرر لا يعيد حالة اشتراك مدفوع؛ callbacks وحالات الفشل تُكتب في سجل التدقيق. طلب الاسترداد يغير الحالة في المنصة بعد موافقة المدير، لكن رد الأموال لبوابة الدفع يدوي وليس تكاملًا آليًا مثبتًا.

## 4. نقاط الضعف والمخاطر والتحسينات

هذه نتائج مراجعة للكود المحلي وليست ثغرات مؤكدة في موقع منشور. درجات الخطورة تقديرية وتعتمد على إعدادات النشر.

| الأولوية | النقطة | الدليل والأثر | الإجراء المقترح |
|---|---|---|---|
| **متوسط** | callback الدفع يكتب سجلًا قبل قبول التوقيع ولا يظهر عليه throttle | مسار callback عام ومستثنى من CSRF. `CourseController::paymobCallback` يسجل حتى التوقيعات الفاشلة في `paymob_callback_logs`؛ هذا مفيد للتدقيق لكنه يسمح بتضخيم جدول السجل بطلبات عامة غير موثقة، بما يضغط قاعدة البيانات والتخزين. | ضع حماية عند WAF/موازن الحمل مع حدود حجم/معدل مناسبة لا تمنع retries الشرعية من Paymob، ونفّذ سياسة احتفاظ/أرشفة للسجلات وتنبيهًا عند ارتفاع الرفض. اختبر retries بعد أي حد معدل. |
| **متوسط** | المصادقة الثنائية غير إلزامية افتراضيًا | `config/fortify.php` يقرأ `REQUIRE_2FA` وقيمته الافتراضية `false`؛ middleware يتجاوز الفحص عند تعطيلها. بذلك تبقى حسابات المدير والمدرس على كلمة المرور فقط إذا لم يُفعّل المتغير في الإنتاج. | فعّلها للبيئة الإنتاجية، اختبر تهيئة واستعادة 2FA، واحتفظ بإجراء recovery آمن مع تدقيق تغير إعداد البيئة. |
| **متوسط** | سجل callback يحتفظ بعنوان IP دون سياسة احتفاظ ظاهرة | `PaymobCallbackAuditService` يحفظ IP وبيانات تعريف للطلب. IP بيانات شخصية وقد تتراكم بلا حد إذا لم تُطبق سياسة حذف/أرشفة. | حدّد مدة احتفاظ ملائمة، وصلاحيات قراءة، ومبررًا وبيان خصوصية؛ قلّل أو جزّئ IP إذا لم يعد ضروريًا للتحقيق. |
| **متوسط** | إعدادات التشغيل مختلفة بين أمثلة البيئة والتوثيق | `config/queue.php` افتراضيه database، بينما `.env.example` يختار Redis، وبعض شرح `README.md` يصف database كافتراضي محلي. الاعتماد على Redis دون خدمة متاحة يؤخر/يفشل الإشعارات والطوابير. | وحّد الإعدادات الموصى بها حسب local/staging/production، وأضف خطوة readiness تتحقق من الاتصال بالطابور والـcache قبل فتح التسجيل. |
| **منخفض/توسّع** | البحث بالاحتواء في عنوان الدورة | كتالوج الكورسات يُفلتر الآن في SQL ويستخدم pagination؛ شرط `LIKE '%term%'` لن يستفيد عادةً من B-tree مع بادئة wildcard عند تضخم البيانات. | راقب `EXPLAIN` وأزمنة الاستعلام، ثم اعتمد full-text index أو خدمة بحث إذا أثبت القياس الحاجة. |
| **منخفض/توسّع** | صفحة الملف الشخصي تجمع الاشتراكات ودروسها لحساب واحد | استعلامات eager loading وSQL التجميعي تقلل N+1، لكن تحميل جميع دروس جميع دورات حساب ضخم قد يكبر حجم الذاكرة والصفحة. | اختبر مستخدمًا لديه آلاف الدروس؛ أضف تقسيمًا/تحميلًا مرحليًا إذا ظهرت مشكلة فعلية. |
| **منخفض/تشغيلي** | الحالات المهمة مخزنة كسلاسل | حالات الدفع والاسترداد والكوبون والاختبار تدار منطقيًا في التطبيق؛ قاعدة البيانات لا تظهر فيها قيود CHECK لكل انتقال. الكتابة المباشرة أو أخطاء كود مستقبلية قد تنشئ حالة غير معروفة. | أضف قيودًا/ثوابت حالات واختبارات انتقالات، مع migration متوافقة مع MySQL وSQLite. |
| **منخفض/تشغيلي** | النسخ الاحتياطي والاستعادة لم يُختبرا فعليًا في بيئة منفصلة | إعداد الجدولة موجود، لكن نجاح `backup:run` وحده لا يثبت قابلية الاسترجاع. | نفّذ تمرين استعادة لقاعدة وملفات في staging معزول، وسجّل RPO/RTO والنتيجة. |
| **غير محسوم** | لا يوجد قياس سعة 200 مستخدم متزامن | اختبار 200 طلب التسجيل الحالي متتابع داخل test runner ويثبت رفض المحاولات الزائدة من IP واحد، لكنه ليس اختبار تزامن أو benchmark لقاعدة MySQL/Redis. | خطط لاختبار ضغط بمستخدمين افتراضيين وجلسات/IPs متعددة على staging، مع موافقة وقياس CPU/DB/queue والـp95، لا على الإنتاج. |

### نقاط حماية قائمة وفق الشيفرة

- رفض SVG في رفع صور الكورسات والسماح بصيغ صور نقطية محددة، مع حد حجم.
- الفيديو يرفع MP4 فقط وبحجم أقصى 20MB بحسب Form Requests، ويخزن على قرص خاص ويُعرض بعد Policy authorization؛ اختبارات المشروع تغطي استجابة Range.
- تحقق HMAC باستخدام `hash_equals` وHMAC-SHA512؛ التحقق من حالة الدفع والمبلغ والعملة ومعرّف التكامل؛ منع إعادة معالجة اشتراك مدفوع.
- حماية CSRF عامة مع استثناء callback موقّع؛ كلمات المرور تُخزّن عبر hashing؛ تحديث الجلسة عند تسجيل الدخول.
- التسجيل والمصادقة وإعادة إرسال البريد والكوبونات لها محددات معدل، والتحقق من الأدوار والملكية موجود في middleware/Policies/Requests.
- الكوبون لا يخصم أكثر من سعر الدورة، ويُحفظ المبلغ/الخصم على الاشتراك بدل إعادة حسابهما بعد الدفع.
- تصدير المدرس يتلقى معرف المدرس الحالي؛ الفواتير والتحقق من الملكية في `PaymentController`.

### عناصر لم يظهر دليل كافٍ على أنها أخطاء

- مسار مشاهدة الدرس والشهادة يملكان فحوص اشتراك/تقدم في وحدات التحكم والخدمات؛ لم يظهر خلال هذه المراجعة تسريب مؤكد لبيانات مستخدم آخر.
- ملف رفع الفيديو يعتمد على Symfony `BinaryFileResponse` واختبار Range في المشروع يثبت `206 Partial Content`، لذلك لم أعتبر غياب Range مشكلة قائمة.
- لا يوجد دليل على أن رد الاسترداد المالي آلي؛ واجهة التدفق توضح المعالجة اليدوية، لذا ينبغي تسويق الميزة كـ«طلب استرداد ومراجعة» لا كـ«استرداد تلقائي».

## 5. قائمة الميزات الحالية للمستخدم النهائي

### الطالب

1. إنشاء حساب طالب ببيانات الاتصال والمرحلة/الصف/النظام التعليمي، ثم تأكيد البريد.
2. تسجيل الدخول وتذكر الجلسة، مع استعادة كلمة المرور وتغيير بريد الحساب.
3. استعراض الكورسات المنشورة والبحث فيها وتصفية النتائج التعليمية مع صفحات متعددة.
4. الاشتراك في الدورات المجانية أو بدء الدفع عبر Vodafone Cash/Fawry إذا كانت بيانات التاجر مفعلة.
5. تطبيق كوبون خصم ضمن شروط صلاحيته وحد استخدامه.
6. متابعة الكورسات المسجل بها والتقدم والدرس التالي المقترح ووقت التعلم.
7. مشاهدة فيديوهات الدروس المصرح بها، حفظ موضع المشاهدة، تتبع التقدم وإكمال الدرس.
8. طرح أسئلة وتعليقات على الدروس واستقبال الردود والإشعارات.
9. أداء اختبار نهائي بمدة وترتيب أسئلة محددين ورؤية النتيجة وعدد المحاولات.
10. استلام شهادة إتمام عند استيفاء شروط الكورس، والتحقق العام من رقمها/QR.
11. كتابة تقييم للكورس بعد استيفاء شرط الإكمال المضبوط في الإعداد.
12. عرض سجل الاشتراكات والفواتير وطلب مراجعة استرداد.
13. اختيار المظهر الفاتح/الداكن واستخدام البحث العام.

### المدرّس

1. لوحة مؤشرات للدورات والطلاب والدخل المكتمل والتقدم.
2. إنشاء دورة وتعديلها ونشرها بعد تجهيز محتواها، مع وصف وسعر وصورة وتصنيف تعليمي.
3. إدارة الدروس وترتيبها ورفع فيديو MP4.
4. إعداد اختبار الدورة، أسئلته واختياراته ودرجة النجاح والمدة وعدد المحاولات.
5. إنشاء كوبونات نسبة/قيمة ثابتة وتحديد المواعيد وعدد الاستخدامات وتعطيلها.
6. الاطلاع على نشاط الطلاب المسجلين وإشعارات الاشتراك.
7. طلب إنشاء تقرير شهري في الخلفية وتنزيل قوائم الطلاب والمدفوعات بصيغة Excel.

### المدير

1. لوحة ملخص المنصة والمستخدمين والكورسات والاشتراكات.
2. البحث عن المستخدمين وتصفية قائمتهم وتعديل البيانات والأدوار.
3. فتح قفل تسجيل دخول المستخدم عند الحاجة.
4. حماية إدارية من حذف الحساب الحالي أو فقد آخر مدير.
5. مراجعة طلبات الاسترداد وتغيير حالتها وإضافة ملاحظة؛ تنفيذ رد المال يدوي.
6. الوصول إلى لوحة Horizon للمدير فقط إذا كانت خدمة Horizon مهيأة.
7. المصادقة الثنائية متاحة عبر Fortify؛ إلزامها يعتمد على `REQUIRE_2FA`.

### الزائر

1. الصفحة الرئيسية، دليل استخدام تفاعلي، وتصفح تسجيل الدخول/إنشاء الحساب.
2. صفحة عامة للتحقق من الشهادة.
3. تحميل خطوط الواجهة من Google Fonts بحسب اتصال المتصفح.

## 6. قاعدة البيانات والترحيلات

ملفات الترحيل الموجودة (23):

```text
0001_01_01_000000_create_users_table.php
0001_01_01_000001_create_cache_table.php
0001_01_01_000002_create_jobs_table.php
2026_10_01_153741_courses_table.php.php
2026_10_06_155608_create_lessons_table.php
2026_10_07_095026_create_lesson_progress_table.php
2026_10_12_150000_create_course_enrollments_table.php
2026_10_13_200000_create_course_quizzes_table.php
2026_10_13_200100_create_course_certificates_table.php
2026_10_14_170000_create_notifications_table.php
2026_10_14_173000_add_watched_seconds_to_lesson_progress_table.php
2026_10_14_190000_create_course_reviews_table.php
2026_10_14_192000_create_lesson_comments_table.php
2026_10_16_180000_add_login_lock_fields_to_users_table.php
2026_10_25_150000_add_preferred_theme_to_users_table.php
2026_10_26_000000_add_performance_indexes.php
2026_10_26_180000_create_teacher_reports_table.php
2026_10_26_190000_add_learning_and_billing_features.php
2026_10_28_162400_add_education_fields_to_courses_table.php
2026_10_28_170000_add_student_details_to_users_table.php
2026_10_28_180000_add_education_grade_and_system_to_users_table.php
2026_11_01_170000_add_two_factor_columns_to_users_table.php
2026_11_01_190000_create_paymob_callback_logs_table.php
```

كما توجد `UserFactory.php` و`DatabaseSeeder.php`. ترحيل Paymob يضيف سجل التدقيق، لذلك يجب تشغيل migrations على **البيئة الصحيحة بعد التحقق من الاتصال وأخذ نسخة احتياطية**؛ هذا التقرير لم يشغّل ترحيلًا على بيانات الإنتاج.

## 7. الاختبارات والجودة

ملفات الاختبار تغطي: لوحة الإدارة والمدرس، المصادقة و2FA وإعادة تعيين كلمة المرور وتأكيد البريد ومحددات المعدل، إنشاء الكورسات والرفع والكتالوج، checkout وPaymob والكوبونات والفواتير والاسترداد، الدروس والفيديو والتقدم والتعليقات، الاختبارات والشهادات، الإشعارات، والتقارير.

ملفات `Feature` المحصورة:

```text
AdminDashboardTest
BackupAndQueueReadinessTest
CourseBillingFeaturesTest
CourseCatalogCacheTest
CourseCheckoutTest
CourseEditTest
CourseNotificationsTest
CourseQuizCertificateTest
CourseReviewTest
EmailVerificationTest
ExampleTest
FinalizeMediaUploadTest
LandingPageTest
LessonCommentsTest
LessonManagementTest
LessonProgressTest
LessonVideoAccessTest
LoginRateLimitTest
MyCoursesTest
PageGuideTest
PasswordResetTest
ProfileActivityTest
RegistrationLoadTest
RegistrationRateLimitTest
RequireTwoFactorAuthenticationTest
TeacherDashboardTest
TeacherReportsTest
TwoFactorLoginTest
UserObserverTest
UserUpdateValidationTest
```

يوجد أيضًا `tests/TestCase.php` واختبار `tests/Unit/ExampleTest.php`. تشغيل المجموعة الكاملة في 6 نوفمبر 2026: **192 passed، 0 failed**. اختبار `RegistrationLoadTest` يقيس 200 طلب تسجيل متتابعًا في بيئة الاختبار، لا تحميلًا متزامنًا.

سير CI في `.github/workflows/laravel.yml` يختبر PHP 8.2 و8.3، ويثبت Composer، ثم يشغّل Pint وLarastan واختبارات Laravel. لم يُشغّل بناء Vite في هذه المراجعة؛ محاولة تشغيل task الخاصة بالبناء لم تجد Terminal صالحًا في أداة VS Code، لذا لا أقدّم ادعاء نجاح build.

## 8. ترتيب العمل المقترح

1. **قبل النشر:** اضبط قرص نسخ احتياطي خارجيًا، وكلمة مرور تشفير الإشعار والبريد، ثم نفّذ نسخة تجريبية واستعادة كاملة في بيئة منفصلة.
2. **قبل فتح حسابات الإدارة:** فعّل 2FA إجباريًا في الإنتاج واختبر الاستعادة والحسابات الاحتياطية.
3. **قبل تفعيل الدفع المباشر:** اختبر callback على Paymob sandbox لحالات النجاح والفشل والتكرار وتأخر الإشعار، وخطة التعامل مع اختلاف المبلغ، ثم طبق migrations على staging أولًا.
4. **قبل اعتماد النسخ:** نفّذ استعادة كاملة في بيئة منفصلة، بما في ذلك قاعدة البيانات والملفات، وتأكد من تشغيل قراءة آمنة بعد الاستعادة.
5. **قبل إعلان السعة:** أجرِ اختبار ضغط متزامنًا ومعتمدًا على staging مع مراقبة زمن الاستجابة وDB/Redis/queue ومعدلات 429؛ لا تستخدم الإنتاج كهدف للاختبار.
6. **تحسين تشغيلي:** أضف retention وتنبيهات لسجلات Paymob الفاشلة، وتأكد من سياسة الخصوصية الخاصة بعناوين IP.
7. **تنظيف التوثيق:** `SITE_FULL_DOCUMENTATION_AR.md` و`project-overview.html` يحتويان أرقامًا/ملاحظات قديمة (مثل غياب 2FA وأرقام الاختبارات والنماذج). اعتمد هذا التقرير كمرجع أحدث وحدّث المستندات القديمة قبل تسليمها للعميل.
8. **مراجعة التبعيات:** شغّل فحص Composer/npm لمخاطر التبعيات، وراجع تحديثات الأمان وفق lockfiles، ثم أعد تشغيل CI.

## 9. مراجع داخل المشروع

- إعداد الدفع والتجارب: [PAYMOB_SETUP.md](./PAYMOB_SETUP.md)
- إعدادات النشر والطوابير: [README.md](./README.md)
- المسارات: [routes/web.php](./routes/web.php) و[routes/console.php](./routes/console.php)
- إعدادات الخدمات: [config/](./config)
- منطق التطبيق: [app/](./app)
- بنية البيانات: [database/migrations/](./database/migrations)
- الاختبارات: [tests/](./tests)
