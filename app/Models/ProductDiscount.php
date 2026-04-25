<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'actual_price', 'discount_percentage', 'discount_amount', 'final_price', 'status'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
