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
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $responsableLaboRole = Role::firstOrCreate(['name' => 'responsable_labo']);


        // Create Superadmin user
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'first_name' => 'Super Admin',
            'last_name' => 'Super Admin',
            'password' => Hash::make('superadmin@example.com'), // Change this to a more secure password
        ]);

        // Assign the Superadmin role
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('Superadmin user seeded successfully.');
        // Create Superadmin user
        $teacher = User::firstOrCreate([
            'email' => 'teacher@example.com',
        ], [
            'first_name' => 'teacher',
            'last_name' => 'one',
            'password' => Hash::make('teacher@example.com'), // Change this to a more secure password
        ]);

        // Assign the Superadmin role
        $teacher->assignRole($teacherRole);
        $this->command->info('teacher seeded successfully.');

        // Create Superadmin user
        $responsableLabo = User::firstOrCreate([
            'email' => 'responsableLabo@example.com',
        ], [
            'first_name' => 'responsableLabo',
            'last_name' => 'one',
            'password' => Hash::make('responsableLabo@example.com'), // Change this to a more secure password
        ]);

        // Assign the Superadmin role
        $responsableLabo->assignRole($responsableLaboRole);

        $this->command->info('responsableLabo seeded successfully.');
    }
};
