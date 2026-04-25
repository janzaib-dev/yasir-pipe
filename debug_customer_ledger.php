<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\JournalEntry;
use App\Services\BalanceService;

echo "\n=== Customer Ledger Debug (Bertha Newton) ===\n\n";

// Find customer
$customer = Customer::where('customer_name', 'like', '%Bertha%')->first();

if (!$customer) {
    echo "Customer not found!\n";
    exit;
}

echo "Customer: {$customer->customer_name} (ID: {$customer->id})\n";
echo "Opening Balance: " . number_format($customer->opening_balance ?? 0, 2) . "\n\n";

// Get all journal entries
$entries = JournalEntry::where('party_type', Customer::class)
    ->where('party_id', $customer->id)
    ->orderBy('id', 'asc')
    ->get();

echo "Journal Entries:\n";
echo str_repeat("-", 100) . "\n";
echo str_pad("ID", 5) . str_pad("Date", 12) . str_pad("Description", 40) . str_pad("Debit", 15) . str_pad("Credit", 15) . "\n";
echo str_repeat("-", 100) . "\n";

foreach ($entries as $entry) {
    echo str_pad($entry->id, 5) .
         str_pad($entry->entry_date, 12) .
         str_pad(substr($entry->description, 0, 38), 40) .
         str_pad(number_format($entry->debit, 2), 15) .
         str_pad(number_format($entry->credit, 2), 15) .
         "\n";
}

echo "\n";

// Calculate running balance manually
echo "Running Balance Calculation:\n";
echo str_repeat("-", 100) . "\n";

$runningBalance = $customer->opening_balance ?? 0;
echo "Opening Balance: " . number_format($runningBalance, 2) . "\n\n";

foreach ($entries as $entry) {
    $prevBalance = $runningBalance;
    $runningBalance += ($entry->debit - $entry->credit);
    
    echo "{$entry->description}\n";
    echo "  Previous: " . number_format($prevBalance, 2) . "\n";
    echo "  Dr: " . number_format($entry->debit, 2) . " Cr: " . number_format($entry->credit, 2) . "\n";
    echo "  Calculation: {$prevBalance} + {$entry->debit} - {$entry->credit} = {$runningBalance}\n";
    echo "  New Balance: " . BalanceService::formatBalance($runningBalance) . "\n\n";
}

echo "Final Balance: " . BalanceService::formatBalance($runningBalance) . "\n\n";

// Use BalanceService
$balanceService = app(BalanceService::class);
$calculatedBalance = $balanceService->getCustomerBalance($customer->id);

echo "BalanceService Calculated: " . BalanceService::formatBalance($calculatedBalance) . "\n";

if ($calculatedBalance == $runningBalance) {
    echo "✅ Manual and Service calculations match!\n\n";
} else {
    echo "❌ Mismatch! Manual: {$runningBalance}, Service: {$calculatedBalance}\n\n";
}
