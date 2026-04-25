<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOnhand extends Model
{
    protected $table = 'v_stock_onhand';
    public $timestamps = false;

    // View ka primary key natural nahi hota; yahan product_id ko key treat kar lein
    protected $primaryKey = 'product_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['product_id', 'onhand_qty'];
}
