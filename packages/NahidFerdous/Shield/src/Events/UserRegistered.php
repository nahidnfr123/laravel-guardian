<?php

namespace NahidFerdous\Shield\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $request;

    /**
     * Create a new event instance.
     */
    public function __construct($user, $request = null)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
