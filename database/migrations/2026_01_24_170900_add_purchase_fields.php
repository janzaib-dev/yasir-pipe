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
            // New purchase fields and generic price fields support
            // For by_size: 'price_per_m2' exists. Need 'purchase_price_per_m2'
            // For by_cartons/by_pieces: need 'sale_price_per_box', 'purchase_price_per_piece'

            // By Size - Purchase
            $table->decimal('purchase_price_per_m2', 12, 2)->default(0)->after('price_per_m2');

            // By Cartons/Pieces - Sale & Purchase
            $table->decimal('sale_price_per_box', 12, 2)->default(0)->after('price_per_m2')->comment('Used for By Cartons and By Pieces');
            $table->decimal('purchase_price_per_piece', 12, 2)->default(0)->after('sale_price_per_box')->comment('Used for By Cartons and By Pieces');

            // Totals - Purchase (Sale total exists as total_price)
            $table->decimal('total_purchase_price', 15, 2)->default(0)->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_price_per_m2',
                'sale_price_per_box',
                'purchase_price_per_piece',
                'total_purchase_price',
            ]);
        });
    }
};
