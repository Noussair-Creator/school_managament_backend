<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // Ensure the Superadmin role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);

        // Create Superadmin user
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('superadmin@example.com'), // Change this to a more secure password
        ]);

        // Assign the Superadmin role
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('Superadmin user seeded successfully.');
    }
}
