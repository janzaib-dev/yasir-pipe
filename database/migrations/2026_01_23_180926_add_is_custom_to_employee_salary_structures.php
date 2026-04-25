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
        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('is_active');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['is_custom', 'updated_by']);
        });
    }
};
