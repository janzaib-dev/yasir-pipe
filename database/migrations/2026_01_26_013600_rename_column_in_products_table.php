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
            // Check if modification is needed (for safety in mixed environments)
            if (Schema::hasColumn('products', 'sale_price_per_piece')) {
                DB::statement("ALTER TABLE products CHANGE sale_price_per_piece sale_price_per_box DECIMAL(12,2) DEFAULT 0");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sale_price_per_box')) {
                DB::statement("ALTER TABLE products CHANGE sale_price_per_box sale_price_per_piece DECIMAL(12,2) DEFAULT 0");
            }
        });
    }
};
