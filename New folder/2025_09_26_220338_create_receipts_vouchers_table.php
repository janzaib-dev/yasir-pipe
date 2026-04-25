<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts_vouchers', function (Blueprint $table) {
            $table->id();

            // Header fields
            $table->text('rvid')->nullable();          // Receipt Voucher ID
            $table->text('receipt_date')->nullable();  // Receipt Date
            $table->text('entry_date')->nullable();    // Entry Date
            $table->text('type')->nullable();          // Type (vendor/customer/walkin/account head)
            $table->text('party_id')->nullable();      // Vendor/Customer/Account ID
            $table->text('tel')->nullable();           // Tel / Account Code
            $table->text('remarks')->nullable();       // Remarks

            // Row-wise data (JSON store)
            $table->text('narration_id')->nullable();   // narration json
            $table->text('reference_no')->nullable();   // reference json
            $table->text('row_account_head')->nullable(); // account head json
            $table->text('row_account_id')->nullable();   // account json
            $table->text('discount_value')->nullable(); // discount json
            $table->text('kg')->nullable();             // kg json
            $table->text('rate')->nullable();           // rate json
            $table->text('amount')->nullable();         // amount json

            // Footer total
            $table->text('total_amount')->nullable();   // total

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts_vouchers');
    }
};
