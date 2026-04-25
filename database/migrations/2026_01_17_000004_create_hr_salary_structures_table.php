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
        // Salary Structure Table - Stores salary configuration per employee
        Schema::create('hr_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();

            // Salary Type: salary, commission, both
            $table->enum('salary_type', ['salary', 'commission', 'both'])->default('salary');

            // Base Salary (overrides employee's basic_salary for payroll)
            $table->decimal('base_salary', 12, 2)->default(0);

            // Commission settings
            $table->decimal('commission_percentage', 5, 2)->nullable(); // e.g., 5.00 = 5%
            $table->decimal('sales_target', 12, 2)->nullable(); // Monthly target

            // Allowances (JSON for flexibility)
            // Format: [{"name": "Housing", "amount": 5000}, {"name": "Transport", "amount": 2000}]
            $table->json('allowances')->nullable();

            // Deductions (JSON for flexibility)
            // Format: [{"name": "Tax", "amount": 1000}, {"name": "Insurance", "amount": 500}]
            $table->json('deductions')->nullable();

            // Leave salary per day (for leave encashment)
            $table->decimal('leave_salary_per_day', 10, 2)->nullable();

            $table->timestamps();

            // One salary structure per employee
            $table->unique('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_salary_structures');
    }
};
