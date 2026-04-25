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
            $table->foreignId('parent_structure_id')->nullable()->after('id')->constrained('hr_salary_structures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            $table->dropForeign(['parent_structure_id']);
            $table->dropColumn('parent_structure_id');
        });
    }
};
