<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BiometricDevice;
use App\Services\BiometricSyncService;

class SyncAttendanceFromDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync-from-device';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull attendance logs from all active biometric devices';

    protected BiometricSyncService $syncService;

    public function __construct(BiometricSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attendance sync from biometric devices...');

        $devices = BiometricDevice::active()->get();

        if ($devices->isEmpty()) {
            $this->warn('No active biometric devices found.');
            return 0;
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($devices as $device) {
            $this->info("Syncing from: {$device->name} ({$device->ip_address})");

            $result = $this->syncService->pullAttendanceFromDevice($device);

            if ($result['success']) {
                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
                $this->line("  ✓ Created: {$result['created']}, Skipped: {$result['skipped']}");
            } else {
                $this->error("  ✗ Failed: {$result['message']}");
            }
        }

        $this->newLine();
        $this->info("Attendance sync completed!");
        $this->info("Total created: {$totalCreated}");
        $this->info("Total skipped: {$totalSkipped}");

        return 0;
    }
}
