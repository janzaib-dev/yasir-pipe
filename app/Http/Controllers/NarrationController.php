<?php

namespace App\Http\Controllers;

use App\Models\Narration;
use Illuminate\Http\Request;

class NarrationController extends Controller
{
    public function index()
    {
        $narrations = Narration::latest()->get();

        return view('admin_panel.accounts.narration', compact('narrations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_head' => 'required|string|max:255',
            'narration' => 'required|string',
        ]);

        if ($request->id) {
            // Update
            $narration = Narration::findOrFail($request->id);
            $narration->update([
                'expense_head' => $request->expense_head,
                'narration' => $request->narration,
            ]);

            return redirect()->back()->with('success', 'Narration updated successfully.');
        } else {
            // Create
            Narration::create([
                'expense_head' => $request->expense_head,
                'narration' => $request->narration,
            ]);

            return redirect()->back()->with('success', 'Narration added successfully.');
        }
    }

    public function destroy($id)
    {
        Narration::findOrFail($id)->delete();

        return redirect()->route('narrations.index')->with('success', 'Narration deleted successfully.');
    }

    public function fetch(Request $request)
    {
        $query = Narration::query();
        if ($request->has('type')) {
             // Use LIKE for looser matching (e.g. 'Receipt Voucher' vs 'receipt voucher')
            $query->where('expense_head', 'like', '%' . $request->type . '%');
        }

        return response()->json($query->latest()->get());
    }
}
