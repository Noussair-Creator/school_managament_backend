<?php

namespace App\Notifications;

use App\Models\User; // Import User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistrationAlert extends Notification implements ShouldQueue // Implement ShouldQueue
{
    use Queueable;

    protected User $newUser; // The user who just registered

    /**
     * Create a new notification instance.
     */
    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // $notifiable here is the Super Admin receiving the alert
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     // $notifiable is the Super Admin
    //     // $this->newUser is the Teacher/Lab Manager who registered
    //     $roleNames = $this->newUser->roles->pluck('name')->implode(', '); // Get role name(s)

    //     return (new MailMessage)
    //         ->subject("New User Registration: {$this->newUser->email} ({$roleNames})")
    //         ->greeting("Hello {$notifiable->first_name},") // Greet the Super Admin
    //         ->line("A new user has registered with the role(s): {$roleNames}.")
    //         ->line("Name: {$this->newUser->first_name} {$this->newUser->last_name}")
    //         ->line("Email: {$this->newUser->email}")
    //         // Add more relevant info if needed
    //         ->action('View User Profile', url("/admin/users/{$this->newUser->id}")) // Example link (adjust URL)
    //         ->line('Please review the new registration.');
    // }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        $roleNames = $this->newUser->roles->pluck('name')->implode(', ');
        return [
            'message' => "New {$roleNames} registered: {$this->newUser->first_name} {$this->newUser->last_name} ({$this->newUser->email})",
            'new_user_id' => $this->newUser->id,
            'new_user_name' => "{$this->newUser->first_name} {$this->newUser->last_name}",
            'new_user_email' => $this->newUser->email,
            'new_user_roles' => $roleNames,
            'action_url' => url("/admin/users/{$this->newUser->id}"), // Example link
        ];
    }
}
