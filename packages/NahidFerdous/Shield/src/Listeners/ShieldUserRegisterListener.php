<?php

namespace NahidFerdous\Shield\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Events\ShieldUserRegisterEvent;
use NahidFerdous\Shield\Mail\VerifyEmailMail;
use NahidFerdous\Shield\Models\EmailVerificationToken;

class ShieldUserRegisterListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ShieldUserRegisterEvent $event): void
    {
        $user = $event->user;

        // Example: Log user registration
        Log::info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'request_data' => $event->requestData,
        ]);

        // Send verification email if enabled
        if (config('shield.auth.create_user.send_verification_email', false)) {
            $this->sendVerificationEmail($user);
        }

        // Example: Send welcome email
        // Mail::to($user->email)->send(new WelcomeEmail($user));

        // Example: Create user profile
        // $user->profile()->create([...]);

        // Example: Send notification to admins
        // Notification::send(User::admins()->get(), new NewUserRegistered($user));
    }

    /**
     * Send verification email to user
     */
    protected function sendVerificationEmail($user): void
    {
        // Delete any existing tokens for this user
        EmailVerificationToken::where('user_id', $user->id)->delete();

        // Generate new token
        $token = Str::random(64);
        $expiresAt = now()->addHours(config('shield.emails.verify_email.expiration', 24));

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        // Generate verification URL
        $url = (string) config('shield.emails.verify_email.redirect_url', url(config('shield.route_prefix').'/verify-email'));
        $redirectUrl = $url.'?token='.$token;

        // Send email (will be queued by the mailable itself)
        Mail::to($user->email)->send(new VerifyEmailMail($user, $redirectUrl));
    }
}
