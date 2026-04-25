<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    //     protected $fillable = [
    //     'voucher_type', 'date', 'sales_officer', 'type', 'party',
    //     'sub_head', 'narration', 'amount'
    // ];
    protected $fillable = [
        'voucher_type', 'date', 'sales_officer', 'type', 'person',
        'sub_head', 'narration', 'amount', 'status'
    ];

}
