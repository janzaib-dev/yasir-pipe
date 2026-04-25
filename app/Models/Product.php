<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // protected $fillable = [
    //     'creater_id', 'category_id', 'sub_category_id', 'item_code', 'item_name', 'size',
    //     'opening_carton_quantity', 'carton_quantity', 'loose_pieces', 'pcs_in_carton',
    //     'wholesale_price', 'retail_price', 'initial_stock', 'alert_quantity'
    // ];
    // app/Models/Product.php
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_stocks', 'product_id', 'warehouse_id')
            ->withPivot('quantity', 'price', 'remarks');
    }

    // app/Models/Product.php

    public function activeDiscount()
    {
        return $this->hasOne(ProductDiscount::class, 'product_id')
            ->where('status', 1); // only active discount
    }

    public function category_relation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sub_category_relation()
    {
        return $this->belongsTo(Subcategory::class, 'sub_category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(\App\Models\WarehouseStock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function packages()
    {
        return $this->hasMany(ProductPackage::class, 'product_id');
    }

    public function scopeWithAvailable($q)
    {
        return $q->withSum('movements as available_qty', 'qty'); // sum of ledger
    }
}
