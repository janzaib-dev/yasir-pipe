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
        Schema::create('purchase_return_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_return_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');

    $table->string('unit')->nullable();
    $table->integer('qty')->default(0);
    $table->decimal('price', 12, 2)->default(0);
    $table->decimal('item_discount', 12, 2)->default(0);
    $table->decimal('line_total', 12, 2)->default(0);

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
