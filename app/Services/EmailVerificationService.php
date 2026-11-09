<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailVerificationService
{
    private const CODE_LIFETIME_MINUTES = 10;

    private const MAX_CODE_ATTEMPTS = 5;

    public function sendCode(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
            'email_verification_code_attempts' => 0,
        ])->save();

        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($user->name, $code));
        } catch (TransportExceptionInterface $exception) {
            Log::error('Email verification code could not be sent.', [
                'user_id' => $user->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function verifyCode(User $user, string $code): void
    {
        $error = DB::transaction(function () use ($user, $code): ?string {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (
                !$user->email_verification_code_hash
                || !$user->email_verification_code_expires_at
                || $user->email_verification_code_expires_at->isPast()
            ) {
                $user->forceFill([
                    'email_verification_code_hash' => null,
                    'email_verification_code_expires_at' => null,
                    'email_verification_code_attempts' => 0,
                ])->save();

                return 'انتهت صلاحية الرمز أو لم يُرسل بعد. اطلب رمزًا جديدًا.';
            }

            if ($user->email_verification_code_attempts >= self::MAX_CODE_ATTEMPTS) {
                return 'تجاوزت عدد المحاولات المسموح. اطلب رمزًا جديدًا للمتابعة.';
            }

            if (!Hash::check($code, $user->email_verification_code_hash)) {
                $user->increment('email_verification_code_attempts');

                return 'رمز التحقق غير صحيح. راجعه وحاول مرة أخرى.';
            }

            $user->forceFill([
                'email_verified_at' => now(),
                'email_verification_code_hash' => null,
                'email_verification_code_expires_at' => null,
                'email_verification_code_attempts' => 0,
            ])->save();

            return null;
        });

        if ($error !== null) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        $user->refresh();
    }
}
