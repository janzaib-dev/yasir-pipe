<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LoanPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'hr.loans.view',
            'hr.loans.create',
            'hr.loans.approve', 
            'hr.loans.delete',
            'hr.loans.schedule',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign to Super Admin
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
    }
}
