<?php
/**
 * Test Script for Customer Balance Calculation using Journal Entries
 * 
 * Run: php test_customer_balance.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Services\BalanceService;
use Illuminate\Support\Facades\DB;

echo "\n=== Customer Balance Test (Journal Entries) ===\n\n";

try {
    // 1. Find or create test customer
    echo "Step 1: Setting up test customer...\n";
    $customer = Customer::where('customer_name', 'LIKE', '%Test%')->first();
    
    if (!$customer) {
        $customer = Customer::first();
    }
    
    if (!$customer) {
        echo "❌ No customers found in database. Please create a customer first.\n";
        exit(1);
    }
    
    echo "  ✓ Using customer: {$customer->customer_name} (ID: {$customer->id})\n";
    echo "  ✓ Opening Balance: " . number_format($customer->opening_balance ?? 0, 2) . "\n\n";

    // 2. Get current balance from journal entries
    echo "Step 2: Checking current balance from journal entries...\n";
    $balanceService = app(BalanceService::class);
    $currentBalance = $balanceService->getCustomerBalance($customer->id);
    
    echo "  ✓ Current Balance (from journal entries): " . BalanceService::formatBalance($currentBalance) . "\n\n";

    // 3. Show recent journal entries
    echo "Step 3: Recent journal entries for this customer...\n";
    $entries = JournalEntry::where('party_type', Customer::class)
        ->where('party_id', $customer->id)
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();
    
    if ($entries->isEmpty()) {
        echo "  ⚠ No journal entries found for this customer yet.\n";
        echo "  → Create a sale or receipt to generate entries.\n\n";
    } else {
        echo "  Found {$entries->count()} recent entries:\n\n";
        echo "  " . str_pad("Date", 12) . str_pad("Description", 40) . str_pad("Debit", 12) . str_pad("Credit", 12) . "\n";
        echo "  " . str_repeat("-", 76) . "\n";
        
        foreach ($entries as $entry) {
            echo "  " . 
                str_pad($entry->entry_date, 12) . 
                str_pad(substr($entry->description, 0, 38), 40) . 
                str_pad(number_format($entry->debit, 2), 12) . 
                str_pad(number_format($entry->credit, 2), 12) . 
                "\n";
        }
        echo "\n";
    }

    // 4. Get ledger for last 30 days
    echo "Step 4: Customer ledger (last 30 days)...\n";
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
    
    $ledgerData = $balanceService->getCustomerLedger($customer->id, $startDate, $endDate);
    
    echo "  Opening Balance: " . BalanceService::formatBalance($ledgerData['opening_balance']) . "\n";
    echo "  Transactions: {$ledgerData['transactions']->count()}\n";
    echo "  Closing Balance: " . BalanceService::formatBalance($ledgerData['closing_balance']) . "\n\n";

    // 5. Check recent sales
    echo "Step 5: Recent sales for this customer...\n";
    $sales = Sale::where('customer_id', $customer->id)
        ->where('sale_status', 'posted')
        ->orderBy('id', 'desc')
        ->limit(3)
        ->get();
    
    if ($sales->isEmpty()) {
        echo "  ⚠ No posted sales found for this customer.\n\n";
    } else {
        echo "  Found {$sales->count()} recent sales:\n\n";
        echo "  " . str_pad("Invoice", 15) . str_pad("Date", 12) . str_pad("Amount", 12) . str_pad("Status", 10) . "\n";
        echo "  " . str_repeat("-", 49) . "\n";
        
        foreach ($sales as $sale) {
            echo "  " . 
                str_pad($sale->invoice_no, 15) . 
                str_pad($sale->created_at->format('Y-m-d'), 12) . 
                str_pad(number_format($sale->total_net, 2), 12) . 
                str_pad($sale->sale_status, 10) . 
                "\n";
        }
        echo "\n";
    }

    // 6. Summary
    echo "=== Summary ===\n\n";
    echo "Customer: {$customer->customer_name}\n";
    echo "Opening Balance: " . BalanceService::formatBalance($customer->opening_balance ?? 0) . "\n";
    echo "Current Balance: " . BalanceService::formatBalance($currentBalance) . "\n";
    
    if ($currentBalance > 0) {
        echo "Status: Customer OWES " . number_format($currentBalance, 2) . " (Debit)\n";
    } elseif ($currentBalance < 0) {
        echo "Status: Customer has ADVANCE of " . number_format(abs($currentBalance), 2) . " (Credit)\n";
    } else {
        echo "Status: Account is SETTLED (Balance = 0)\n";
    }
    
    echo "\n✅ Test Complete!\n\n";

    // 7. Instructions
    echo "=== Next Steps ===\n\n";
    echo "To test the system:\n";
    echo "1. Create a sale for this customer and POST it\n";
    echo "2. Run this script again to see the balance increase\n";
    echo "3. Create a receipt voucher for the customer\n";
    echo "4. Run this script again to see the balance decrease\n\n";
    echo "To view in browser:\n";
    echo "- Customer Ledger Report: /report/customer-ledger\n";
    echo "- Chart of Accounts: /chart-of-accounts\n\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
