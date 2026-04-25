<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BiometricDevice;
use App\Services\BiometricDeviceService;

class FixDeviceTime extends Command
{
    protected $signature = 'device:sync-time';
    protected $description = 'Sync biometric device time to server time';

    public function handle()
    {
        $device = BiometricDevice::first();
        if (!$device) {
            $this->error("No device found.");
            return;
        }

        $this->info("Syncing time for device: {$device->name} ({$device->ip_address})");
        $this->info("Current Server Time: " . now()->toDateTimeString() . " (" . config('app.timezone') . ")");

        $service = app(BiometricDeviceService::class);
        $result = $service->syncTime($device);

        if ($result) {
            $this->info("✓ Time synced successfully!");
            $this->info("Please pull attendance again to verify timestamps.");
        } else {
            $this->error("Failed to sync time. Check connections/logs.");
        }
    }
}
