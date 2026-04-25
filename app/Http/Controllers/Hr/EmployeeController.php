<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('hr.employees.view')) {
            abort(403, 'Unauthorized action.');
        }
        $employees = Employee::with(['department', 'designation', 'shift', 'leaves' => function ($q) {
            $q->where('leave_type', 'Casual');
        }])->latest()->paginate(12);
        $departments = Department::all();
        $designations = Designation::all();
        $shifts = \App\Models\Hr\Shift::all();

        return view('hr.employees.index', compact('employees', 'departments', 'designations', 'shifts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string|max:11',
            'email' => 'required|email|max:255|unique:hr_employees,email,'.$request->edit_id,
            'department_id' => 'required|exists:hr_departments,id',
            'designation_id' => 'required|exists:hr_designations,id',
            'joining_date' => 'required|date',
            'basic_salary' => 'required|numeric',
            'password' => 'nullable|min:6',
            'punch_gap_minutes' => 'nullable|integer|min:1|max:120',
            'document_degree' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_certificate' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_hsc_marksheet' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_ssc_marksheet' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_cv' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['document_degree', 'document_certificate', 'document_hsc_marksheet', 'document_ssc_marksheet', 'document_cv', 'password', 'casual_leave_days']);
        $data['is_docs_submitted'] = $request->has('is_docs_submitted') ? 1 : 0;

        // Handle Custom Shift Logic
        if ($request->shift_id === 'custom') {
            $data['shift_id'] = null; // No standard shift assigned
        } else {
            // Standard shift assigned, clear custom times
            $data['custom_start_time'] = null;
            $data['custom_end_time'] = null;
        }

        if ($request->filled('edit_id')) {
            if (! auth()->user()->can('hr.employees.edit')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            $employee = Employee::findOrFail($request->edit_id);

            // Update User email if changed
            if ($employee->user_id) {
                $user = \App\Models\User::find($employee->user_id);
                if ($user) {
                    $user->email = $request->email;
                    $user->name = $request->first_name.' '.$request->last_name;
                    if ($request->filled('password')) {
                        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
                    }
                    $user->save();
                }
            }

            $employee->update($data);
        } else {
            if (! auth()->user()->can('hr.employees.create')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            // Create User Account
            $user = \App\Models\User::create([
                'name' => $request->first_name.' '.$request->last_name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);

            $data['user_id'] = $user->id;
            $employee = Employee::create($data);
        }

        // Handle File Uploads (Create/Update in hr_employee_documents)
        $fileFields = ['document_degree', 'document_certificate', 'document_hsc_marksheet', 'document_ssc_marksheet', 'document_cv'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('employee_docs', 'public');

                $employee->documents()->updateOrCreate(
                    ['type' => str_replace('document_', '', $field)],
                    ['file_path' => $path, 'file_name' => $file->getClientOriginalName()]
                );
            }
        }

        // Handle Casual Leave Days Sync
        if ($request->has('casual_leave_days')) {
            $submittedDates = $request->casual_leave_days ? explode(',', $request->casual_leave_days) : [];
            $submittedDates = array_map('trim', $submittedDates);

            // Get existing single-day Casual leaves
            $existingLeaves = $employee->leaves()
                ->where('leave_type', 'Casual')
                ->whereRaw('start_date = end_date') // Only manage single-day leaves to avoid messing up ranges
                ->get();

            $existingDates = $existingLeaves->pluck('start_date')->map(function ($d) {
                return \Carbon\Carbon::parse($d)->format('Y-m-d');
            })->toArray();

            // 1. Create new leaves
            foreach ($submittedDates as $date) {
                if (! empty($date) && ! in_array($date, $existingDates)) {
                    // Check if leave already exists (e.g. part of a range) - optional check
                    $employee->leaves()->create([
                        'leave_type' => 'Casual',
                        'start_date' => $date,
                        'end_date' => $date,
                        'reason' => 'Casual Leave assigned via Employee Form',
                        'status' => 'approved',
                    ]);
                }
            }

            // 2. Delete removed leaves
            foreach ($existingLeaves as $leave) {
                $leaveDate = \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d');
                if (! in_array($leaveDate, $submittedDates)) {
                    $leave->delete();
                }
            }
        }

        // Auto-sync to biometric device when creating new employee
        // Auto-sync to biometric device when creating new employee
        // DISABLED FOR PERFORMANCE: This causes the request to hang if the device is slow.
        // Users should sync manually from the Device Manager page.
        /*
        if (! $request->filled('edit_id')) {
            try {
                $activeDevice = \App\Models\BiometricDevice::active()->first();
                if ($activeDevice) {
                    $syncService = app(\App\Services\BiometricSyncService::class);
                    $syncService->syncEmployeeToDevice($employee, $activeDevice);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to auto-sync employee to biometric device: '.$e->getMessage());
                // Don't fail the employee creation if sync fails
            }
        }
        */

        return response()->json(['success' => 'Employee saved successfully']);
    }

    public function destroy(Employee $employee)
    {
        if (! auth()->user()->can('hr.employees.delete')) {
            abort(403, 'Unauthorized action.');
        }
        // Delete User Account
        if ($employee->user_id) {
            \App\Models\User::destroy($employee->user_id);
        }
        // Delete all casual leave records for this employee
        $employee->leaves()->where('leave_type', 'Casual')->delete();
        $employee->delete();

        return response()->json(['success' => 'Employee deleted successfully']);
    }

    /**
     * Get face encodings for all employees (for Kiosk)
     */
    public function getEncodings()
    {
        $employees = Employee::whereNotNull('face_encoding')
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'face_encoding', 'face_photo', 'designation_id', 'department_id')
            ->with(['department', 'designation'])
            ->get();

        $data = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'department' => $emp->department->name ?? 'N/A',
                'designation' => $emp->designation->name ?? 'N/A',
                'photo' => $emp->face_photo ? asset($emp->face_photo) : null,
                'descriptor' => $emp->face_encoding,
            ];
        });

        return response()->json($data);
    }

    /**
     * Store face encoding for an employee
     */
    public function storeFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'descriptor' => 'required|array',
            'image' => 'nullable|string', // Base64 image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);
        $employee->face_encoding = $request->descriptor;

        // Save face photo if provided
        if ($request->image) {
            $imageData = explode(',', $request->image);
            if (count($imageData) > 1) {
                $decoded = base64_decode($imageData[1]);
                $fileName = 'face_'.$employee->id.'_'.time().'.jpg';
                $path = 'uploads/faces/';

                if (! file_exists(public_path($path))) {
                    mkdir(public_path($path), 0755, true);
                }

                file_put_contents(public_path($path.$fileName), $decoded);
                $employee->face_photo = $path.$fileName;
            }
        }

        $employee->save();

        return response()->json(['success' => 'Face registered successfully for '.$employee->full_name]);
    }
}
