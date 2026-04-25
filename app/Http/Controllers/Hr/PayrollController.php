<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display paginated payrolls with filters
     */
    public function index(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Payroll::with(['employee.designation', 'employee.department']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('payroll_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $payrolls = $query->latest()->paginate(12);
        $employees = Employee::all();

        return view('hr.payroll.index', compact('payrolls', 'employees'))->with('activeTab', 'all');
    }

    /**
     * Show monthly payrolls only
     */
    public function monthly(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Payroll::with(['employee.designation', 'employee.department'])
            ->monthly();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        $payrolls = $query->latest()->paginate(12);
        $employees = Employee::whereHas('salaryStructure', function ($q) {
            $q->whereIn('salary_type', ['salary', 'both']);
        })->get();

        return view('hr.payroll.index', compact('payrolls', 'employees'))->with('activeTab', 'monthly');
    }

    /**
     * Show daily payrolls only
     */
    public function daily(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Payroll::with(['employee.designation', 'employee.department'])
            ->daily();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        $payrolls = $query->latest()->paginate(12);
        $employees = Employee::whereHas('salaryStructure', function ($q) {
            $q->where('use_daily_wages', true);
        })->get();

        return view('hr.payroll.index', compact('payrolls', 'employees'))->with('activeTab', 'daily');
    }

    /**
     * Get detailed payroll breakdown
     */
    public function details($id)
    {
        if (! auth()->user()->can('hr.payroll.view')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::with(['employee.designation', 'details', 'reviewer'])->findOrFail($id);

        // Format payroll period based on type
        $payrollPeriod = $this->formatPayrollPeriod($payroll);

        // Get allowance details
        $allowanceDetails = $payroll->details()->where('type', 'allowance')->get()->map(function ($detail) {
            return [
                'name' => $detail->name,
                'amount' => $detail->amount,
                'description' => $detail->description,
                'calculation_type' => $detail->description ? 'fixed' : 'fixed', // Can enhance this later
            ];
        });

        // Get deduction details (non-attendance)
        $deductionDetails = $payroll->details()->where('type', 'deduction')->get()->map(function ($detail) {
            return [
                'name' => $detail->name,
                'amount' => $detail->amount,
                'description' => $detail->description,
            ];
        });

        // Get attendance breakdown for the payroll period
        $attendanceBreakdown = $this->getAttendanceBreakdown($payroll);

        return response()->json([
            'payroll' => $payroll,
            'payroll_period' => $payrollPeriod,
            'breakdown' => [
                'earnings' => [
                    'basic_salary' => $payroll->basic_salary,
                    'allowances' => $payroll->allowances,
                    'manual_allowances' => $payroll->manual_allowances,
                    'total' => $payroll->gross_salary,
                ],
                'deductions' => [
                    'fixed_deductions' => $payroll->deductions,
                    'attendance_deductions' => $payroll->attendance_deductions,
                    'carried_forward' => $payroll->carried_forward_deduction,
                    'carried_forward_to_next' => $payroll->carried_forward_to_next,
                    'manual_deductions' => $payroll->manual_deductions,
                    'total' => $payroll->total_deductions,
                ],
                'net_payable' => $payroll->net_salary,
            ],
            'allowance_details' => $allowanceDetails,
            'deduction_details' => $deductionDetails,
            'attendance_breakdown' => $attendanceBreakdown,
        ]);
    }

    /**
     * Format payroll period based on payroll type
     */
    private function formatPayrollPeriod($payroll): array
    {
        if ($payroll->payroll_type === 'daily') {
            // For daily: Display Date, Month, and Year (e.g., "15 March 2026")
            $date = \Carbon\Carbon::parse($payroll->month);
            return [
                'type' => 'daily',
                'formatted' => $date->format('d F Y'),
                'day' => $date->format('d'),
                'month' => $date->format('F'),
                'year' => $date->format('Y'),
            ];
        } else {
            // For monthly: Display Month and Year only (e.g., "March 2026")
            $date = \Carbon\Carbon::parse($payroll->month . '-01');
            return [
                'type' => 'monthly',
                'formatted' => $date->format('F Y'),
                'month' => $date->format('F'),
                'year' => $date->format('Y'),
            ];
        }
    }

    /**
     * Get attendance breakdown for payroll period
     */
    private function getAttendanceBreakdown($payroll): array
    {
        $employee = $payroll->employee;
        
        // Get salary structure for deduction policy
        $structure = $this->payrollService->getEffectiveSalaryStructure($employee);
        $policy = $structure ? ($structure->attendance_deduction_policy ?? []) : [];
        $perDayDeduction = $structure ? ($structure->leave_salary_per_day ?? 0) : 0;
        
        if ($payroll->payroll_type === 'monthly') {
            // For monthly payroll, get attendance stats for the entire month
            $startDate = \Carbon\Carbon::parse($payroll->month . '-01')->startOfMonth();
            $endDate = \Carbon\Carbon::parse($payroll->month . '-01')->endOfMonth();
            
            $totalWorkingDays = $this->getWorkingDaysInRange($startDate, $endDate);
            
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('date', 'asc')
                ->get();
            
            // Check if attendance data is complete
            $hasData = $attendances->count() > 0;
            
            $daysPresent = $attendances->filter(fn($att) => strtolower($att->status) === 'present')->count();
            $daysAbsent = $attendances->filter(fn($att) => strtolower($att->status) === 'absent')->count();
            $lateCheckIns = $attendances->where('is_late', true)->count();
            $earlyCheckOuts = $attendances->where('is_early_leave', true)->count(); // Fixed: is_early_leave
            
            // Calculate deduction breakdown
            $lateMinutesTotal = $attendances->sum('late_minutes');
            $earlyMinutesTotal = $attendances->sum('early_leave_minutes'); // Fixed: early_leave_minutes
            
            // Calculate actual deduction amounts
            $absenceDeduction = $daysAbsent * $perDayDeduction;
            
            $lateDeduction = 0;
            $latePenalty = $policy['late_penalty_per_instance'] ?? 0;
            if ($latePenalty > 0) {
                $lateDeduction = $lateCheckIns * $latePenalty;
            }
            
            $earlyDeduction = 0;
            $earlyPenalty = $policy['early_penalty_per_instance'] ?? 0;
            if ($earlyPenalty > 0) {
                $earlyDeduction = $earlyCheckOuts * $earlyPenalty;
            }
            
            // Build detailed records for each issue type
            $absentDays = $attendances->filter(fn($att) => strtolower($att->status) === 'absent')
                ->map(function ($att) use ($perDayDeduction) {
                    return [
                        'date' => \Carbon\Carbon::parse($att->date)->format('d M Y'),
                        'day' => \Carbon\Carbon::parse($att->date)->format('l'),
                        'deduction' => $perDayDeduction,
                    ];
                })->values()->toArray();
            
            $lateDays = $attendances->where('is_late', true)->map(function ($att) use ($latePenalty) {
                return [
                    'date' => \Carbon\Carbon::parse($att->date)->format('d M Y'),
                    'day' => \Carbon\Carbon::parse($att->date)->format('l'),
                    'check_in' => $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('h:i A') : 'N/A', // Fixed: clock_in
                    'late_minutes' => $att->late_minutes ?? 0,
                    'deduction' => $latePenalty,
                ];
            })->values()->toArray();
            
            $earlyDays = $attendances->where('is_early_leave', true)->map(function ($att) use ($earlyPenalty) { // Fixed: is_early_leave
                return [
                    'date' => \Carbon\Carbon::parse($att->date)->format('d M Y'),
                    'day' => \Carbon\Carbon::parse($att->date)->format('l'),
                    'check_out' => $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('h:i A') : 'N/A', // Fixed: clock_out
                    'early_minutes' => $att->early_leave_minutes ?? 0, // Fixed: early_leave_minutes
                    'deduction' => $earlyPenalty,
                ];
            })->values()->toArray();
            
            return [
                'has_data' => $hasData,
                'data_message' => $hasData ? null : 'Attendance data incomplete for this period',
                'has_attendance_deductions' => $payroll->attendance_deductions > 0,
                'total_working_days' => $totalWorkingDays,
                'days_present' => $daysPresent,
                'days_absent' => $daysAbsent,
                'late_check_ins' => $lateCheckIns,
                'early_check_outs' => $earlyCheckOuts,
                'late_minutes_total' => $lateMinutesTotal,
                'early_minutes_total' => $earlyMinutesTotal,
                'total_deduction' => $payroll->attendance_deductions,
                'deduction_details' => [
                    'absence_deduction' => $absenceDeduction,
                    'late_deduction' => $lateDeduction,
                    'early_deduction' => $earlyDeduction,
                    'per_day_rate' => $perDayDeduction,
                    'late_penalty_rate' => $latePenalty,
                    'early_penalty_rate' => $earlyPenalty,
                ],
                // Detailed day-by-day records
                'absent_records' => $absentDays,
                'late_records' => $lateDays,
                'early_records' => $earlyDays,
            ];
        } else {
            // For daily payroll
            $date = \Carbon\Carbon::parse($payroll->month);
            
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $date->format('Y-m-d'))
                ->first();
            
            if ($attendance) {
                // Get specific deduction amounts from saved details
                $lateDeductionAmount = $payroll->details
                    ->filter(fn($d) => str_contains(strtolower($d->name), 'late check-in'))
                    ->sum('amount');
                    
                $earlyDeductionAmount = $payroll->details
                    ->filter(fn($d) => str_contains(strtolower($d->name), 'early leave') || str_contains(strtolower($d->name), 'early check-out'))
                    ->sum('amount');

                return [
                    'has_data' => true,
                    'has_attendance_deductions' => $payroll->attendance_deductions > 0,
                    'date' => $date->format('Y-m-d'),
                    'formatted_date' => $date->format('d M Y'),
                    'day' => $date->format('l'),
                    'status' => $attendance->status,
                    'check_in' => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : 'N/A',
                    'check_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : 'N/A',
                    'is_late' => $attendance->is_late,
                    'is_early_out' => $attendance->is_early_leave,
                    'late_minutes' => $attendance->late_minutes ?? 0,
                    'early_checkout_minutes' => $attendance->early_leave_minutes ?? 0,
                    'total_deduction' => $payroll->attendance_deductions,
                    'late_deduction_amount' => $lateDeductionAmount,
                    'early_deduction_amount' => $earlyDeductionAmount,
                ];
            }
            
            return [
                'has_data' => false,
                'data_message' => 'Attendance data incomplete for this period',
                'has_attendance_deductions' => false,
                'date' => $date->format('Y-m-d'),
                'status' => 'No attendance record',
            ];
        }
    }



    /**
     * Calculate working days in a date range (excluding weekends)
     */
    private function getWorkingDaysInRange($startDate, $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            // Exclude Saturdays (6) and Sundays (0)
            if (!in_array($current->dayOfWeek, [0, 6])) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Generate payroll (manual or single employee)
     */
    public function generate(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'month' => 'required',
            'payroll_type' => 'required|in:monthly,daily',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $employee = Employee::with('salaryStructure')->findOrFail($request->employee_id);

            // Check if payroll already exists
            $exists = Payroll::where('employee_id', $employee->id)
                ->where('month', $request->month)
                ->where('payroll_type', $request->payroll_type)
                ->exists();

            if ($exists) {
                return response()->json([
                    'errors' => ['month' => ['Payroll already generated for this period.']],
                ], 422);
            }

            if ($request->payroll_type === 'monthly') {
                $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $request->month);
            } else { // daily
                // For manual daily payroll generation, we need a date
                $validator = Validator::make($request->all(), [
                    'date' => 'required|date',
                ]);

                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }

                $attendance = Attendance::where('employee_id', $employee->id)
                    ->whereDate('date', $request->date)
                    ->first();

                if (! $attendance || ! $attendance->clock_out) {
                    return response()->json([
                        'errors' => ['date' => ['No completed attendance record found for this date.']],
                    ], 422);
                }

                $payrollData = $this->payrollService->calculateDailyPayroll($employee, $attendance);
            }

            // Create payroll
            $payroll = Payroll::create(array_merge(
                ['employee_id' => $employee->id],
                Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
            ));

            // Save detailed breakdown
            $this->payrollService->savePayrollDetails(
                $payroll,
                $payrollData['allowance_details'] ?? [],
                $payrollData['deduction_details'] ?? []
            );

            // Update pending deductions for daily payroll
            if ($request->payroll_type === 'daily') {
                $this->payrollService->updatePendingDeductions(
                    $employee,
                    $payrollData['new_pending_deductions'] ?? 0
                );
            }

            DB::commit();

            return response()->json([
                'success' => 'Payroll generated successfully.',
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => ['general' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Generate monthly payrolls for all salaried employees
     */
    public function generateMonthly(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $employees = Employee::with('salaryStructure')
                ->whereHas('salaryStructure', function ($q) {
                    $q->whereIn('salary_type', ['salary', 'both']);
                })
                ->where('status', 'active')
                ->get();

            $generated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($employees as $employee) {
                // Skip if already exists
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('month', $request->month)
                    ->where('payroll_type', 'monthly')
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                try {
                    $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $request->month);

                    $payroll = Payroll::create(array_merge(
                        ['employee_id' => $employee->id],
                        Arr::except($payrollData, ['allowance_details', 'deduction_details'])
                    ));

                    $this->payrollService->savePayrollDetails(
                        $payroll,
                        $payrollData['allowance_details'] ?? [],
                        $payrollData['deduction_details'] ?? []
                    );

                    $generated++;
                } catch (\Exception $e) {
                    $errors[] = $employee->full_name.': '.$e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => "Monthly payroll generated for {$generated} employees. {$skipped} skipped (already exists).",
                'errors' => $errors,
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => ['general' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Generate daily payrolls for all daily wage employees for a specific date
     */
    public function generateDaily(Request $request)
    {
        if (! auth()->user()->can('hr.payroll.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Fetch employees configured for daily wages
            $employees = Employee::with('salaryStructure')
                ->whereHas('salaryStructure', function ($q) {
                    $q->where('use_daily_wages', true);
                })
                ->where('status', 'active')
                ->get();

            $generated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($employees as $employee) {
                // Skip if already exists for this date
                $monthStr = Carbon::parse($request->date)->format('Y-m');
                // Check exact date overlap for daily payroll
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('payroll_type', 'daily')
                    ->whereDate('created_at', $request->date) // Usually we might check a date column, currently daily stores date in 'month' or created_at? 
                    // Let's check how calculateDailyPayroll stores it. It stores 'month' => Y-m-d.
                    ->where('month', $request->date) 
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Get attendance for the date
                $attendance = Attendance::where('employee_id', $employee->id)
                    ->whereDate('date', $request->date)
                    ->first();

                if (! $attendance || ! $attendance->clock_out) {
                    $errors[] = $employee->full_name . ': No completed attendance found.';
                    continue;
                }

                try {
                    $payrollData = $this->payrollService->calculateDailyPayroll($employee, $attendance);

                    $payroll = Payroll::create(array_merge(
                        ['employee_id' => $employee->id],
                        Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
                    ));

                    $this->payrollService->savePayrollDetails(
                        $payroll,
                        $payrollData['allowance_details'] ?? [],
                        $payrollData['deduction_details'] ?? []
                    );

                    $this->payrollService->updatePendingDeductions(
                        $employee,
                        $payrollData['new_pending_deductions'] ?? 0
                    );

                    $generated++;
                } catch (\Exception $e) {
                    $errors[] = $employee->full_name . ': ' . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => "Daily payroll generated for {$generated} employees. {$skipped} skipped. " . (count($errors) > 0 ? count($errors) . " errors." : ""),
                'errors' => $errors,
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => ['general' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Update payroll (add manual allowances/deductions, edit notes)
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('hr.payroll.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        if (! $payroll->canEdit()) {
            return response()->json([
                'error' => 'Cannot edit paid payroll.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'manual_allowances' => 'nullable|numeric|min:0',
            'manual_deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Update manual adjustments
            $payroll->update([
                'manual_allowances' => $request->manual_allowances ?? 0,
                'manual_deductions' => $request->manual_deductions ?? 0,
                'notes' => $request->notes,
            ]);

            // Recalculate net salary
            $totalDeductions = $payroll->deductions +
                              $payroll->attendance_deductions +
                              $payroll->manual_deductions +
                              $payroll->carried_forward_deduction;

            $grossSalary = $payroll->basic_salary +
                          $payroll->allowances +
                          $payroll->manual_allowances;

            $payroll->update([
                'gross_salary' => $grossSalary,
                'net_salary' => $grossSalary - $totalDeductions,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Payroll updated successfully.',
                'payroll' => $payroll->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => ['general' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Mark payroll as reviewed
     */
    public function markReviewed($id)
    {
        if (! auth()->user()->can('hr.payroll.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        if (! $payroll->canMarkReviewed()) {
            return response()->json([
                'error' => 'Payroll is not in generated status.',
            ], 403);
        }

        $payroll->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => 'Payroll marked as reviewed successfully.',
        ]);
    }

    /**
     * Mark payroll as paid
     */
    public function markPaid($id)
    {
        if (! auth()->user()->can('hr.payroll.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        if (! $payroll->canMarkPaid()) {
            return response()->json([
                'error' => 'Payroll cannot be marked as paid.',
            ], 403);
        }

        $payroll->update([
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        return response()->json([
            'success' => 'Payroll marked as paid successfully.',
        ]);
    }

    /**
     * Delete payroll
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('hr.payroll.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $payroll = Payroll::findOrFail($id);

        // Only allow deletion if not paid
        if ($payroll->status === 'paid') {
            return response()->json([
                'error' => 'Cannot delete paid payroll.',
            ], 403);
        }

        $payroll->delete();

        return response()->json([
            'success' => 'Payroll deleted successfully.',
        ]);
    }

    /**
     * Auto-generate daily payroll when employee checks out
     * This should be called from attendance checkout process
     */
    public function autoGenerateDaily(Employee $employee, Attendance $attendance)
    {
        // Check if employee uses daily wages
        if (! $employee->salaryStructure || ! $employee->salaryStructure->use_daily_wages) {
            return;
        }

        // Check if payroll already exists for this date
        $month = Carbon::parse($attendance->date)->format('Y-m');
        $exists = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('payroll_type', 'daily')
            ->whereDate('created_at', $attendance->date)
            ->exists();

        if ($exists) {
            return;
        }

        try {
            DB::beginTransaction();

            $payrollData = $this->payrollService->calculateDailyPayroll($employee, $attendance);

            $payroll = Payroll::create(array_merge(
                ['employee_id' => $employee->id],
                Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
            ));

            $this->payrollService->savePayrollDetails(
                $payroll,
                $payrollData['allowance_details'] ?? [],
                $payrollData['deduction_details'] ?? []
            );

            $this->payrollService->updatePendingDeductions(
                $employee,
                $payrollData['new_pending_deductions'] ?? 0
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Auto-generate daily payroll failed: '.$e->getMessage());
        }
    }
}
