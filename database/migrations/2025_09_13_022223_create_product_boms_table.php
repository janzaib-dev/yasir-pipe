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
        Schema::create('product_boms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->foreignId('part_id')->constrained('products')->cascadeOnDelete();
    $table->decimal('qty_per_unit', 12, 3);
    $table->timestamps();

    $table->unique(['product_id','part_id']); // 1 part once per product
}); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_boms');
    }
};
