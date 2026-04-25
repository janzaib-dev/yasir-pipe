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
            // New fields for extended size modes
            $table->integer('loose_pieces')->nullable()->default(0)->after('boxes_quantity');
            $table->integer('piece_quantity')->nullable()->default(0)->after('loose_pieces')->comment('Unit input for by_pieces mode'); 
            $table->decimal('total_stock_qty', 15, 2)->nullable()->default(0)->after('piece_quantity')->comment('Calculated Total Stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['loose_pieces', 'piece_quantity', 'total_stock_qty']);
        });
    }
};
