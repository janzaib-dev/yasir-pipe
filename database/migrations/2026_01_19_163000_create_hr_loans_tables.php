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
        // Loans Table
        Schema::create('hr_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('installment_amount', 10, 2)->default(0)->comment('Monthly deductible amount, 0 for manual/large sum');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('reason')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // Loan Payments (History)
        Schema::create('hr_loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('hr_loans')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('type')->default('salary_deduction'); // salary_deduction, bank_transfer, cash
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Scheduled Deductions (One-off scheduled deductions)
        Schema::create('hr_loan_scheduled_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('hr_loans')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('deduction_month'); // Format: YYYY-MM
            $table->enum('status', ['pending', 'deducted', 'skipped'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_loan_scheduled_deductions');
        Schema::dropIfExists('hr_loan_payments');
        Schema::dropIfExists('hr_loans');
    }
};
