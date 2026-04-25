<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'discount'      => 'decimal:2',
        'extra_cost'    => 'decimal:2',
        'net_amount'    => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'due_amount'    => 'decimal:2',
    ];

    public function branch()   { return $this->belongsTo(Branch::class); }
    public function warehouse(){ return $this->belongsTo(Warehouse::class); }
    public function vendor()   { return $this->belongsTo(Vendor::class, 'vendor_id'); }
    public function items()    { return $this->hasMany(PurchaseItem::class); }
    public function returns()  { return $this->hasMany(PurchaseReturn::class); }
}
