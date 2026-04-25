<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json, text
            $table->string('group')->default('general'); // company, sales, inventory, accounting
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('settings')->insert([
            // Company Settings
            [
                'key' => 'company_name',
                'value' => 'Three Stars Medical',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Company Name',
                'description' => 'Official company name displayed on invoices and reports',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_address',
                'value' => '',
                'type' => 'text',
                'group' => 'company',
                'label' => 'Company Address',
                'description' => 'Full company address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'company_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Phone Number',
                'description' => 'Primary contact number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'PKR',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Currency Symbol',
                'description' => 'Currency used in the system',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sales Settings
            [
                'key' => 'debt_warning_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'sales',
                'label' => 'Debt Warning Days',
                'description' => 'Number of days after which a warning notification is sent for unpaid invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'debt_critical_days',
                'value' => '10',
                'type' => 'integer',
                'group' => 'sales',
                'label' => 'Debt Critical Days',
                'description' => 'Number of days after which a critical notification is sent for unpaid invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'invoice_terms',
                'value' => 'Payment due within 30 days. Late payments may incur additional charges.',
                'type' => 'text',
                'group' => 'sales',
                'label' => 'Invoice Terms & Conditions',
                'description' => 'Default terms and conditions displayed on invoices',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Inventory Settings
            [
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Low Stock Threshold',
                'description' => 'Minimum quantity before low stock warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'expiry_alert_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Expiry Alert Days',
                'description' => 'Number of days before expiry to show warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
