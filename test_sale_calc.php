<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== Sale Calculation Test ===\n\n";

// Simulate the calculation logic
$qtyPieces = 100;  // User enters 100 pieces
$pricePerPiece = 720;  // User enters 720 per piece
$loose = 0;
$discount = 0;

$totalPieces = $qtyPieces + $loose;
$lineTotal = $totalPieces * $pricePerPiece;
$lineTotal = $lineTotal - ($lineTotal * $discount / 100);

echo "Input:\n";
echo "  Quantity: {$qtyPieces} pieces\n";
echo "  Price per Piece: {$pricePerPiece}\n";
echo "  Loose Pieces: {$loose}\n";
echo "  Discount: {$discount}%\n\n";

echo "Calculation:\n";
echo "  Total Pieces: {$totalPieces}\n";
echo "  Line Total: {$totalPieces} × {$pricePerPiece} = " . number_format($lineTotal, 2) . "\n\n";

echo "Expected: 72,000.00\n";
echo "Actual: " . number_format($lineTotal, 2) . "\n";

if ($lineTotal == 72000) {
    echo "\n✅ CORRECT! Calculation matches expected value.\n\n";
} else {
    echo "\n❌ WRONG! Calculation does not match.\n\n";
}

// Test with pieces_per_box conversion
echo "Storage Calculation:\n";
$ppb = 2;  // Pieces per box
$storedQtyBox = $totalPieces / $ppb;
echo "  Pieces per Box: {$ppb}\n";
echo "  Stored as Boxes: " . number_format($storedQtyBox, 2) . " boxes\n";
echo "  (100 pieces ÷ 2 = 50 boxes)\n\n";
