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
        Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
    $table->enum('type', ['in','out','assembly_in','assembly_out','adjustment']);
    $table->decimal('qty', 12, 3); // +ve for in/assembly_in, -ve for out/assembly_out
    $table->string('ref_type')->nullable();  // e.g. 'PO','SO','ASSEMBLY','ADJ'
    $table->unsignedBigInteger('ref_id')->nullable();
    $table->string('note')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
