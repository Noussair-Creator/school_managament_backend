<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolePermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permissions = null, $strict = false)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Convert comma-separated permissions into an array
        $permissionsArray = $permissions ? explode(',', $permissions) : [];

        if (!empty($permissionsArray)) {
            $userPermissions = $this->getUserPermissions($user);

            if ($strict) {
                // Strict Mode: User must have all specified permissions
                $missingPermissions = array_diff($permissionsArray, $userPermissions->toArray());

                if (!empty($missingPermissions)) {
                    return response()->json([
                        'message' => 'Access denied. Missing permissions: ' . implode(', ', $missingPermissions)
                    ], 403);
                }
            } else {
                // Non-Strict Mode: User must have at least one of the specified permissions
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
            // Get all permissions assigned to the user through roles
            return $user->roles()->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique();
        });
    }
}
