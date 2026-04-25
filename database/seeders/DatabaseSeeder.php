<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run other seeders
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            WarehouseSeeder::class,
            // PermissionRoleUserSeeder::class, // REMOVED: Using new AllModulesPermissionsSeeder
            ModulesTableSeeder::class, 
            AllModulesPermissionsSeeder::class, // NEW: All module permissions (module.view, module.create, etc.)
            SuperadminSeeder::class, 
        ]);
    }
}
