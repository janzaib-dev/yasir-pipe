<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptsVoucher extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function generateRVID($userId)
    {
        $last = self::latest('id')->first();
        $nextId = $last ? $last->id + 1 : 1;
        return 'RVID-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
    
    public function mainCustomer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function subCustomer()
    {
         // Assuming sub customers are also in customers table or handled via same logic
        return $this->belongsTo(Customer::class, 'party_id');
    }
}
