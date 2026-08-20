<?php

namespace App\Mail;

use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SponsorEvent $event,
        public SponserEventTicket $invitation,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your invitation to ' . $this->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
