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
            $table->json('attendance_deduction_policy')->nullable()->after('deductions');
            $table->boolean('carry_forward_deductions')->default(false)->after('attendance_deduction_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            $table->dropColumn(['attendance_deduction_policy', 'carry_forward_deductions']);
        });
    }
};
