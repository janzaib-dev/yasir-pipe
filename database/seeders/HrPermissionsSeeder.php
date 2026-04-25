<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // HR Permissions List
        $permissions = [
            // Departments
            'hr.departments.view',
            'hr.departments.create',
            'hr.departments.edit',
            'hr.departments.delete',

            // Designations
            'hr.designations.view',
            'hr.designations.create',
            'hr.designations.edit',
            'hr.designations.delete',

            // Employees
            'hr.employees.view',
            'hr.employees.create',
            'hr.employees.edit',
            'hr.employees.delete',

            // Attendance
            'hr.attendance.view',
            'hr.attendance.create', // Manual entry by Admin
            'hr.attendance.mark',   // Self service - usually not checked by permission if for all users, but good to have
            'hr.attendance.delete',
            'hr.attendance.report',

            // Shifts
            'hr.shifts.view',
            'hr.shifts.create',
            'hr.shifts.edit',
            'hr.shifts.delete',

            // Holidays
            'hr.holidays.view',
            'hr.holidays.create',
            'hr.holidays.edit',
            'hr.holidays.delete',

            // Leaves
            'hr.leaves.view',
            'hr.leaves.create',
            'hr.leaves.edit',
            'hr.leaves.delete',
            'hr.leaves.approve',

            // Payroll
            'hr.payroll.view',
            'hr.payroll.generate',
            'hr.payroll.edit',
            'hr.payroll.delete',

            // Salary Structure
            'hr.salary.structure.view',
            'hr.salary.structure.create',
            'hr.salary.structure.edit',
            'hr.salary.structure.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Super Admin Role and assign all permissions
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
    }
}
