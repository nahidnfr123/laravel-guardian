<?php

namespace NahidFerdous\Shield\Traits;

use NahidFerdous\Shield\Notifications\ResetPasswordNotification;

trait ShieldUser
{
    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification(string $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
