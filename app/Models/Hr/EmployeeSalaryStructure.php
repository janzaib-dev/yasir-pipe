<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    use HasFactory;
    
    protected $table = 'employee_salary_structures';

    protected $fillable = [
        'employee_id',
        'salary_structure_id',
        'start_date',
        'end_date',
        'is_active',
        'is_custom',
        'assigned_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('end_date');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForStructure($query, $structureId)
    {
        return $query->where('salary_structure_id', $structureId);
    }

    /**
     * Helper Methods
     */
    public function endAssignment($endDate = null)
    {
        $this->update([
            'end_date' => $endDate ?? now()->toDateString(),
            'is_active' => false,
        ]);
    }
}
