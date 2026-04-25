<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockEntryService
{
    /**
     * Add stock to a warehouse.
     *
     * @return WarehouseStock
     *
     * @throws Exception
     */
    public function addStock(int $warehouseId, int $productId, int $totalPieces, int $totalBox = 0, ?string $remarks = null)
    {
        return DB::transaction(function () use ($warehouseId, $productId, $totalPieces, $totalBox, $remarks) {
            // 1. Validation
            $warehouse = Warehouse::findOrFail($warehouseId);
            $product = Product::findOrFail($productId);

            if ($totalPieces < 0) { // Changed <= 0 to < 0 to allow zero updates if needed, though usually add is > 0
                throw new \InvalidArgumentException('Quantity must be non-negative.');
            }

            // 2. Get or Create Warehouse Stock Record
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ]);

            // Initialize if new
            if (! $stock->exists) {
                $stock->quantity = 0; // Legacy field, treated as Box Quantity
                $stock->total_pieces = 0;
            }

            // 3. Update Stock
            $previousPieces = $stock->total_pieces ?? 0;
            $newPieces = $previousPieces + $totalPieces;

            $previousBox = $stock->quantity ?? 0;
            $newBox = $previousBox + $totalBox;

            $stock->total_pieces = $newPieces;
            $stock->quantity = $newBox;

            $stock->save();

      
            return $stock;
        });
    }
}
