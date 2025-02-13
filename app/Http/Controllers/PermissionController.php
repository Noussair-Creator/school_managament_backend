<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.permission:,create permission')->only('store');
        $this->middleware('role.permission:,show permission')->only('show');
        $this->middleware('role.permission:,update permission')->only('update');
        $this->middleware('role.permission:,delete permission')->only('destroy');
        $this->middleware('role.permission:,give permissions')->only('assignPermissionToRole');
        $this->middleware('role.permission:,remove permissions')->only('revokePermissionFromRole');
    }

    // Get all permissions
    public function index()
    {
        $permissions = Permission::all();
        return response()->json($permissions);
    }

    // Create a new permission
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        // Create a new permission
        $permission = Permission::create(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    // Get a single permission by ID
    public function show($permissionId)
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        return response()->json($permission);
    }

    // Update a permission
    public function update(Request $request, $permissionId)
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permissionId,
        ]);

        // Update the permission
        $permission->name = $validated['name'];
        $permission->save();

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission
        ]);
    }

    // Delete a permission
    public function destroy($permissionId)
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        // Delete the permission
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully']);
    }

    // Assign a permission to a role
    public function assignPermissionToRole(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        // Assign the permission to the role
        $role->givePermissionTo($validated['permission']);

        return response()->json([
            'message' => 'Permission assigned to role successfully',
            'role' => $role
        ]);
    }

    // Revoke a permission from a role
    public function revokePermissionFromRole(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        // Revoke the permission from the role
        $role->revokePermissionTo($validated['permission']);

        return response()->json([
            'message' => 'Permission revoked from role successfully',
            'role' => $role
        ]);
    }

    // Uncomment below methods to assign/revoke permissions for users (if needed)

    // // Assign a permission to a user
    // public function assignPermissionToUser(Request $request, $userId)
    // {
    //     $user = User::find($userId);

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $validated = $request->validate([
    //         'permission' => 'required|string|exists:permissions,name',
    //     ]);

    //     // Assign permission to the user
    //     $user->givePermissionTo($validated['permission']);

    //     return response()->json([
    //         'message' => 'Permission assigned to user successfully',
    //         'user' => $user
    //     ]);
    // }

    // // Revoke a permission from a user
    // public function revokePermissionFromUser(Request $request, $userId)
    // {
    //     $user = User::find($userId);

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $validated = $request->validate([
    //         'permission' => 'required|string|exists:permissions,name',
    //     ]);

    //     // Revoke the permission from the user
    //     $user->revokePermissionTo($validated['permission']);

    //     return response()->json([
    //         'message' => 'Permission revoked from user successfully',
    //         'user' => $user
    //     ]);
    // }
}
