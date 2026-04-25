<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'hr_loans';

    protected $fillable = [
        'employee_id',
        'amount',
        'installment_amount',
        'status',
        'reason',
        'paid_amount'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class, 'loan_id');
    }

    public function scheduledDeductions()
    {
        return $this->hasMany(LoanScheduledDeduction::class, 'loan_id');
    }

    // Accessors
    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'approved')->whereRaw('paid_amount < amount');
    }
}
