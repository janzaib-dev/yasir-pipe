<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Zone;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::orderByDesc('id')->get();
        return view('admin_panel.zone.index', compact('zones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone' => 'required|string|max:255'
        ]);

        Zone::updateOrCreate(
            ['id' => $request->edit_id],
            ['zone' => $request->zone]
        );

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $zone = Zone::findOrFail($id);
        return response()->json($zone);
    }

    public function destroy($id)
    {
        Zone::findOrFail($id)->delete();
        return response()->json(['status' => 'deleted']);
    }
}
