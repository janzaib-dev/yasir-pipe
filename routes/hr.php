<?php

use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\HolidayController;
use App\Http\Controllers\Hr\LeaveController;
use App\Http\Controllers\Hr\PayrollController;
use App\Http\Controllers\Hr\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('hr')->name('hr.')->group(function () {

    // Departments
    Route::middleware(['permission:hr.departments.view'])->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    });
    Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store')->middleware('permission:hr.departments.create|hr.departments.edit');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy')->middleware('permission:hr.departments.delete');

    // Designations
    Route::middleware(['permission:hr.designations.view'])->group(function () {
        Route::get('designations', [\App\Http\Controllers\Hr\DesignationController::class, 'index'])->name('designations.index');
    });
    Route::post('designations', [\App\Http\Controllers\Hr\DesignationController::class, 'store'])->name('designations.store')->middleware('permission:hr.designations.create|hr.designations.edit');
    Route::delete('designations/{designation}', [\App\Http\Controllers\Hr\DesignationController::class, 'destroy'])->name('designations.destroy')->middleware('permission:hr.designations.delete');

    // Loans
    Route::get('/loans', [\App\Http\Controllers\Hr\LoanController::class, 'index'])->name('loans.index')->middleware('permission:hr.loans.view');
    Route::post('/loans', [\App\Http\Controllers\Hr\LoanController::class, 'store'])->name('loans.store')->middleware('permission:hr.loans.create');
    Route::post('/loans/{id}/approve', [\App\Http\Controllers\Hr\LoanController::class, 'approve'])->name('loans.approve')->middleware('permission:hr.loans.approve');
    Route::post('/loans/{id}/reject', [\App\Http\Controllers\Hr\LoanController::class, 'reject'])->name('loans.reject')->middleware('permission:hr.loans.approve');
    Route::delete('/loans/{id}', [\App\Http\Controllers\Hr\LoanController::class, 'destroy'])->name('loans.destroy')->middleware('permission:hr.loans.delete');
    Route::post('/loans/schedule', [\App\Http\Controllers\Hr\LoanController::class, 'scheduleDeduction'])->name('loans.schedule')->middleware('permission:hr.loans.schedule');

    // Employees
    Route::middleware(['permission:hr.employees.view'])->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    });
    Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store')->middleware('permission:hr.employees.create|hr.employees.edit');
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy')->middleware('permission:hr.employees.delete');

    // Face Recognition Routes
    Route::get('employees/encodings', [EmployeeController::class, 'getEncodings'])->name('employees.encodings');
    Route::post('employees/face-register', [EmployeeController::class, 'storeFace'])->name('employees.face-register')->middleware('permission:hr.employees.edit');

    // Shifts
    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index')->middleware('permission:hr.shifts.view');
    Route::post('shifts/sync', [ShiftController::class, 'syncToDevices'])->name('shifts.sync')->middleware('permission:hr.shifts.edit');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store')->middleware('permission:hr.shifts.create|hr.shifts.edit');
    Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy')->middleware('permission:hr.shifts.delete');

    // Holidays
    Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index')->middleware('permission:hr.holidays.view');
    Route::post('holidays', [HolidayController::class, 'store'])->name('holidays.store')->middleware('permission:hr.holidays.create|hr.holidays.edit');
    Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy')->middleware('permission:hr.holidays.delete');
    Route::get('holidays/list', [HolidayController::class, 'getHolidays'])->name('holidays.list')->middleware('permission:hr.holidays.view');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index')->middleware('permission:hr.attendance.view');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store')->middleware('permission:hr.attendance.create');
    Route::get('attendance/kiosk', [AttendanceController::class, 'kiosk'])->name('attendance.kiosk')->middleware('permission:hr.attendance.create');
    Route::post('attendance/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark')->middleware('permission:hr.attendance.create');
    Route::post('attendance/pull', [AttendanceController::class, 'pullFromDevices'])->name('attendance.pull')->middleware('permission:hr.biometric.devices.edit');
    Route::post('attendance/mark-absent', [AttendanceController::class, 'markAbsent'])->name('attendance.mark-absent')->middleware('permission:hr.attendance.create');

    // Payroll
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index')->middleware('permission:hr.payroll.view');
    Route::get('payroll/monthly', [PayrollController::class, 'monthly'])->name('payroll.monthly')->middleware('permission:hr.payroll.view');
    Route::get('payroll/daily', [PayrollController::class, 'daily'])->name('payroll.daily')->middleware('permission:hr.payroll.view');
    Route::get('payroll/{payroll}/details', [PayrollController::class, 'details'])->name('payroll.details')->middleware('permission:hr.payroll.view');
    Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate')->middleware('permission:hr.payroll.create');
    Route::post('payroll/generate-monthly', [PayrollController::class, 'generateMonthly'])->name('payroll.generate-monthly')->middleware('permission:hr.payroll.create');
    Route::post('payroll/generate-daily', [PayrollController::class, 'generateDaily'])->name('payroll.generate-daily')->middleware('permission:hr.payroll.create');
    Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update')->middleware('permission:hr.payroll.edit');
    Route::patch('payroll/{payroll}/mark-reviewed', [PayrollController::class, 'markReviewed'])->name('payroll.mark-reviewed')->middleware('permission:hr.payroll.edit');
    Route::patch('payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid')->middleware('permission:hr.payroll.edit');
    Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy')->middleware('permission:hr.payroll.delete');

    // Leaves
    Route::get('leaves', [LeaveController::class, 'index'])->name('leaves.index')->middleware('permission:hr.leaves.view');
    Route::post('leaves', [LeaveController::class, 'store'])->name('leaves.store')->middleware('permission:hr.leaves.create');
    Route::patch('leaves/{leave}/status', [LeaveController::class, 'updateStatus'])->name('leaves.update-status')->middleware('permission:hr.leaves.approve');

    // Salary Structure
    // Index - requires any of view/create/edit (controller handles specifics)
    Route::get('salary-structure', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'index'])->name('salary-structure.index')->middleware('permission:hr.salary.structure.view|hr.salary.structure.create|hr.salary.structure.edit');

    // Create standalone salary structure
    Route::get('salary-structure/create', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'create'])->name('salary-structure.create')->middleware('permission:hr.salary.structure.create|hr.salary.structure.edit');
    Route::post('salary-structure', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'store'])->name('salary-structure.store')->middleware('permission:hr.salary.structure.create|hr.salary.structure.edit');

    Route::post('salary-structure/bulk-edit', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'bulkEdit'])->name('salary-structure.bulk-edit')->middleware('permission:hr.salary.structure.edit');
    Route::post('salary-structure/bulk-update', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'bulkUpdate'])->name('salary-structure.bulk-update')->middleware('permission:hr.salary.structure.edit');

    // Edit page - requires view (for read-only), create (for new), or edit (for existing)
    Route::get('salary-structure/{employee}/edit', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'edit'])->name('salary-structure.edit')->middleware('permission:hr.salary.structure.view|hr.salary.structure.create|hr.salary.structure.edit');
    // Update - requires create (for new) or edit (for existing) - controller handles logic
    Route::put('salary-structure/{employee}', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'update'])->name('salary-structure.update')->middleware('permission:hr.salary.structure.create|hr.salary.structure.edit');

    // Edit Template (Standalone Structure)
    Route::get('salary-structure/{salaryStructure}/edit-template', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'editTemplate'])->name('salary-structure.edit-template')->middleware('permission:hr.salary.structure.edit');
    Route::put('salary-structure/{salaryStructure}/update-template', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'updateTemplate'])->name('salary-structure.update-template')->middleware('permission:hr.salary.structure.edit');
    Route::delete('salary-structure/{salaryStructure}/destroy-template', [\App\Http\Controllers\Hr\SalaryStructureController::class, 'destroyTemplate'])->name('salary-structure.destroy-template')->middleware('permission:hr.salary.structure.delete');

    // Salary Structure Assignment (New Architecture)
    Route::get('salary-structure/{salaryStructure}/assign', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'assignPage'])->name('salary-structure.assign-page')->middleware('permission:hr.salary.structure.edit');
    Route::post('salary-structure/fetch-employees', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'fetchEmployees'])->name('salary-structure.fetch-employees')->middleware('permission:hr.salary.structure.edit');
    Route::post('salary-structure/{salaryStructure}/assign', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'assign'])->name('salary-structure.assign')->middleware('permission:hr.salary.structure.edit');
    Route::get('salary-structure/{salaryStructure}/view-assigned', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'viewAssigned'])->name('salary-structure.view-assigned')->middleware('permission:hr.salary.structure.view');
    Route::delete('salary-structure/{salaryStructure}/employee/{employee}', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'removeAssignment'])->name('salary-structure.remove-assignment')->middleware('permission:hr.salary.structure.edit');

    // Individual Updates
    Route::get('salary-structure/{salaryStructure}/individual-update', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'individualUpdatePage'])->name('salary-structure.individual-update-page')->middleware('permission:hr.salary.structure.edit');
    Route::get('salary-structure/individual/{employee}/edit', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'editIndividual'])->name('salary-structure.edit-individual')->middleware('permission:hr.salary.structure.edit');
    Route::post('salary-structure/individual/{employee}/update', [\App\Http\Controllers\Hr\SalaryStructureAssignmentController::class, 'updateIndividual'])->name('salary-structure.update-individual')->middleware('permission:hr.salary.structure.edit');

    // Biometric Devices
    Route::middleware(['permission:hr.biometric.devices.view'])->group(function () {
        Route::get('biometric-devices', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'index'])->name('biometric-devices.index');
    });
    Route::post('biometric-devices', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'store'])->name('biometric-devices.store')->middleware('permission:hr.biometric.devices.create|hr.biometric.devices.edit');
    Route::put('biometric-devices/{device}', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'update'])->name('biometric-devices.update')->middleware('permission:hr.biometric.devices.edit');
    Route::delete('biometric-devices/{device}', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'destroy'])->name('biometric-devices.destroy')->middleware('permission:hr.biometric.devices.delete');

    // Biometric Device Actions
    Route::post('biometric-devices/{device}/test', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'testConnection'])->name('biometric-devices.test')->middleware('permission:hr.biometric.devices.view');
    Route::post('biometric-devices/{device}/sync-employees', [App\Http\Controllers\Hr\BiometricDeviceController::class, 'syncEmployees'])->name('biometric-devices.sync-employees')->middleware('permission:hr.biometric.devices.edit');
    Route::post('biometric-devices/{device}/pull-attendance', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'pullAttendance'])->name('biometric-devices.pull-attendance')->middleware('permission:hr.biometric.devices.edit');

    // HR Settings
    Route::post('settings/update', [\App\Http\Controllers\Hr\HrSettingController::class, 'update'])->name('settings.update')->middleware('permission:hr.biometric.devices.edit');

});

// My Attendance - Available to all authenticated users (no HR permission required)
Route::middleware(['auth'])->group(function () {
    Route::get('my-attendance', [AttendanceController::class, 'myAttendance'])->name('my-attendance');
    Route::post('my-attendance/mark', [AttendanceController::class, 'markMyAttendance'])->name('my-attendance.mark');
});
