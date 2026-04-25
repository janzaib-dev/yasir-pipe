<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JournalEntry;
use App\Models\VoucherMaster;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

echo "\n=== Fixing Incorrect Journal Entry Parties ===\n\n";

try {
    DB::beginTransaction();

    // 1. Find Journal Entries linked to Customers that are DEBITS (for Receipts)
    // Receipts should only credit the customer (reduce AR).
    // The Debit side is Cash, which should NOT be linked to the Customer.
    
    // Logic: Look for JournalEntries where:
    // - Source is VoucherMaster
    // - Voucher Type is RECEIPT
    // - Entry is DEBIT > 0
    // - Party Type is Customer
    // - Party ID is NOT NULL
    
    // We need to join with VoucherMaster to check type
    // But JournalEntry stores source_type and source_id.
    
    $badEntries = JournalEntry::where('source_type', VoucherMaster::class)
        ->where('party_type', Customer::class)
        ->where('debit', '>', 0) // Debit entries linked to customer
        ->whereHasMorph('source', [VoucherMaster::class], function ($query) {
            $query->where('voucher_type', 'RV'); // Receipt Voucher
        })
        ->get();
        
    if ($badEntries->isEmpty()) {
        echo "✓ No incorrect Receipt Debit entries found.\n";
    } else {
        echo "Found {$badEntries->count()} incorrect Debit entries linked to Customers (fixing...)\n";
        
        foreach ($badEntries as $entry) {
            echo "  Ref fixing ID: {$entry->id} - Dr: {$entry->debit}\n";
            $entry->party_type = null;
            $entry->party_id = null;
            $entry->save();
        }
    }

    // 2. Also check Payment Vouchers? (Credit side should not be Vendor)
    // Skipping for now as focus is Customer Receipt.

    DB::commit();
    echo "\n✅ Fix Complete!\n\n";

    // Re-Verify Balance for Bertha
    echo "Re-verifying Balance for Bertha Newton...\n";
    $customer = Customer::where('customer_name', 'like', '%Bertha%')->first();
    if ($customer) {
        $balanceService = app(\App\Services\BalanceService::class);
        $bal = $balanceService->getCustomerBalance($customer->id);
        echo "Customer: {$customer->customer_name}\n";
        echo "New Balance: " . \App\Services\BalanceService::formatBalance($bal) . "\n";
        
        // Expected:
        // Sale 1: 72k Dr
        // Sale 2: 72k Dr
        // Receipt: 244k Cr
        // Net: 144k - 244k = -100k (100k Cr)
        
        if ($bal == -100000) {
            echo "✅ Balance is CORRECT (-100,000)!\n";
        } else {
            echo "❌ Balance is still mismatch: $bal\n";
        }
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
