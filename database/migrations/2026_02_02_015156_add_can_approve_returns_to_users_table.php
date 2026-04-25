<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_approve_returns')->default(false)->after('email');
            $table->boolean('can_approve_past_deadline_returns')->default(false)->after('can_approve_returns');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_approve_returns', 'can_approve_past_deadline_returns']);
        });
    }
};
