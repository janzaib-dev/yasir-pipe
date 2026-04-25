<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\HrSetting;
use Carbon\Carbon;

class BiometricSyncService
{
    protected BiometricDeviceService $deviceService;

    public function __construct(BiometricDeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Sync single employee to device
     */
    public function syncEmployeeToDevice(Employee $employee, BiometricDevice $device): array
    {
        // Generate device user ID if not exists
        if (! $employee->device_user_id) {
            $employee->device_user_id = $this->generateDeviceUserId($device);
        } else {
            // Check for potential duplicate ID conflict on this device
            $conflict = Employee::where('biometric_device_id', $device->id)
                ->where('device_user_id', $employee->device_user_id)
                ->where('id', '!=', $employee->id)
                ->exists();

            if ($conflict) {
                // ID conflict detected! Regenerate ID for this user
                $employee->device_user_id = $this->generateDeviceUserId($device);
            }
        }

        // Ensure strictly linked to this device
        if ($employee->biometric_device_id !== $device->id) {
            $employee->biometric_device_id = $device->id;
            $employee->save();
        }

        // Add user to device
        $success = $this->deviceService->addUserToDevice(
            $device,
            $employee->device_user_id,
            $employee->full_name
        );

        if ($success) {
            $employee->last_device_sync_at = now();
            $employee->save();

            return [
                'success' => true,
                'message' => 'Employee synced successfully. Device User ID: '.$employee->device_user_id,
                'device_user_id' => $employee->device_user_id,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to sync employee to device.',
        ];
    }

    /**
     * Sync all employees to device
     */
    public function syncAllEmployeesToDevice(BiometricDevice $device): array
    {
        $employees = Employee::where('status', 'active')->get();
        $synced = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            $result = $this->syncEmployeeToDevice($employee, $device);
            if ($result['success']) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => true,
            'synced' => $synced,
            'failed' => $failed,
            'message' => "Synced {$synced} employees. Failed: {$failed}",
        ];
    }

    /**
     * Get punch gap minutes from global settings
     */
    protected function getPunchGapMinutes(): int
    {
        return HrSetting::getPunchGapMinutes();
    }

    /**
     * Pull attendance logs from device and create attendance records
     */
    public function pullAttendanceFromDevice(BiometricDevice $device): array
    {
        $logs = $this->deviceService->getAttendanceLogs($device);

        if (empty($logs)) {
            return [
                'success' => false,
                'message' => 'No attendance logs found on device.',
                'created' => 0,
                'skipped' => 0,
                'duplicates' => 0,
                'failed' => 0,
            ];

        }

        // Sort logs by timestamp to ensure we process them in order
        usort($logs, function ($a, $b) {
            return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
        });

        $created = 0;
        $skipped = 0;
        $duplicates = 0;

        foreach ($logs as $log) {
            // Find employee by device_user_id
            $employee = Employee::where('device_user_id', $log['id'])->first();

            if (! $employee) {
                $skipped++;

                continue;
            }

            // Parse timestamp
            $timestamp = Carbon::parse($log['timestamp']);
            $date = $timestamp->toDateString();

            // Find or create attendance record for this date
            $attendance = Attendance::firstOrCreate([
                'employee_id' => $employee->id,
                'date' => $date,
            ], [
                'status' => 'present',
            ]);

            // LOGIC WITH 20-MINUTE GAP:
            // 1. First punch of the day = Check-In
            // 2. Punches within 20 min of Check-In = Ignored (duplicate/accidental)
            // 3. Punches after 20 min from Check-In AND is LATER = Check-Out
            // 4. Punches within 20 min of Check-Out = Ignored (duplicate/accidental)
            // 5. Punches after 20 min from Check-Out AND is LATER = Update Check-Out

            if (! $attendance->check_in_time) {
                // No check-in yet - this is the first punch (Check-In)
                // Calculate Late Status
                $shift = $employee->shift ?? \App\Models\Hr\Shift::where('is_default', true)->first();
                $isLate = false;
                $lateMinutes = 0;

                if ($shift) {
                    $timeStr = null;

                    if ($employee->custom_start_time) {
                        $timeStr = $employee->custom_start_time;
                    } elseif ($shift->start_time) {
                        // Ensure we only get the time part if it's a Carbon object or full datetime string
                        $timeStr = \Carbon\Carbon::parse($shift->start_time)->format('H:i:s');
                    }

                    if ($timeStr) {
                        // Parse start time relative to the attendance date
                        $shiftStart = Carbon::parse($attendance->date.' '.$timeStr);

                        // Add grace period
                        $lateThreshold = $shiftStart->copy()->addMinutes($shift->grace_minutes ?? 0);

                        if ($timestamp->gt($lateThreshold)) {
                            $isLate = true;
                            $lateMinutes = $shiftStart->diffInMinutes($timestamp);
                        }

                        // Calculate Early In
                        if ($timestamp->lt($shiftStart)) {
                            $attendance->is_early_in = true;
                            $attendance->early_in_minutes = $timestamp->diffInMinutes($shiftStart);
                        }
                    }
                }

                $attendance->is_late = $isLate;
                $attendance->late_minutes = $lateMinutes;
                $attendance->status = $isLate ? 'late' : 'present';

                $attendance->check_in_time = $timestamp->toDateTimeString();
                $attendance->check_in_location = 'Biometric Device';
                $attendance->save();
                $created++;
                $lastLogDate = $date;
                \Log::info("Created Check-In for {$employee->full_name} at {$timestamp}. Shifts: ".($shift ? $shift->name : 'None').'. Late: '.($isLate ? "Yes ({$lateMinutes}m)" : 'No'));
            } else { // Existing attendance record
                // RE-CALCULATE LATE STATUS (In case shift changed or first sync was incorrect)
                $shift = $employee->shift ?? \App\Models\Hr\Shift::where('is_default', true)->first();
                if ($shift && $attendance->check_in_time) {
                    $checkInTime = Carbon::parse($attendance->check_in_time);
                    $timeStr = null;

                    if ($employee->custom_start_time) {
                        $timeStr = $employee->custom_start_time;
                    } elseif ($shift->start_time) {
                        $timeStr = \Carbon\Carbon::parse($shift->start_time)->format('H:i:s');
                    }

                    if ($timeStr) {
                        $shiftStart = Carbon::parse($attendance->date.' '.$timeStr);
                        $lateThreshold = $shiftStart->copy()->addMinutes($shift->grace_minutes ?? 0);

                        if ($checkInTime->gt($lateThreshold)) {
                            $isLate = true;
                            $lateMinutes = $shiftStart->diffInMinutes($checkInTime);

                            // Only update if changed
                            if (! $attendance->is_late || $attendance->late_minutes != $lateMinutes || $attendance->status != 'late') {
                                $attendance->is_late = true;
                                $attendance->late_minutes = $lateMinutes;
                                $attendance->status = 'late';
                                $attendance->save(); // Save update
                                \Log::info("Updated Late Status for {$employee->full_name}. CheckIn: {$checkInTime->toTimeString()}. Late: Yes ({$lateMinutes}m)");
                            }
                        }
                    }
                }

                $checkInTime = Carbon::parse($attendance->check_in_time);
                $gap = $this->getPunchGapMinutes();

                // IMPORTANT: Punch must be AFTER check-in time (not before or same)
                if ($timestamp->lte($checkInTime)) {
                    // This punch is at or before check-in - skip (likely already processed or out of order)
                    $duplicates++;

                    continue;
                }

                $minutesSinceCheckIn = $checkInTime->diffInMinutes($timestamp);
                \Log::info("Processing punch for {$employee->full_name}. Time: {$timestamp}. CheckIn: {$checkInTime}. Diff: {$minutesSinceCheckIn}m. Gap: {$gap}m");

                if ($minutesSinceCheckIn < $gap) {
                    // Within gap - ignore (accidental duplicate)
                    $duplicates++;
                    \Log::info("IGNORED: Punch within gap ({$minutesSinceCheckIn}m < {$gap}m)");

                    continue;
                }

                // This punch is more than gap minutes AFTER check-in - can be check-out
                if (! $attendance->check_out_time || $timestamp->gt(Carbon::parse($attendance->check_out_time))) {
                    $attendance->check_out_time = $timestamp->toDateTimeString();
                    $attendance->check_out_location = 'Biometric Device';

                    // Calculate Early Leave
                    $shift = $employee->shift ?? \App\Models\Hr\Shift::where('is_default', true)->first();
                    if ($shift) {
                        $endTimeStr = $employee->custom_end_time ?: ($shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i:s') : null);
                        if ($endTimeStr) {
                            $shiftEnd = Carbon::parse($attendance->date.' '.$endTimeStr);
                            if ($timestamp->lt($shiftEnd)) {
                                $attendance->is_early_leave = true;
                                $attendance->early_leave_minutes = $timestamp->diffInMinutes($shiftEnd);
                            } else {
                                $attendance->is_early_leave = false;
                                $attendance->early_leave_minutes = 0;
                            }
                        }
                    }

                    $this->calculateTotalHours($attendance);
                    $attendance->save();
                    $created++;
                    \Log::info("ACCEPTED: Check-out recorded at {$timestamp}. Early Leave: ".($attendance->is_early_leave ? "Yes ({$attendance->early_leave_minutes}m)" : 'No'));
                }
            }
        }

        // Update device last sync time
        $device->last_sync_at = now();
        $device->save();

        return [
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'duplicates' => $duplicates,
            'failed' => 0,
            'failed' => 0,
            'last_log_date' => $lastLogDate ?? null,
            'message' => "Processed attendance. Synced: {$created}, Skipped (no employee): {$skipped}, Duplicates ignored: {$duplicates}" . (isset($lastLogDate) ? ". Latest Date: $lastLogDate" : ""),
        ];
    }

    /**
     * Calculate and set total hours worked
     */
    protected function calculateTotalHours(Attendance $attendance): void
    {
        if ($attendance->check_in_time && $attendance->check_out_time) {
            $checkIn = Carbon::parse($attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->check_out_time);
            $attendance->total_hours = round($checkOut->diffInMinutes($checkIn) / 60, 2);
        }
    }

    /**
     * Generate unique device user ID
     */
    protected function generateDeviceUserId(BiometricDevice $device): string
    {
        // Get the highest device_user_id for this device
        $lastEmployee = Employee::where('biometric_device_id', $device->id)
            ->whereNotNull('device_user_id')
            ->orderByRaw('CAST(device_user_id AS UNSIGNED) DESC')
            ->first();

        $nextId = $lastEmployee ? (int) $lastEmployee->device_user_id + 1 : 1;

        return (string) $nextId;
    }
}
