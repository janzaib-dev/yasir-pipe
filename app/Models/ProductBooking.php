<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBooking extends Model
{
    protected $fillable = [
        'customer', 'reference', 'product', 'product_code', 'brand', 'unit',
        'per_price', 'per_discount', 'qty', 'per_total', 'color',
        'total_amount_Words', 'total_bill_amount', 'total_extradiscount',
        'total_net', 'cash', 'card', 'change', 'total_items'
    ];

    public function customer_relation()
    {
        return $this->belongsTo(Customer::class, 'customer', 'id');
    }
}

