<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
    'purchase_id',
    'vendor_id',
    'warehouse_id',
    'return_invoice',
    'return_date',
    'return_reason',
    'transport',
    'vehicle_no',
    'driver_name',
    'delivery_person',
    'bill_amount',
    'item_discount',
    'extra_discount',
    'net_amount',
    'paid',
    'balance',
    'remarks',
];
 public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // ✅ Warehouse Relationship
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ✅ Return Items
    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
