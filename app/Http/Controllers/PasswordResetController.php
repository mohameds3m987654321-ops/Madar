<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class PasswordResetController extends Controller
{
    private const TOKEN_LIFETIME_MINUTES = 60;

    public function requestForm()
    {
        return view('users.forgot-password');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        $user = User::where('email', $email)->first();

        if ($user) {
            // التوكن القابل للاستخدام: نسخة SHA-256 مخزنة، والأصل يذهب في الرابط فقط
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => hash('sha256', $token),
                    'created_at' => now(),
                ]
            );

            $user->notify(new ResetPasswordNotification($token));
        }

        // نفس الرسالة سواء الإيميل موجود أو لا — لمنع تعداد الحسابات (User Enumeration)
        return back()
            ->with('status', 'إذا كان هذا البريد مسجلًا لدينا، فستصلك رسالة تحتوي رابط إعادة تعيين كلمة المرور.');
    }

    public function resetForm(string $token, Request $request)
    {
        return view('users.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        $valid = $record !== null
            && hash_equals((string) $record->token, hash('sha256', (string) $data['token']))
            && abs(now()->diffInMinutes($record->created_at)) <= self::TOKEN_LIFETIME_MINUTES;

        if (! $valid) {
            return back()
                ->withInput(['email' => $data['email']])
                ->withErrors(['token' => 'رابط إعادة التعيين غير صالح أو انتهت صلاحيته. اطلب رابطًا جديدًا.']);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // التوكن يُستخدم مرة واحدة فقط
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()
            ->route('login')
            ->with('success', 'تم تعيين كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.');
    }
}
