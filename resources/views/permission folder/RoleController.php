<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
     public function index()
    {
        $roles = Role::orderBy('name',"ASC")->get();
        $allPermissions  = Permission::all();
        return view('admin_panel.roles.role', compact(['roles', 'allPermissions']));
    }

    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;
         $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name,'.$request->edit_id,
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
            $role = role::find($editId);
            $msg = [
                'success' => 'Roles Updated Successfully',
                'reload' => true
            ];
        } else {
            $role = new role();
            $msg = [
                'success' => 'Roles Created Successfully',
                'redirect' => route('roles.index')
            ];
        }

        $role->name = $request->name;
        $role->save();

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
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');

    }

    public function updatePermissions(Request $request)
    {
        // $request->validate([
        //     'edit_id' => 'required|exists:roles,id',
        //     'permissions' => 'array',
        //     'permissions.*' => 'exists:permissions,name'
        // ]);
        // dd($request->toArray());
        $role = Role::findOrFail($request->edit_id);

        // Assign new roles (by name)
        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', 'Role permissions updated successfully!');
    }
}
