<?php

namespace App\Notifications;

use App\Models\SupportInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportInquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportInquiry $inquiry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('استفسار جديد من بوت مدار')
            ->greeting('وصل استفسار جديد لفريق مدار')
            ->line($this->inquiry->question)
            ->action('فتح صندوق الاستفسارات', route('admin.support-inquiries.index'))
            ->line('المحادثة وبيانات التواصل المتاحة محفوظة في صندوق الدعم، ويمكن متابعة الرد من هناك.');
    }
}
