<?php

/**
 * Automatic Debt Notification Test Script
 * This script simulates the automatic daily check by creating old sales and running the debt check
 * 
 * Usage: php test_automatic_debt_check.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\DB;

echo "=== Automatic Debt Notification Test ===\n\n";

// Step 1: Check current settings
echo "Step 1: Checking Settings...\n";
$warningDays = \App\Models\Setting::get('debt_warning_days', 7);
$criticalDays = \App\Models\Setting::get('debt_critical_days', 10);
echo "  ✓ Warning threshold: {$warningDays} days\n";
echo "  ✓ Critical threshold: {$criticalDays} days\n\n";

// Step 2: Find or create a test customer
echo "Step 2: Setting up test customer...\n";
$customer = Customer::first();
if (!$customer) {
    echo "  ❌ No customers found! Please create a customer first.\n";
    exit(1);
}
echo "  ✓ Using customer: {$customer->name} (ID: {$customer->id})\n\n";

// Step 3: Create test sales with different ages
echo "Step 3: Creating test sales...\n";

// Create a 5-day old sale (should NOT trigger)
$sale1 = Sale::create([
    'customer_id' => $customer->id,
    'invoice_number' => 'TEST-' . time() . '-1',
    'total_amount' => 1000,
    'paid_amount' => 0,
    'remaining_amount' => 1000,
    'sale_date' => now()->subDays(5),
    'created_at' => now()->subDays(5),
    'updated_at' => now()->subDays(5),
]);
echo "  ✓ Created 5-day old sale (ID: {$sale1->id}) - Should NOT trigger\n";

// Create an 8-day old sale (should trigger WARNING)
$sale2 = Sale::create([
    'customer_id' => $customer->id,
    'invoice_number' => 'TEST-' . time() . '-2',
    'total_amount' => 2000,
    'paid_amount' => 0,
    'remaining_amount' => 2000,
    'sale_date' => now()->subDays(8),
    'created_at' => now()->subDays(8),
    'updated_at' => now()->subDays(8),
]);
echo "  ✓ Created 8-day old sale (ID: {$sale2->id}) - Should trigger WARNING\n";

// Create a 12-day old sale (should trigger CRITICAL)
$sale3 = Sale::create([
    'customer_id' => $customer->id,
    'invoice_number' => 'TEST-' . time() . '-3',
    'total_amount' => 3000,
    'paid_amount' => 0,
    'remaining_amount' => 3000,
    'sale_date' => now()->subDays(12),
    'created_at' => now()->subDays(12),
    'updated_at' => now()->subDays(12),
]);
echo "  ✓ Created 12-day old sale (ID: {$sale3->id}) - Should trigger CRITICAL\n\n";

// Step 4: Count notifications before
echo "Step 4: Checking notifications before debt check...\n";
$beforeCount = SystemNotification::count();
echo "  ✓ Current notification count: {$beforeCount}\n\n";

// Step 5: Run the debt check command
echo "Step 5: Running debt check command...\n";
echo "  → Executing: php artisan debt:check\n\n";

// Execute the command
$exitCode = \Artisan::call('debt:check');
$output = \Artisan::output();

echo $output;

// Step 6: Count notifications after
echo "\nStep 6: Verifying results...\n";
$afterCount = SystemNotification::count();
$newNotifications = $afterCount - $beforeCount;
echo "  ✓ New notifications created: {$newNotifications}\n";

// Get the new notifications
$notifications = SystemNotification::where('source_type', 'debt_check')
    ->orderBy('created_at', 'desc')
    ->limit($newNotifications)
    ->get();

echo "\n  Notification Details:\n";
foreach ($notifications as $notif) {
    $user = User::find($notif->user_id);
    echo "    - [{$notif->type}] {$notif->title}\n";
    echo "      For: {$user->name} ({$user->email})\n";
    echo "      Message: {$notif->message}\n\n";
}

// Step 7: Cleanup instructions
echo "=== Test Complete! ===\n\n";
echo "Expected Results:\n";
echo "  ✓ Should create 2 notifications (1 warning for 8-day sale, 1 critical for 12-day sale)\n";
echo "  ✓ 5-day old sale should be ignored\n\n";

echo "To verify in browser:\n";
echo "  1. Refresh your browser\n";
echo "  2. Check the bell icon - should show badge with notification count\n";
echo "  3. Click bell to view notifications at /notifications\n\n";

echo "To clean up test data:\n";
echo "  DELETE FROM sales WHERE invoice_number LIKE 'TEST-%';\n";
echo "  DELETE FROM system_notifications WHERE source_type = 'debt_check';\n\n";

echo "To test automatic scheduling:\n";
echo "  1. Set up Windows Task Scheduler or Linux Cron\n";
echo "  2. Schedule: php artisan schedule:run (every minute)\n";
echo "  3. The debt:check command runs daily at 9:00 AM\n";
