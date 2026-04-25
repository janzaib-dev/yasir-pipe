<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('hr.leaves.view')) {
            abort(403, 'Unauthorized action.');
        }
        $leaves = Leave::with('employee')->latest()->paginate(12);
        $employees = Employee::all();

        return view('hr.leaves.index', compact('leaves', 'employees'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'leave_type' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (! auth()->user()->can('hr.leaves.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        Leave::create($request->all());

        return response()->json([
            'success' => 'Leave request submitted successfully.',
            'reload' => true,
        ]);
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        if (! auth()->user()->can('hr.leaves.approve')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $leave->update(['status' => $request->status]);

        return response()->json([
            'success' => 'Leave status updated.',
            'reload' => true,
        ]);
    }
}
