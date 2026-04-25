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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('total_m2', 15, 4)->nullable()->change();
            $table->decimal('pieces_per_m2', 15, 6)->nullable()->change();
            $table->decimal('pieces_per_box', 15, 4)->nullable()->change();
            $table->decimal('height', 15, 4)->nullable()->change();
            $table->decimal('width', 15, 4)->nullable()->change();
            $table->decimal('length_value', 15, 4)->nullable()->change();
            $table->decimal('weight_per_unit', 15, 4)->nullable()->change();
            $table->decimal('purchase_price_per_m2', 15, 2)->nullable()->change();
            $table->decimal('purchase_price_per_piece', 15, 2)->nullable()->change();
            $table->decimal('sale_price_per_box', 15, 2)->nullable()->change();
            $table->decimal('sale_price_per_piece', 15, 2)->nullable()->change();
            $table->decimal('total_stock_qty', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Keep nullable for safety or restore previous state if known
        });
    }
};
