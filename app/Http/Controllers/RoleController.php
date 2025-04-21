<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * RoleController constructor.
     * Apply middleware for role-based access control.
     **/
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role.permission:list roles')->only('index');
        $this->middleware('role.permission:create role')->only('store');
        $this->middleware('role.permission:show role')->only('show');
        $this->middleware('role.permission:update role')->only('update');
        $this->middleware('role.permission:delete role')->only('destroy');

        $this->middleware('role.permission:assign permissions')->only('assignPermissionToRole');
        $this->middleware('role.permission:remove permissions')->only('revokePermissionFromRole');

        $this->middleware('role.permission:show role permissions')->only('showPermissions');

        // roles to users
        $this->middleware('role.permission:assign role')->only('assignRole');
        $this->middleware('role.permission:remove role')->only('revokeRole');
    }


    // list all roles
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    // Create a new role
    public function store(Request $request)
    {
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
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::find($userId);

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
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Get all the permissions assigned to the role
        $permissions = $role->permissions;

        return response()->json([
            'role' => $role->name,
            'permissions' => $permissions
        ]);
    }

    // Assign permission to a role
    public function assignPermissionToRole(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = Permission::findByName($validated['permission']);
        $role->givePermissionTo($permission);

        return response()->json([
            'message' => 'Permission assigned to role successfully',
            'role' => $role,
            'permission' => $permission
        ]);
    }

    // Revoke permission from a role
    public function revokePermissionFromRole(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = Permission::findByName($validated['permission']);
        $role->revokePermissionTo($permission);

        return response()->json([
            'message' => 'Permission revoked from role successfully',
            'role' => $role,
            'permission' => $permission
        ]);
    }
}
