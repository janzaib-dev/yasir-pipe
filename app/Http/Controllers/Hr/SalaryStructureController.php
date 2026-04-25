<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSalaryStructure;
use App\Models\Hr\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalaryStructureController extends Controller
{
    use Traits\SalaryStructureHelper;

    public function index()
    {
        // Need at least view permission to see the list
        if (! auth()->user()->can('hr.salary.structure.view') && 
            ! auth()->user()->can('hr.salary.structure.create') && 
            ! auth()->user()->can('hr.salary.structure.edit')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Fetch salary structures with assigned employee counts
        // Show only Templates (no parent, no specific employee)
        $structures = SalaryStructure::whereNull('parent_structure_id')
            ->whereNull('employee_id')
            ->withCount(['assignedEmployees as assigned_count'])
            ->with(['children' => function($query) {
                $query->select('id', 'parent_structure_id')->withCount('assignedEmployees as assigned_count');
            }])
            ->orderBy('created_at', 'desc')->paginate(12);

        // Pass permission flags to view
        $canView = auth()->user()->can('hr.salary.structure.view');
        $canCreate = auth()->user()->can('hr.salary.structure.create');
        $canEdit = auth()->user()->can('hr.salary.structure.edit');
        $canDelete = auth()->user()->can('hr.salary.structure.delete');

        return view('hr.salary-structure.index', compact('structures', 'canView', 'canCreate', 'canEdit', 'canDelete'));
    }

    /**
     * Show form to create a new standalone salary structure
     */
    public function create()
    {
        if (!auth()->user()->can('hr.salary.structure.create') && 
            !auth()->user()->can('hr.salary.structure.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $salaryStructure = new SalaryStructure();
        $readOnly = false;
        $hasSalaryStructure = false;
        $canCreate = auth()->user()->can('hr.salary.structure.create');
        $canEdit = auth()->user()->can('hr.salary.structure.edit');
        
        // Use a dummy employee for form compatibility
        $employee = new Employee();
        $employee->id = null; // Will create standalone structure

        return view('hr.salary-structure.create', compact('salaryStructure', 'employee', 'readOnly', 'hasSalaryStructure', 'canCreate', 'canEdit'));
    }

    /**
     * Store a new standalone salary structure
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('hr.salary.structure.create') && 
            !auth()->user()->can('hr.salary.structure.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'salary_type' => 'required|in:salary,commission,both',
            'base_salary' => 'nullable|numeric|min:0',
            'daily_wages' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'sales_target' => 'nullable|numeric|min:0',
            'leave_salary_per_day' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|array',
            'deductions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Process allowances, deductions, and attendance rules same as update method
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

        // Validate and normalize deduction rules
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

        // Create standalone salary structure (no employee_id)
        SalaryStructure::create([
            'name' => $request->name,
            'employee_id' => null, // Standalone structure
            'salary_type' => $request->salary_type,
            'base_salary' => $request->base_salary ?? 0,
            'daily_wages' => $request->daily_wages ?? 0,
            'use_daily_wages' => $request->has('use_daily_wages') || $request->use_daily_wages == '1',
            'commission_percentage' => $request->commission_percentage,
            'sales_target' => $request->sales_target,
            'leave_salary_per_day' => $request->leave_salary_per_day,
            'allowances' => $allowances ?: null,
            'deductions' => $deductions ?: null,
            'attendance_deduction_policy' => $attendancePolicy,
            'carry_forward_deductions' => $request->has('carry_forward_deductions') || $request->carry_forward_deductions == '1',
        ]);

        return response()->json([
            'success' => 'Salary Structure Created Successfully',
            'redirect' => route('hr.salary-structure.index'),
        ]);
    }

    /**
     * Show form to edit a standalone salary structure template
     */
    public function editTemplate(SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $readOnly = false;
        $hasSalaryStructure = true;
        // Permissions
        $canCreate = auth()->user()->can('hr.salary.structure.create');
        $canEdit = auth()->user()->can('hr.salary.structure.edit');

        // Check for active assignments to any employee
        // We check if any employee is currently assigned to this structure OR if it has children (custom structures based on it)
        $hasAssignments = $salaryStructure->assignedEmployees()->exists() || $salaryStructure->children()->exists();

        // Dummy employee for view compatibility
        $employee = new Employee();
        $employee->id = null;

        return view('hr.salary-structure.create', compact('salaryStructure', 'employee', 'readOnly', 'hasSalaryStructure', 'canCreate', 'canEdit', 'hasAssignments'));
    }

    /**
     * Update a standalone salary structure template
     */
    public function updateTemplate(Request $request, SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        // Check for active assignments
        $hasAssignments = $salaryStructure->assignedEmployees()->exists() || $salaryStructure->children()->exists();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'salary_type' => 'required|in:salary,commission,both',
            'base_salary' => 'nullable|numeric|min:0',
            'daily_wages' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'sales_target' => 'nullable|numeric|min:0',
            'leave_salary_per_day' => 'nullable|numeric|min:0',
            'commission_tiers' => 'nullable|array',
            'commission_tiers.*.percentage' => 'nullable|numeric|min:0|max:100',
            'commission_tiers.*.upto_amount' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required_with:allowances|string|min:3',
            'allowances.*.amount' => 'required_with:allowances|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.name' => 'required_with:deductions|string|min:3',
            'deductions.*.amount' => 'required_with:deductions|numeric|min:0',
            'late_rules' => 'nullable|array',
            'late_rules.*.max_minutes' => 'nullable|numeric|min:0',
            'late_rules.*.amount' => 'required_with:late_rules|numeric|min:0',
            'early_rules' => 'nullable|array',
            'early_rules.*.max_minutes' => 'nullable|numeric|min:0',
            'early_rules.*.amount' => 'required_with:early_rules|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Rule: Payroll type cannot be changed if employees are assigned
        if ($hasAssignments && $request->salary_type !== $salaryStructure->salary_type) {
             return response()->json(['errors' => ['salary_type' => ['Payroll type cannot be changed because this structure has assigned employees.']]], 422);
        }

        // Validate and normalize deduction rules
        $lateRulesValidation = $this->validateAndNormalizeDeductionRules($request->late_rules ?? [], 'late_rules');
        if ($lateRulesValidation['error']) {
            return response()->json(['errors' => ['late_rules' => [$lateRulesValidation['error']]]], 422);
        }

        $earlyRulesValidation = $this->validateAndNormalizeDeductionRules($request->early_rules ?? [], 'early_rules');
        if ($earlyRulesValidation['error']) {
            return response()->json(['errors' => ['early_rules' => [$earlyRulesValidation['error']]]], 422);
        }

        // Filter and Process Data (logic reused from store/update)
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

        $commissionTiers = collect($request->commission_tiers ?? [])->filter(function ($item) {
            return isset($item['percentage']) && isset($item['upto_amount']) && $item['percentage'] > 0 && $item['upto_amount'] > 0;
        })->values()->toArray();

        $attendancePolicy = [
            'late_rules' => $lateRulesValidation['rules'],
            'early_rules' => $earlyRulesValidation['rules'],
        ];

        DB::beginTransaction();
        try {
            /*
             * PREVIOUSLY: Separation Logic was here.
             * NOW: Direct update. Changes WILL affect currently assigned employees.
             */
             // No legacy archiving. Direct update proceeds below.

            // Now Update the Original Structure (which becomes the "New Version" for future assignments)
            // If assignments exist, we DO NOT update salary_type or use_daily_wages to strictly preserve structural integrity
            $updateData = [
                'name' => $request->name,
                'base_salary' => $request->base_salary ?? 0,
                'daily_wages' => $request->daily_wages ?? 0,
                'commission_percentage' => $request->commission_percentage,
                'sales_target' => $request->sales_target,
                'commission_tiers' => $commissionTiers ?: null,
                'leave_salary_per_day' => $request->leave_salary_per_day,
                'allowances' => $allowances ?: null,
                'deductions' => $deductions ?: null,
                'attendance_deduction_policy' => $attendancePolicy,
                'carry_forward_deductions' => $request->has('carry_forward_deductions') || $request->carry_forward_deductions == '1',
            ];

            if (!$hasAssignments) {
                // Only allow structure type changes if NO ONE is assigned
                $updateData['salary_type'] = $request->salary_type;
                $updateData['use_daily_wages'] = $request->has('use_daily_wages') || $request->use_daily_wages == '1';
            }

            $salaryStructure->update($updateData);

            DB::commit();

            return response()->json([
                'success' => 'Salary Structure Updated Successfully' . ($hasAssignments ? ' (Changes applied to all assigned employees).' : ''),
                'redirect' => route('hr.salary-structure.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a standalone salary structure template
     */
    public function destroyTemplate(SalaryStructure $salaryStructure)
    {
        if (!auth()->user()->can('hr.salary.structure.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        // Hard Block Deletion If Assigned
        // Check for active pivot assignments
        $hasActiveAssignments = $salaryStructure->assignedEmployees()
            ->wherePivot('is_active', true)
            ->exists();
            
        // Also check if any Custom structures (children) depend on it
        $hasChildren = $salaryStructure->children()->exists();

        if ($hasActiveAssignments || $hasChildren) {
           return response()->json([
               'error' => 'Cannot delete salary structure assigned to active employees.'
           ], 422);
        }

        // Safe Delete
        $salaryStructure->delete();

        return response()->json([
            'success' => 'Salary Structure Template Deleted Successfully',
            'redirect' => route('hr.salary-structure.index'), 
        ]);
    }


    public function edit(Employee $employee)
    {
        $hasSalaryStructure = $employee->salaryStructure !== null;
        $canView = auth()->user()->can('hr.salary.structure.view');
        $canCreate = auth()->user()->can('hr.salary.structure.create');
        $canEdit = auth()->user()->can('hr.salary.structure.edit');

        // Permission logic:
        // - If employee HAS salary structure: need view or edit permission
        // - If employee DOESN'T have salary structure: need create or edit permission
        if ($hasSalaryStructure) {
            if (! $canView && ! $canEdit) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (! $canCreate && ! $canEdit) {
                abort(403, 'Unauthorized action.');
            }
        }

        $salaryStructure = $employee->salaryStructure ?? new SalaryStructure;

        // Determine if form should be read-only
        // Read-only if: has salary structure AND only has view permission (not edit)
        // OR: doesn't have salary structure AND only has view permission (not create or edit)
        $readOnly = false;
        if ($hasSalaryStructure && $canView && !$canEdit) {
            $readOnly = true;
        }

        return view('hr.salary-structure.edit', compact('employee', 'salaryStructure', 'readOnly', 'hasSalaryStructure', 'canCreate', 'canEdit'));
    }

    public function update(Request $request, Employee $employee)
    {
        $hasSalaryStructure = $employee->salaryStructure !== null;
        $canCreate = auth()->user()->can('hr.salary.structure.create');
        $canEdit = auth()->user()->can('hr.salary.structure.edit');

        // Permission logic for update:
        // - If employee HAS salary structure: need edit permission
        // - If employee DOESN'T have salary structure: need create or edit permission
        if ($hasSalaryStructure) {
            if (! $canEdit) {
                return response()->json(['error' => 'Unauthorized action. You need edit permission to modify existing salary structure.'], 403);
            }
        } else {
            if (! $canCreate && ! $canEdit) {
                return response()->json(['error' => 'Unauthorized action. You need create permission to assign salary structure.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'salary_type' => 'required|in:salary,commission,both',
            'base_salary' => 'nullable|numeric|min:0',
            'daily_wages' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'sales_target' => 'nullable|numeric|min:0',
            'leave_salary_per_day' => 'nullable|numeric|min:0',
            'commission_tiers' => 'nullable|array',
            'commission_tiers.*.percentage' => 'nullable|numeric|min:0|max:100',
            'commission_tiers.*.upto_amount' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required_with:allowances|string|min:3',
            'allowances.*.amount' => 'required_with:allowances|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.name' => 'required_with:deductions|string|min:3',
            'deductions.*.amount' => 'required_with:deductions|numeric|min:0',
            'late_rules' => 'nullable|array',
            'late_rules.*.max_minutes' => 'nullable|numeric|min:0',
            'late_rules.*.amount' => 'required_with:late_rules|numeric|min:0',
            'early_rules' => 'nullable|array',
            'early_rules.*.max_minutes' => 'nullable|numeric|min:0',
            'early_rules.*.amount' => 'required_with:early_rules|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate and normalize deduction rules
        $lateRulesValidation = $this->validateAndNormalizeDeductionRules($request->late_rules ?? [], 'late_rules');
        if ($lateRulesValidation['error']) {
            return response()->json(['errors' => ['late_rules' => [$lateRulesValidation['error']]]], 422);
        }

        $earlyRulesValidation = $this->validateAndNormalizeDeductionRules($request->early_rules ?? [], 'early_rules');
        if ($earlyRulesValidation['error']) {
            return response()->json(['errors' => ['early_rules' => [$earlyRulesValidation['error']]]], 422);
        }

        // Filter out empty allowances/deductions
        $allowances = collect($request->allowances ?? [])->filter(function ($item) {
            return ! empty($item['name']) && isset($item['amount']);
        })->map(function($item) {
             // Ensure is_active is boolean
             $item['is_active'] = isset($item['is_active']) && ($item['is_active'] == '1' || $item['is_active'] === true || $item['is_active'] === 'true');
             return $item;
        })->values()->toArray();

        $deductions = collect($request->deductions ?? [])->filter(function ($item) {
            return ! empty($item['name']) && isset($item['amount']);
        })->map(function($item) {
             $item['is_active'] = isset($item['is_active']) && ($item['is_active'] == '1' || $item['is_active'] === true || $item['is_active'] === 'true');
             return $item;
        })->values()->toArray();

        // Filter out empty commission tiers
        $commissionTiers = collect($request->commission_tiers ?? [])->filter(function ($item) {
            return isset($item['percentage']) && isset($item['upto_amount'])
                   && $item['percentage'] > 0 && $item['upto_amount'] > 0;
        })->values()->toArray();

        // Process Attendance Rules with normalized data
        $attendancePolicy = [
            'late_rules' => $lateRulesValidation['rules'],
            'early_rules' => $earlyRulesValidation['rules'],
        ];

        SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'salary_type' => $request->salary_type,
                'base_salary' => $request->base_salary ?? 0,
                'daily_wages' => $request->daily_wages ?? 0,
                'use_daily_wages' => $request->has('use_daily_wages') || $request->use_daily_wages == '1',
                'commission_percentage' => $request->commission_percentage,
                'sales_target' => $request->sales_target,
                'commission_tiers' => $commissionTiers ?: null,
                'leave_salary_per_day' => $request->leave_salary_per_day,
                'allowances' => $allowances ?: null,
                'deductions' => $deductions ?: null,
                'attendance_deduction_policy' => $attendancePolicy,
                'carry_forward_deductions' => $request->has('carry_forward_deductions') || $request->carry_forward_deductions == '1',
            ]
        );

        return response()->json([
            'success' => 'Salary Structure ' . ($hasSalaryStructure ? 'Updated' : 'Created') . ' Successfully',
            'redirect' => route('hr.salary-structure.index'),
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:hr_employees,id',
        ]);

        $ids = $request->employee_ids;

        // If only one employee selected, redirect to standard edit
        if (count($ids) == 1) {
            return redirect()->route('hr.salary-structure.edit', $ids[0]);
        }

        $employees = Employee::with('salaryStructure')->whereIn('id', $ids)->get();

        return view('hr.salary-structure.bulk-edit', compact('employees'));
    }

    public function bulkUpdate(Request $request)
    {
        // To be implemented
        return redirect()->back()->with('error', 'Bulk update not fully implemented yet.');
    }




}

