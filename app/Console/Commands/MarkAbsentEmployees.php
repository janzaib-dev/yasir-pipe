<?php

namespace App\Console\Commands;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Holiday;
use App\Models\Hr\Leave;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'attendance:mark-absent {date?}';

    protected $description = 'Mark active employees as absent if no attendance record exists for the date';

    public function handle()
    {
        $dateStr = $this->argument('date') ?? date('Y-m-d');
        $date = Carbon::parse($dateStr);
        $dayName = strtolower($date->format('l')); // e.g., 'monday'

        $this->info('Marking absent employees for: '.$date->toDateString());

        // Check if it's a holiday
        if (Holiday::isHoliday($date)) {
            $holiday = Holiday::getHoliday($date);
            $this->info("Skipping: {$date->toDateString()} is a holiday ({$holiday->name})");

            return;
        }

        // Get all active employees
        $employees = Employee::where('status', 'active')->get();
        $absentCount = 0;
        $leaveCount = 0;

        foreach ($employees as $employee) {
            // Check if attendance exists
            $exists = Attendance::where('employee_id', $employee->id)
                ->where('date', $date->toDateString())
                ->exists();

            if (! $exists) {
                // Check if employee has approved leave on this date
                $hasLeave = Leave::hasApprovedLeave($employee->id, $date);

                if ($hasLeave) {
                    // Mark as Leave instead of Absent
                    $leave = Leave::getApprovedLeave($employee->id, $date);
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'status' => 'leave',
                        'is_late' => false,
                        'late_minutes' => 0,
                        'is_early_in' => false,
                        'early_in_minutes' => 0,
                        'is_early_leave' => false,
                        'early_leave_minutes' => 0,
                        'total_hours' => 0,
                    ]);

                    $this->line("Marked Leave: {$employee->full_name} ({$leave->leave_type})");
                    $leaveCount++;
                } else {
                    // Create Absent Record
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'status' => 'absent',
                        'is_late' => false,
                        'late_minutes' => 0,
                        'is_early_in' => false,
                        'early_in_minutes' => 0,
                        'is_early_leave' => false,
                        'early_leave_minutes' => 0,
                        'total_hours' => 0,
                    ]);

                    $this->line("Marked Absent: {$employee->full_name}");
                    $absentCount++;
                }
            }
        }

        $this->info("Completed. Marked {$absentCount} employees as absent and {$leaveCount} on leave.");
    }
}
