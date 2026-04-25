<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $table = 'hr_salary_structures';

    protected $fillable = [
        'name',
        'parent_structure_id',
        'employee_id',
        'salary_type',
        'base_salary',
        'daily_wages',
        'use_daily_wages',
        'commission_percentage',
        'sales_target',
        'commission_tiers',
        'allowances',
        'deductions',
        'attendance_deduction_policy',
        'carry_forward_deductions',
        'leave_salary_per_day',
    ];

    protected $casts = [
        'use_daily_wages' => 'boolean',
        'carry_forward_deductions' => 'boolean',
        'allowances' => 'array',
        'deductions' => 'array',
        'attendance_deduction_policy' => 'array',
        'commission_tiers' => 'array',
    ];

    /**
     * Legacy relationship (for backward compatibility)
     * @deprecated Use employees() or assignedEmployees() instead
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Many-to-many: All employee assignments (history)
     */
    public function employees()
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_salary_structures'
        )->withPivot(['start_date', 'end_date', 'is_active', 'assigned_by', 'notes'])
         ->withTimestamps()
         ->using(EmployeeSalaryStructure::class);
    }

    /**
     * Get only currently assigned employees
     */
    public function assignedEmployees()
    {
        return $this->belongsToMany(
            Employee::class,
            'employee_salary_structures'
        )->wherePivot('is_active', true)
         ->wherePivotNull('end_date')
         ->withPivot(['start_date', 'end_date', 'is_active', 'assigned_by', 'notes'])
         ->withTimestamps();
    }

    /**
     * Get all assignment records
     */
    public function assignments()
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    /**
     * Get count of currently assigned employees
     */
    public function getAssignedCountAttribute()
    {
        return $this->assignedEmployees()->count();
    }

    /**
     * Calculate total allowances
     */
    public function getTotalAllowancesAttribute()
    {
        if (!$this->allowances) return 0;
        return collect($this->allowances)->sum('amount');
    }

    /**
     * Calculate total deductions
     */
    public function getTotalDeductionsAttribute()
    {
        if (!$this->deductions) return 0;
        return collect($this->deductions)->sum('amount');
    }

    /**
     * Calculate tiered commission based on sales
     * 
     * @param float $monthlySales Total sales for the month
     * @return float Total commission earned
     */
    public function calculateTieredCommission($monthlySales)
    {
        if (!$this->commission_tiers || $monthlySales <= 0) {
            return 0;
        }

        $totalCommission = 0;
        $remainingSales = $monthlySales;
        $previousThreshold = 0;

        // Sort tiers by upto_amount ascending
        $tiers = collect($this->commission_tiers)->sortBy('upto_amount')->values();

        foreach ($tiers as $tier) {
            $uptoAmount = floatval($tier['upto_amount'] ?? 0);
            $percentage = floatval($tier['percentage'] ?? 0);

            if ($uptoAmount <= 0 || $percentage <= 0) {
                continue;
            }

            // Calculate the sales amount in this tier
            $tierRange = $uptoAmount - $previousThreshold;
            $salesInTier = min($remainingSales, $tierRange);

            if ($salesInTier > 0) {
                $totalCommission += ($salesInTier * $percentage) / 100;
                $remainingSales -= $salesInTier;
            }

            $previousThreshold = $uptoAmount;

            if ($remainingSales <= 0) {
                break;
            }
        }

        // If there are remaining sales beyond the last tier, 
        // apply the last tier's percentage to the remaining amount
        if ($remainingSales > 0 && $tiers->count() > 0) {
            $lastTier = $tiers->last();
            $lastPercentage = floatval($lastTier['percentage'] ?? 0);
            if ($lastPercentage > 0) {
                $totalCommission += ($remainingSales * $lastPercentage) / 100;
            }
        }

        return $totalCommission;
    }

    /**
     * Calculate total salary for a given month
     * 
     * @param float $monthlySales Total sales for the month
     * @param int $leaveDays Number of unpaid leave days
     * @return array Breakdown of salary components
     */
    public function calculateMonthlySalary($monthlySales = 0, $leaveDays = 0)
    {
        $breakdown = [
            'base_salary' => 0,
            'commission' => 0,
            'allowances' => $this->total_allowances,
            'deductions' => $this->total_deductions,
            'leave_deduction' => 0,
            'gross' => 0,
            'net' => 0,
        ];

        // Base salary (for 'salary' and 'both' types)
        if (in_array($this->salary_type, ['salary', 'both'])) {
            $breakdown['base_salary'] = $this->base_salary;
        }

        // Commission (for 'commission' and 'both' types)
        if (in_array($this->salary_type, ['commission', 'both'])) {
            // Use tiered commission if tiers exist
            if ($this->commission_tiers && count($this->commission_tiers) > 0) {
                $breakdown['commission'] = $this->calculateTieredCommission($monthlySales);
            } elseif ($monthlySales > 0 && $this->commission_percentage > 0) {
                // Fallback to flat percentage
                $breakdown['commission'] = ($monthlySales * $this->commission_percentage) / 100;
            }
        }

        // Leave deduction
        if ($leaveDays > 0 && $this->leave_salary_per_day > 0) {
            $breakdown['leave_deduction'] = $leaveDays * $this->leave_salary_per_day;
        }

        // Calculate totals
        $breakdown['gross'] = $breakdown['base_salary'] + $breakdown['commission'] + $breakdown['allowances'];
        $breakdown['net'] = $breakdown['gross'] - $breakdown['deductions'] - $breakdown['leave_deduction'];

        return $breakdown;
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_structure_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_structure_id');
    }
}
