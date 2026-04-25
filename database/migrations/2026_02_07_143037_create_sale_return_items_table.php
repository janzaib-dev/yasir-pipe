<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_return_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            
            // Quantity Fields - Support Decimal Boxes
            $table->decimal('qty', 15, 2)->comment('Total pieces returned');
            $table->decimal('boxes', 15, 2)->default(0)->comment('Box quantity (can be decimal like 1.2)');
            $table->integer('loose_pieces')->default(0)->comment('Loose pieces');
            
            // Pricing
            $table->decimal('price', 15, 2)->default(0)->comment('Price per piece');
            $table->decimal('item_discount', 15, 2)->default(0);
            $table->string('unit')->default('pc');
            $table->decimal('line_total', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
