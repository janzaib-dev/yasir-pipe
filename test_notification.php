<?php

/**
 * Test Notification Script
 * Run this to create a test notification for the logged-in user
 * 
 * Usage: php test_notification.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SystemNotification;
use App\Models\User;

echo "=== Notification System Test ===\n\n";

// Get the first user (or specify user ID)
$user = User::first();

if (!$user) {
    echo "❌ No users found in database!\n";
    exit(1);
}

echo "Creating test notification for: {$user->name} (ID: {$user->id})\n\n";

// Create a test warning notification
$notification1 = SystemNotification::create([
    'user_id' => $user->id,
    'title' => 'Test Warning Notification',
    'message' => 'This is a test warning notification. Customer ABC has an overdue payment of 7 days.',
    'type' => 'warning',
    'source_type' => 'debt_check',
    'source_id' => null,
    'action_url' => '/customer/1',
    'is_read' => false,
]);

echo "✅ Created Warning Notification (ID: {$notification1->id})\n";

// Create a test critical notification
$notification2 = SystemNotification::create([
    'user_id' => $user->id,
    'title' => 'Test Critical Notification',
    'message' => 'This is a test critical notification. Customer XYZ has an overdue payment of 10 days!',
    'type' => 'critical',
    'source_type' => 'debt_check',
    'source_id' => null,
    'action_url' => '/customer/2',
    'is_read' => false,
]);

echo "✅ Created Critical Notification (ID: {$notification2->id})\n";

// Create a test info notification
$notification3 = SystemNotification::create([
    'user_id' => $user->id,
    'title' => 'Test Info Notification',
    'message' => 'This is a test info notification. System update completed successfully.',
    'type' => 'info',
    'source_type' => 'system',
    'source_id' => null,
    'action_url' => null,
    'is_read' => false,
]);

echo "✅ Created Info Notification (ID: {$notification3->id})\n\n";

// Get unread count
$unreadCount = SystemNotification::getUnreadCount($user->id);
echo "📊 Total Unread Notifications: {$unreadCount}\n\n";

echo "=== Test Complete! ===\n";
echo "Now refresh your browser and check:\n";
echo "1. The bell icon should show a red badge with number {$unreadCount}\n";
echo "2. Click the bell to view notifications at: /notifications\n";
echo "3. You should see 3 test notifications (Warning, Critical, Info)\n";
