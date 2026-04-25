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
            if (!Schema::hasColumn('purchase_items', 'size_mode')) {
                $table->string('size_mode')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('purchase_items', 'pieces_per_box')) {
                $table->decimal('pieces_per_box', 12, 2)->default(1)->after('size_mode');
            }
            if (!Schema::hasColumn('purchase_items', 'pieces_per_m2')) {
                $table->decimal('pieces_per_m2', 12, 2)->default(0)->after('pieces_per_box');
            }
            if (!Schema::hasColumn('purchase_items', 'boxes_qty')) {
                $table->decimal('boxes_qty', 12, 2)->default(0)->after('pieces_per_m2');
            }
            if (!Schema::hasColumn('purchase_items', 'loose_qty')) {
                $table->decimal('loose_qty', 12, 2)->default(0)->after('boxes_qty');
            }
            if (!Schema::hasColumn('purchase_items', 'length')) {
                $table->string('length')->nullable()->after('loose_qty');
            }
            if (!Schema::hasColumn('purchase_items', 'width')) {
                $table->string('width')->nullable()->after('length');
            }
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
