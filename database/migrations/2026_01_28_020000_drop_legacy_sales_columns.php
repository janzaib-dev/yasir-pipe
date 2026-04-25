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
            $columns = [
                'product_code', 'brand', 'unit', 'per_price', 'per_discount',
                'qty', 'per_total', 'color',
                'per_total_pieces', 'per_price_per_piece', 'per_price_per_m2', 'per_loose_pieces',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Restore as nullable text
            $table->text('product_code')->nullable();
            $table->text('brand')->nullable();
            $table->text('unit')->nullable();
            $table->text('per_price')->nullable();
            $table->text('per_discount')->nullable();
            $table->text('qty')->nullable();
            $table->text('per_total')->nullable();
            $table->text('color')->nullable();
            $table->text('per_total_pieces')->nullable();
            $table->text('per_price_per_piece')->nullable();
            $table->text('per_price_per_m2')->nullable();
            $table->text('per_loose_pieces')->nullable();
        });
    }
};
