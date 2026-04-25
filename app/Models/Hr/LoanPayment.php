<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $table = 'hr_loan_payments';

    protected $fillable = [
        'loan_id',
        'amount',
        'payment_date',
        'type', // salary_deduction, bank_transfer, cash
        'notes'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
