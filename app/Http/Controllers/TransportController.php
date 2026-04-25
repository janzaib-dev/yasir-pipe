<?php

namespace App\Http\Controllers;

use App\Models\Transport;
use Illuminate\Http\Request;
use Auth;

class TransportController extends Controller
{
    public function index()
    {
        $transports = Transport::all();
        return view('admin_panel.transport.index', compact('transports'));
    }

    public function create()
    {
        return view('admin_panel.transport.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'company_name' => 'required',
            'mobile' => 'required',
        ]);

        Transport::create([
            'admin_or_user_id' => Auth::id(),
            'company_name' => $request->company_name,
            'name' => $request->name,
            'name_ur' => $request->name_ur,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'address_ur' => $request->address_ur,
        ]);


        return redirect()->route('transport.index')->with('success', 'Transport added successfully.');
    }
public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required',
        'company_name' => 'required',
        'mobile' => 'required',
    ]);

    $transport = Transport::findOrFail($id);

    $transport->update([
        'company_name' => $request->company_name,
        'name' => $request->name,
        'name_ur' => $request->name_ur,
        'email' => $request->email,
        'mobile' => $request->mobile,
        'address' => $request->address,
        'address_ur' => $request->address_ur,
    ]);

    return redirect()->route('transport.index')->with('success', 'Transport updated successfully.');
}

    public function edit($id)
    {
        $transport = Transport::findOrFail($id);
        return view('admin_panel.transport.edit', compact('transport'));
    }

    public function delete($id)
    {
        $transport = Transport::findOrFail($id);
        $transport->delete();

        return redirect()->route('transport.index')->with('success', 'Transport deleted successfully.');
    }
}
