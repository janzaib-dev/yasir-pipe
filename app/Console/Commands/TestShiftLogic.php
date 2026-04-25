<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Hr\Shift;
use App\Models\Hr\Employee;

class TestShiftLogic extends Command
{
    protected $signature = 'test:shift-logic';
    protected $description = 'Test Late calculation logic';

    public function handle()
    {
        $this->info("=== Testing Shift Logic ===");

        // Simulate Shift
        $start = "09:00";
        $grace = 15;
        $this->info("Shift Rule: Start {$start} | Grace {$grace}m");
        
        // Late Threshold
        // Note: Logic copied from BiometricSyncService for simulation
        $today = date('Y-m-d');
        $shiftStart = Carbon::parse("$today $start");
        $threshold = $shiftStart->copy()->addMinutes($grace);
        
        $this->info("Late Threshold: " . $threshold->format('H:i:s'));
        $this->newLine();

        $scenarios = [
            '09:00:00' => 'Present (On Time)',
            '09:10:00' => 'Present (Within Grace)',
            '09:15:00' => 'Present (Exact Grace)',
            '09:15:01' => 'Late (1 sec over)',
            '09:20:00' => 'Late (20 mins)',
        ];

        foreach ($scenarios as $timeStr => $expect) {
            $punch = Carbon::parse("$today $timeStr");
            
            $isLate = false;
            $lateMinutes = 0;

            if ($punch->gt($threshold)) {
                $isLate = true;
                $lateMinutes = $shiftStart->diffInMinutes($punch);
            }

            $currentStatus = $isLate ? "LATE ({$lateMinutes}m)" : "PRESENT";
            $color = $isLate ? 'error' : 'info';
            
            $this->line("Punch: $timeStr | Result: <$color>$currentStatus</$color> | Expect: $expect");
        }
    }
}
