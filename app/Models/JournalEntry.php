<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    // The primary source of truth for all Financial Reporting.
    
    protected $guarded = [];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // Polymorphic relation to Source (VoucherMaster, Sale, Purchase, etc.)
    public function source()
    {
        return $this->morphTo();
    }
    // Polymorphic relation to Party (Customer, Vendor, etc.)
    public function party()
    {
        return $this->morphTo();
    }
}
