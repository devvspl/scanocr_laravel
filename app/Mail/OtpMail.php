<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $type;

    public function __construct($otp, $type = 'reset')
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'login' => 'Your WolfBooks Login OTP',
            'register' => 'Verify Your WolfBooks Registration',
            'reset' => 'Your WolfBooks Password Reset OTP',
        ];

        return new Envelope(
            subject: $subjects[$this->type] ?? 'Your WolfBooks OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }
}
