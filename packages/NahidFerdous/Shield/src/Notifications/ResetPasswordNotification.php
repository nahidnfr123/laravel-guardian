<?php

namespace NahidFerdous\Shield\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        $subject = config('shield.emails.reset_password.subject', 'Reset Password Notification');
        $customView = config('shield.emails.reset_password.template', null);
        $view = $customView ?: 'shield::emails.shield_reset_password_mail';

        $redirectUrl = url(config('app.url').'/reset-password?token='.$this->token.'&email='.$notifiable->email);

        if ($view && view()->exists($view)) {
            return (new MailMessage)
                ->subject($subject)
                ->view($view, [
                    'user' => $notifiable,
                    'redirectUrl' => $redirectUrl,
                    'token' => $this->token,
                    'expireMinutes' => config('auth.passwords.users.expire', null),
                ]);
        }

        // Default Laravel-style template
        return (new MailMessage)
            ->subject($subject)
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $redirectUrl)
            ->line('This password reset link will expire in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
