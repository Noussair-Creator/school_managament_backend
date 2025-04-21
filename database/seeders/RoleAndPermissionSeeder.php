<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; // Make sure User model is imported
use Illuminate\Support\Facades\Log; // Optional: for logging errors

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Define all permissions ---
        $permissions = [

            // --- Core Role/Permission Management (Superadmin) ---
            'list roles',
            'create role',
            'show role',
            'update role',
            'delete role',
            'assign role', // Assign role to user
            'remove role', // Remove role from user
            'show role permissions',
            'assign permissions', // Assign permission(s) to role
            'remove permissions', // Remove permission(s) from role
            'list permissions',
            'create permission', // Allow admin to create new permissions if needed via UI?
            'show permission',
            'update permission',
            'delete permission',

            // --- User Management (Superadmin) ---
            'list users',
            'create user', // Generic user creation
            'create responsable', // Specific user creation (if different logic)
            'show user', // View user details
            'update user', // Admin update any user
            'delete user', // Admin delete any user

            // --- Profile Management (Self) ---
            'show profile',
            'update profile',
            'delete profile',

            // --- Location Management (Admin) ---
            'list locations',    // Now needed by more roles to view
            'create location',
            'show location',     // Now needed by more roles to view
            'update location',
            'delete location',

            // --- Material Management (Admin - NEW) ---
            'list materials',    // Allow others to view list? Yes, for selection.
            'manage materials',  // Combined permission for CRUD (Admin only)
            // OR more granular:
            // 'create material',
            // 'show material',
            // 'update material',
            // 'delete material',

            // --- Reservations Management ---
            'make reservation',    // Lab Manager create PENDING
            'list reservations',   // Admin (all+filter), Lab Manager (own), Teacher (assigned)
            'update reservation',  // Admin (all pending/rejected?), Lab Manager (own pending)
            'cancel reservation',  // Admin (pending/approved?), Lab Manager (own pending)
            'approve reservation', // Admin approve/reject PENDING

            // --- Posts Management ---
            'list posts',
            'create post',
            'show post',
            'update post',
            'delete post',

            // --- Comments Management ---
            'list comments',
            'create comment',
            // 'delete comment', // Add permission if needed

            // --- Documents Management ---
            'list documents',
            'create document',
            'show document',
            'delete document',

        ];

        // Remove duplicates just in case and ensure unique values
        $permissions = array_values(array_unique($permissions));

        // Create permissions if they don't exist, using 'sanctum' guard
        $createdCount = 0;
        foreach ($permissions as $permission) {
            try {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
                $createdCount++;
            } catch (\Exception $e) {
                $this->command->error("Error creating permission '{$permission}': " . $e->getMessage());
                Log::error("Seeder Error - Permission Creation '{$permission}': " . $e->getMessage());
            }
        }
        $this->command->info($createdCount . ' permissions ensured for guard [sanctum].');


        // --- Define Roles and Assign Permissions ---

        // Superadmin: Has all permissions
        try {
            $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'sanctum']);
            // Assign only permissions created for the 'sanctum' guard
            $superadmin->syncPermissions(Permission::where('guard_name', 'sanctum')->pluck('name'));
            $this->command->info('Superadmin role ensured and synced with all [sanctum] permissions.');
        } catch (\Exception $e) {
            $this->command->error("Error ensuring Superadmin role: " . $e->getMessage());
            Log::error("Seeder Error - Superadmin Role: " . $e->getMessage());
        }


        // Eleve (Student/Guest): Limited permissions
        try {
            $eleve = Role::firstOrCreate(['name' => 'eleve', 'guard_name' => 'sanctum']);
            $elevePermissions = [
                'show profile',
                'update profile',
                'delete profile',
                // Public viewing permissions (posts, documents, locations) are usually not controlled here
                // Add specific view permissions only if you restrict public routes later
                // 'list posts',
                // 'show post',
                // 'list documents',
                // 'show document',
                // 'list locations',
                // 'show location',
                // 'create comment', // Can students comment?
            ];
            $eleve->syncPermissions(Permission::whereIn('name', $elevePermissions)->where('guard_name', 'sanctum')->pluck('name'));
            $this->command->info('Eleve role ensured and synced with permissions.');
        } catch (\Exception $e) {
            $this->command->error("Error ensuring Eleve role: " . $e->getMessage());
            Log::error("Seeder Error - Eleve Role: " . $e->getMessage());
        }


        // Responsable Labo (Lab Manager): Manages Reservations + Profile
        try {
            $responsable_labo = Role::firstOrCreate(['name' => 'responsable_labo', 'guard_name' => 'sanctum']);
            $responsable_labo_permissions = [
                'show profile',
                'update profile',
                'delete profile',
                // Reservation Permissions
                'make reservation',    // Create pending
                'list reservations',   // Sees own
                'update reservation',  // Update own pending
                'cancel reservation',  // Cancel own pending
                // Location Viewing
                'list locations',
                'show location',
                // Material Viewing (to select materials for reservation)
                'list materials', // <-- ADDED
                // 'show material', // Maybe needed if list isn't detailed enough
            ];
            $responsable_labo->syncPermissions(Permission::whereIn('name', $responsable_labo_permissions)->where('guard_name', 'sanctum')->pluck('name'));
            $this->command->info('Responsable Labo role ensured and synced with permissions.');
        } catch (\Exception $e) {
            $this->command->error("Error ensuring Responsable Labo role: " . $e->getMessage());
            Log::error("Seeder Error - Responsable Labo Role: " . $e->getMessage());
        }


        // Teacher: Views assigned reservations + Profile
        try {
            $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'sanctum']);
            $teacher_permissions = [
                'show profile',
                'update profile',
                'delete profile',
                // Reservation Permissions
                'list reservations',   // Sees assigned approved/pending
                // Location Viewing
                'list locations',
                'show location',
                // Material Viewing? Maybe not necessary if they just see the final reservation details?
                // 'list materials',
            ];
            $teacher->syncPermissions(Permission::whereIn('name', $teacher_permissions)->where('guard_name', 'sanctum')->pluck('name'));
            $this->command->info('Teacher role ensured and synced with permissions.');
        } catch (\Exception $e) {
            $this->command->error("Error ensuring Teacher role: " . $e->getMessage());
            Log::error("Seeder Error - Teacher Role: " . $e->getMessage());
        }


        // --- Assign Superadmin Role to Default User ---
        // IMPORTANT: Ensure the user actually exists before assigning.
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com'); // Use env variable or fallback
        try {
            $adminUser = User::where('email', $adminEmail)->first();
            if ($adminUser) {
                $adminUser->assignRole('superadmin');
                $this->command->info("Superadmin role assigned to User: {$adminEmail} (ID: {$adminUser->id}).");
            } else {
                $this->command->warn("Default admin user ('{$adminEmail}') not found. Superadmin role not assigned.");
                Log::warning("Seeder Warning - Default admin user ('{$adminEmail}') not found.");
            }
        } catch (\Exception $e) {
            $this->command->error("Error assigning superadmin role to '{$adminEmail}': " . $e->getMessage());
            Log::error("Seeder Error - Assigning Superadmin Role: " . $e->getMessage());
        }


        $this->command->info('Roles and permissions seeding completed successfully.');
    }
}
