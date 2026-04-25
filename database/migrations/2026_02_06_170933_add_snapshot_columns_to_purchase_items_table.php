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
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('size_mode')->nullable()->after('product_id');
            $table->decimal('pieces_per_box', 12, 2)->default(1)->after('size_mode');
            $table->decimal('pieces_per_m2', 12, 2)->default(0)->after('pieces_per_box');
            $table->decimal('boxes_qty', 12, 2)->default(0)->after('pieces_per_m2');
            $table->decimal('loose_qty', 12, 2)->default(0)->after('boxes_qty');
            // snapshot dimensions
            $table->string('length')->nullable()->after('loose_qty');
            $table->string('width')->nullable()->after('length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn([
                'size_mode',
                'pieces_per_box',
                'pieces_per_m2',
                'boxes_qty',
                'loose_qty',
                'length',
                'width'
            ]);
        });
    }
};
