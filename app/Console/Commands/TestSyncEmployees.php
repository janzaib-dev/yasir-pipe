<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Models\Hr\Employee;
use App\Services\BiometricDeviceService;
use App\Services\BiometricSyncService;
use Illuminate\Console\Command;

class TestSyncEmployees extends Command
{
    protected $signature = 'employees:test-sync {device_id?}';

    protected $description = 'Test syncing employees to biometric device';

    public function handle()
    {
        $this->info('=== Testing Employee Sync to Biometric Device ===');
        $this->newLine();

        // Get device
        $deviceId = $this->argument('device_id');

        if ($deviceId) {
            $device = BiometricDevice::find($deviceId);
        } else {
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

            return 1;
        }

        $this->info('✓ Connection Successful!');
        $this->newLine();

        // Step 2: Check Employees
        $this->info('Step 2: Checking employees in database...');
        $employees = Employee::where('status', 'active')->get();

        if ($employees->isEmpty()) {
            $this->warn('No active employees found in database.');

            return 0;
        }

        $this->info("Found {$employees->count()} active employees");
        $this->newLine();

        // Display employees
        $this->table(
            ['ID', 'Name', 'Device User ID', 'Linked Device', 'Last Sync'],
            $employees->map(fn ($emp) => [
                $emp->id,
                $emp->full_name ?? $emp->first_name.' '.$emp->last_name,
                $emp->device_user_id ?? 'Not Set',
                $emp->biometric_device_id ?? 'Not Linked',
                $emp->last_device_sync_at ?? 'Never',
            ])->toArray()
        );
        $this->newLine();

        // Step 3: Sync Employees
        $this->info('Step 3: Syncing employees to device...');
        $syncService = app(BiometricSyncService::class);
        $result = $syncService->syncAllEmployeesToDevice($device);

        if ($result['success']) {
            $this->info('✓ Sync Complete!');
            $this->info("  - Synced: {$result['synced']} employees");
            $this->info("  - Failed: {$result['failed']} employees");
        } else {
            $this->error("Sync Failed: {$result['message']}");
        }

        $this->newLine();

        // Show updated employees
        $this->info('Updated Employee Status:');
        $updatedEmployees = Employee::where('status', 'active')->get();
        $this->table(
            ['ID', 'Name', 'Device User ID', 'Linked Device ID', 'Last Sync'],
            $updatedEmployees->map(fn ($emp) => [
                $emp->id,
                $emp->full_name ?? $emp->first_name.' '.$emp->last_name,
                $emp->device_user_id ?? 'Not Set',
                $emp->biometric_device_id ?? 'Not Linked',
                $emp->last_device_sync_at ?? 'Never',
            ])->toArray()
        );

        $this->newLine();
        $this->info('=== Test Complete ===');

        return 0;
    }
}
