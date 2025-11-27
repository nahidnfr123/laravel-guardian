<?php

namespace NahidFerdous\Shield\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use NahidFerdous\Shield\Events\ShieldUserRegisterEvent;
use NahidFerdous\Shield\Listeners\ShieldUserRegisterListener;

class ShieldEventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ShieldUserRegisterEvent::class => [
            ShieldUserRegisterListener::class,
            // Add more listeners here
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
