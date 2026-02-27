<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamJoinRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $requesterName,
        public string $requesterEmail,
        public string $tenantName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TimeBudget: Someone requested to join your team',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-join-request',
        );
    }
}
