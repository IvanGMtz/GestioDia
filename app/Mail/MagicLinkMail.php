<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu enlace de acceso a GestioDia',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            with: [
                'url' => route('magic-link.consume', ['token' => $this->token]),
            ],
        );
    }
}
