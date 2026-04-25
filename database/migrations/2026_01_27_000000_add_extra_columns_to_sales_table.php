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
            $table->text('per_total_pieces')->nullable()->after('per_total');
            $table->text('per_price_per_piece')->nullable()->after('per_total_pieces');
            $table->text('per_price_per_m2')->nullable()->after('per_price_per_piece');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['per_total_pieces', 'per_price_per_piece', 'per_price_per_m2']);
        });
    }
};
