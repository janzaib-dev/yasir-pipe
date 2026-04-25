<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_part')->default(0)->after('brand_id'); 
            $table->boolean('is_assembled')->default(0)->after('is_part'); 
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_part', 'is_assembled', 'bom_json']);
        });
    }
};
