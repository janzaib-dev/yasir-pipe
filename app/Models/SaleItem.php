<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'warehouse_id', 'product_id', 'product_package_id',
        'brand_id', 'category_id', 'sub_category_id', 'unit_id',
        'qty', 'price', 'total',
        'discount_percent', 'discount_amount',
        'color', 'total_pieces', 'loose_pieces',
        'price_per_piece', 'price_per_m2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function package()
    {
        return $this->belongsTo(ProductPackage::class, 'product_package_id');
    }
}
