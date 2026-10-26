<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent synchronously (not queued): QUEUE_CONNECTION=database would need a worker.
 */
class ResetPasswordMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $nama,
        public string $token,
        public string $email,
        public string $resetUrl,
        public int $expireMinutes = 60,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Atur Ulang Kata Sandi - Portal Quiz 2 WebPro');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reset-password',
            text: 'mail.reset-password-text',
        );
    }
}
