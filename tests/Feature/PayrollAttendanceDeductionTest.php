<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Hr\Employee;
use App\Models\Hr\SalaryStructure;
use App\Models\Hr\Attendance;
use App\Models\Hr\Payroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class PayrollAttendanceDeductionTest extends TestCase
{
    // use RefreshDatabase; // Use transaction rollback to keep DB clean

    public function test_daily_wage_payroll_with_attendance_deductions()
    {
        // 1. Setup Admin User
        $user = User::factory()->create();
        // Mock permission check by creating permission or bypassing
        // For simplicity, we assume the controller check passes if we use a super-admin or similar.
        // Or we just seed the permission.
        $permissionName = 'hr.payroll.create';
        if (!Permission::where('name', $permissionName)->exists()) {
             Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
        }
        $user->givePermissionTo($permissionName);

        // 1b. Create Dependencies
        $dept = \App\Models\Hr\Department::firstOrCreate(['name' => 'Test Dept']);
        $desig = \App\Models\Hr\Designation::firstOrCreate(['name' => 'Test Role']); // Corrected to 'name'

        // 2. Create Employee
        $employee = Employee::create([
            'first_name' => 'Test',
            'last_name' => 'Daily',
            'email' => 'testdaily@example.com',
            'phone' => '1234567890',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'status' => 'active',
            'joining_date' => now(),
            'basic_salary' => 0,
            // Add other required fields if any (usually nullable or defaults)
        ]);

        // 3. Create Salary Structure
        // Daily Wage: 1000
        // Late Rule: 10-60min => 50 (Fixed)
        // Early Rule: 10-60min => 10% (Percentage) => 100
        $structure = SalaryStructure::create([
            'employee_id' => $employee->id,
            'salary_type' => 'salary', // Or combined
            'base_salary' => 0,
            'use_daily_wages' => true,
            'daily_wages' => 1000,
            'carry_forward_deductions' => true,
            'attendance_deduction_policy' => [
                'late_rules' => [
                    [
                        'min_minutes' => 10,
                        'max_minutes' => 60,
                        'amount' => 50,
                        'type' => 'fixed'
                    ]
                ],
                'early_rules' => [
                    [
                        'min_minutes' => 10,
                        'max_minutes' => 60,
                        'amount' => 10, // 10 percent
                        'type' => 'percentage'
                    ]
                ]
            ],
            'allowances' => [],
            'deductions' => []
        ]);

        // 4. Create Attendance Records (January 2024)
        $month = '2024-01';
        
        // Day 1: Perfect
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2024-01-01',
            'status' => 'present',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'late_minutes' => 0,
            'early_leave_minutes' => 0
        ]);

        // Day 2: Late 20 mins (Expect 50 ded)
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2024-01-02',
            'status' => 'present',
            'clock_in' => '09:20:00',
            'clock_out' => '18:00:00',
            'late_minutes' => 20,
            'early_leave_minutes' => 0
        ]);

        // Day 3: Early 20 mins (Expect 10% of 1000 = 100 ded)
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2024-01-03',
            'status' => 'present',
            'clock_in' => '09:00:00',
            'clock_out' => '17:40:00',
            'late_minutes' => 0,
            'early_leave_minutes' => 20
        ]);

        // Day 4: Late 20 & Early 20 (Expect 50 + 100 = 150 ded)
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2024-01-04',
            'status' => 'present',
            'clock_in' => '09:20:00',
            'clock_out' => '17:40:00',
            'late_minutes' => 20,
            'early_leave_minutes' => 20
        ]);

        // 5. Generate Payroll via API
        $response = $this->actingAs($user)->postJson('/hr/payroll/generate', [
            'employee_id' => $employee->id,
            'month' => $month
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Payroll generated successfully.']);

        // 6. Verify Payroll Record
        $payroll = Payroll::where('employee_id', $employee->id)->where('month', $month)->first();

        $this->assertNotNull($payroll);
        
        // Calculations:
        // Earnings: 4 days * 1000 = 4000
        // Deductions: 0 + 50 + 100 + 150 = 300
        // Net: 3700
        
        $this->assertEquals(4000, $payroll->basic_salary, 'Basic Payroll (Daily Wages Total) Incorrect');
        $this->assertEquals(300, $payroll->deductions, 'Total Deductions Incorrect');
        $this->assertEquals(3700, $payroll->net_salary, 'Net Salary Incorrect');

        // Clean up (Optional if using RefreshDatabase, but manual here for safety vs persistent db)
        // Permissions are tricky with RefreshDatabase if we create them manually inside.
        // We'll leave it as is.
    }
}
