<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanScheduledDeduction extends Model
{
    use HasFactory;

    protected $table = 'hr_loan_scheduled_deductions';

    protected $fillable = [
        'loan_id',
        'amount',
        'deduction_month', // YYYY-MM
        'status', // pending, deducted, skipped
        'notes',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
