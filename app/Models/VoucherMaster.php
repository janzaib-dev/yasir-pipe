<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherMaster extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    // Type Constants
    const TYPE_RECEIPT = 'receipt';
    const TYPE_PAYMENT = 'payment';
    const TYPE_EXPENSE = 'expense';
    const TYPE_JOURNAL = 'journal';
    const TYPE_CONTRA = 'contra';

    /**
     * Relationships
     */
    public function details()
    {
        return $this->hasMany(VoucherDetail::class, 'voucher_master_id');
    }

    // Polymorphic relation to Party (Customer, Vendor, etc.)
    public function party()
    {
        return $this->morphTo();
    }
    
    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
