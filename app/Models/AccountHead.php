<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'head_id');
    }
}
