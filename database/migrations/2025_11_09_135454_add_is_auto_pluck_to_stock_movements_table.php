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
        Schema::table('stock_movements', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('stock_movements', 'is_auto_pluck')) {
                $table->tinyInteger('is_auto_pluck')->default(0)->after('ref_id');

            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
             if (Schema::hasColumn('stock_movements', 'is_auto_pluck')) {
                $table->dropColumn('is_auto_pluck');
            }
            //
        });
    }
};
