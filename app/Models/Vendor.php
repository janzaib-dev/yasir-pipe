<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{protected $fillable = ['name', 'email', 'phone', 'address','opening_balance']; 
    use HasFactory;

      public function ledger()
    {
        return $this->hasOne(VendorLedger::class);
    }
}
