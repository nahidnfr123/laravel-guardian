<?php

namespace NahidFerdous\Shield\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShieldUserRegisterEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public $requestData;

    /**
     * Create a new event instance.
     */
    public function __construct($user, array $requestData = [])
    {
        $this->user = $user;
        $this->requestData = $requestData;
    }
}
