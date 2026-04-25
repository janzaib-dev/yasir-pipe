<?php

/**
 * Check Sales Ages - Diagnostic Script
 * This shows you exactly how old your unpaid sales are
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

echo "=== Sales Age Diagnostic ===\n\n";

// Get all unpaid sales (matching the logic from CheckOverdueDebts command)
$unpaidSales = Sale::where(function ($query) {
        $query->where('sale_status', '!=', 'paid')
              ->orWhereRaw('(total_net - COALESCE(cash, 0) - COALESCE(card, 0)) > 0');
    })
    ->with('customer_relation')
    ->orderBy('created_at', 'asc')
    ->get();

if ($unpaidSales->isEmpty()) {
    echo "❌ No unpaid sales found!\n";
    exit(1);
}

echo "Found {$unpaidSales->count()} unpaid sales:\n\n";

foreach ($unpaidSales as $sale) {
    $age = now()->diffInDays($sale->created_at);
    $remaining = $sale->total_net - ($sale->cash + $sale->card);
    $customerName = $sale->customer_relation->customer_name ?? 'Unknown';
    
    $status = '⚪ OK';
    if ($age == 10) {
        $status = '🔴 CRITICAL (EXACT MATCH - Will trigger today!)';
    } elseif ($age == 7) {
        $status = '🟡 WARNING (EXACT MATCH - Will trigger today!)';
    } elseif ($age > 10) {
        $status = '⚫ TOO OLD (Already notified on day 10)';
    } elseif ($age > 7) {
        $status = '⚫ TOO OLD (Already notified on day 7)';
    }
    
    echo "  {$status}\n";
    echo "      Sale #{$sale->id} - Invoice: {$sale->invoice_no}\n";
    echo "      Age: {$age} days old (Created: {$sale->created_at->format('Y-m-d H:i')})\n";
    echo "      Unpaid: Rs. " . number_format($remaining, 2) . "\n";
    echo "      Customer: {$customerName}\n\n";
}

echo "\n=== Summary ===\n";
$exactCritical = $unpaidSales->filter(fn($s) => now()->diffInDays($s->created_at) == 10)->count();
$exactWarning = $unpaidSales->filter(fn($s) => now()->diffInDays($s->created_at) == 7)->count();
$tooOld = $unpaidSales->filter(fn($s) => now()->diffInDays($s->created_at) > 10)->count();
$normal = $unpaidSales->filter(fn($s) => now()->diffInDays($s->created_at) < 7)->count();

echo "  🔴 Exactly 10 days (CRITICAL - will notify): {$exactCritical}\n";
echo "  🟡 Exactly 7 days (WARNING - will notify): {$exactWarning}\n";
echo "  ⚫ Older than 10 days (already notified): {$tooOld}\n";
echo "  ⚪ Less than 7 days (OK): {$normal}\n\n";

echo "⚠️  IMPORTANT: Notifications only trigger on EXACT day matches!\n";
echo "   - Day 7: Warning notification\n";
echo "   - Day 10: Critical notification\n\n";

if ($exactCritical + $exactWarning == 0) {
    echo "💡 No sales are exactly 7 or 10 days old right now.\n";
    echo "   To test notifications:\n\n";
    echo "   Option 1: Run the automatic test script (creates test sales)\n";
    echo "   php test_automatic_debt_check.php\n\n";
    
    if ($unpaidSales->isNotEmpty()) {
        $firstSale = $unpaidSales->first();
        echo "   Option 2: Manually set a sale to be exactly 8 days old\n";
        echo "   UPDATE sales SET created_at = DATE_SUB(NOW(), INTERVAL 8 DAY) WHERE id = {$firstSale->id};\n";
        echo "   Then run: php artisan debt:check\n\n";
    }
}
