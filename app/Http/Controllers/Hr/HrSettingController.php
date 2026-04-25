<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrSetting;
use Illuminate\Http\Request;

class HrSettingController extends Controller
{
    /**
     * Update HR Settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'attendance_punch_gap_minutes' => 'required|integer|min:1|max:120',
        ]);

        HrSetting::setValue('attendance_punch_gap_minutes', $request->attendance_punch_gap_minutes);

        return response()->json(['success' => 'Settings updated successfully!']);
    }
}
