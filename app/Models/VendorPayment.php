<?php

// app/Models/VendorPayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPayment extends Model
{
    protected $fillable = [
        'vendor_id',
        'admin_or_user_id',
        'payment_date',
        'amount',
        'payment_method',
        'note',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
