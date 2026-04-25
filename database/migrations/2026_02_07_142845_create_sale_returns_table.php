<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('return_invoice')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->date('return_date');

            // Financial Fields
            $table->decimal('bill_amount', 15, 2)->default(0);
            $table->decimal('item_discount', 15, 2)->default(0);
            $table->decimal('extra_discount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->string('status')->default('posted'); // posted, draft

            $table->timestamps();

            // Foreign Keys
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
