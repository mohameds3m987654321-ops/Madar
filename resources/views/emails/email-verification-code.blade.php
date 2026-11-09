<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد البريد الإلكتروني</title>
</head>
<body style="margin:0;padding:24px;background:#f4f7f6;color:#18332d;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right">
    <main style="max-width:520px;margin:0 auto;padding:32px;border:1px solid #dce8e4;border-radius:16px;background:#fff">
        <table role="presentation" style="margin-bottom:24px;border-collapse:collapse;color:#12634f;font-size:20px;font-weight:700">
            <tr>
                <td style="width:38px;height:38px;border-radius:12px;background:#12634f;color:#fff;text-align:center;font-size:0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" style="vertical-align:middle">
                        <path d="M4 17.5 10.8 4l3.1 6.1-3.2 6.4M12.5 17.5l3.8-7.4 3.7 7.4M7 17.5h13" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span style="font-size:0">م</span>
                </td>
                <td style="padding-right:10px">مدار</td>
            </tr>
        </table>
        <h1 style="margin:0 0 16px;font-size:22px">أهلًا {{ $userName }}</h1>
        <p style="line-height:1.9">استخدم الرمز التالي لتأكيد بريدك الإلكتروني في منصة مدار:</p>
        <p dir="ltr" style="margin:24px 0;padding:16px;border-radius:12px;background:#eef7f3;color:#12634f;text-align:center;font-size:32px;font-weight:700;letter-spacing:8px">{{ $code }}</p>
        <p style="line-height:1.9">الرمز صالح لمدة 10 دقائق ولمرة واحدة. إذا لم تطلب إنشاء حساب، يمكنك تجاهل هذه الرسالة.</p>
        <p style="margin-top:28px;color:#66756f">منصة مدار</p>
    </main>
</body>
</html>
