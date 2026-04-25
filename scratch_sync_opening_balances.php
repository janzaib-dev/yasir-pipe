<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Vendor;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Services\BalanceService;
use App\Services\JournalEntryService;

$balanceService = app(BalanceService::class);
$journalService = app(JournalEntryService::class);
$apId = $balanceService->getAccountsPayableId();
$arId = $balanceService->getAccountsReceivableId();

// 1. Sync Vendor Opening Balances
foreach (Vendor::where('opening_balance', '>', 0)->get() as $v) {
    $exists = JournalEntry::where('party_type', Vendor::class)
        ->where('party_id', $v->id)
        ->where('description', 'Opening Balance')
        ->exists();
    
    if (!$exists) {
        $journalService->recordEntry(
            $v,
            $apId,
            0,
            $v->opening_balance,
            "Opening Balance",
            $v->created_at->format('Y-m-d'),
            $v
        );
        echo "Created Opening Journal for Vendor: {$v->name} ({$v->opening_balance})\n";
    }
}

// 2. Sync Customer Opening Balances
foreach (Customer::where('opening_balance', '>', 0)->get() as $c) {
    $exists = JournalEntry::where('party_type', Customer::class)
        ->where('party_id', $c->id)
        ->where('description', 'Opening Balance')
        ->exists();
    
    if (!$exists) {
        $journalService->recordEntry(
            $c,
            $arId,
            $c->opening_balance, // Debit for Customer
            0,
            "Opening Balance",
            $c->created_at->format('Y-m-d'),
            $c
        );
        echo "Created Opening Journal for Customer: {$c->customer_name} ({$c->opening_balance})\n";
    }
}
