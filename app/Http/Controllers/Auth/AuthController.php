<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
class AuthController extends Controller
{
    /**
     * User registration method.
     */
    public function register(Request $request)
    {
        // Validate user input
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email', // Ensure email is unique
            'password' => 'required|string|confirmed' // Ensure password confirmation
        ]);

        // Hash the password before saving
        $validated['password'] = Hash::make($validated['password']);

        // Create the user in the database
        $user = User::create($validated);

        // Assign a default "guest" role to the user
        $user->assignRole('guest');

        // Generate a token for the user
        $token = $user->createToken($request->email)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'User registered successfully with the guest role'
        ], 201);
    }

    /**
     * User login method.
     */
    public function login(Request $request)
    {
        // Validate the login input
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Find the user by email
        $user = User::where('email', $validated['email'])->first();

        // Check if the user exists and if the password matches
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Revoke any old tokens (optional, to prevent multiple active sessions)
        $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken($request->email);
        // Token will be automatically saved in the 'personal_access_tokens' table, including tokenable_type and tokenable_id

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'message' => 'User logged in successfully'
        ]);
    }



    /**
     * User logout method.
     */
    public function logout(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No authenticated user found.'
            ], 401);
        }

        // Revoke all tokens for the user
        $user->tokens()->delete();

        return response()->json([
            'message' => 'User logged out successfully'
        ], 200);
    }


        // Method to count active logged-in users
        public function countLoggedInUsers()
        {
            // Count the number of users with non-expired tokens
            $loggedInUsers = PersonalAccessToken::where('tokenable_type', User::class)
            ->distinct('tokenable_id') // Use tokenable_id to count unique users
            ->count('tokenable_id');

            return response()->json(['logged_in_users' => $loggedInUsers]);
        }

}
