<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'hr_holidays';

    protected $fillable = [
        'name',
        'date',
        'type',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Check if a specific date is a holiday
     */
    public static function isHoliday($date)
    {
        return self::whereDate('date', $date)->exists();
    }

    /**
     * Get holiday for a specific date
     */
    public static function getHoliday($date)
    {
        return self::whereDate('date', $date)->first();
    }

    /**
     * Get all holidays for a month
     */
    public static function getMonthHolidays($year, $month)
    {
        return self::whereYear('date', $year)
                   ->whereMonth('date', $month)
                   ->orderBy('date')
                   ->get();
    }

    /**
     * Get all holidays for a year
     */
    public static function getYearHolidays($year)
    {
        return self::whereYear('date', $year)
                   ->orderBy('date')
                   ->get();
    }
}
