<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name',"ASC")->get();
        return view('admin_panel.permissions.permission', compact('permissions'));
    }

    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name,' . $request->edit_id,
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        // Step 2: Check for user_id uniqueness (exclude self in edit)
        // $userExists = Branch::where('user_id', $request->user_id)
        //     ->when($editId, fn($q) => $q->where('id', '!=', $editId))
        //     ->exists();

        // if ($userExists) {
        //     return response()->json([
        //         'errors' => [
        //             'user_id' => ['This user is already assigned to another branch.']
        //         ]
        //     ]);
        // }

        // Step 3: Save or update logic
        if (!empty($editId)) {
//            $permission = Permission::find($editId);
            $permission = Permission::findOrFail($editId);
            $msg = [
                'success' => 'Permission Updated Successfully',
                'reload' => true
            ];
        } else {
            $permission = new Permission();
            $msg = [
                'success' => 'Permission Created Successfully',
                'redirect' => route('permissions.index')
            ];
        }

        $permission->name = $request->name;
        $permission->save();

        return response()->json($msg);

    }

    /**
     * Display the specified resource.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');

    }
}
