<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function master()
    {
        return $this->belongsTo(VoucherMaster::class, 'voucher_master_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
