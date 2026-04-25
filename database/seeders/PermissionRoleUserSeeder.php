<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionRoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting PermissionRoleUserSeeder');

        // Ensure required tables exist
        $requiredTables = [
            'users',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->command->error("Required table '{$table}' does not exist. Run migrations and publish Spatie migrations before seeding.");

                return;
            }
        }

        $modules = [
            'products', 'categories', 'subcategories', 'brands', 'units',
            'inward_gatepass', 'purchases', 'vendors', 'warehouses', 'stock_transfers',
            'sales', 'customers', 'sales_officers', 'zones', 'vouchers', 'reports', 'assembly', 'inventory',
        ];

        $actions = ['read', 'add', 'edit', 'delete'];

        DB::beginTransaction();
        try {
            // Create permissions
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    $name = "$module.$action";
                    Permission::firstOrCreate(['name' => $name]);
                }
            }

            // Create roles
            $admin = Role::firstOrCreate(['name' => 'admin']);
            $manager = Role::firstOrCreate(['name' => 'manager']);
            $staff = Role::firstOrCreate(['name' => 'staff']);
            // superAdmin role - full access
            $superAdmin = Role::firstOrCreate(['name' => 'superAdmin']);

            // Assign permissions
            $allPermissions = Permission::all();
            // admin gets all (for backward compatibility)
            $admin->syncPermissions($allPermissions);
            // superAdmin explicitly gets all permissions too
            $superAdmin->syncPermissions($allPermissions);

            $managerPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%.read')
                    ->orWhere('name', 'like', '%.add')
                    ->orWhere('name', 'like', '%.edit');
            })->get();
            $manager->syncPermissions($managerPermissions);

            $staffPermissions = Permission::where('name', 'like', '%.read')->get();
            $staff->syncPermissions($staffPermissions);

            // Create sample users
            $sampleUsers = [
                // admin with superAdmin role and specified secure password
                ['name' => 'Admin User', 'email' => 'admin@admin.com', 'role' => 'superAdmin', 'password' => '11223344'],
                ['name' => 'Manager User', 'email' => 'manager@example.com', 'role' => 'manager', 'password' => 'password'],
                ['name' => 'Staff User', 'email' => 'staff@example.com', 'role' => 'staff', 'password' => 'password'],
            ];

            foreach ($sampleUsers as $u) {
                $user = User::firstOrCreate(
                    ['email' => $u['email']],
                    ['name' => $u['name'], 'password' => Hash::make($u['password'] ?? 'password')]
                );

                if (! $user->hasRole($u['role'])) {
                    $user->assignRole($u['role']);
                }
            }

            DB::commit();
            $this->command->info('PermissionRoleUserSeeder completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRoleUserSeeder error: '.$e->getMessage(), ['exception' => $e]);
            $this->command->error('Seeder failed: '.$e->getMessage()."\nSee storage/logs/laravel.log for full trace.");
        }
    }
}
