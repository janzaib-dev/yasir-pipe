<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JournalEntry;
use App\Models\VoucherMaster;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

echo "\n=== Improved Cleanup Script ===\n\n";

// 1. Inspect existing entries for Bertha to verify the problem attributes
$customer = Customer::where('customer_name', 'like', '%Bertha%')->first();
if ($customer) {
    $entries = JournalEntry::where('party_type', Customer::class)
        ->where('party_id', $customer->id)
        ->get();
        
    foreach ($entries as $e) {
        if ($e->debit > 0) {
           echo "Debit Entry ID: {$e->id}, Dr: {$e->debit}, Source: {$e->source_type} #{$e->source_id}\n";
           if ($e->source_type == VoucherMaster::class) {
               $vm = VoucherMaster::find($e->source_id);
               echo "  -> Voucher Type: " . ($vm ? $vm->voucher_type : 'N/A') . "\n";
           }
        }
    }
}

echo "\nFixing...\n";

// 2. Fix the entries matching the pattern
// Pattern: Debit side of a 'receipt' Voucher linked to a Customer
// Receipt Vouchers usually Dr Cash, Cr Customer. Dr Cash should NOT be linked to Customer.

$badEntries = JournalEntry::where('source_type', VoucherMaster::class)
    ->where('party_type', Customer::class)
    ->where('debit', '>', 0)
    ->whereHasMorph('source', [VoucherMaster::class], function ($query) {
        $query->whereIn('voucher_type', ['receipt', 'RV']); // Check both potential values
    })
    ->get();

if ($badEntries->isEmpty()) {
    echo "No bad entries found via query.\n";
} else {
    foreach ($badEntries as $e) {
        echo "Fixing Entry ID: {$e->id} (Dr: {$e->debit})\n";
        $e->party_type = null;
        $e->party_id = null;
        $e->save();
    }
    echo "Fixed {$badEntries->count()} entries.\n";
}

// Check Balance result
$balanceService = app(\App\Services\BalanceService::class);
$bal = $balanceService->getCustomerBalance($customer->id);
echo "\nNew Balance: " . \App\Services\BalanceService::formatBalance($bal) . "\n";
