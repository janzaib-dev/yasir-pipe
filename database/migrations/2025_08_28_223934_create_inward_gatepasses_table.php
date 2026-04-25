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
        Schema::create('inward_gatepasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('vendor_id');
            $table->foreignId('purchase_id')->nullable();   // linked when bill comes
            $table->date('gatepass_date')->nullable();
            $table->string('gatepass_no')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending','linked','cancelled'])->default('pending');

            $table->foreignId('created_by')->nullable();  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inward_gatepasses');
    }
};
