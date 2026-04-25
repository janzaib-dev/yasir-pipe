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
            $table->string('model')->nullable();
            $table->string('hs_code')->nullable();
            $table->string('pack_type')->nullable();
            $table->string('pack_qty')->nullable();
            $table->string('piece_per_pack')->nullable();
            $table->string('loose_piece')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            
        });
    }
};
