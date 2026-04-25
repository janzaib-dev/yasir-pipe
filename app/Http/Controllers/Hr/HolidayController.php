<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('hr.holidays.view')) {
            abort(403, 'Unauthorized action.');
        }
        $year = request('year', date('Y'));
        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->paginate(12)->withQueryString();
        return view('hr.holidays.index', compact('holidays', 'year'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:public,company,optional',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('edit_id')) {
            if (!auth()->user()->can('hr.holidays.edit')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            $holiday = Holiday::findOrFail($request->edit_id);
            $holiday->update($request->all());
            $message = 'Holiday Updated Successfully';
        } else {
            if (!auth()->user()->can('hr.holidays.create')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            // Check if date already exists
            if (Holiday::whereDate('date', $request->date)->exists()) {
                return response()->json(['errors' => ['date' => ['A holiday already exists on this date.']]], 422);
            }
            Holiday::create($request->all());
            $message = 'Holiday Created Successfully';
        }

        return response()->json(['success' => $message]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('hr.holidays.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();
        return response()->json(['success' => 'Holiday Deleted Successfully']);
    }

    /**
     * API to get holidays for calendar
     */
    public function getHolidays(Request $request)
    {
        if (!auth()->user()->can('hr.holidays.view')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');
        
        $query = Holiday::whereYear('date', $year);
        
        if ($month) {
            $query->whereMonth('date', $month);
        }
        
        return response()->json($query->orderBy('date')->get());
    }
}
