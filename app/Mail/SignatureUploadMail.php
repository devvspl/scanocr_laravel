<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatureUploadMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $signUrl;

    public function __construct(User $user, string $signUrl)
    {
        $this->user    = $user;
        $this->signUrl = $signUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Upload Your Digital Signature — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signature-upload',
        );
    }
}
