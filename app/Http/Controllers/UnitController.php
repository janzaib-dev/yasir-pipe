<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class UnitController extends Controller
{
    public function index()
    {
        // $userId = Auth::id();
      $unit = Unit::get();
      return  view("admin_panel.unit.index",compact('unit'));


    }

    public function store(request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:units,name,'.$request->edit_id,
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }


        if($request->has('edit_id') && $request->edit_id != '' || $request->edit_id != null ){
            $Company = Unit::find($request->edit_id);
            $msg = [
                'success' => 'Unit Updated Successfully',
                'reload' => true
            ];
        }
        else{
            $Company = new Unit();
            $msg = [
                'success' => 'Unit Created Successfully',
                'redirect' => route('Unit.home')
            ];
        }
        $Company->name = $request->name;
        $Company->save();

        return response()->json($msg);
    }

    public function delete($id)
    {

        $company = Unit::find($id);
        if ($company) {
            $company->delete();
            $msg = [
                'success' => 'Unit Deleted Successfully',
                'reload' =>  route('Unit.home'),
            ];
        } else {
            $msg = ['error' => 'Unit Not Found'];
        }
        return response()->json($msg);
    }
}
