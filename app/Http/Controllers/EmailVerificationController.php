<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UpdateEmailRequest;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('users.profile');
        }

        return view('users.verify-email');
    }

    public function verify(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $verification->verifyCode($request->user(), $validated['code']);

        return redirect()
            ->route('users.profile')
            ->with('success', 'تم تأكيد بريدك الإلكتروني بنجاح.');
    }

    public function verifyLegacyLink(EmailVerificationRequest $request): RedirectResponse
    {
        if (!$request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()
            ->route('users.profile')
            ->with('success', 'تم تأكيد بريدك الإلكتروني بنجاح.');
    }

    public function resend(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('users.profile');
        }

        $sent = $verification->sendCode($request->user());

        return back()->with($this->sendMessage($sent));
    }

    public function updateEmail(
        UpdateEmailRequest $request,
        EmailVerificationService $verification,
    ): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        if (strcasecmp($user->email, $validated['email']) === 0) {
            return back()->with('status', 'هذا هو البريد الحالي. يمكنك إعادة إرسال رمز التحقق.');
        }

        $user->email = $validated['email'];
        $user->email_verified_at = null;
        $user->email_verification_code_hash = null;
        $user->email_verification_code_expires_at = null;
        $user->email_verification_code_attempts = 0;
        $user->save();
        $sent = $verification->sendCode($user);

        return back()->with($this->sendMessage($sent, 'تم تحديث البريد. '));
    }

    private function sendMessage(bool $sent, string $prefix = ''): array
    {
        if (!$sent) {
            return ['email_error' => $prefix . 'تعذر إرسال رمز التحقق الآن. حسابك محفوظ؛ راجع إعدادات البريد وحاول إعادة الإرسال.'];
        }

        if (config('mail.default') === 'log') {
            return ['status' => $prefix . 'وضع البريد الحالي لا يرسل رسائل فعلية؛ رمز التحقق مسجل في storage/logs/laravel.log.'];
        }

        return ['status' => $prefix . 'تم إرسال رمز تحقق جديد إلى بريدك الإلكتروني.'];
    }
}
