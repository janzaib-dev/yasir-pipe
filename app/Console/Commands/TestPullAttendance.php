<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Services\BiometricDeviceService;
use App\Services\BiometricSyncService;
use Illuminate\Console\Command;

class TestPullAttendance extends Command
{
    protected $signature = 'attendance:test-pull {device_id?}';

    protected $description = 'Test pulling attendance logs from biometric device';

    public function handle()
    {
        $this->info('=== Testing Attendance Pull from Biometric Device ===');
        $this->newLine();

        // Get device
        $deviceId = $this->argument('device_id');

        if ($deviceId) {
            $device = BiometricDevice::find($deviceId);
        } else {
            // Get first active device
            $device = BiometricDevice::where('is_active', true)->first();
        }

        if (! $device) {
            $this->error('No biometric device found! Please add a device first.');

            return 1;
        }

        $this->info("Device: {$device->name}");
        $this->info("IP: {$device->ip_address}:{$device->port}");
        $this->newLine();

        // Step 1: Test Connection
        $this->info('Step 1: Testing connection...');
        $deviceService = app(BiometricDeviceService::class);
        $connectionResult = $deviceService->testConnection($device);

        if (! $connectionResult['success']) {
            $this->error("Connection FAILED: {$connectionResult['message']}");
            $this->error('Please check:');
            $this->line('  - Device is powered on and connected to network');
            $this->line('  - IP address and port are correct');
            $this->line('  - Username/password are correct');

            return 1;
        }

        $this->info('✓ Connection Successful!');
        $this->newLine();

        // Step 2: Get Raw Logs
        $this->info('Step 2: Fetching raw attendance logs from device...');
        $rawLogs = $deviceService->getAttendanceLogs($device);

        if (empty($rawLogs)) {
            $this->warn('No attendance logs found on device.');
            $this->line('This could mean:');
            $this->line('  - No punches recorded on device yet');
            $this->line('  - Logs were already cleared');
            $this->line('  - Device API returned no data');

            return 0;
        }

        $this->info('✓ Found '.count($rawLogs).' raw log entries!');
        $this->newLine();

        // Display raw logs
        $this->info('Raw Attendance Logs:');
        $this->table(
            ['Device User ID', 'Timestamp', 'State', 'UID'],
            array_map(fn ($log) => [
                $log['id'] ?? 'N/A',
                $log['timestamp'] ?? 'N/A',
                $log['state'] ?? 'N/A',
                $log['uid'] ?? 'N/A',
            ], array_slice($rawLogs, 0, 20)) // Show first 20
        );

        if (count($rawLogs) > 20) {
            $this->line('... and '.(count($rawLogs) - 20).' more entries');
        }
        $this->newLine();

        // Step 3: Sync to Database
        $this->info('Step 3: Syncing logs to database (with 20-min duplicate protection)...');
        $syncService = app(BiometricSyncService::class);
        $syncResult = $syncService->pullAttendanceFromDevice($device);

        if ($syncResult['success']) {
            $this->info('✓ Sync Complete!');
            $this->info("  - Created/Updated: {$syncResult['created']} records");
            $this->info("  - Skipped (no matching employee): {$syncResult['skipped']} records");
            $this->info("  - Duplicates ignored (within 20 min): " . ($syncResult['duplicates'] ?? 0) . " records");
        } else {
            $this->warn("Sync Warning: {$syncResult['message']}");
        }

        $this->newLine();
        $this->info('=== Test Complete ===');

        return 0;
    }
}
