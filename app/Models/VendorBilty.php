<?php

// app/Models/VendorBilty.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorBilty extends Model
{
    protected $fillable = [
        'vendor_id',
        'purchase_id',
        'bilty_no',
        'vehicle_no',
        'transporter_name',
        'delivery_date',
        'note',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
