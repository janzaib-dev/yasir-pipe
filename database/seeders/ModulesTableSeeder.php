<?php

namespace Database\Seeders;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a minimal `modules` table if it doesn't exist so the seeder can run safely.
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // List of module/page names used in the project (lowercase, dot-separated)
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
            'inventory.onhand',
            'users',
            'roles',
            'permissions',
            'branches',
            'zones',
            'sales.officers',
            'narrations',
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
            'hr.biometric.devices',
        ];

        foreach ($modules as $name) {
            DB::table('modules')->updateOrInsert(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
