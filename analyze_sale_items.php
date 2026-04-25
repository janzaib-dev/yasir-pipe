<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\SaleItem;

echo "\n=== Analyzing Latest Sale Items ===\n\n";

$latestSale = Sale::orderBy('id', 'desc')->first();

if (!$latestSale) {
    echo "No sales found.\n";
    exit;
}

echo "Sale: {$latestSale->invoice_no}\n";
echo "Total: " . number_format($latestSale->total_net, 2) . "\n\n";

$items = SaleItem::where('sale_id', $latestSale->id)->get();

if ($items->isEmpty()) {
    echo "No items found.\n";
    exit;
}

echo "Sale Items:\n";
echo str_repeat("-", 100) . "\n";
echo str_pad("Product", 25) . 
     str_pad("Qty(Box)", 12) . 
     str_pad("Pieces", 12) . 
     str_pad("Loose", 10) . 
     str_pad("Price", 12) . 
     str_pad("Total", 15) . "\n";
echo str_repeat("-", 100) . "\n";

foreach ($items as $item) {
    echo str_pad(substr($item->product_name, 0, 23), 25) . 
         str_pad(number_format($item->qty, 2), 12) . 
         str_pad($item->total_pieces, 12) . 
         str_pad($item->loose_pieces, 10) . 
         str_pad(number_format($item->price, 2), 12) . 
         str_pad(number_format($item->total, 2), 15) . "\n";
}

echo "\n";

// Check product details
if ($items->count() > 0) {
    $firstItem = $items->first();
    $product = \App\Models\Product::find($firstItem->product_id);
    
    if ($product) {
        echo "Product Details:\n";
        echo "  Name: {$product->item_name}\n";
        echo "  Retail Price: " . number_format($product->retail_price, 2) . "\n";
        echo "  Pieces Per Box: {$product->pieces_per_box}\n";
        echo "  Size Mode: {$product->size_mode}\n\n";
        
        echo "Calculation Analysis:\n";
        echo "  Item Price: " . number_format($firstItem->price, 2) . "\n";
        echo "  Item Qty (Boxes): " . number_format($firstItem->qty, 2) . "\n";
        echo "  Item Total Pieces: {$firstItem->total_pieces}\n";
        echo "  Item Total: " . number_format($firstItem->total, 2) . "\n\n";
        
        echo "Expected Calculations:\n";
        echo "  If Price is PER BOX:\n";
        echo "    {$firstItem->qty} boxes × {$firstItem->price} = " . number_format($firstItem->qty * $firstItem->price, 2) . "\n";
        echo "  If Price is PER PIECE:\n";
        echo "    {$firstItem->total_pieces} pieces × {$firstItem->price} = " . number_format($firstItem->total_pieces * $firstItem->price, 2) . "\n";
    }
}
