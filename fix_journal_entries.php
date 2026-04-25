<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fixing Existing Journal Entries ===\n\n";

// Find all journal entries from Sales that don't have party_type set
$entries = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
    ->whereNull('party_type')
    ->get();

echo "Found {$entries->count()} journal entries from Sales without party info\n\n";

$fixed = 0;
foreach ($entries as $entry) {
    $sale = \App\Models\Sale::find($entry->source_id);
    if ($sale && $sale->customer_id) {
        // Only update the DEBIT entry (Accounts Receivable) with customer party
        // The CREDIT entry (Sales Revenue) doesn't need party info
        if ($entry->debit > 0) {
            $entry->party_type = \App\Models\Customer::class;
            $entry->party_id = $sale->customer_id;
            $entry->save();
            $fixed++;
            echo "Fixed Entry ID {$entry->id} for Sale #{$sale->invoice_no} - Customer ID {$sale->customer_id}\n";
        }
    }
}

echo "\n✅ Fixed $fixed journal entries\n";

// Now test the customer ledger
echo "\n=== Testing Customer Ledger ===\n";
$customer = \App\Models\Customer::first();
if ($customer) {
    echo "Customer: {$customer->customer_name} (ID: {$customer->id})\n";
    
    $balanceService = app(\App\Services\BalanceService::class);
    $ledger = $balanceService->getCustomerLedger($customer->id, '2000-01-01', date('Y-m-d'));
    
    echo "Opening Balance: {$ledger['opening_balance']}\n";
    echo "Transactions: " . count($ledger['transactions']) . "\n";
    echo "Closing Balance: {$ledger['closing_balance']}\n";
    
    if (count($ledger['transactions']) > 0) {
        echo "\nFirst 3 transactions:\n";
        foreach (array_slice($ledger['transactions']->toArray(), 0, 3) as $t) {
            echo "  {$t['date']} | {$t['description']} | Dr: {$t['debit']} | Cr: {$t['credit']} | Bal: {$t['balance']}\n";
        }
    }
}
