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
        // Drop the BOM table first as it has foreign keys to products
        Schema::dropIfExists('product_boms');

        // Do NOT drop the columns from products table, keep 'is_part' and 'is_assembled' for seeder and app use
        // Schema::table('products', function (Blueprint $table) {
        //     $table->dropColumn(['is_part', 'is_assembled']);
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add columns back
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_part')->default(0)->after('brand_id');
            $table->boolean('is_assembled')->default(0)->after('is_part');
        });

        // Create table back
        Schema::create('product_boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('qty_per_unit', 12, 3);
            $table->timestamps();

            $table->unique(['product_id', 'part_id']);
        });
    }
};
