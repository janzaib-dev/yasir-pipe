<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_stock_onhand AS
            SELECT product_id, product_package_id,
                   ROUND(COALESCE(SUM(
                     CASE
                       WHEN type IN ('in','assembly_in')  THEN  ABS(qty)
                       WHEN type IN ('out','assembly_out') THEN -ABS(qty)
                       WHEN type='adjustment'              THEN  qty
                       ELSE 0
                     END
                   ),0),3) AS onhand_qty
            FROM stock_movements
            GROUP BY product_id, product_package_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original view
        DB::statement("
            CREATE OR REPLACE VIEW v_stock_onhand AS
            SELECT product_id,
                   ROUND(COALESCE(SUM(
                     CASE
                       WHEN type IN ('in','assembly_in')  THEN  ABS(qty)
                       WHEN type IN ('out','assembly_out') THEN -ABS(qty)
                       WHEN type='adjustment'              THEN  qty
                       ELSE 0
                     END
                   ),0),3) AS onhand_qty
            FROM stock_movements
            GROUP BY product_id
        ");
    }
};
