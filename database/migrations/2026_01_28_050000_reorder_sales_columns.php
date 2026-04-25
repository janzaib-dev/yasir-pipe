<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Reordering via raw SQL MODIFY COLUMN for MySQL

            // 1. Header
            DB::statement('ALTER TABLE sales MODIFY COLUMN invoice_no VARCHAR(255) AFTER id');
            DB::statement('ALTER TABLE sales MODIFY COLUMN customer_id BIGINT UNSIGNED NULL AFTER invoice_no');
            DB::statement('ALTER TABLE sales MODIFY COLUMN reference VARCHAR(255) NULL AFTER customer_id');

            // 2. Status
            if (Schema::hasColumn('sales', 'sale_status')) {
                DB::statement('ALTER TABLE sales MODIFY COLUMN sale_status VARCHAR(255) NULL AFTER reference');
            }

            // 3. Totals / Financials
            DB::statement('ALTER TABLE sales MODIFY COLUMN total_items DECIMAL(12,2) NULL AFTER sale_status');

            // Assuming total_bill_amount holds the subtotal (based on controller logic)
            DB::statement('ALTER TABLE sales MODIFY COLUMN total_bill_amount DECIMAL(12,2) NULL AFTER total_items');

            DB::statement('ALTER TABLE sales MODIFY COLUMN total_extradiscount DECIMAL(12,2) NULL AFTER total_bill_amount');
            DB::statement('ALTER TABLE sales MODIFY COLUMN total_net DECIMAL(12,2) NULL AFTER total_extradiscount');

            // 4. Payments
            DB::statement('ALTER TABLE sales MODIFY COLUMN cash DECIMAL(12,2) NULL AFTER total_net');
            DB::statement('ALTER TABLE sales MODIFY COLUMN card DECIMAL(12,2) NULL AFTER cash');
            DB::statement('ALTER TABLE sales MODIFY COLUMN `change` DECIMAL(12,2) NULL AFTER card');

            // 5. Misc
            DB::statement('ALTER TABLE sales MODIFY COLUMN total_amount_Words TEXT NULL AFTER `change`');

            // 6. Timestamps
            DB::statement('ALTER TABLE sales MODIFY COLUMN created_at TIMESTAMP NULL AFTER total_amount_Words');
            DB::statement('ALTER TABLE sales MODIFY COLUMN updated_at TIMESTAMP NULL AFTER created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No specific reverse for reordering needed usually
    }
};
