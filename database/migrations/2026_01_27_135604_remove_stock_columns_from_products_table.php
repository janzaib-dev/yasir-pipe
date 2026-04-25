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
            $table->dropColumn([
                'boxes_quantity',
                'loose_pieces',
                'piece_quantity',
                'total_stock_qty',
                'total_price',
                'total_purchase_price',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('boxes_quantity')->nullable();
            $table->integer('loose_pieces')->nullable();
            $table->integer('piece_quantity')->nullable();
            $table->integer('total_stock_qty')->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->decimal('total_purchase_price', 15, 2)->nullable();
        });
    }
};
