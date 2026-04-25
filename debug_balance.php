<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\JournalEntry;
use App\Models\Customer;

echo "\n=== Investigating Balance Bug ===\n\n";

// Get most recent sale
$latestSale = Sale::orderBy('id', 'desc')->first();

if (!$latestSale) {
    echo "No sales found.\n";
    exit;
}

echo "Latest Sale:\n";
echo "  Invoice: {$latestSale->invoice_no}\n";
echo "  Customer ID: {$latestSale->customer_id}\n";
echo "  Amount: " . number_format($latestSale->total_net, 2) . "\n";
echo "  Status: {$latestSale->sale_status}\n";
echo "  Created: {$latestSale->created_at}\n\n";

// Get journal entries for this sale
echo "Journal Entries for this Sale:\n";
$saleEntries = JournalEntry::where('source_type', Sale::class)
    ->where('source_id', $latestSale->id)
    ->get();

if ($saleEntries->isEmpty()) {
    echo "  ⚠ NO JOURNAL ENTRIES FOUND!\n\n";
} else {
    echo "  Found {$saleEntries->count()} entries:\n\n";
    foreach ($saleEntries as $entry) {
        echo "  Account ID: {$entry->account_id}\n";
        echo "  Debit: " . number_format($entry->debit, 2) . "\n";
        echo "  Credit: " . number_format($entry->credit, 2) . "\n";
        echo "  Description: {$entry->description}\n";
        echo "  Party: " . ($entry->party_type ?? 'None') . " ID:" . ($entry->party_id ?? '-') . "\n";
        echo "  ---\n";
    }
}

// Get customer balance
$customer = Customer::find($latestSale->customer_id);
if ($customer) {
    echo "\nCustomer: {$customer->customer_name}\n";
    echo "  Opening Balance: " . number_format($customer->opening_balance ?? 0, 2) . "\n";
    
    $balanceService = app(\App\Services\BalanceService::class);
    $balance = $balanceService->getCustomerBalance($customer->id);
    echo "  Current Balance: " . \App\Services\BalanceService::formatBalance($balance) . "\n\n";
    
    // Show ALL journal entries for this customer
    echo "All Journal Entries for Customer:\n";
    $allEntries = JournalEntry::where('party_type', Customer::class)
        ->where('party_id', $customer->id)
        ->orderBy('id', 'desc')
        ->get();
    
    echo "  Total entries: {$allEntries->count()}\n\n";
    foreach ($allEntries as $entry) {
        echo "  [{$entry->id}] {$entry->entry_date} - {$entry->description}\n";
        echo "      Dr: " . number_format($entry->debit, 2) . " Cr: " . number_format($entry->credit, 2) . "\n";
    }
}
