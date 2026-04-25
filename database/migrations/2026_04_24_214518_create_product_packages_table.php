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
        Schema::create('product_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('symbol')->nullable();
            $table->boolean('is_fraction')->default(false);
            $table->string('sku')->nullable();
            $table->decimal('weight', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('length', 10, 4)->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('conversion_factor', 15, 6)->default(1);
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('sale_price', 15, 4)->default(0);
            $table->boolean('is_base')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_packages');
    }
};
