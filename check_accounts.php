<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Account;

echo "\n=== Accounts in Database ===\n\n";

$accounts = Account::select('id', 'title', 'account_code', 'type')->get();

if ($accounts->isEmpty()) {
    echo "No accounts found!\n\n";
} else {
    echo str_pad("ID", 5) . str_pad("Code", 15) . str_pad("Title", 40) . str_pad("Type", 10) . "\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($accounts as $acc) {
        echo str_pad($acc->id, 5) . 
             str_pad($acc->account_code ?? '-', 15) . 
             str_pad(substr($acc->title, 0, 38), 40) . 
             str_pad($acc->type ?? '-', 10) . 
             "\n";
    }
}

echo "\nTotal: " . $accounts->count() . " accounts\n\n";
