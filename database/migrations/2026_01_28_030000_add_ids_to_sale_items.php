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
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('sale_items', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('brand_id');
            }
            if (! Schema::hasColumn('sale_items', 'sub_category_id')) {
                $table->unsignedBigInteger('sub_category_id')->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('sale_items', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('sub_category_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['brand_id', 'category_id', 'sub_category_id', 'unit_id']);
        });
    }
};
