<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Renames using raw SQL for better compatibility
        // We check existence first to avoid errors if re-running

        $hasQty = Schema::hasColumn('sale_items', 'qty');
        $hasSalesQty = Schema::hasColumn('sale_items', 'sales_qty');

        if ($hasSalesQty && ! $hasQty) {
            DB::statement('ALTER TABLE sale_items CHANGE sales_qty qty DECIMAL(12, 2) DEFAULT 0');
        }

        $hasPrice = Schema::hasColumn('sale_items', 'price');
        $hasSalesPrice = Schema::hasColumn('sale_items', 'sales_price');

        if ($hasSalesPrice && ! $hasPrice) {
            DB::statement('ALTER TABLE sale_items CHANGE sales_price price DECIMAL(12, 2) DEFAULT 0');
        }

        $hasTotal = Schema::hasColumn('sale_items', 'total');
        $hasAmount = Schema::hasColumn('sale_items', 'amount');

        if ($hasAmount && ! $hasTotal) {
            DB::statement('ALTER TABLE sale_items CHANGE amount total DECIMAL(12, 2) DEFAULT 0');
        }

        // Step 2: Additions / Missing Columns
        Schema::table('sale_items', function (Blueprint $table) {
            // Ensure base columns exist if rename failed or wasn't needed (safety net)
            if (! Schema::hasColumn('sale_items', 'qty')) {
                $table->decimal('qty', 12, 2)->default(0)->after('product_id');
            }
            if (! Schema::hasColumn('sale_items', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('qty');
            }
            if (! Schema::hasColumn('sale_items', 'total')) {
                $table->decimal('total', 12, 2)->default(0)->after('price');
            }

            // Add new missing columns
            if (! Schema::hasColumn('sale_items', 'total_pieces')) {
                $table->integer('total_pieces')->default(0)->after('qty');
            }
            if (! Schema::hasColumn('sale_items', 'loose_pieces')) {
                $table->integer('loose_pieces')->default(0)->after('total_pieces');
            }
            if (! Schema::hasColumn('sale_items', 'price_per_piece')) {
                $table->decimal('price_per_piece', 12, 2)->default(0)->after('price');
            }
            if (! Schema::hasColumn('sale_items', 'price_per_m2')) {
                $table->decimal('price_per_m2', 12, 2)->default(0)->after('price_per_piece');
            }
            if (! Schema::hasColumn('sale_items', 'color')) {
                $table->text('color')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        // Reverse renames and additions
        // DB::statement("ALTER TABLE sale_items CHANGE qty sales_qty DECIMAL(12, 2) DEFAULT 0");
        // DB::statement("ALTER TABLE sale_items CHANGE price sales_price DECIMAL(12, 2) DEFAULT 0");
        // DB::statement("ALTER TABLE sale_items CHANGE total amount DECIMAL(12, 2) DEFAULT 0");

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['total_pieces', 'loose_pieces', 'price_per_piece', 'price_per_m2', 'color']);
        });
    }
};
