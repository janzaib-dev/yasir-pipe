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
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->integer('loose_pieces')->default(0)->after('quantity');
            $table->integer('boxes_quantity')->default(0)->after('quantity');
            $table->integer('pieces_per_box')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropColumn(['loose_pieces', 'boxes_quantity', 'pieces_per_box']);
        });
    }
};
