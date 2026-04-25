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
        Schema::table('hr_payrolls', function (Blueprint $table) {
            // Payroll type differentiation
            $table->enum('payroll_type', ['monthly', 'daily'])->default('monthly')->after('employee_id');
            
            // Enhanced salary breakdown
            $table->decimal('gross_salary', 10, 2)->default(0)->after('basic_salary');
            $table->decimal('allowances', 10, 2)->default(0)->after('gross_salary');
            $table->decimal('attendance_deductions', 10, 2)->default(0)->after('allowances');
            $table->decimal('manual_deductions', 10, 2)->default(0)->after('attendance_deductions');
            $table->decimal('manual_allowances', 10, 2)->default(0)->after('manual_deductions');
            $table->decimal('carried_forward_deduction', 10, 2)->default(0)->after('manual_allowances');
            
            // Admin notes and tracking
            $table->text('notes')->nullable()->after('net_salary');
            $table->boolean('auto_generated')->default(false)->after('notes');
            
            // Enhanced status workflow
            $table->dropColumn('status');
        });
        
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->enum('status', ['generated', 'reviewed', 'paid'])->default('generated')->after('auto_generated');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'payroll_type',
                'gross_salary',
                'allowances',
                'attendance_deductions',
                'manual_deductions',
                'manual_allowances',
                'carried_forward_deduction',
                'notes',
                'auto_generated',
                'reviewed_by',
                'reviewed_at',
            ]);
            
            $table->dropColumn('status');
        });
        
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid'])->default('pending');
        });
    }
};
