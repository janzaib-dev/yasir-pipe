<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BiometricDevice;
use App\Services\BiometricDeviceService;

// 1. Setup Service
$service = new BiometricDeviceService();

// 2. Setup Device Mock
$device = new BiometricDevice();
$device->ip_address = '192.0.0.64';
$device->port = 80;
$device->username = 'admin';
$device->password = 'admin123'; // Replace with actual if known, using dummy for structure
// Note: If using Digest Auth, password must be correct.

echo "Testing Hikvision ISAPI at http://{$device->ip_address}:{$device->port}...\n";

// 3. Test Connect
if ($service->connect($device)) {
    echo "SUCCESS: Connected to ISAPI.\n";
    
    // 4. Test Time Sync
    echo "Attempting Time Sync...\n";
    if ($service->syncTime($device)) {
        echo "SUCCESS: Time Synced.\n";
    } else {
        echo "FAILED: Time Sync.\n";
    }

    // 5. Test Logs
    echo "Fetching Logs...\n";
    $logs = $service->getAttendanceLogs($device);
    echo "Logs Found: " . count($logs) . "\n";
    if (!empty($logs)) {
        print_r(array_slice($logs, 0, 3));
    }

} else {
    echo "FAILED: Connection refused or Auth failed.\n";
}
