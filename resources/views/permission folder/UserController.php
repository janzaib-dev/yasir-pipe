<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        // dd("ok");
        $users = User::where('email', '!=', 'superadmin@example.com')->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'superadmin');
        })->get();
        $allRoles = Role::all();

        return view('admin_panel.users.users', compact(['users', 'allRoles']));
    }

    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;
        $passwordRule = $editId ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users,email,'.$request->edit_id,
            'password' => $passwordRule,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        if (! empty($editId)) {
            $user = User::find($editId);
            $msg = [
                'success' => 'User Updated Successfully',
                'reload' => true,
            ];
        } else {
            $user = new User;
            $msg = [
                'success' => 'User Created Successfully',
                'redirect' => route('users.index'),
            ];
        }

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Always sync roles (empty array will remove all roles)
        $user->syncRoles($request->roles ?? []);

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
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');

    }

    public function updateRoles(Request $request)
    {
        $user = User::findOrFail($request->edit_id);

        // Assign new roles (by name)
        $user->syncRoles($request->roles ?? []);

        // Return JSON so AJAX handlers get a clear response
        return response()->json(['success' => 'User roles updated successfully!', 'reload' => true]);
    }
}
