<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

class WarehouseStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $warehouse = Warehouse::first();

        if (!$warehouse) {
             $warehouse = Warehouse::create([
                'branch_id' => 1,
                'warehouse_name' => 'Main Store',
                'creater_id' => 1,
                'location' => 'HQ',
             ]);
        }

        foreach ($products as $product) {
            // Check if stock already exists
            $exists = WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->exists();

            if (!$exists) {
                // Determine sensible defaults if columns allow null or have defaults
                // Assuming product has fields, otherwise default to simple logic
                $pcs = $product->pieces_per_box ?? 1;
                $boxes = $product->boxes_quantity ?? 0;
                $loose = $product->loose_pieces ?? 0;
                $qty = $product->total_stock_qty ?? 0;
                
                // If total stock is 0 but we want to seed something:
                if ($qty == 0) {
                     $qty = 100;
                     $boxes = 10;
                     $pcs = 10;
                     $loose = 0;
                }

                WarehouseStock::create([
                    'warehouse_id'   => $warehouse->id,
                    'product_id'     => $product->id,
                    'quantity'       => $qty,
                    'pieces_per_box' => $pcs,
                    // 'price' removed
                    'remarks'        => 'Seeded via WarehouseStockSeeder',
                ]);
            }
        }
    }
}
