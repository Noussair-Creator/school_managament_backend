<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            'all roles',
            'create role',
            'show role',
            'update role',
            'delete role',
            'assign role',
            'remove role',
            'create permission',
            'show permission',
            'update permission',
            'delete permission',
            'give permissions',
            'remove permissions',
            'create user',
            'show user',
            'update user',
            'delete user',
            'create classroom',
            'show classroom',
            'update classroom',
            'delete classroom',
            'create reservation',
            'show reservation',
            'update reservation',
            'delete reservation',
            'create comment',
            'show comment',
            'update comment',
            'delete comment',
            'create post',
            'show post',
            'update post',
            'delete post',
            'create document',
            'show document',
            'update document',
            'delete document',
            'all documents',

        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create the Superadmin role and assign all permissions
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'sanctum']);
        $superadmin->syncPermissions(Permission::all());

        // Create the Guest role and assign limited permissions
        $guest = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'sanctum']);
        $guestPermissions = ['show classroom', 'show reservation'];
        $guest->syncPermissions($guestPermissions);

        // Assign the Superadmin role to a specific user (Modify the ID if needed)
        $user = User::find(1); // Change to the actual superadmin user ID
        if ($user) {
            $user->assignRole('superadmin');
        }

        // Output success message
        $this->command->info('Roles, permissions, and default Superadmin user seeded successfully.');
    }
}
