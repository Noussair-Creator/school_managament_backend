<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
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
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create the Superadmin role and assign all permissions
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'sanctum']);
        $superadmin->givePermissionTo(Permission::all());

        // Create the Guest role and assign only limited permissions
        $guest = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'sanctum']);
        $guestPermissions = [
            'show classroom',   // Guests can view classrooms
            'show reservation', // Guests can view reservations
        ];
        $guest->givePermissionTo($guestPermissions);

        // Output success message
        $this->command->info('Roles and permissions seeded successfully.');
    }
}
