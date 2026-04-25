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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id');
            $table->foreignId('product_id');

            $table->string('unit')->nullable();                 // optional visible unit text
            $table->decimal('price', 12, 2)->default(0);        // unit (wholesale) price
            $table->decimal('item_discount', 12, 2)->default(0);// absolute per-line discount
            $table->integer('qty')->default(0);
            $table->decimal('line_total', 12, 2)->default(0);   // (qty*price)-item_discount

            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
