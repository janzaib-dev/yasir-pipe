<?php

namespace App\Services;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSalaryStructure;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayrollDetail;
use App\Models\Hr\SalaryStructure;
use Carbon\Carbon;

class PayrollCalculationService
{
    /**
     * Get the effective salary structure for an employee
     * Handles custom/edited structures vs template structures
     */
    public function getEffectiveSalaryStructure(Employee $employee): ?SalaryStructure
    {
        // Get the active assignment
        $activeAssignment = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereNull('end_date')
            ->with('salaryStructure')
            ->latest('start_date')
            ->first();

        if (! $activeAssignment || ! $activeAssignment->salaryStructure) {
            // Fallback to legacy relationship
            return $employee->salaryStructure;
        }

        $structure = $activeAssignment->salaryStructure;

        // If this is a custom assignment, check if there's a child structure for this employee
        if ($activeAssignment->is_custom) {
            // Look for an employee-specific child structure
            $customStructure = SalaryStructure::where('parent_structure_id', $structure->id)
                ->where('employee_id', $employee->id)
                ->first();

            if ($customStructure) {
                return $customStructure;
            }
        }

        return $structure;
    }

    /**
     * Calculate allowances with support for both fixed and percentage types
     */
    private function calculateAllowances(SalaryStructure $structure): array
    {
        $allowances = collect($structure->allowances ?? [])->filter(function ($item) {
            return ! empty($item['is_active']) && $item['is_active'] !== 'false';
        });

        $baseSalary = $structure->base_salary ?? 0;
        $totalAllowances = 0;
        $details = [];

        foreach ($allowances as $allowance) {
            $name = $allowance['name'] ?? 'Allowance';
            $type = $allowance['type'] ?? 'fixed'; // 'fixed' or 'percentage'
            $value = floatval($allowance['amount'] ?? 0);

            if ($type === 'percentage') {
                // Calculate based on base salary
                $amount = ($baseSalary * $value) / 100;
                $description = "{$value}% of base salary";
            } else {
                $amount = $value;
                $description = $allowance['description'] ?? null;
            }

            $totalAllowances += $amount;
            $details[] = [
                'name' => $name,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
            ];
        }

        return [
            'total' => $totalAllowances,
            'details' => $details,
        ];
    }

    /**
     * Calculate fixed deductions from salary structure
     */
    private function calculateFixedDeductions(SalaryStructure $structure): array
    {
        $deductions = collect($structure->deductions ?? [])->filter(function ($item) {
            return ! empty($item['is_active']) && $item['is_active'] !== 'false';
        });

        $baseSalary = $structure->base_salary ?? 0;
        $totalDeductions = 0;
        $details = [];

        foreach ($deductions as $deduction) {
            $name = $deduction['name'] ?? 'Deduction';
            $type = $deduction['type'] ?? 'fixed';
            $value = floatval($deduction['amount'] ?? 0);

            if ($type === 'percentage') {
                $amount = ($baseSalary * $value) / 100;
                $description = "{$value}% of base salary";
            } else {
                $amount = $value;
                $description = $deduction['description'] ?? null;
            }

            $totalDeductions += $amount;
            $details[] = [
                'name' => $name,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
            ];
        }

        return [
            'total' => $totalDeductions,
            'details' => $details,
        ];
    }

    /**
     * Calculate attendance-based deductions for monthly payroll
     */
    private function calculateMonthlyAttendanceDeductions(
        Employee $employee,
        string $month,
        SalaryStructure $structure
    ): array {
        $startDate = Carbon::parse($month.'-01')->startOfMonth();
        $endDate = Carbon::parse($month.'-01')->endOfMonth();

        $policy = $structure->attendance_deduction_policy ?? [];
        $perDayDeduction = $structure->leave_salary_per_day ?? 0;

        // Fetch attendance records for the period
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // Calculate working days (excluding weekends)
        $totalWorkingDays = $this->getWorkingDaysInRange($startDate, $endDate);

        // Attendance stats
        $daysPresent = $attendances->filter(fn ($att) => strtolower($att->status) === 'present')->count();
        $daysAbsent = $attendances->filter(fn ($att) => strtolower($att->status) === 'absent')->count();
        $lateCheckIns = $attendances->where('is_late', true)->count();
        $earlyCheckOuts = $attendances->where('is_early_leave', true)->count(); // Fixed: is_early_leave
        $totalLateMinutes = $attendances->sum('late_minutes');
        $totalEarlyMinutes = $attendances->sum('early_leave_minutes'); // Fixed: early_leave_minutes

        // If no attendance data exists
        $hasAttendanceData = $attendances->count() > 0;

        // Calculate deductions
        $absenceDeduction = 0;
        $lateDeduction = 0;
        $earlyDeduction = 0;
        $deductionDetails = [];

        // Absence deduction (absent days × per-day deduction)
        if ($daysAbsent > 0 && $perDayDeduction > 0) {
            $absenceDeduction = $daysAbsent * $perDayDeduction;
            $deductionDetails[] = [
                'name' => "Absence Deduction ({$daysAbsent} days)",
                'amount' => $absenceDeduction,
                'description' => "{$daysAbsent} absent days × Rs. {$perDayDeduction}",
            ];
        }

        // Late check-in penalty
        if ($lateCheckIns > 0) {
            $latePenalty = $policy['late_penalty_per_instance'] ?? 0;
            if ($latePenalty > 0) {
                $lateDeduction = $lateCheckIns * $latePenalty;
                $deductionDetails[] = [
                    'name' => "Late Check-in Penalty ({$lateCheckIns} times)",
                    'amount' => $lateDeduction,
                    'description' => "{$lateCheckIns} late check-ins × Rs. {$latePenalty}",
                ];
            }

            // Alternative: Use late rules if defined
            if (empty($latePenalty) && ! empty($policy['late_rules'])) {
                foreach ($attendances->where('is_late', true) as $att) {
                    $deduction = $this->calculateLateDeduction(
                        $att->late_minutes ?? 0,
                        $policy['late_rules'],
                        $structure->base_salary / 30 // Per-day rate
                    );
                    $lateDeduction += $deduction;
                }
                if ($lateDeduction > 0) {
                    $deductionDetails[] = [
                        'name' => "Late Check-in Penalties ({$lateCheckIns} times, {$totalLateMinutes} min)",
                        'amount' => $lateDeduction,
                        'description' => 'Calculated based on late rules',
                    ];
                }
            }
        }

        // Early check-out penalty
        if ($earlyCheckOuts > 0) {
            $earlyPenalty = $policy['early_penalty_per_instance'] ?? 0;
            if ($earlyPenalty > 0) {
                $earlyDeduction = $earlyCheckOuts * $earlyPenalty;
                $deductionDetails[] = [
                    'name' => "Early Check-out Penalty ({$earlyCheckOuts} times)",
                    'amount' => $earlyDeduction,
                    'description' => "{$earlyCheckOuts} early check-outs × Rs. {$earlyPenalty}",
                ];
            }

            // Alternative: Use early rules if defined
            if (empty($earlyPenalty) && ! empty($policy['early_rules'])) {
                foreach ($attendances->where('is_early_leave', true) as $att) { // Fixed: is_early_leave
                    $deduction = $this->calculateEarlyDeduction(
                        $att->early_leave_minutes ?? 0, // Fixed: early_leave_minutes
                        $policy['early_rules'],
                        $structure->base_salary / 30
                    );
                    $earlyDeduction += $deduction;
                }
                if ($earlyDeduction > 0) {
                    $deductionDetails[] = [
                        'name' => "Early Check-out Penalties ({$earlyCheckOuts} times, {$totalEarlyMinutes} min)",
                        'amount' => $earlyDeduction,
                        'description' => 'Calculated based on early rules',
                    ];
                }
            }
        }

        $totalDeduction = $absenceDeduction + $lateDeduction + $earlyDeduction;

        return [
            'total' => $totalDeduction,
            'details' => $deductionDetails,
            'breakdown' => [
                'has_data' => $hasAttendanceData,
                'total_working_days' => $totalWorkingDays,
                'days_present' => $daysPresent,
                'days_absent' => $daysAbsent,
                'late_check_ins' => $lateCheckIns,
                'early_check_outs' => $earlyCheckOuts,
                'total_late_minutes' => $totalLateMinutes,
                'total_early_minutes' => $totalEarlyMinutes,
                'absence_deduction' => $absenceDeduction,
                'late_deduction' => $lateDeduction,
                'early_deduction' => $earlyDeduction,
            ],
        ];
    }

    /**
     * Calculate working days in a date range (excluding weekends)
     */
    private function getWorkingDaysInRange(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Exclude Saturdays (6) and Sundays (0)
            if (! in_array($current->dayOfWeek, [0, 6])) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate monthly payroll for a salaried employee
     */
    public function calculateMonthlyPayroll(Employee $employee, string $month): array
    {
        // Get effective salary structure (handles custom assignments)
        $structure = $this->getEffectiveSalaryStructure($employee);

        if (! $structure) {
            throw new \Exception('Employee has no salary structure assigned.');
        }

        $baseSalary = $structure->base_salary ?? 0;

        // Calculate allowances (supports fixed and percentage types)
        $allowanceData = $this->calculateAllowances($structure);
        $activeAllowances = $allowanceData['total'];
        $allowanceDetails = $allowanceData['details'];

        // Calculate fixed deductions
        $fixedDeductionData = $this->calculateFixedDeductions($structure);
        $activeFixedDeductions = $fixedDeductionData['total'];
        $fixedDeductionDetails = $fixedDeductionData['details'];

        // Calculate attendance-based deductions
        $attendanceDeductionData = $this->calculateMonthlyAttendanceDeductions($employee, $month, $structure);
        $attendanceDeductions = $attendanceDeductionData['total'];
        $attendanceBreakdown = $attendanceDeductionData['breakdown'];

        // Add attendance deduction details to deduction details
        $allDeductionDetails = array_merge($fixedDeductionDetails, $attendanceDeductionData['details']);

        // Calculate gross salary
        $grossSalary = $baseSalary + $activeAllowances;

        // Calculate total deductions
        $totalDeductions = $activeFixedDeductions + $attendanceDeductions;

        // Calculate net salary (prevent negative)
        $netSalary = max(0, $grossSalary - $totalDeductions);

        return [
            'payroll_type' => 'monthly',
            'month' => $month,
            'basic_salary' => $baseSalary,
            'gross_salary' => $grossSalary,
            'allowances' => $activeAllowances,
            'deductions' => $activeFixedDeductions,
            'attendance_deductions' => $attendanceDeductions,
            'manual_deductions' => 0,
            'manual_allowances' => 0,
            'carried_forward_deduction' => 0,
            'bonuses' => 0,
            'net_salary' => $netSalary,
            'auto_generated' => true,
            'status' => 'generated',
            'allowance_details' => $allowanceDetails,
            'deduction_details' => $allDeductionDetails,
            'attendance_breakdown' => $attendanceBreakdown,
        ];
    }

    /**
     * Calculate daily payroll for a daily wage employee
     */
    public function calculateDailyPayroll(Employee $employee, Attendance $attendance): array
    {
        // Get effective salary structure
        $structure = $this->getEffectiveSalaryStructure($employee);

        if (! $structure || ! $structure->use_daily_wages) {
            throw new \Exception('Employee is not configured for daily wages.');
        }

        $dailyRate = $structure->daily_wages;
        $policy = $structure->attendance_deduction_policy ?? [];
        $carryForward = $structure->carry_forward_deductions ?? false;

        // Start with the daily rate
        $dayEarning = $dailyRate;
        $dayDeduction = 0;
        $deductionDetails = [];

        // Apply late check-in deductions
        if (($attendance->late_minutes ?? 0) > 0) {
            $lateDeduction = $this->calculateLateDeduction(
                $attendance->late_minutes,
                $policy['late_rules'] ?? [],
                $dailyRate
            );

            if ($lateDeduction > 0) {
                $dayDeduction += $lateDeduction;
                $deductionDetails[] = [
                    'name' => 'Late Check-in ('.$attendance->late_minutes.' min)',
                    'amount' => $lateDeduction,
                    'description' => 'Late arrival deduction for '.Carbon::parse($attendance->date)->format('M d, Y'),
                ];
            }
        }

        // Apply early check-out deductions
        if (($attendance->early_leave_minutes ?? 0) > 0) {
            $earlyDeduction = $this->calculateEarlyDeduction(
                $attendance->early_leave_minutes,
                $policy['early_rules'] ?? [],
                $dailyRate
            );

            if ($earlyDeduction > 0) {
                $dayDeduction += $earlyDeduction;
                $deductionDetails[] = [
                    'name' => 'Early Leave ('.$attendance->early_leave_minutes.' min)',
                    'amount' => $earlyDeduction,
                    'description' => 'Early departure deduction for '.Carbon::parse($attendance->date)->format('M d, Y'),
                ];
            }
        }

        // Handle carried forward deductions from previous day
        $carriedForwardDeduction = $employee->pending_deductions ?? 0;
        $totalDeductions = $dayDeduction + $carriedForwardDeduction;

        // Determine net payable and remaining carry-forward
        $netSalary = 0;
        $newPendingDeductions = 0;

        if ($totalDeductions <= $dayEarning) {
            // Can pay full amount after deductions
            $netSalary = $dayEarning - $totalDeductions;
            $newPendingDeductions = 0;
        } else {
            // Deductions exceed daily earning
            if ($carryForward) {
                // Carry forward is allowed
                $netSalary = 0;
                $newPendingDeductions = $totalDeductions - $dayEarning;
            } else {
                // Carry forward not allowed - cap deductions at daily earning
                $netSalary = 0;
                $newPendingDeductions = 0;
                $totalDeductions = $dayEarning;
            }
        }

        // Prevent negative salary
        $netSalary = max(0, $netSalary);

        return [
            'payroll_type' => 'daily',
            'month' => Carbon::parse($attendance->date)->format('Y-m-d'), // Store full date for daily
            'basic_salary' => $dailyRate,
            'gross_salary' => $dailyRate,
            'allowances' => 0,
            'deductions' => 0, // Fixed deductions don't apply to daily
            'attendance_deductions' => $dayDeduction,
            'manual_deductions' => 0,
            'manual_allowances' => 0,
            'carried_forward_deduction' => $carriedForwardDeduction,
            'bonuses' => 0,
            'net_salary' => $netSalary,
            'auto_generated' => true,
            'status' => 'generated',
            'carried_forward_to_next' => $newPendingDeductions,
            'new_pending_deductions' => $newPendingDeductions,
            'deduction_details' => $deductionDetails,
            'allowance_details' => [],
            'attendance_breakdown' => [
                'has_data' => true,
                'date' => $attendance->date,
                'status' => $attendance->status,
                'check_in' => $attendance->clock_in,
                'check_out' => $attendance->clock_out,
                'is_late' => $attendance->is_late ?? false,
                'is_early_out' => $attendance->is_early_leave ?? false,
                'late_minutes' => $attendance->late_minutes ?? 0,
                'early_checkout_minutes' => $attendance->early_leave_minutes ?? 0,
                'total_deduction' => $dayDeduction,
            ],
        ];
    }

    /**
     * Calculate late check-in deduction
     */
    private function calculateLateDeduction(int $lateMinutes, array $rules, float $dailyRate): float
    {
        if (empty($rules)) {
            return 0;
        }

        foreach ($rules as $rule) {
            $min = $rule['min_minutes'] ?? 0;
            $max = $rule['max_minutes'] ?? null;

            if ($lateMinutes >= $min && (is_null($max) || $lateMinutes <= $max)) {
                $amount = $rule['amount'] ?? 0;
                $type = $rule['type'] ?? 'fixed';

                if ($type === 'percentage') {
                    return ($dailyRate * $amount) / 100;
                } else {
                    return floatval($amount);
                }
            }
        }

        return 0;
    }

    /**
     * Calculate early check-out deduction
     */
    private function calculateEarlyDeduction(int $earlyMinutes, array $rules, float $dailyRate): float
    {
        if (empty($rules)) {
            return 0;
        }

        foreach ($rules as $rule) {
            $min = $rule['min_minutes'] ?? 0;
            $max = $rule['max_minutes'] ?? null;

            if ($earlyMinutes >= $min && (is_null($max) || $earlyMinutes <= $max)) {
                $amount = $rule['amount'] ?? 0;
                $type = $rule['type'] ?? 'fixed';

                if ($type === 'percentage') {
                    return ($dailyRate * $amount) / 100;
                } else {
                    return floatval($amount);
                }
            }
        }

        return 0;
    }

    /**
     * Save payroll details (allowances and deductions breakdown)
     */
    public function savePayrollDetails(Payroll $payroll, array $allowanceDetails, array $deductionDetails): void
    {
        // Save allowances
        foreach ($allowanceDetails as $detail) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'allowance',
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);
        }

        // Save deductions
        foreach ($deductionDetails as $detail) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);
        }
    }

    /**
     * Update employee's pending deductions
     */
    public function updatePendingDeductions(Employee $employee, float $newPendingDeductions): void
    {
        $employee->update([
            'pending_deductions' => $newPendingDeductions,
        ]);
    }
}
