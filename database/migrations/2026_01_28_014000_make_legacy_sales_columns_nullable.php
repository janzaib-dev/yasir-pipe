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
        Schema::table('sales', function (Blueprint $table) {
            // Make legacy comma-separated columns nullable
            $table->text('product')->nullable()->change();
            $table->text('product_code')->nullable()->change();
            $table->text('brand')->nullable()->change();
            $table->text('unit')->nullable()->change();
            $table->text('per_price')->nullable()->change();
            $table->text('per_discount')->nullable()->change();
            $table->text('qty')->nullable()->change();
            $table->text('per_total')->nullable()->change();
            // Also nullable for booking related legacy columns if they exist
            if (Schema::hasColumn('sales', 'per_total_pieces')) {
                $table->text('per_total_pieces')->nullable()->change();
            }
            if (Schema::hasColumn('sales', 'per_price_per_piece')) {
                $table->text('per_price_per_piece')->nullable()->change();
            }
            if (Schema::hasColumn('sales', 'per_price_per_m2')) {
                $table->text('per_price_per_m2')->nullable()->change();
            }
            if (Schema::hasColumn('sales', 'per_loose_pieces')) {
                $table->text('per_loose_pieces')->nullable()->change();
            }
            if (Schema::hasColumn('sales', 'color')) {
                $table->text('color')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // We generally don't revert to NOT NULL without knowing defaults,
            // but effectively we just leave them nullable.
        });
    }
};
