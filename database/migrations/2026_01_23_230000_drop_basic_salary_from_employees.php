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
        Schema::table('hr_employees', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employees', 'basic_salary')) {
                $table->dropColumn('basic_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->default(0)->after('joining_date');
        });
    }
};
