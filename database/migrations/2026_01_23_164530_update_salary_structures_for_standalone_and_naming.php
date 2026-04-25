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
            // Drop foreign key if exists
            try {
                $table->dropForeign(['employee_id']);
            } catch (\Exception $e) {
            }

            // Drop unique constraint
            try {
                $table->dropUnique('hr_salary_structures_employee_id_unique');
            } catch (\Exception $e) {
            }

            // Make employee_id nullable
            $table->unsignedBigInteger('employee_id')->nullable()->change();

            // Add normal index
            try {
                $table->index('employee_id');
            } catch (\Exception $e) {
            }

            // Re-add foreign key
            try {
                $table->foreign('employee_id')->references('id')->on('hr_employees')->cascadeOnDelete();
            } catch (\Exception $e) {
            }

            // Add name column if not exists
            if (! Schema::hasColumn('hr_salary_structures', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            // This down migration is risky because if we have nulls, we can't revert to not null
            // We will attempt to revert assuming data is clean
            $table->dropColumn('name');
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            $table->unique('employee_id');
        });
    }
};
