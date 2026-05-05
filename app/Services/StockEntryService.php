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
    public function addStock(int $warehouseId, int $productId, int $totalPieces, int $totalBox = 0, ?string $remarks = null, ?int $productPackageId = null)
    {
        return DB::transaction(function () use ($warehouseId, $productId, $totalPieces, $totalBox, $remarks, $productPackageId) {
            // 1. Validation
            $warehouse = Warehouse::findOrFail($warehouseId);
            $product = Product::findOrFail($productId);

            if ($totalPieces < 0) {
                throw new \InvalidArgumentException('Quantity must be non-negative.');
            }

            // 2. Get or Create Warehouse Stock Record
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_package_id' => $productPackageId,
            ]);

            // Initialize if new
            if (! $stock->exists) {
                $stock->quantity = 0; // Box Quantity
                $stock->total_pieces = 0;
            }

            // 3. Update Stock
            $stock->total_pieces += $totalPieces;
            $stock->quantity += $totalBox;
            $stock->remarks = $remarks;

            $stock->save();

            // 4. Log Movement
            StockMovement::create([
                'product_id' => $productId,
                'product_package_id' => $productPackageId,
                'warehouse_id' => $warehouseId,
                'type' => 'in',
                'qty' => $totalPieces,
                'ref_type' => 'MANUAL_ENTRY',
                'note' => $remarks ?? 'Manual stock addition via service',
            ]);
      
            return $stock;
        });
    }
}
