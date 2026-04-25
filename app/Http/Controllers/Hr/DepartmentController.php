<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('hr.departments.view')) {
            abort(403, 'Unauthorized action.');
        }
        $departments = Department::latest()->paginate(12);

        return view('hr.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('edit_id')) {
            if (! auth()->user()->can('hr.departments.edit')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            $department = Department::findOrFail($request->edit_id);
            $department->update($request->all());

            return response()->json([
                'success' => 'Department Updated Successfully',
                'reload' => true,
            ]);
        } else {
            if (! auth()->user()->can('hr.departments.create')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            Department::create($request->all());

            return response()->json([
                'success' => 'Department Created Successfully',
                'reload' => true,
            ]);
        }
    }

    public function update(Request $request, Department $department)
    {
        // Not used with this AJAX pattern
    }

    public function destroy(Department $department)
    {
        if (! auth()->user()->can('hr.departments.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        $department->delete();

        return response()->json([
            'success' => 'Department Deleted Successfully',
            'reload' => true,
        ]);
    }
}
