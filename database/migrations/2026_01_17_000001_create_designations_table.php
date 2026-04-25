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
        // 1. Create Designations Table
        Schema::create('hr_designations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Modify Employees Table
        // We need to drop the old string 'designation' column and add 'designation_id' foreign key.
        // Since we just created the table, we can assume it's safe to drop the column or we can keep it as backup.
        // Let's drop it to be clean.
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn('designation');
            $table->foreignId('designation_id')->after('department_id')->constrained('hr_designations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropColumn('designation_id');
            $table->string('designation')->nullable(); // Restore column
        });

        Schema::dropIfExists('hr_designations');
    }
};
