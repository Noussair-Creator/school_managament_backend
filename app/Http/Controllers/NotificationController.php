<?php

namespace App\Http\Controllers;

// Remove the import for your deleted custom model:
// use App\Models\UserNotification; // <-- REMOVE THIS LINE

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// Optional: Type-hinting for Laravel's internal notification model if needed later
// use Illuminate\Notifications\DatabaseNotification as LaravelDbNotification;

class NotificationController extends Controller
{
    // Optional: Add auth middleware if not applied globally in routes/api.php
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    /**
     * Fetch notifications for the authenticated user using the Notifiable trait.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Get the authenticated user (who has the Notifiable trait)

        // --- Use the relationships provided by the Notifiable trait ---
        $query = $user->notifications(); // Base query for user's notifications

        // --- Filtering ---
        if ($request->query('status') === 'unread') {
            $query = $user->unreadNotifications(); // Scope provided by trait
        } elseif ($request->query('status') === 'read') {
            $query = $user->readNotifications(); // Scope provided by trait
        }

        // --- Ordering (Newest first is default) ---
        // $query->latest(); // Can be explicit if needed

        // --- Pagination ---
        $limit = $request->query('limit', 15);
        $limit = min(max(5, (int)$limit), 100); // Enforce reasonable limits
        $notifications = $query->paginate($limit);

        // --- Unread Count ---
        $unreadCount = $user->unreadNotifications()->count(); // Use trait method

        // --- Return Response ---
        // $notifications->items() will contain instances of Illuminate\Notifications\DatabaseNotification
        // which have id, type, data (already cast to array), read_at, created_at etc.
        return response()->json([
            'notifications' => $notifications, // Paginated result object
            'unread_count' => $unreadCount,
        ]);

        /* // --- Using API Resources (Still Recommended) ---
           // php artisan make:resource NotificationResource
           // return \App\Http\Resources\NotificationResource::collection($notifications)
           //             ->additional(['meta' => ['unread_count' => $unreadCount]]);
        */
    }

    /**
     * Mark a specific notification as read.
     * Finds the notification via the user relationship for security.
     *
     * @param string $notificationId The UUID of the notification from the route.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function markAsRead(Request $request, string $notificationId) // Accept ID from route
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- Find the notification via the user's relationship ---
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            // Notification not found OR doesn't belong to the authenticated user
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        // --- Mark as read using the method on the notification object ---
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
        // Or: return response()->noContent();
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- Use the collection method provided by the Notifiable trait ---
        $user->unreadNotifications->markAsRead(); // Efficiently marks all loaded unread as read

        return response()->json(['message' => 'All unread notifications marked as read.']);
        // Or: return response()->noContent();
    }

    /**
     * Delete a specific notification
     *
     * @param string $notificationId The UUID of the notification from the route.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function destroy(string $notificationId) // Accept ID from route
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // --- Find the notification via the user's relationship ---
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        // --- Delete the notification ---
        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
        // Or: return response()->noContent();
    }
}
