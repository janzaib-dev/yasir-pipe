<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class CategoryController extends Controller
{
    
    public function index()
    {
        // $userId = Auth::id();
      $category = Category::get();
      return  view("admin_panel.category.index",compact('category'));


    }

    public function store(Request $request)
{
    
    // Validation
    $validator = Validator::make($request->all(), [
        'name' => 'required|unique:categories,name,' . $request->edit_id . ',id',
    ]);

     if ($validator->fails()) {

    return redirect()->back()
        ->withErrors($validator)
        ->withInput()
        ->with('catagory_swal_error', $validator->errors()->first());
}

    /**
     * UPDATE CATEGORY
     */
    if ($request->filled('edit_id')) {
        $category = Category::findOrFail($request->edit_id);
        $category->name = $request->name;
        $category->save();

        return response()->json([
            'success' => 'Category Updated Successfully',
            'reload'  => true
        ]);
    }

    /**
     * CREATE CATEGORY
     */
    $category = new Category();
    $category->name = $request->name;
    $category->save();

    /**
     * IF REQUEST FROM PRODUCT PAGE
     */
    $obj = Category::all();
    if ($request->page === 'product_page') {
        
       return redirect()->back()->with('success', 'Category saved successfully');
    }

    /**
     * NORMAL FLOW
     */
    return response()->json([
        'success'  => 'Category Created Successfully',
        'redirect' => route('Category.home')
    ]);
}

    public function delete($id)
    {

        $company = Category::find($id);
        if ($company) {
            $company->delete();
            $msg = [
                'success' => 'Category Deleted Successfully',
                'reload' =>  route('Category.home'),
            ];
        } else {
            $msg = ['error' => 'Category Not Found'];
        }
        return response()->json($msg);
    }
   
     
}
