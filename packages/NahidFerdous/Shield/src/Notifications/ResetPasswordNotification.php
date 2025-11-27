<?php

namespace NahidFerdous\Shield\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $customView = config('shield.emails.reset_password.template');

        $resetUrl = url(config('app.url').'/reset-password?token='.$this->token.'&email='.$notifiable->email);

        if ($customView && view()->exists($customView)) {
            return (new MailMessage)
                ->subject('Reset Password Notification')
                ->view($customView, [
                    'user' => $notifiable,
                    'resetUrl' => $resetUrl,
                    'token' => $this->token,
                ]);
        }

        // Default Laravel-style template
        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
