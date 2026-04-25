<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // create index (if not exists → handled by try/catch)
        try {
            DB::statement('CREATE INDEX idx_sm_product_created ON stock_movements (product_id, created_at)');
        } catch (\Exception $e) {
            // ignore if already exists
        }

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

    public function down(): void
    {
        // drop view
        DB::statement('DROP VIEW IF EXISTS v_stock_onhand');

        // drop index
        try {
            DB::statement('ALTER TABLE stock_movements DROP INDEX idx_sm_product_created');
        } catch (\Exception $e) {
            // ignore if index not exist
        }
    }
};
