<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Purchase extends Model
{
    use HasFactory;
    use SoftDeletes;
    // app/Models/Purchase.php
    protected $table = 'purchases'; // if it's not default

   

    protected $fillable = [
        'invoice_no',
        'supplier',
        'purchase_date',
        'warehouse_id',
        'item_category',
        'item_name',
        'quantity',
        'price',
        'total',
        'note',
        'unit',
        'total_price',
        'discount',
        'Payable_amount',
        'paid_amount',
        'due_amount',
        'status',
        'is_return'
    ];
}
