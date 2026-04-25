<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price'         => 'decimal:2',
        'item_discount' => 'decimal:2',
        'line_total'    => 'decimal:2',
    ];

    
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product()  { return $this->belongsTo(Product::class); }

    public function package()
    {
        return $this->belongsTo(ProductPackage::class, 'product_package_id');
    }
}
