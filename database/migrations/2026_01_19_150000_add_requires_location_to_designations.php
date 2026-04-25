<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add requires_location field to designations for attendance tracking
     */
    public function up(): void
    {
        Schema::table('hr_designations', function (Blueprint $table) {
            $table->boolean('requires_location')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_designations', function (Blueprint $table) {
            $table->dropColumn('requires_location');
        });
    }
};
