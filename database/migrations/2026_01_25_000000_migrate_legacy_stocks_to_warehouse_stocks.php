<?php

use App\Models\WarehouseStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrate legacy 'stocks' to 'warehouse_stocks'
        $legacyStocks = DB::table('stocks')->get();

        foreach ($legacyStocks as $stock) {
            // Find or Create WarehouseStock record
            // We sum up because there might be multiple legacy entries or existing new entries
            $warehouseStock = WarehouseStock::firstOrNew([
                'warehouse_id' => $stock->warehouse_id,
                'product_id' => $stock->product_id,
            ]);

            // Add legacy quantity to existing (or 0)
            $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $stock->qty;

            // Set a default price if missing (optional, from legacy if available?)
            // Legacy 'stocks' doesn't seem to have price, so we leave as is or 0
            if (is_null($warehouseStock->price)) {
                $warehouseStock->price = 0;
            }

            $warehouseStock->save();
        }

        // 2. Ensure all Products have at least a 0-record in WarehouseStock (Optional, but good for safety)
        // Adjust logic: Only needed if you want "empty" rows. For now, we only migrate actual data.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing a data sync is dangerous/undefined.
        // We do strictly nothing to avoid data loss.
    }
};
