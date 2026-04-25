<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_documents';

    protected $fillable = [
        'employee_id',
        'type',
        'file_path',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
