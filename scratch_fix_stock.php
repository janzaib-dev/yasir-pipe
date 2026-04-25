<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WarehouseStock;
use App\Models\Product;

$stock = WarehouseStock::find(1);
if ($stock) {
    $stock->total_pieces = 154; // 150 + 4
    $stock->quantity = 154 / 12; 
    $stock->save();
    echo "Fixed Product ID 2 to 154 pieces.\n";
}

// Fix all others too
foreach (WarehouseStock::all() as $s) {
    $p = Product::find($s->product_id);
    if (!$p) continue;
    $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;
    $expectedQty = $s->total_pieces / $ppb;
    if (abs($s->quantity - $expectedQty) > 0.001) {
        $s->quantity = $expectedQty;
        $s->save();
        echo "Synced Product {$p->id} quantity to {$s->quantity}\n";
    }
}
