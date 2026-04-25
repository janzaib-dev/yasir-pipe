<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'hr_attendances';

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'check_in_time',
        'check_out_time',
        'check_in_photo',
        'check_out_photo',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_location',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_location',
        'status',
        'is_late',
        'late_minutes',
        'is_early_in',
        'early_in_minutes',
        'is_early_leave',
        'early_leave_minutes',
        'total_hours',
        'device_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
