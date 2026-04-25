<?php
/**
 * Backfill Journal Entries for Existing Sales
 * 
 * This script creates journal entries for sales that were posted before
 * the journal entry system was implemented.
 * 
 * Run: php backfill_journal_entries.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use Illuminate\Support\Facades\DB;

echo "\n=== Backfill Journal Entries for Existing Sales ===\n\n";

try {
    // Get all posted sales without journal entries
    $sales = Sale::where('sale_status', 'posted')
        ->whereDoesntHave('journalEntries')
        ->with('customer_relation')
        ->get();
    
    if ($sales->isEmpty()) {
        echo "✓ No sales need backfilling. All posted sales have journal entries.\n\n";
        exit(0);
    }
    
    echo "Found {$sales->count()} sales that need journal entries.\n\n";
    
    $journalService = app(JournalEntryService::class);
    
    // Get actual account IDs from database
    $arAccount = \App\Models\Account::where('title', 'like', '%Receivable%')->first();
    $salesAccount = \App\Models\Account::where('title', 'like', '%Sales%')->first();
    
    if (!$arAccount || !$salesAccount) {
        echo "❌ Required accounts not found. Please run create_accounts.php first.\n\n";
        exit(1);
    }
    
    $arAccountId = $arAccount->id;
    $salesAccountId = $salesAccount->id;
    
    echo "Using Accounts Receivable ID: {$arAccountId}\n";
    echo "Using Sales Revenue ID: {$salesAccountId}\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($sales as $sale) {
        try {
            DB::beginTransaction();
            
            $date = $sale->created_at->format('Y-m-d');
            $customer = $sale->customer_relation;
            
            if (!$customer) {
                echo "⚠ Skipping Sale #{$sale->invoice_no} - No customer found\n";
                $errorCount++;
                DB::rollBack();
                continue;
            }
            
            // Dr Accounts Receivable (with customer party)
            $journalService->recordEntry(
                $sale,
                $arAccountId,
                $sale->total_net,
                0,
                "Sale Invoice #{$sale->invoice_no}",
                $date,
                $customer
            );
            
            // Cr Sales Revenue
            $journalService->recordEntry(
                $sale,
                $salesAccountId,
                0,
                $sale->total_net,
                "Sale Invoice #{$sale->invoice_no}",
                $date
            );
            
            DB::commit();
            
            echo "✓ Created entries for Sale #{$sale->invoice_no} - Amount: " . number_format($sale->total_net, 2) . " - Customer: {$customer->customer_name}\n";
            $successCount++;
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Error for Sale #{$sale->invoice_no}: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ Successfully created: $successCount\n";
    echo "❌ Errors: $errorCount\n";
    echo "\nTotal journal entries now: " . JournalEntry::count() . "\n\n";
    
    // Show updated balance for first customer
    if ($successCount > 0) {
        $firstSale = $sales->first();
        if ($firstSale && $firstSale->customer_relation) {
            $balanceService = app(\App\Services\BalanceService::class);
            $balance = $balanceService->getCustomerBalance($firstSale->customer_id);
            
            echo "Sample Customer Balance Check:\n";
            echo "Customer: {$firstSale->customer_relation->customer_name}\n";
            echo "Balance: " . \App\Services\BalanceService::formatBalance($balance) . "\n\n";
        }
    }
    
    echo "✅ Backfill Complete!\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
