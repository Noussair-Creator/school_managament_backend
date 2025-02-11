<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // Get all roles
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    // Create a new role
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        // Create a new role
        $role = Role::create(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ], 201);
    }

    // Get a single role by ID
    public function show($roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json($role);
    }

    // Update a role
    public function update(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $roleId,
        ]);

        // Update role
        $role->name = $validated['name'];
        $role->save();

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role
        ]);
    }

    // Delete a role
    public function destroy($roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Delete the role
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    // Assign a role to a user
    public function assignRole(Request $request, $userId)
    {
        // Validate that the 'role' field is provided and not empty
        $request->validate([
            'role' => 'required|string|exists:roles,name',  // Ensure role is required, a string, and exists in the 'roles' table
        ]);

        // Find the user
        $user = User::find($userId);

        // Ensure the user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Sync roles to ensure the user has only the assigned role
        $user->syncRoles([]);  // Clear all existing roles
        $user->assignRole($request->role);  // Assign the new role

        return response()->json(['message' => 'Role assigned successfully']);
    }

    // Revoke a role from a user
    public function revokeRole(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Validate the role before revoking it
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        // Revoke the role from the user
        $user->removeRole($validated['role']);

        return response()->json([
            'message' => 'Role revoked successfully',
            'user' => $user
        ]);
    }
    // Get all permissions assigned to a specific role
    public function showPermissions($roleId)
    {
        // Find the role by ID
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Get all the permissions assigned to the role
        $permissions = $role->permissions;

        // Return the permissions associated with the role
        return response()->json([
            'role' => $role->name,
            'permissions' => $permissions
        ]);
    }
}
