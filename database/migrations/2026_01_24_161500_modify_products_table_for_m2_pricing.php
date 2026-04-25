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
        Schema::table('products', function (Blueprint $table) {
            // Drop columns
            // Using array to drop multiple columns is cleaner if database driver supports it (MySQL does)
            $table->dropColumn([
                'wholesale_price',
                'alert_quantity',
                'initial_stock',
                'price',          // Retail Price
                'pack_type',      // Packaging Type
                'pack_qty',       // Packaging Quantity
                'piece_per_pack', // Unit per Packing
                'loose_piece',     // Loose Piece
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            // Limit `size_mode` to configured enum or string. Default 'by_size'.
            $table->string('size_mode')->default('by_size')->after('item_name');

            // Dimensions
            $table->decimal('height', 8, 2)->nullable()->after('size_mode')->comment('Height in cm');
            $table->decimal('width', 8, 2)->nullable()->after('height')->comment('Width in cm');

            // Box/Packing
            // "pieces_per_box" replaces maybe "piece_per_pack"
            $table->integer('pieces_per_box')->default(0)->after('width');
            $table->integer('boxes_quantity')->default(0)->after('pieces_per_box');

            // Calculated fields
            $table->decimal('total_m2', 12, 4)->default(0)->after('boxes_quantity');
            $table->decimal('price_per_m2', 12, 2)->default(0)->after('total_m2'); // Sale Price per m2
            $table->decimal('total_price', 15, 2)->default(0)->after('price_per_m2'); // Sale Total
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn([
                'size_mode',
                'height',
                'width',
                'pieces_per_box',
                'boxes_quantity',
                'total_m2',
                'price_per_m2',
                'total_price',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            // Restore removed columns (types must match original migrations)
            $table->text('price')->nullable();
            $table->text('wholesale_price')->nullable();
            $table->text('initial_stock')->nullable();
            $table->integer('alert_quantity')->nullable();
            $table->string('pack_type')->nullable();
            $table->string('pack_qty')->nullable();
            $table->string('piece_per_pack')->nullable();
            $table->string('loose_piece')->nullable();
        });
    }
};
