<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolePermissionMiddleware
{
    public function handle(Request $request, Closure $next, $roles = null, $permissions = null, $strict = false)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Convert comma-separated roles & permissions into arrays
        $rolesArray = $roles ? explode(',', $roles) : [];
        $permissionsArray = $permissions ? explode(',', $permissions) : [];

        // Strict Mode: User must have all roles and permissions
        if ($strict) {
            // Check if the user has all the required roles
            if (!empty($rolesArray) && !$user->hasAllRoles($rolesArray)) {
                return response()->json([
                    'message' => 'Access denied. Missing roles: ' . implode(', ', $rolesArray)
                ], 403);
            }

            // Check if the user has all the required permissions
            if (!empty($permissionsArray)) {
                $userPermissions = $this->getUserPermissions($user);

                $missingPermissions = array_diff($permissionsArray, $userPermissions->toArray());

                if (!empty($missingPermissions)) {
                    return response()->json([
                        'message' => 'Access denied. Missing permissions: ' . implode(', ', $missingPermissions)
                    ], 403);
                }
            }
        } else {
            // Non-strict Mode: User needs any of the provided roles and permissions
            if (!empty($rolesArray) && !$user->hasAnyRole($rolesArray)) {
                return response()->json([
                    'message' => 'Access denied. Missing roles: ' . implode(', ', $rolesArray)
                ], 403);
            }

            if (!empty($permissionsArray)) {
                $userPermissions = $this->getUserPermissions($user);

                // Check if the user has any of the required permissions
                if (!$userPermissions->intersect($permissionsArray)->count()) {
                    return response()->json([
                        'message' => 'Access denied. Missing permissions: ' . implode(', ', $permissionsArray)
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    /**
     * Get a list of the user's permissions, considering roles and any custom permissions.
     *
     * @param $user
     * @return \Illuminate\Support\Collection
     */
    private function getUserPermissions($user)
    {
        // Cache user permissions for efficiency (e.g., cache for 60 minutes)
        return cache()->remember("user_permissions_{$user->id}", 60, function() use ($user) {
            // Get all permissions across all roles assigned to the user
            return $user->roles()->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique();
        });
    }
}


// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Route;

// class RolePermissionMiddleware
// {
//     public function handle(Request $request, Closure $next, $roles = null, $permissions = null)
//     {
//         $user = Auth::user();

//         // Check if the user is authenticated
//         if (!$user) {
//             return response()->json(['message' => 'Unauthorized'], 401);
//         }

//         // Check for role-based access
//         if ($roles) {
//             $rolesArray = explode(',', $roles); // Convert to array
//             if (!$user->hasAnyRole($rolesArray)) {
//                 return response()->json([
//                     'message' => 'Access denied. Requires one of the roles: ' . implode(', ', $rolesArray)
//                 ], 403);
//             }
//         }

//         // Check for permission-based access
//         if ($permissions) {
//             $permissionsArray = explode(',', $permissions); // Convert to array
//             if (!$user->hasAnyPermission($permissionsArray)) { // Use hasAnyPermission()
//                 return response()->json([
//                     'message' => 'Access denied. Requires one of the permissions: ' . implode(', ', $permissionsArray)
//                 ], 403);
//             }
//         }

//         // Automatically check required permissions for certain routes
//         $currentRoute = Route::currentRouteName();

//         // Ensure the route is named before checking permissions
//         if ($currentRoute) {
//             $routePermissions = [
//                 'roles.store' => 'create role',
//                 'roles.show' => 'show role',
//                 'roles.update' => 'update role',
//                 'roles.destroy' => 'delete role',
//                 'roles.assign' => 'assign role',
//                 'roles.remove' => 'remove role',
//                 'permissions.store' => 'create permission',
//                 'permissions.show' => 'show permission',
//                 'permissions.update' => 'update permission',
//                 'permissions.destroy' => 'delete permission',
//                 'permissions.give' => 'give permissions',
//                 'permissions.remove' => 'remove permissions',
//                 'users.store' => 'create user',
//                 'users.show' => 'show user',
//                 'users.update' => 'update user',
//                 'users.destroy' => 'delete user',
//                 'classrooms.store' => 'create classroom',
//                 'classrooms.show' => 'show classroom',
//                 'classrooms.update' => 'update classroom',
//                 'classrooms.destroy' => 'delete classroom',
//                 'reservations.store' => 'create reservation',
//                 'reservations.show' => 'show reservation',
//                 'reservations.update' => 'update reservation',
//                 'reservations.destroy' => 'delete reservation',
//                 'reservations.cancel' => 'delete reservation',
//             ];

//             if (isset($routePermissions[$currentRoute])) {
//                 $requiredPermission = $routePermissions[$currentRoute];
//                 if (!$user->can($requiredPermission)) {
//                     return response()->json([
//                         'message' => 'Access denied. You do not have the "' . $requiredPermission . '" permission.'
//                     ], 403);
//                 }
//             }
//         }

//         return $next($request);
//     }
// }
