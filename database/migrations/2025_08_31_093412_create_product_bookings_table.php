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
    Schema::create('product_bookings', function (Blueprint $table) {
    $table->id();
    $table->text('customer');
    $table->text('reference')->nullable();
    $table->text('product');
    $table->text('product_code');
    $table->text('brand');
    $table->text('unit');
    $table->text('per_price');
    $table->text('per_discount');
    $table->text('qty');
    $table->text('per_total');
    $table->text('color');
    $table->text('total_amount_Words');
    $table->text('total_bill_amount');
    $table->text('total_extradiscount');
    $table->text('total_net');
    $table->text('cash');
    $table->text('card');
    $table->text('change');
    $table->text('total_items');
    $table->text('booking_date');
    $table->text('sale_date');
    $table->timestamps();
});



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bookings');
    }
};
