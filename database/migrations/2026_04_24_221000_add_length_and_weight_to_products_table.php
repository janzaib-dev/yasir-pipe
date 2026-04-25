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
            if (!Schema::hasColumn('products', 'length_value')) {
                $table->decimal('length_value', 15, 4)->nullable()->after('width');
            }
            if (!Schema::hasColumn('products', 'length_unit')) {
                $table->string('length_unit', 20)->nullable()->after('length_value');
            }
            if (!Schema::hasColumn('products', 'weight_per_unit')) {
                $table->decimal('weight_per_unit', 15, 4)->nullable()->after('length_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['length_value', 'length_unit', 'weight_per_unit']);
        });
    }
};
