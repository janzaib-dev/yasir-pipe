<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AllModulesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Comprehensive list of modules
        $modules = [
            'home',
            'profile',
            'products',
            'product.bookings',
            'discount.products',
            'categories',
            'subcategories',
            'brands',
            'units',
            'warehouse',
            'warehouse.stock',
            'stock.transfer',
            'stock.adjust',
            'stocks',
            'purchases',
            'purchase.returns',
            'vendors',
            'vendor.bilties',
            'inward.gatepass',
            'sales',
            'sales.returns',
            'customers',
            'customer.ledger',
            'bookings',

            'chart.of.accounts',
            'expense.voucher',
            'receipts.voucher',
            'journal.voucher',
            'payment.voucher',
            'income.voucher',
            'item.stock.report',
            'purchase.report',
            'sale.report',
            'reporting',
            'recovery.report',
            'payable.report',
            'parties.balance.report',
            'aging.report',
            'balance.sheet.report',
            'profit.loss.report',
            'inventory.onhand',
            'vendor.ledger',
            'users',
            'roles',
            'permissions',
            'branches',
            'zones',
            'sales.officers',
            'narrations',
            'executive.report',
            'package.types',
            // HR Modules
            'hr.departments',
            'hr.employees',
            'hr.attendance',
            'hr.payroll',
            'hr.leaves',
            'hr.designations',
            'hr.shifts',
            'hr.holidays',
            'hr.salary.structure',
            'hr.loans',
            'hr.biometric.devices'
        ];

        // Standard actions
        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                // Format: module.action (lowercase)
                $permissionName = strtolower("{$module}.{$action}");
                Permission::firstOrCreate(['name' => $permissionName]);
            }
        }

        // Grant all new permissions to Super Admin just in case (though Gate::before handles it)
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }
}
