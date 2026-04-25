<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'hr_payrolls';

    protected $fillable = [
        'employee_id',
        'payroll_type',
        'month',
        'basic_salary',
        'gross_salary',
        'allowances',
        'deductions',
        'attendance_deductions',
        'manual_deductions',
        'manual_allowances',
        'carried_forward_deduction',
        'carried_forward_to_next',
        'bonuses',
        'net_salary',
        'notes',
        'auto_generated',
        'status',
        'reviewed_by',
        'reviewed_at',
        'payment_date',
    ];

    protected $casts = [
        'auto_generated' => 'boolean',
        'reviewed_at' => 'datetime',
        'payment_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopeMonthly($query)
    {
        return $query->where('payroll_type', 'monthly');
    }

    public function scopeDaily($query)
    {
        return $query->where('payroll_type', 'daily');
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Helper Methods
     */
    public function canEdit()
    {
        return $this->status !== 'paid';
    }

    public function canMarkReviewed()
    {
        return $this->status === 'generated';
    }

    public function canMarkPaid()
    {
        return in_array($this->status, ['generated', 'reviewed']);
    }

    public function isMonthly()
    {
        return $this->payroll_type === 'monthly';
    }

    public function isDaily()
    {
        return $this->payroll_type === 'daily';
    }

    /**
     * Accessors
     */
    public function getTotalAllowancesAttribute()
    {
        return $this->allowances + $this->manual_allowances;
    }

    public function getTotalDeductionsAttribute()
    {
        return $this->deductions + 
               $this->attendance_deductions + 
               $this->manual_deductions + 
               $this->carried_forward_deduction;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'generated' => '<span class="hr-tag warning">Generated</span>',
            'reviewed' => '<span class="hr-tag info">Reviewed</span>',
            'paid' => '<span class="hr-tag success">Paid</span>',
        ];

        return $badges[$this->status] ?? '';
    }

    public function getTypeBadgeAttribute()
    {
        $badges = [
            'monthly' => '<span class="hr-tag primary">Monthly</span>',
            'daily' => '<span class="hr-tag success">Daily</span>',
        ];

        return $badges[$this->payroll_type] ?? '';
    }
}
