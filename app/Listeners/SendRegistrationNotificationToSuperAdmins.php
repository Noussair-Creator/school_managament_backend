<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\User; // Import User model
use App\Notifications\NewUserRegistrationAlert; // ** Ensure this exists **
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification; // Import Notification facade

class SendRegistrationNotificationToSuperAdmins implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(UserRegistered $event): void
    {
        $newUser = $event->user;

        // --- CORRECTED Role Check ---
        // Use the exact role names you confirmed ('responsable_labo')
        if (!$newUser->hasRole(['teacher', 'responsable_labo'])) { // <-- FIXED
            Log::info("UserRegistered event for user {$newUser->email} with role(s) " . $newUser->roles->pluck('name')->implode(', ') . " does not require super admin notification. Skipping.");
            return; // Exit if the role isn't one we notify admins about
        }

        Log::info("Handling UserRegistered event for relevant user: {$newUser->email}. Finding Super Admins...");

        // --- CORRECTED Super Admin Role Name ---
        // Use the exact role name you confirmed ('superadmin')
        $superAdmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'superadmin'); // <-- FIXED (lowercase, no hyphen)
        })->get();

        if ($superAdmins->isEmpty()) {
            Log::warning("No Super Admin users (role 'superadmin') found to notify about the registration of {$newUser->email}."); // Updated log message
            return; // No one to notify
        }

        Log::info("Found {$superAdmins->count()} Super Admin(s) to notify about {$newUser->email}.");

        // --- Send a notification TO each Super Admin ---
        try {
            Notification::send($superAdmins, new NewUserRegistrationAlert($newUser));
            Log::info("Successfully queued notification to Super Admins regarding user {$newUser->email}.");
        } catch (\Exception $e) {
            Log::error("Failed to queue/send notification to Super Admins for user {$newUser->email}: " . $e->getMessage());
        }
    }
}
