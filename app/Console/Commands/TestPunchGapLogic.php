<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Hr\HrSetting;

class TestPunchGapLogic extends Command
{
    protected $signature = 'test:punch-gap';
    protected $description = 'Test punch gap calculation logic';

    public function handle()
    {
        $gap = HrSetting::getPunchGapMinutes();
        $this->info("Current Global Punch Gap: {$gap} minutes");

        $checkIn = Carbon::parse('2023-01-01 10:00:00');
        $this->info("Check In Time: " . $checkIn->toDateTimeString());

        $scenarios = [
            '10:02:59' => 'Should Ignore (Under 3m)',
            '10:03:00' => 'Should Accept (Exactly 3m)',
            '10:05:00' => 'Should Accept (5m)',
            '10:19:59' => 'Should Accept (19m)',
            '10:20:00' => 'Should Accept (20m)',
        ];

        foreach ($scenarios as $time => $expectation) {
            $punch = Carbon::parse("2023-01-01 $time");
            $diff = $checkIn->diffInMinutes($punch);
            $diffFloat = $checkIn->floatDiffInMinutes($punch);
            
            $isIgnored = $diff < $gap;
            
            $status = $isIgnored ? 'IGNORED' : 'ACCEPTED';
            $color = $isIgnored ? 'error' : 'info';
            
            $this->line("Punch: $time | Diff: {$diff}m | Status: <$color>$status</$color> | Expect: $expectation");
        }

        $this->info("\n--- Multi-User Simulation ---");
        $this->info("Scenario: User A checks in at 10:00. User B checks in at 10:01.");
        
        $userACheckIn = Carbon::parse('2023-01-01 10:00:00');
        $userBCheckIn = Carbon::parse('2023-01-01 10:01:00');
        
        $userBPunchTime = Carbon::parse('2023-01-01 10:01:00'); // User B punching
        
        // Logic for User B (Checking if User B is blocked by User A? No, logic depends on User B's checkin)
        // Actually, User B is checking IN. Punch gap applies to subsequent punches (check-out).
        // Let's verify User B's Check-Out at 10:02 (1 min after their own check-in).
        
        $userBCheckOutAttempt = Carbon::parse('2023-01-01 10:02:00');
        $userBGap = $userBCheckIn->diffInMinutes($userBCheckOutAttempt);
        $userBStatus = ($userBGap < $gap) ? 'IGNORED' : 'ACCEPTED';
        
        $this->line("User B Check-In: 10:01");
        $this->line("User B Check-Out Attempt: 10:02 (Diff: {$userBGap}m)");
        $this->line("Global Gap: {$gap}m");
        $this->line("User B Status: " . ($userBStatus == 'IGNORED' ? '<error>IGNORED (Blocked by own timer)</error>' : '<info>ACCEPTED</info>'));

        $userBCheckOutAttempt2 = Carbon::parse('2023-01-01 10:05:00');
        $userBGap2 = $userBCheckIn->diffInMinutes($userBCheckOutAttempt2);
        
        $this->line("User B Check-Out Attempt: 10:05 (Diff: {$userBGap2}m)");
        $this->line("User B Status: " . ($userBGap2 < $gap ? '<error>IGNORED</error>' : '<info>ACCEPTED</info>'));

    }
}
