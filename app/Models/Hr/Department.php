<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'hr_departments';

    protected $fillable = ['name', 'description'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
