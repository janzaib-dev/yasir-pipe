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
        // Departments Table
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Employees Table
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->constrained('hr_departments')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_docs_submitted')->default(false);

            // Documents are now handled in 'hr_employee_documents' table (1:M or 1:1)

            $table->date('date_of_birth')->nullable();
            $table->date('joining_date');
            // This 'designation' column is a string placeholder.
            // If Designation module is used, a later migration (e.g. 2026_01_17_000001_create_designations_table.php)
            // will drop this column and add 'designation_id'.
            // We keep it here so this file remains a standalone 'base' setup if needed,
            // but for full 'copy-paste' of the final system, one should copy ALL migrations.
            $table->string('designation');

            $table->decimal('basic_salary', 10, 2);
            $table->enum('status', ['active', 'non-active', 'terminated'])->default('active');
            $table->timestamps();
        });

        // Attendance Table
        Schema::create('hr_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');
            $table->timestamps();
        });

        // Leaves Table
        Schema::create('hr_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('leave_type'); // Sick, Casual, Annual
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // Payroll Table
        Schema::create('hr_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('month'); // e.g., "2024-01"
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_payrolls');
        Schema::dropIfExists('hr_leaves');
        Schema::dropIfExists('hr_attendances');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('hr_departments');
    }
};
