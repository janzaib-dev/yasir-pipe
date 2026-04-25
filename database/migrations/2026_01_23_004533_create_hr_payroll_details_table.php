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
        Schema::create('hr_payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('hr_payrolls')->cascadeOnDelete();
            $table->enum('type', ['allowance', 'deduction']);
            $table->string('name'); // e.g., "Late Check-in - Jan 15", "Transport Allowance"
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_details');
    }
};
