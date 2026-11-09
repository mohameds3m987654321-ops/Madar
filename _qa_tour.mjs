// شاشات كاملة للمشروع كله — ملف واحد يعمل جولة تصوير
export default async function run(page, ui) {
  const out = 'd:/xampp/htdocs/return/_shots';
  const shot = async (name, opts) => {
    opts = opts || {};
    await page.waitForTimeout(opts.wait == null ? 700 : opts.wait);
    await page.screenshot({ path: out + '/' + name + '.png', fullPage: opts.full === true });
  };
  const scrollAll = async () => {
    await page.evaluate(async () => {
      for (let y = 0; y <= document.body.scrollHeight; y += 500) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 90));
      }
      window.scrollTo(0, 0);
    });
    await page.waitForTimeout(700);
  };

  // ---- الرئيسية (فاتح) ----
  await page.goto('http://127.0.0.1:8098/', { waitUntil: 'networkidle' });
  await shot('01-home-light-top');
  await scrollAll();
  await shot('02-home-light-full', { full: true });

  // ---- الرئيسية (داكن) ----
  await page.evaluate(() => localStorage.setItem('madar-theme', 'dark'));
  await page.goto('http://127.0.0.1:8098/', { waitUntil: 'networkidle' });
  await shot('03-home-dark-top');
  await scrollAll();
  await shot('04-home-dark-full', { full: true });
  await page.evaluate(() => localStorage.setItem('madar-theme', 'light'));

  // ---- تسجيل الدخول ----
  await page.goto('http://127.0.0.1:8098/login', { waitUntil: 'networkidle' });
  await shot('05-login');

  // ---- إنشاء حساب ----
  await page.goto('http://127.0.0.1:8098/register', { waitUntil: 'networkidle' });
  await shot('06-register-step1');
  await page.fill('#name', 'محمد أحمد علي');
  await page.fill('#email', 'student@example.com');
  await page.fill('#student_phone', '01000000000');
  await page.fill('#parent_phone', '01100000000');
  await page.click('#nextBtn');
  await page.waitForTimeout(600);
  await shot('07-register-step2');

  // ---- نسيت كلمة المرور ----
  await page.goto('http://127.0.0.1:8098/forgot-password', { waitUntil: 'networkidle' });
  await shot('08-forgot-password');

  // ---- دليل الاستخدام ----
  await page.goto('http://127.0.0.1:8098/guide', { waitUntil: 'networkidle' });
  await shot('10-guide');

  // ---- صفحة الدورات ----
  await page.goto('http://127.0.0.1:8098/courses', { waitUntil: 'networkidle' });
  await scrollAll();
  await shot('11-courses-list', { full: true });

  // ---- صفحة تسجيل الدخول (للدخول كمسؤول/معلم وتصوير اللوحات) ----
  // ملاحظة: هذه الخطوات تفترض وجود بيانات تجريبية
  await page.goto('http://127.0.0.1:8098/login', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'admin@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1000);

  // ---- لوحة تحكم المسؤول ----
  await page.goto('http://127.0.0.1:8098/admin', { waitUntil: 'networkidle' });
  await shot('12-admin-dashboard');

  // ---- لوحة تحكم المعلم ----
  await page.goto('http://127.0.0.1:8098/teacher/dashboard', { waitUntil: 'networkidle' });
  await shot('13-teacher-dashboard');

  // ---- الملف الشخصي ----
  await page.goto('http://127.0.0.1:8098/profile', { waitUntil: 'networkidle' });
  await shot('14-user-profile');

  // ---- دوراتي ----
  await page.goto('http://127.0.0.1:8098/my-courses', { waitUntil: 'networkidle' });
  await shot('15-my-courses');

  return 'done';
}
