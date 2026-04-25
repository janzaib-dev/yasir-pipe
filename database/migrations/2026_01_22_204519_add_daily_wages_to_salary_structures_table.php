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
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            $table->decimal('daily_wages', 10, 2)->nullable()->after('base_salary');
            $table->boolean('use_daily_wages')->default(false)->after('daily_wages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            $table->dropColumn(['daily_wages', 'use_daily_wages']);
        });
    }
};
