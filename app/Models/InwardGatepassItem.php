<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InwardGatepassItem extends Model
{
    use HasFactory;

      protected $fillable = ['inward_gatepass_id','product_id','qty'];

    public function gatepass()
    {
        return $this->belongsTo(InwardGatepass::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
