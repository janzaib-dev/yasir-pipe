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
            if (! Schema::hasColumn('products', 'boxes_quantity')) {
                $table->integer('boxes_quantity')->default(0)->nullable();
            }
            if (! Schema::hasColumn('products', 'loose_pieces')) {
                $table->integer('loose_pieces')->default(0)->nullable();
            }
            if (! Schema::hasColumn('products', 'piece_quantity')) {
                $table->integer('piece_quantity')->default(0)->nullable();
            }
            if (! Schema::hasColumn('products', 'total_stock_qty')) {
                $table->decimal('total_stock_qty', 10, 2)->default(0)->nullable();
            }
            if (! Schema::hasColumn('products', 'pieces_per_m2')) {
                $table->decimal('pieces_per_m2', 12, 6)->default(0)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['boxes_quantity', 'loose_pieces', 'piece_quantity', 'total_stock_qty', 'pieces_per_m2']);
        });
    }
};
