<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = 'setting_' . $key;
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): bool
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }

        $setting->value = $value;
        $setting->save();

        // Clear cache
        Cache::forget('setting_' . $key);

        return true;
    }

    /**
     * Get all settings grouped by category
     */
    public static function getAllGrouped(): array
    {
        return self::all()->groupBy('group')->map(function ($settings) {
            return $settings->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => self::castValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'label' => $setting->label,
                    'description' => $setting->description,
                ];
            });
        })->toArray();
    }

    /**
     * Cast value to appropriate type
     */
    private static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
