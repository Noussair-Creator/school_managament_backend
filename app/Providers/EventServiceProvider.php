<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \Illuminate\Auth\Events\Registered::class => [ // Default Laravel registration event (if used)
            \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
        ],

        // --- Add your custom mapping here ---
        \App\Events\UserRegistered::class => [
            \App\Listeners\SendRegistrationNotificationToSuperAdmins::class,
            // Add other listeners for UserRegistered here if needed later
            // e.g., \App\Listeners\SendWelcomeEmail::class,
        ],
        // --- End custom mapping ---

        // Add mappings for other custom events like ReservationCreated later
        // \App\Events\ReservationCreated::class => [
        //    \App\Listeners\SendReservationRequestNotification::class,
        // ],

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
        return false; // Keep as false if manually defining in $listen array
    }
}
