<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'price',
        'item_discount',
        'qty',
        'unit',
        'line_total',
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

