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
            if (! Schema::hasColumn('products', 'sale_price_per_piece')) {
                $table->decimal('sale_price_per_piece', 15, 2)->nullable()->default(0)->after('purchase_price_per_piece');
            }
            if (! Schema::hasColumn('products', 'purchase_price_per_box')) {
                $table->decimal('purchase_price_per_box', 15, 2)->nullable()->default(0)->after('purchase_price_per_piece');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sale_price_per_piece')) {
                $table->dropColumn('sale_price_per_piece');
            }
            if (Schema::hasColumn('products', 'purchase_price_per_box')) {
                $table->dropColumn('purchase_price_per_box');
            }
        });
    }
};
