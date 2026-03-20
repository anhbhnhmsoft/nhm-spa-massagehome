<?php

namespace App\Mail;

use App\Enums\Language;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $otp Mã OTP
     * @param Language $locale Ngôn ngữ người dùng (vi, en,...)
     */
    public function __construct(
        public string $otp,
        public $locale = Language::VIETNAMESE
    ) {
        // Thiết lập ngôn ngữ cho Mailable này
        $this->locale($this->locale->value);
    }

    /**
     * Tiêu đề sẽ tự động dịch dựa trên locale đã set ở trên
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('auth.mail.otp.subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
