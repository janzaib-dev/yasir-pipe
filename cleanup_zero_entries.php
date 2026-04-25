<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

echo "\n=== Cleaning Up Zero-Amount Journal Entries ===\n\n";

try {
    // Find zero-amount entries (both debit AND credit are 0)
    $zeroEntries = JournalEntry::where('debit', 0)
        ->where('credit', 0)
        ->get();
    
    if ($zeroEntries->isEmpty()) {
        echo "✓ No zero-amount entries found. Database is clean!\n\n";
        exit(0);
    }
    
    echo "Found {$zeroEntries->count()} zero-amount journal entries:\n\n";
    
    foreach ($zeroEntries as $entry) {
        echo "  ID: {$entry->id} - {$entry->description} (Date: {$entry->entry_date})\n";
    }
    
    echo "\nDeleting these entries...\n";
    
    DB::beginTransaction();
    
    $deleted = JournalEntry::where('debit', 0)
        ->where('credit', 0)
        ->delete();
    
    DB::commit();
    
    echo "✓ Deleted {$deleted} zero-amount entries\n\n";
    
    // Verify cleanup
    $remaining = JournalEntry::where('debit', 0)->where('credit', 0)->count();
    
    if ($remaining == 0) {
        echo "✅ Cleanup Complete! All zero-amount entries removed.\n\n";
    } else {
        echo "⚠ Warning: {$remaining} zero-amount entries still remain.\n\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
