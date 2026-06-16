<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiKeyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $apiKey,
        public readonly string $chatUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to AI Gateway — Your API Key');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.api_key');
    }
}
