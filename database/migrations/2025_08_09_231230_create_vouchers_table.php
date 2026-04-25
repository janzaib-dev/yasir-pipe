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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
    $table->string('voucher_type'); // expense, receipt, journal, payment, income
    $table->string('sales_officer');
    $table->date('date');
    $table->string('type'); // customer, sub-customer, supplier
    $table->string('person');
    $table->string('sub_head');
    $table->text('narration')->nullable();
    $table->decimal('amount', 15, 2);
    $table->enum('status', ['draft', 'posted'])->default('draft');
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
