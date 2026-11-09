<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportInquiryReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $question,
        public readonly string $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رد فريق الدعم | منصة مدار',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-inquiry-reply',
        );
    }
}
