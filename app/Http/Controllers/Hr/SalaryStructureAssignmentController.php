<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSalaryStructure;
use App\Models\Hr\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalaryStructureAssignmentController extends Controller
{
    use Traits\SalaryStructureHelper;

    /**
     * Show assignment page
     */
    public function assignPage(SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $departments = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();

        return view('hr.salary-structure.assign', compact('salaryStructure', 'departments', 'designations'));
    }

    /**
     * Fetch employees by filter
     */
    public function fetchEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter_type' => 'required|in:all,department,designation',
            'department_id' => 'nullable|required_if:filter_type,department|exists:hr_departments,id',
            'designation_id' => 'nullable|required_if:filter_type,designation|exists:hr_designations,id',
            'salary_structure_id' => 'required|exists:hr_salary_structures,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Restore functionality with safer query
            $query = Employee::with(['department', 'designation', 'activeSalaryStructure.salaryStructure']);
            
            // Apply filters
            if ($request->filter_type === 'department') {
                $query->where('department_id', $request->department_id);
            } elseif ($request->filter_type === 'designation') {
                $query->where('designation_id', $request->designation_id);
            }

            $employees = $query->orderBy('first_name')->get();

            // Add assignment status for this salary structure
            $salaryStructure = SalaryStructure::find($request->salary_structure_id);
            if (!$salaryStructure) {
                return response()->json(['error' => 'Salary Structure not found (ID: ' . $request->salary_structure_id . ')'], 404);
            }
            
            // Simplify pluck to avoid potential ambiguity or join issues
            // Fetch validation of assignment via collection to be safe
            // Use relations properly
            $assignedEmployeeIds = $salaryStructure->assignedEmployees->pluck('id')->toArray();

            $employees = $employees->map(function ($employee) use ($assignedEmployeeIds, $request) {
                // Check if employee is strictly assigned to THIS structure
                $employee->is_already_assigned = in_array($employee->id, $assignedEmployeeIds);
                
                // Check if has another active structure
                $activeStruct = $employee->activeSalaryStructure;
                $employee->has_other_structure = $activeStruct && 
                                                 $activeStruct->salary_structure_id != $request->salary_structure_id;
                
                return $employee;
            });

            return response()->json([
                'employees' => $employees,
                'count' => $employees->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server Error: ' . $e->getMessage() . ' on line ' . $e->getLine()
            ], 500);
        }
    }

    /**
     * Assign salary structure to employees (bulk)
     */
    public function assign(Request $request, SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:hr_employees,id',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date ?? Carbon::today()->toDateString();
        $assigned = 0;
        $skipped = [];
        $replaced = 0;

        DB::beginTransaction();
        try {
            foreach ($request->employee_ids as $employeeId) {
                $employee = Employee::find($employeeId);

                if (!$employee || $employee->status !== 'active') {
                    $skipped[] = [
                        'id' => $employeeId,
                        'name' => $employee->full_name ?? 'Unknown',
                        'reason' => 'Employee is not active',
                    ];
                    continue;
                }

                // Check if already assigned to THIS structure
                $existingAssignment = EmployeeSalaryStructure::where('employee_id', $employeeId)
                    ->where('salary_structure_id', $salaryStructure->id)
                    ->where('is_active', true)
                    ->whereNull('end_date')
                    ->first();

                if ($existingAssignment) {
                    $skipped[] = [
                        'id' => $employeeId,
                        'name' => $employee->full_name,
                        'reason' => 'This employee already has this salary structure assigned',
                    ];
                    continue;
                }

                // End any other active salary structure assignments
                $otherActiveAssignments = EmployeeSalaryStructure::where('employee_id', $employeeId)
                    ->where('salary_structure_id', '!=', $salaryStructure->id)
                    ->where('is_active', true)
                    ->whereNull('end_date')
                    ->get();

                foreach ($otherActiveAssignments as $oldAssignment) {
                    $oldAssignment->endAssignment(Carbon::parse($startDate)->subDay()->toDateString());
                    $replaced++;
                }

                // Create new assignment
                EmployeeSalaryStructure::create([
                    'employee_id' => $employeeId,
                    'salary_structure_id' => $salaryStructure->id,
                    'start_date' => $startDate,
                    'is_active' => true,
                    'assigned_by' => auth()->id(),
                    'notes' => $request->notes,
                ]);

                $assigned++;
            }

            DB::commit();

            $message = "Salary structure assigned successfully to {$assigned} employee(s).";
            if ($replaced > 0) {
                $message .= " {$replaced} previous salary structure(s) ended automatically.";
            }
            if (count($skipped) > 0) {
                $message .= " " . count($skipped) . " employee(s) skipped.";
            }

            return response()->json([
                'success' => $message,
                'assigned_count' => $assigned,
                'skipped_count' => count($skipped),
                'replaced_count' => $replaced,
                'skipped' => $skipped,
                'redirect' => route('hr.salary-structure.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Assignment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View assigned employees
     */
    public function viewAssigned(SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Include assignments from Child (Custom) structures
        $structureIds = $salaryStructure->children()->pluck('id')->push($salaryStructure->id);

        $assignments = EmployeeSalaryStructure::whereIn('salary_structure_id', $structureIds)
            ->with(['employee.department', 'employee.designation', 'assignedBy', 'salaryStructure'])
            ->orderBy('is_active', 'desc')
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return view('hr.salary-structure.view-assigned', compact('salaryStructure', 'assignments'));
    }

    /**
     * Remove/end employee assignment
     */
    public function removeAssignment(Request $request, SalaryStructure $salaryStructure, Employee $employee)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->where('salary_structure_id', $salaryStructure->id)
            ->where('is_active', true)
            ->whereNull('end_date')
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Active assignment not found.'], 404);
        }

        $assignment->endAssignment($request->end_date);

        return response()->json([
            'success' => 'Salary structure assignment ended successfully.',
        ]);
    }

    /**
     * Show page to select employee for individual update
     */
    public function individualUpdatePage(SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Get IDs of this structure and all its custom children
        $structureIds = $salaryStructure->children()->pluck('id')->push($salaryStructure->id);

        // Fetch employees who have an ACTIVE assignment to any of these structures
        $assignments = Employee::whereHas('activeSalaryStructure', function($q) use ($structureIds) {
                $q->whereIn('salary_structure_id', $structureIds);
            })
            ->with(['department', 'designation', 'activeSalaryStructure'])
            ->orderBy('first_name')
            ->paginate(15);

        return view('hr.salary-structure.individual-update-list', compact('salaryStructure', 'assignments'));
    }

    /**
     * Edit individual employee's salary structure (Override)
     */
    public function editIndividual(Employee $employee)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Get current active structure
        $currentAssignment = $employee->activeSalaryStructure;
        
        if (!$currentAssignment) {
            return redirect()->back()->with('error', 'Employee does not have an active salary structure.');
        }
        
        $salaryStructure = $currentAssignment->salaryStructure;
        
        // Prepare view data similar to create/edit
        $readOnly = false;
        $canCreate = true;
        $canEdit = true;
        $isIndividualUpdate = true; // Flag for view

        return view('hr.salary-structure.edit-individual', compact(
            'salaryStructure', 
            'employee', 
            'currentAssignment',
            'readOnly', 
            'canCreate', 
            'canEdit',
            'isIndividualUpdate'
        ));
    }

    /**
     * Process individual update (Clone and Assign)
     */
    public function updateIndividual(Request $request, Employee $employee)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'effective_date' => 'required|date', // Effective date for the new structure
            'salary_type' => 'required|in:salary,commission,both',
            'base_salary' => 'nullable|numeric|min:0',
            'daily_wages' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'allowances' => 'nullable|array',
            'deductions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Check Effective Date Validity
            $activeAssignment = $employee->activeSalaryStructure;
            $effectiveDate = Carbon::parse($request->effective_date);
            
            if ($activeAssignment) {
                $lastStartDate = Carbon::parse($activeAssignment->start_date);
                if ($effectiveDate->lt($lastStartDate)) {
                    return response()->json(['errors' => ['effective_date' => ['Effective date cannot be before the current structure start date.']]], 422);
                }
            }

            // 2. Create NEW Custom Salary Structure
            // Process allowances/deductions (copy logic from SalaryStructureController store)
            $allowances = collect($request->allowances ?? [])->filter(function ($item) {
                return ! empty($item['name']) && isset($item['amount']);
            })->map(function($item) {
                 $item['is_active'] = isset($item['is_active']) && ($item['is_active'] == '1' || $item['is_active'] === true || $item['is_active'] === 'true');
                 return $item;
            })->values()->toArray();
    
            $deductions = collect($request->deductions ?? [])->filter(function ($item) {
                return ! empty($item['name']) && isset($item['amount']);
            })->map(function($item) {
                 $item['is_active'] = isset($item['is_active']) && ($item['is_active'] == '1' || $item['is_active'] === true || $item['is_active'] === 'true');
                 return $item;
            })->values()->toArray();

            // Handle Attendance Policy
            $lateRulesValidation = $this->validateAndNormalizeDeductionRules($request->late_rules ?? [], 'late_rules');
            if ($lateRulesValidation['error']) {
                return response()->json(['errors' => ['late_rules' => [$lateRulesValidation['error']]]], 422);
            }

            $earlyRulesValidation = $this->validateAndNormalizeDeductionRules($request->early_rules ?? [], 'early_rules');
            if ($earlyRulesValidation['error']) {
                return response()->json(['errors' => ['early_rules' => [$earlyRulesValidation['error']]]], 422);
            }

            $attendancePolicy = [
                'late_rules' => $lateRulesValidation['rules'],
                'early_rules' => $earlyRulesValidation['rules'],
            ];
            
            $salaryStructure = $activeAssignment ? $activeAssignment->salaryStructure : null;

            $newStructure = SalaryStructure::create([
                'name' => $request->name, // Custom Name
                'parent_structure_id' => $salaryStructure ? ($salaryStructure->parent_structure_id ?? $salaryStructure->id) : null, // Maintain hierarchy
                'employee_id' => $employee->id, // Link to employee (Custom)
                'salary_type' => $request->salary_type,
                'base_salary' => $request->base_salary ?? 0,
                'daily_wages' => $request->daily_wages ?? 0,
                'use_daily_wages' => $request->has('use_daily_wages') || $request->use_daily_wages == '1',
                'commission_percentage' => $request->commission_percentage,
                'sales_target' => $request->sales_target,
                'allowances' => $allowances ?: null,
                'deductions' => $deductions ?: null,
                'attendance_deduction_policy' => $attendancePolicy, 
                'leave_salary_per_day' => $request->leave_salary_per_day,
                'carry_forward_deductions' => $request->has('carry_forward_deductions') || $request->carry_forward_deductions == '1',
            ]);

            // 3. End Current Active Assignment
            if ($activeAssignment) {
                $endDate = $effectiveDate->copy()->subDay();
                $activeAssignment->update([
                    'end_date' => $endDate,
                    'is_active' => false
                ]);
            }

            // 4. Create New Assignment
            EmployeeSalaryStructure::create([
                'employee_id' => $employee->id,
                'salary_structure_id' => $newStructure->id,
                'start_date' => $effectiveDate,
                'is_active' => true,
                'is_custom' => true,
                'assigned_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'notes' => 'Individual Update: ' . ($request->notes ?? ''),
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Individual salary structure updated successfully.',
                'redirect' => route('hr.salary-structure.view-assigned', $newStructure->id), // Or back to list
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }
}
