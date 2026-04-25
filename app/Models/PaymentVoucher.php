<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucher extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function generateInvoiceNo()
    {
        $last = self::latest('id')->first();
        $nextId = $last ? $last->id + 1 : 1;
        return 'PVID-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
