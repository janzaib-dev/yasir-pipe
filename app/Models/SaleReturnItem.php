<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_return_id',
        'product_id',
        'warehouse_id',
        'qty',
        'boxes',
        'loose_pieces',
        'price',
        'item_discount',
        'unit',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'boxes' => 'decimal:2',
        'price' => 'decimal:2',
        'item_discount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
