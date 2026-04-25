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
        if (!Schema::hasTable('receipts_vouchers')) {
            Schema::create('receipts_vouchers', function (Blueprint $table) {
                $table->id();

                // Header fields
                $table->string('rvid')->nullable();          // Receipt Voucher ID
                $table->date('receipt_date')->nullable();  // Receipt Date
                $table->date('entry_date')->nullable();    // Entry Date
                $table->string('type')->nullable();          // Type (vendor/customer/walkin/account head)
                $table->string('party_id')->nullable();      // Vendor/Customer/Account ID
                $table->string('tel')->nullable();           // Tel / Account Code
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
                $table->decimal('total_amount', 15, 2)->default(0);   // total
                $table->boolean('processed')->default(false);

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_vouchers')) {
            Schema::create('payment_vouchers', function (Blueprint $table) {
                $table->id();

                // Header fields
                $table->string('pvid')->nullable();          // Payment Voucher ID
                $table->date('receipt_date')->nullable();  // Receipt Date
                $table->date('entry_date')->nullable();    // Entry Date
                $table->string('type')->nullable();          // Type (vendor/customer/walkin/account head)
                $table->string('party_id')->nullable();      // Vendor/Customer/Account ID
                $table->string('tel')->nullable();           // Tel / Account Code
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
                $table->decimal('total_amount', 15, 2)->default(0);   // total

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('expense_vouchers')) {
            Schema::create('expense_vouchers', function (Blueprint $table) {
                $table->id();

                // Header fields
                $table->string('evid')->nullable();          // Expense Voucher ID
                $table->date('entry_date')->nullable();    // Entry Date
                $table->string('type')->nullable();          // Type (vendor/customer/walkin/account head)
                $table->string('party_id')->nullable();      // Vendor/Customer/Account ID
                $table->string('tel')->nullable();           // Tel / Account Code
                $table->text('remarks')->nullable();       // Remarks

                // Row-wise data (JSON store)
                $table->text('narration_id')->nullable();   // narration json
                $table->text('row_account_head')->nullable(); // account head json
                $table->text('row_account_id')->nullable();   // account json
                // Footer total
                $table->text('amount')->nullable();   // JSON amount
                $table->decimal('total_amount', 15, 2)->default(0);   // total

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_vouchers');
        Schema::dropIfExists('payment_vouchers');
        Schema::dropIfExists('receipts_vouchers');
    }
};
