<?php
// database/migrations/2025_08_18_000001_create_vendor_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('admin_or_user_id');
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payment_method')->nullable(); // e.g., cash, bank, cheque
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_payments');
    }
}
