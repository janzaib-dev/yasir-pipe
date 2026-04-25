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
         Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('product_id');

            $table->integer('qty')->default(0);
            $table->integer('reserved_qty')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'warehouse_id', 'product_id'], 'stocks_unique_triplet');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
