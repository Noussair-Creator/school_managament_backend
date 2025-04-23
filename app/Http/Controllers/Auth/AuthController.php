<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\UserRegistered; // Import the custom event
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // <-- Added missing import

class AuthController extends Controller
{
    /**
     * User registration method (Role: eleve).
     * Does NOT dispatch UserRegistered event by default.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->assignRole('eleve'); // Ensure 'eleve' role exists

        // NOTE: UserRegistered event is NOT dispatched for 'eleve' role here.
        // If notifications are needed, dispatch the event:
        // try { UserRegistered::dispatch($user); } catch (\Exception $e) { Log::error(...) }

        $user->load('roles:name');
        $token = $user->createToken($request->email ?? 'register-token-eleve')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'User registered successfully (role = eleve)'
        ], 201);
    }

    /**
     * Teacher registration method (Role: teacher).
     * Dispatches UserRegistered event.
     */
    public function registerTeacher(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        // --- Ensure this role name matches what the Listener checks ---
        $user->assignRole('teacher'); // Ensure 'teacher' role exists and matches listener check

        // --- Dispatch the UserRegistered Event ---
        try {
            Log::info("Attempting to dispatch UserRegistered for Teacher: " . $user->email); // Optional: More detailed logging
            UserRegistered::dispatch($user);
            Log::info("Dispatched UserRegistered successfully for Teacher: " . $user->email); // Optional: Success logging
        } catch (\Exception $e) {
            Log::error("Failed to dispatch UserRegistered event for Teacher {$user->email}: " . $e->getMessage());
        }
        // --- End Event Dispatch ---

        $user->load('roles:name');
        $token = $user->createToken($request->email ?? 'register-token-teacher')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Teacher registered successfully (role = teacher)'
        ], 201);
    }

    /**
     * Lab Manager registration method (Role: Lab Manager).
     * Dispatches UserRegistered event.
     */
    public function registerResponsable(Request $request) // Consider renaming to registerLabManager for clarity
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        // --- Ensure this role name matches what the Listener checks ---
        $user->assignRole('responsable_labo'); // Ensure 'Lab Manager' role exists and matches listener check

        // --- Dispatch the UserRegistered Event ---
        try {
            Log::info("Attempting to dispatch UserRegistered for Lab Manager: " . $user->email); // Optional logging
            UserRegistered::dispatch($user);
            Log::info("Dispatched UserRegistered successfully for Lab Manager: " . $user->email); // Optional logging
        } catch (\Exception $e) {
            Log::error("Failed to dispatch UserRegistered event for Lab Manager {$user->email}: " . $e->getMessage());
        }
        // --- End Event Dispatch ---

        $user->load('roles:name');
        $token = $user->createToken($request->email ?? 'register-token-labmanager')->plainTextToken; // Changed default name

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Lab Manager registered successfully (role = Lab Manager)'
        ], 201);
    }

    /**
     * User login method (API Token Authentication).
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => __('auth.failed')], 401);
        }

        // Load roles before returning
        $user->load('roles:name');

        // Optional: Revoke all old tokens for this user upon new login
        // $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken($request->email ?? 'login-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'User logged in successfully'
        ]); // Default status 200 OK
    }

    /**
     * User logout method (API Token Authentication).
     * NOTE: Route must be protected by 'auth:sanctum' middleware.
     */
    public function logout(Request $request)
    {
        $user = $request->user(); // Get user via token + middleware

        if ($user) {
            // Revoke only the token used for this request
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'User logged out successfully']);
        }

        return response()->json(['message' => 'No authenticated user session found.'], 401);
    }

    /**
     * Get the authenticated user (API Token Auth).
     * NOTE: Route must be protected by 'auth:sanctum' middleware.
     */
    public function user(Request $request)
    {
        $user = $request->user(); // Get user via token + middleware

        if ($user) {
            // Load roles before returning
            $user->load('roles:name');
            return response()->json($user);
        }

        // Should not be reached if middleware is applied correctly
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
