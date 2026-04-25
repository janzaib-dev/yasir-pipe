<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $table = 'hr_payroll_details';

    protected $fillable = [
        'payroll_id',
        'type',
        'name',
        'amount',
        'description',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
