<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.permission:,create user')->only('storeByAdmin');
        $this->middleware('role.permission:,show user')->only('show');
        $this->middleware('role.permission:,update user')->only('update');
        $this->middleware('role.permission:,delete user')->only('deleteByAdmin');


    }

    // Get all users (Superadmin can view all users)
    public function index()
    {
        $users = User::with('roles')->get(); // Eager load roles for each user

        return response()->json($users);
    }

    // Show a specific user (Superadmin can view any user)
    public function show($userId)
    {
        $user = User::with('roles.permissions')->find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    // Update the authenticated user's details (only name, email, and password can be updated)
    public function update(Request $request)
    {

        // Validate the incoming data (excluding role validation)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'password' => 'nullable|string|confirmed|min:8', // Password validation (nullable for not updating)
        ]);

        $user = Auth::user(); // Get the currently authenticated user

        // Update user fields if provided
        if ($request->has('name')) {
            $user->name = $validated['name'];
        }
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }
        if ($request->has('password')) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save(); // Save the updated user

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    // Delete the authenticated user (self-deletion)
    public function delete()
    {
        $user = Auth::user(); // Get the currently authenticated user
        $user->delete(); // Delete the user
        return response()->json(['message' => 'User deleted successfully']);
    }

    // Show the authenticated user's profile
    public function profile()
    {
        $user = Auth::user()->load('roles'); // Eager load roles and permissions relationships

        return response()->json($user);
    }

    // Update a user (Superadmin can update any user)
    public function updateByAdmin(Request $request, $userId)
    {
        $this->authorize('update user'); // Ensures the current user has the permission

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $userId,
            'password' => 'string|confirmed|min:8',
            'role' => 'nullable|string|exists:roles,name', // Role validation
        ]);

        if ($request->has('name')) {
            $user->name = $validated['name'];
        }
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }
        if ($request->has('password')) {
            $user->password = bcrypt($validated['password']);
        }
        if ($request->has('role')) {
            $user->syncRoles($validated['role']); // Sync role to the user
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    // Store a new user by Superadmin (with optional role assignment)
    public function storeByAdmin(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role' => 'nullable|string|exists:roles,name', // Role validation for admin, optional
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        // Assign the role (if provided) or default to 'guest' if no role is provided
        $role = $validated['role'] ?? 'guest';  // Default to 'guest' if no role provided
        $user->assignRole($role);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    // Delete a user (Superadmin can delete any user)
    public function deleteByAdmin($userId)
    {

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
