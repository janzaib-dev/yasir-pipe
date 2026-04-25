<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expense_vouchers', function (Blueprint $table) {
            $table->id();

            // Header fields
            $table->text('evid')->nullable();          // Receipt Voucher ID
            $table->text('entry_date')->nullable();    // Entry Date
            $table->text('type')->nullable();          // Type (vendor/customer/walkin/account head)
            $table->text('party_id')->nullable();      // Vendor/Customer/Account ID
            $table->text('tel')->nullable();           // Tel / Account Code
            $table->text('remarks')->nullable();       // Remarks

            // Row-wise data (JSON store)
            $table->text('narration_id')->nullable();   // narration json
            $table->text('row_account_head')->nullable(); // account head json
            $table->text('row_account_id')->nullable();   // account json
            // Footer total
            $table->text('amount')->nullable();   // total
            $table->text('total_amount')->nullable();   // total

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_vouchers');
    }
};
