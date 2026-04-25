<?php
// database/migrations/2025_08_18_000004_create_customer_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_or_user_id');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_payments');
    }
}
