<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور | منصة مدار')
            ->greeting('مرحبًا!')
            ->line('تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك.')
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line('رابط إعادة التعيين صالح لمدة 60 دقيقة فقط.')
            ->line('إذا لم تطلب إعادة التعيين، تجاهل هذه الرسالة ولن يتغير شيء في حسابك.')
            ->salutation('مع تحيات، فريق منصة مدار');
    }
}
