<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample category
        $category = Category::firstOrCreate(['name' => 'Electronics']);
        $subCategory = Subcategory::firstOrCreate([
            'category_id' => $category->id,
            'name' => 'Air-Condition(AC)',
        ]);

        $unit = Unit::firstOrCreate(['name' => 'Piece']);
        $brand = Brand::firstOrCreate(['name' => 'Samsung']); // brand add

        // 🔁 Auto-generate item code
        $lastId = Product::max('id') ?? 0;
        $nextId = $lastId + 1;
        $itemCode = 'ITEM-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // Sample product
        // Sample product
        $product = Product::create([
            'creater_id' => 1,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'brand_id' => $brand->id,
            'is_part' => 0,
            'is_assembled' => 0,
            'item_code' => $itemCode,
            'unit_id' => $unit->id,
            'item_name' => 'Formal Shirt',
            'color' => json_encode(['Black']),
            // 'price' removed, using specific fields
            'sale_price_per_box' => 5000,
            'sale_price_per_piece' => 5000 / 12, // Calculated
            'purchase_price_per_piece' => 375,
            'purchase_price_per_box' => 375 * 12, // Calculated

            // New fields
            'size_mode' => 'by_cartons',
            'pieces_per_box' => 12,

            // Fix defaults for non-nullable fields
            'total_m2' => 0,
            'height' => 0,
            'width' => 0,
            'pieces_per_m2' => 0,
            'price_per_m2' => 0,
            'purchase_price_per_m2' => 0,

            'barcode_path' => rand(100000000000, 999999999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Also populate Warehouse Stock for this product
        $warehouse = \App\Models\Warehouse::first(); // Assuming WarehouseSeeder ran
        if ($warehouse) {
            \App\Models\WarehouseStock::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => 125, // Boxes
                'total_pieces' => 125 * 12,
                'remarks' => 'Seeded stock',
            ]);
        }
    }
}
