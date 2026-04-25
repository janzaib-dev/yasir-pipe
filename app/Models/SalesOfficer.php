<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOfficer extends Model
{
     use HasFactory;

    protected $fillable = [
        'name', 'name_urdu', 'mobile',
    ];
}
