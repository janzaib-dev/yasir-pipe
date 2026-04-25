<?php

// app/Http/Controllers/SalesOfficerController.php
namespace App\Http\Controllers;

use App\Models\SalesOfficer;
use Illuminate\Http\Request;

class SalesOfficerController extends Controller
{
    public function index()
    {
        $officers = SalesOfficer::latest()->get();
        return view('admin_panel.sales_officer.index', compact('officers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'name_urdu' => 'nullable|string',
            'mobile' => 'required|string',
        ]);

        if ($request->edit_id) {
            $officer = SalesOfficer::findOrFail($request->edit_id);
            $officer->update($request->only('name', 'name_urdu', 'mobile'));
        } else {
            SalesOfficer::create($request->only('name', 'name_urdu', 'mobile'));
        }

        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        return response()->json(SalesOfficer::findOrFail($id));
    }

    public function destroy($id)
    {
        $officer = SalesOfficer::findOrFail($id);
        $officer->delete();

        return response()->json(['success' => true]);
    }
}
