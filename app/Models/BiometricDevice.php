<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'model',
        'is_active',
        'last_sync_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get employees associated with this device
     */
    public function employees(): HasMany
    {
        return $this->hasMany(\App\Models\Hr\Employee::class, 'biometric_device_id');
    }

    /**
     * Scope to get only active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get connection status
     */
    public function getConnectionStatusAttribute(): string
    {
        // This will be updated by the service when testing connection
        return 'Unknown';
    }
}
