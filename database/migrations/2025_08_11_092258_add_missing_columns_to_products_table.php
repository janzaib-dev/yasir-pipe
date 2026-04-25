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
            //
            $table->text('initial_stock')->nullable();
            $table->text('wholesale_price')->nullable();
        $table->string('image',255)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
             $table->dropColumn([
            'wholesale_price',
            'initial_stock',
            'image'
        ]);
        });
    }
};
