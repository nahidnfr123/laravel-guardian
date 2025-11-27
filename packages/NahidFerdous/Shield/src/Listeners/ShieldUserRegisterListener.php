<?php

namespace NahidFerdous\Shield\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use NahidFerdous\Shield\Events\ShieldUserRegisterEvent;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;

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
            // 'email' => $user->email,
            // 'name' => $user->name,
            'request_data' => $event->requestData,
        ]);

        // Send verification email if enabled
        if (config('shield.auth.create_user.send_verification_email', false)) {
            $authService = AuthServiceFactory::make();
            $authService->sendVerificationEmail($user);
        }

        // Example: Send welcome email
        // Mail::to($user->email)->send(new WelcomeEmail($user));

        // Example: Create user profile
        // $user->profile()->create([...]);

        // Example: Send notification to admins
        // Notification::send(User::admins()->get(), new NewUserRegistered($user));
    }
}
