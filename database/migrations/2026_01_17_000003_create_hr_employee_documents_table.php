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
        Schema::create('hr_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('type'); // e.g., 'degree', 'cv', 'ssc', 'hsc', 'certificate'
            $table->string('file_path');
            $table->timestamps();
        });

        // Drop the columns from hr_employees if they exist (cleanup from previous step)
        if (Schema::hasColumn('hr_employees', 'document_degree')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->dropColumn([
                    'document_degree',
                    'document_certificate',
                    'document_hsc_marksheet',
                    'document_ssc_marksheet',
                    'document_cv'
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_documents');
        // We generally don't restore dropped columns in down() for this dev flow, keep it simple.
    }
};
