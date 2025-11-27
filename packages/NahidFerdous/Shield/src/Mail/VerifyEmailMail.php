<?php

namespace NahidFerdous\Shield\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public $redirectUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $redirectUrl)
    {
        $this->user = $user;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('shield.emails.verify_email.subject', 'Verify Email Address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $customView = config('shield.emails.verify_email.template');
        if ($customView && view()->exists($customView)) {
            return new Content(
                view: $customView,
            );
        }

        return new Content(
            view: 'shield::emails.shield_verify_email_mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
