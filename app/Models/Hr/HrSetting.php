<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HrSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("hr_setting_{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value): bool
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'type' => 'integer', 'group' => 'attendance']
        );

        // Clear cache
        Cache::forget("hr_setting_{$key}");

        return true;
    }

    /**
     * Get attendance punch gap in minutes
     */
    public static function getPunchGapMinutes(): int
    {
        return (int) self::getValue('attendance_punch_gap_minutes', 20);
    }

    /**
     * Cast value to correct type
     */
    protected static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => self::castValue($setting->value, $setting->type)];
        })->toArray();
    }
}
