<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

// use Spatie\Permission\Models\Role;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the employee profile associated with this user
     */
    public function employee()
    {
        return $this->hasOne(\App\Models\Hr\Employee::class);
    }

    /**
     * Check if user's employee profile is active (can login)
     * Returns true if user has no employee profile (admin/non-employee users)
     * Returns false if employee status is non-active or terminated
     */
    public function isEmployeeActive()
    {
        $employee = $this->employee;

        // If no employee profile, user can login (admin users, etc.)
        if (! $employee) {
            return true;
        }

        // Only active employees can login
        return $employee->status === 'active';
    }

    //    public function roles()
    //     {
    //         return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
    //                     ->where('model_type', User::class);
    //     }

}
