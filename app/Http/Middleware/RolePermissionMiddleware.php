<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class RolePermissionMiddleware
{
    public function handle(Request $request, Closure $next, $roles = null, $permissions = null)
    {
        $user = Auth::user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if any of the specified roles match
        if ($roles) {
            $roles = explode(',', $roles); // Convert comma-separated roles string to array

            if (!$user->hasAnyRole($roles)) {
                return response()->json(['message' => 'Access denied. Requires one of the roles: ' . implode(', ', $roles)], 403);
            }
        }

        // Check if any of the specified permissions match
        if ($permissions) {
            $permissions = explode(',', $permissions); // Convert comma-separated permissions string to array

            $hasPermission = false;
            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                return response()->json(['message' => 'Access denied. Requires one of the permissions: ' . implode(', ', $permissions)], 403);
            }
        }

        // Automatically check for specific permissions related to role or resource (like 'create role', 'update role', etc.)
        $currentRoute = Route::currentRouteName();

        // Define a mapping for route names and required permissions
        $routePermissions = [
            'roles.store' => 'create role',
            'roles.show' => 'show role',
            'roles.update' => 'update role',
            'roles.destroy' => 'delete role',
            'roles.assign' => 'assign role',
            'roles.remove' => 'remove role',
            'permissions.store' => 'create permission',
            'permissions.show' => 'show permission',
            'permissions.update' => 'update permission',
            'permissions.destroy' => 'delete permission',
            'permissions.give' => 'give permissions',
            'permissions.remove' => 'remove permissions',
            'users.store' => 'create user',
            'users.show' => 'show user',
            'users.update' => 'update user',
            'users.destroy' => 'delete user',
            'classrooms.store' => 'create classroom',
            'classrooms.show' => 'show classroom',
            'classrooms.update' => 'update classroom',
            'classrooms.destroy' => 'delete classroom',
            'reservations.store' => 'create reservation',
            'reservations.show' => 'show reservation',
            'reservations.update' => 'update reservation',
            'reservations.destroy' => 'delete reservation',
            'reservations.cancel' => 'delete reservation',
        ];

        // If the route is in the permissions mapping, check if the user has the required permission
        if (array_key_exists($currentRoute, $routePermissions)) {
            $requiredPermission = $routePermissions[$currentRoute];
            if (!$user->can($requiredPermission)) {
                return response()->json(['message' => 'Access denied. You do not have the ' . $requiredPermission . ' permission.'], 403);
            }
        }

        return $next($request);
    }
}
