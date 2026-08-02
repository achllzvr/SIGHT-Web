<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfessionalInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $professional;
    public $tempPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $professional, string $tempPassword)
    {
        $this->professional = $professional;
        $this->tempPassword = $tempPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to LUMI — Professional Account Created',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.professional-invitation',
            with: [
                'professional' => $this->professional,
                'tempPassword' => $this->tempPassword,
                'loginUrl' => url('/auth/login'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
