<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->string('name_ur')->nullable()->after('name');
            $table->text('address_ur')->nullable()->after('address');
        });
    }

    public function down()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn(['name_ur', 'address_ur']);
        });
    }
};
