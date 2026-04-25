<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Account;
use Illuminate\Support\Facades\DB;

echo "\n=== Creating Required Accounts for Journal Entries ===\n\n";

try {
    DB::beginTransaction();
    
    $accountsToCreate = [
        [
            'title' => 'Accounts Receivable',
            'account_code' => 'AR-001',
            'type' => 'Debit',
            'opening_balance' => 0,
            'current_balance' => 0,
        ],
        [
            'title' => 'Sales Revenue',
            'account_code' => 'SALES-001',
            'type' => 'Credit',
            'opening_balance' => 0,
            'current_balance' => 0,
        ],
    ];
    
    foreach ($accountsToCreate as $accountData) {
        $existing = Account::where('title', $accountData['title'])->first();
        
        if ($existing) {
            echo "✓ Account already exists: {$accountData['title']} (ID: {$existing->id})\n";
        } else {
            $account = Account::create($accountData);
            echo "✓ Created: {$accountData['title']} (ID: {$account->id})\n";
        }
    }
    
    DB::commit();
    
    echo "\n=== Account IDs for Reference ===\n\n";
    
    $ar = Account::where('title', 'like', '%Receivable%')->first();
    $sales = Account::where('title', 'like', '%Sales%')->first();
    $cash = Account::where('title', 'like', '%cash%')->first();
    
    echo "Accounts Receivable ID: " . ($ar ? $ar->id : 'NOT FOUND') . "\n";
    echo "Sales Revenue ID: " . ($sales ? $sales->id : 'NOT FOUND') . "\n";
    echo "Cash ID: " . ($cash ? $cash->id : 'NOT FOUND') . "\n";
    
    echo "\n✅ Accounts Setup Complete!\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
