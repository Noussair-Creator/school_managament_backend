<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Use Auth facade
use Illuminate\Support\Facades\Storage;
// use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;
// use App\Models\Comment;
// use App\Models\Post;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Apply middleware using 'role.permission' format based on your custom middleware
        $this->middleware('role.permission:list users')->only('index');
        // $this->middleware('role.permission:create responsable')->only('createResponsable');
        // $this->middleware('role.permission:create teacher')->only('createTeacher');
        $this->middleware('role.permission:show profile')->only('showProfile');
        $this->middleware('role.permission:update profile')->only('updateProfile');
        $this->middleware('role.permission:delete profile')->only('deleteProfile');
        $this->middleware('role.permission:delete user')->only('deleteByAdmin');
        // $this->middleware('role.permission:show profile')->only('profile'); // Assuming showProfile covers this
    }


    // Get all users (Requires 'list users' permission)
    public function index()
    {
        $users = User::with('roles')->get(); // Eager load roles for each user
        return response()->json($users);
    }

    // Show the authenticated user's profile (Requires 'show profile' permission)
    public function showProfile()
    {
        $user = Auth::user()->load('roles'); // Get the currently logged-in user and load roles
        return response()->json([
            'message' => 'Profile fetched successfully',
            'user' => $user
        ]);
    }

    /**
     * Update the authenticated user's profile.
     * Allows optional updates for address, phone, and picture.
     * (Requires 'update profile' permission)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Validate the incoming request data
        // Fields are now optional ('nullable' allows empty/null, 'sometimes' applies rules only if field is present)
        $validatedData = $request->validate([
            'address' => 'nullable|string|max:255', // No longer required, allows null/empty
            'phone'   => 'nullable|string|max:255', // No longer required, allows null/empty
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'picture' => [
                'sometimes', // Apply rules only if 'picture' is present in the request
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048' // Max 2MB,
            ],
            // If you re-add first/last name later:

        ]);

        $oldPicture = $user->picture; // Store old picture path before potential update
        $pictureUpdated = false;     // Flag to track if a new picture was processed
        $newPicturePath = null;      // Store path of newly uploaded picture temporarily

        // --- Update Logic ---

        // Update picture if provided and validation passed
        if ($request->hasFile('picture')) {
            // Store the new picture
            $imagePath = $request->file('picture')->store('profile_pictures', 'public');

            // Check if storage was successful
            if ($imagePath) {
                $newPicturePath = $imagePath; // Store the new path
                $user->picture = $newPicturePath; // Tentatively update the user model
                $pictureUpdated = true;        // Flag that picture was changed
            } else {
                // Handle potential storage failure if needed, though 'store' usually throws exceptions on major errors
                return response()->json(['message' => 'Failed to store profile picture.'], 500);
            }
        }

        // Update other fields only if they are present in the validated data
        if (array_key_exists('address', $validatedData)) {
            $user->address = $validatedData['address'];
        }

        if (array_key_exists('phone', $validatedData)) {
            $user->phone = $validatedData['phone'];
        }
        if (array_key_exists('first_name', $validatedData)) {
            $user->first_name = $validatedData['first_name'];
        }
        if (array_key_exists('last_name', $validatedData)) {
            $user->last_name = $validatedData['last_name'];
        }

        // --- Save and Respond ---

        // Check if any changes were actually made to the model
        if ($user->isDirty()) { // isDirty() checks if any attributes have changed
            if ($user->save()) {
                // Delete the old picture ONLY if a new one was successfully saved
                if ($pictureUpdated && $oldPicture && Storage::disk('public')->exists($oldPicture)) {
                    // Make sure not to delete the new picture if old and new paths somehow became the same
                    if ($oldPicture !== $newPicturePath) {
                        Storage::disk('public')->delete($oldPicture);
                    }
                }
                return response()->json([
                    'message' => 'Profile updated successfully',
                    'user' => $user->fresh()->load('roles'), // Return fresh data with roles
                ]);
            } else {
                // If saving failed, delete the newly uploaded picture (if any) to avoid orphans
                if ($pictureUpdated && $newPicturePath && Storage::disk('public')->exists($newPicturePath)) {
                    Storage::disk('public')->delete($newPicturePath);
                }
                return response()->json([
                    'message' => 'Failed to update profile',
                ], 500);
            }
        } else {
            // No changes were detected
            return response()->json([
                'message' => 'No changes detected in the profile data.',
                'user' => $user->load('roles'), // Return current data with roles
            ]);
        }
    }

    // Delete the authenticated user (self-deletion) (Requires 'delete profile' permission)
    public function deleteProfile()
    {
        $user = Auth::user();

        // Store path before potentially deleting the user record
        $picturePath = $user->picture;

        if ($user->delete()) { // Attempt to delete the user first
            // If user deletion is successful, then delete the profile picture
            if ($picturePath && Storage::disk('public')->exists($picturePath)) {
                Storage::disk('public')->delete($picturePath);
            }
            // Optionally: Logout the user after deletion if using session/token management
            // Auth::logout(); or $user->tokens()->delete();
            return response()->json(['message' => 'Your profile has been deleted successfully']);
        } else {
            return response()->json(['message' => 'Failed to delete profile'], 500);
        }
    }

    // Delete a user by Admin/Superadmin (Requires 'delete user' permission)
    public function deleteByAdmin($userId)
    {
        // Prevent admin from deleting themselves using this route
        if (Auth::id() == $userId) {
            return response()->json(['message' => 'You cannot delete your own account using this method. Use the profile delete option.'], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Optional: Add checks here - e.g., prevent deleting the last superadmin

        $picturePath = $user->picture; // Store path before deletion

        if ($user->delete()) { // Attempt delete
            // Delete picture after successful user deletion
            if ($picturePath && Storage::disk('public')->exists($picturePath)) {
                Storage::disk('public')->delete($picturePath);
            }
            // Optionally detach roles/permissions if delete event doesn't handle it
            // $user->syncRoles([]);
            return response()->json(['message' => 'User deleted successfully']);
        } else {
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }
}
