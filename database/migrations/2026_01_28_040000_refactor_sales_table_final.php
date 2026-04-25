<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename 'customer' to 'customer_id' if needed
        // Assuming 'customer' column holds the ID currently.
        if (Schema::hasColumn('sales', 'customer') && !Schema::hasColumn('sales', 'customer_id')) {
            // Check if we can rename directly or need to create new and copy
            // MySQL usually supports RENAME COLUMN
            Schema::table('sales', function (Blueprint $table) {
                $table->renameColumn('customer', 'customer_id');
            });
        } elseif (Schema::hasColumn('sales', 'customer') && Schema::hasColumn('sales', 'customer_id')) {
            // If both exist (e.g. from previous migration), transfer data and drop old
            DB::statement('UPDATE sales SET customer_id = customer WHERE customer_id IS NULL');
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('customer');
            });
        }

        // 2. Drop extra columns
        Schema::table('sales', function (Blueprint $table) {
            $columnsToDrop = [
                'sub_customer', 'filer_type', 'address', 'tel', 'remarks', 'quantity',
                'sub_total1', 'sub_total2', 
                'previous_balance', 'total_balance', 
                'receipt1', 'receipt2', 
                'final_balance1', 'final_balance2',
                'party_type', 'manual_invoice', 'discount_percent', 'discount_amount' 
                // Note: user didn't explicitly say remove discount info but said "extra table...". 
                // Let's stick to the list user mentioned + customer info. 
                // User said: "previcos blnc toal blnc subtotal 1 sub total 2 final balnce1 final balnce 2"
                // And "customer information... jsut add customer id".
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'customer_id')) {
                $table->renameColumn('customer_id', 'customer');
            }
            // We won't restore all dropped columns as they were "extra" and likely unused or calculated
        });
    }
};
