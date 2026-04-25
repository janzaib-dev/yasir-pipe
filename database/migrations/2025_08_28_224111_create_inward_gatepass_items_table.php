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
        Schema::create('inward_gatepass_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inward_gatepass_id')->constrained('inward_gatepasses')->onDelete('cascade');
            $table->foreignId('product_id');
            
            $table->integer('qty')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inward_gatepass_items');
    }
};
