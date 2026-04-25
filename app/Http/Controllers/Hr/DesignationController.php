<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesignationController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('hr.designations.view')) {
            abort(403, 'Unauthorized action.');
        }
        $designations = Designation::latest()->paginate(12);
        return view('hr.designations.index', compact('designations'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:hr_designations,name,' . $request->edit_id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'requires_location' => $request->has('requires_location') ? 1 : 0,
        ];

        if ($request->filled('edit_id')) {
            if (!auth()->user()->can('hr.designations.edit')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            $designation = Designation::findOrFail($request->edit_id);
            $designation->update($data);
            return response()->json([
                'success' => 'Designation Updated Successfully',
                'reload' => true
            ]);
        } else {
            if (!auth()->user()->can('hr.designations.create')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            Designation::create($data);
            return response()->json([
                'success' => 'Designation Created Successfully',
                'reload' => true
            ]);
        }
    }

    public function destroy(Designation $designation)
    {
        if (!auth()->user()->can('hr.designations.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        $designation->delete();
        return response()->json([
            'success' => 'Designation Deleted Successfully',
            'reload' => true
        ]);
    }
}
