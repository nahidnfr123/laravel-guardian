<?php

namespace NahidFerdous\Shield\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationUrl;
    public $user;

    public function __construct($user, $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    public function build(): VerifyEmailMail
    {
        $customView = config('shield.email_templates.verify_email');

        if ($customView && view()->exists($customView)) {
            return $this->subject('Verify Your Email Address')
                ->view($customView);
        }

        // Default Laravel-style template
        return $this->subject('Verify Your Email Address')
            ->view('shield::emails.verify-email');
    }
}
