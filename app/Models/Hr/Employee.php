<?php

namespace App\Models\Hr;

use App\Models\User;
use App\Models\Hr\EmployeeSalaryStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'hr_employees';

    protected $fillable = [
        'user_id',
        'department_id',
        'designation_id',
        'shift_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'is_docs_submitted',
        'date_of_birth',
        'joining_date',
        'status',
        'custom_start_time',
        'custom_end_time',
        'face_encoding',
        'face_photo',
        'biometric_device_id',
        'device_user_id',
        'fingerprint_enrolled_at',
        'last_device_sync_at',
        'punch_gap_minutes',
        'pending_deductions',
    ];

    protected $casts = [
        'face_encoding' => 'array',
        'fingerprint_enrolled_at' => 'datetime',
        'last_device_sync_at' => 'datetime',
    ];

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function getDocument($type)
    {
        return $this->documents()->where('type', $type)->first()->file_path ?? null;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Legacy relationship (for backward compatibility)
     *
     * @deprecated Use salaryStructures() or activeSalaryStructure() instead
     */
    public function salaryStructure()
    {
        return $this->hasOne(SalaryStructure::class);
    }

    /**
     * Many-to-many: All salary structure assignments (history)
     */
    public function salaryStructures()
    {
        return $this->belongsToMany(
            SalaryStructure::class,
            'employee_salary_structures'
        )->withPivot(['start_date', 'end_date', 'is_active', 'assigned_by', 'notes'])
            ->withTimestamps()
            ->using(EmployeeSalaryStructure::class);
    }

    /**
     * Get active salary structure assignment
     */
    public function activeSalaryStructure()
    {
        return $this->hasOne(EmployeeSalaryStructure::class)
            ->where('is_active', true)
            ->whereNull('end_date')
            ->with('salaryStructure')
            ->latest('start_date');
    }

    /**
     * Get the actual salary structure related to the active assignment.
     */
    public function getSalaryStructureAttribute()
    {
        return $this->activeSalaryStructure ? $this->activeSalaryStructure->salaryStructure : null;
    }

    /**
     * Get all salary structure assignment records
     */
    public function salaryStructureAssignments()
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the effective start time (custom or shift)
     */
    public function getStartTime()
    {
        if ($this->custom_start_time) {
            return $this->custom_start_time;
        }

        return $this->shift ? $this->shift->start_time : '09:00:00';
    }

    /**
     * Get the effective end time (custom or shift)
     */
    public function getEndTime()
    {
        if ($this->custom_end_time) {
            return $this->custom_end_time;
        }

        return $this->shift ? $this->shift->end_time : '18:00:00';
    }

    /**
     * Get grace minutes from shift or default
     */
    public function getGraceMinutes()
    {
        return $this->shift ? $this->shift->grace_minutes : 15;
    }

    /**
     * Check if employee has registered face
     */
    public function hasFaceRegistered()
    {
        return ! empty($this->face_encoding);
    }

    /**
     * Get biometric device relationship
     */
    public function biometricDevice()
    {
        return $this->belongsTo(\App\Models\BiometricDevice::class, 'biometric_device_id');
    }

    /**
     * Check if employee has fingerprint enrolled on device
     */
    public function hasFingerprint()
    {
        return ! empty($this->device_user_id) && ! empty($this->fingerprint_enrolled_at);
    }
}
