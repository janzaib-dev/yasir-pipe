<?php

// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_id', 'reference', 'total_amount_Words', 'total_bill_amount',
        'total_extradiscount', 'total_net', 'cash', 'card', 'change',
        'total_items', 'discount_type', 'sale_status', 'invoice_no'
    ];

    public function customer_relation()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function product_relation()
    {
        return $this->belongsTo(Product::class, 'product', 'id');
    }
    public static function generateInvoiceNo()
    {
        $lastSale = self::orderBy('id', 'desc')->first();

        if (!$lastSale || !$lastSale->invoice_no) {
            return 'INV-0001';
        }

        // Extract numeric part
        $lastNumber = (int) str_replace('INV-', '', $lastSale->invoice_no);

        // Increment + format
        return 'INV-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }
}
