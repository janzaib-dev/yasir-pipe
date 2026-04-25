<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Attendance;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use App\Models\Hr\Employee;
use App\Models\Hr\Holiday;
use App\Models\Hr\Leave;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->can('hr.attendance.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Get filter values
        $selectedDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDepartment = $request->get('department_id');
        $selectedDesignation = $request->get('designation_id');
        $selectedStatus = $request->get('status');

        $today = Carbon::parse($selectedDate);

        // Build query with filters
        $query = Employee::with(['department', 'designation', 'shift',
            'attendances' => function ($q) use ($selectedDate) {
                $q->whereDate('date', $selectedDate);
            },
            'leaves' => function ($q) use ($selectedDate) {
                $q->where('status', 'approved')
                  ->whereDate('start_date', '<=', $selectedDate)
                  ->whereDate('end_date', '>=', $selectedDate);
            },
        ])->where('status', 'active');

        if ($selectedDepartment) {
            $query->where('department_id', $selectedDepartment);
        }

        if ($selectedDesignation) {
            $query->where('designation_id', $selectedDesignation);
        }

        if ($selectedStatus) {
            $query->whereHas('attendances', function ($q) use ($selectedDate, $selectedStatus) {
                $q->whereDate('date', $selectedDate);
                if ($selectedStatus == 'late') {
                    $q->where(function ($sq) {
                        $sq->where('status', 'late')
                            ->orWhere(function ($ssq) {
                                $ssq->where('status', 'present')->where('is_late', true);
                            });
                    });
                } elseif ($selectedStatus == 'present') {
                    $q->where('status', 'present')->where('is_late', false);
                } else {
                    $q->where('status', $selectedStatus);
                }
            });
        }

        $employees = $query->orderBy('first_name')->paginate(12)->withQueryString();

        // Calculate summary
        // Calculate summary
        $allAttendances = Attendance::whereDate('date', $selectedDate)->get();
        $totalActiveEmployees = Employee::where('status', 'active')->count();
        
        // 1. Present & Late (from key source: actual attendance records)
        $presentCount = $allAttendances->where('status', 'present')->where('is_late', false)->count();
        $lateCount = $allAttendances->filter(function($a) {
            return $a->status == 'late' || ($a->status == 'present' && $a->is_late);
        })->count();
        
        // Get IDs of people who are physically here (present or late) so we don't count them as on leave
        $presentOrLateIds = $allAttendances->whereIn('status', ['present', 'late'])->pluck('employee_id')->values()->toArray();

        // 2. Leaves (from Leave model - source of truth for scheduled leaves)
        // We exclude anyone who is marked present/late today (they showed up despite leave)
        $leaveCount = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->whereHas('employee', function($q) {
                $q->where('status', 'active');
            })
            ->whereNotIn('employee_id', $presentOrLateIds)
            ->count();

        // 3. Absent
        // Anyone active who is not Present, Late, or On Leave
        $accountedForCount = $presentCount + $lateCount + $leaveCount;
        $absentCount = max(0, $totalActiveEmployees - $accountedForCount);

        $summary = [
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'leave' => $leaveCount,
        ];

        $isHoliday = Holiday::isHoliday($today);
        $holiday = Holiday::getHoliday($today);

        // Get departments and designations for filter dropdowns
        $departments = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();

        return view('hr.attendance.index', compact(
            'employees', 'today', 'isHoliday', 'holiday',
            'departments', 'designations', 'summary',
            'selectedDate', 'selectedDepartment', 'selectedDesignation', 'selectedStatus'
        ));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('hr.attendance.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'attendance' => 'required|array',
            'attendance.*.status' => 'nullable|in:present,absent,late,leave',
            'attendance.*.clock_in' => 'nullable|date_format:H:i',
            'attendance.*.clock_out' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $attendanceDate = $request->input('date', 'today');
            $dateParsed = Carbon::parse($attendanceDate);

            // Optimization: Fetch all needed employees with shifts in one query
            $empIds = array_keys($request->attendance);
            \Log::info('Manual Attendance Save Request', ['count' => count($request->attendance), 'data' => $request->attendance]);
            $employees = Employee::with('shift')->whereIn('id', $empIds)->get()->keyBy('id');
            // Get global punch gap for reference if needed (though mostly for UI/pull)
            // $punchGap = \App\Models\Hr\HrSetting::getPunchGapMinutes();

            foreach ($request->attendance as $empId => $data) {
                // ONLY process if the row was explicitly touched by HR (is_dirty == 1)
                $isDirty = isset($data['is_dirty']) && $data['is_dirty'] == '1';
                
                if ($isDirty) {
                    $employee = $employees[$empId] ?? null;
                    if (! $employee) {
                        continue;
                    }

                    // Prepare update data
                    $updateData = [];
                    if (isset($data['status'])) {
                        $updateData['status'] = $data['status'];
                    }

                    // We need to handle potential nulls if they select "empty" time
                    $clockIn = $data['clock_in'] ?? null;
                    $clockOut = $data['clock_out'] ?? null;

                    $updateData['clock_in'] = $clockIn;
                    $updateData['clock_out'] = $clockOut;

                    // IMPORTANT: Update standard check_in_time/check_out_time timestamps
                    // because the View and Biometric Service rely on these.
                    if ($clockIn) {
                        $updateData['check_in_time'] = Carbon::parse($dateParsed->format('Y-m-d').' '.$clockIn)->toDateTimeString();
                        $updateData['check_in_location'] = 'Manual (HR)';
                    } else {
                        $updateData['check_in_time'] = null;
                    }

                    if ($clockOut) {
                        $updateData['check_out_time'] = Carbon::parse($dateParsed->format('Y-m-d').' '.$clockOut)->toDateTimeString();
                        $updateData['check_out_location'] = 'Manual (HR)';
                    } else {
                        $updateData['check_out_time'] = null;
                    }

                    // --- Recalculation Logic ---
                    $isLate = false;
                    $lateMinutes = 0;
                    $isEarlyLeave = false;
                    $earlyLeaveMinutes = 0;
                    $totalHours = 0;

                    $shiftStart = $employee->getStartTime();
                    $shiftEnd = $employee->getEndTime();
                    $graceMinutes = $employee->getGraceMinutes();

                    // Create full Carbon instances for comparison
                    // Note: We use the attendance date provided in request
                    $shiftStartDt = Carbon::parse($dateParsed->format('Y-m-d').' '.Carbon::parse($shiftStart)->format('H:i:s'));
                    $shiftEndDt = Carbon::parse($dateParsed->format('Y-m-d').' '.Carbon::parse($shiftEnd)->format('H:i:s'));

                    // 1. Calculate Late & Early In
                    $isEarlyIn = false;
                    $earlyInMinutes = 0;

                    if ($clockIn && ! empty($clockIn)) {
                        $checkInDt = Carbon::parse($dateParsed->format('Y-m-d').' '.$clockIn);
                        $graceTime = $shiftStartDt->copy()->addMinutes($graceMinutes);

                        $newStatus = 'present'; // Default to present if checked in

                        if ($checkInDt->gt($graceTime)) {
                            $isLate = true;
                            $lateMinutes = $checkInDt->diffInMinutes($shiftStartDt);
                            $newStatus = 'late';
                        } elseif ($checkInDt->lt($shiftStartDt)) {
                             $isEarlyIn = true;
                             $earlyInMinutes = $checkInDt->diffInMinutes($shiftStartDt);
                        }

                        // Only update status if the user didn't explicitly set it to something else (like 'leave' via input)
                        if (! isset($data['status'])) {
                            $updateData['status'] = $newStatus;
                        } elseif ($data['status'] == 'present' && $newStatus == 'late') {
                            $updateData['status'] = 'late';
                        }
                    }

                    // 2. Calculate Early Leave
                    if ($clockOut && ! empty($clockOut)) {
                        $checkOutDt = Carbon::parse($dateParsed->format('Y-m-d').' '.$clockOut);

                        if ($checkOutDt->lt($shiftEndDt)) {
                            $isEarlyLeave = true;
                            $earlyLeaveMinutes = $shiftEndDt->diffInMinutes($checkOutDt);
                        }
                    }

                    // 3. Calculate Total Hours
                    if (! empty($clockIn) && ! empty($clockOut)) {
                        $in = Carbon::parse($clockIn);
                        $out = Carbon::parse($clockOut);
                        if ($out->gt($in)) {
                            $totalHours = round($out->diffInMinutes($in) / 60, 2);
                        }
                    }

                    // Handle 'Absent' Override - But Check for Leave First
                    if (isset($data['status']) && $data['status'] == 'absent') {
                        // Check if employee has approved leave on this date
                        if (Leave::hasApprovedLeave($empId, $dateParsed)) {
                            // Cannot mark absent - employee has approved leave
                            $leave = Leave::getApprovedLeave($empId, $dateParsed);
                            return response()->json([
                                'error' => "Cannot mark {$employee->full_name} as absent. Employee has approved {$leave->leave_type} leave from " . 
                                          Carbon::parse($leave->start_date)->format('M d') . " to " . 
                                          Carbon::parse($leave->end_date)->format('M d, Y') . "."
                            ], 422);
                        }
                        
                        $updateData['clock_in'] = null;
                        $updateData['clock_out'] = null;
                        $updateData['check_in_time'] = null;
                        $updateData['check_out_time'] = null;
                        $isLate = false;
                        $lateMinutes = 0;
                        $isEarlyIn = false;
                        $earlyInMinutes = 0;
                        $isEarlyLeave = false;
                        $earlyLeaveMinutes = 0;
                        $totalHours = 0;
                    }

                    // Check if employee has approved leave (auto-set to leave status)
                    if (Leave::hasApprovedLeave($empId, $dateParsed) && (!isset($data['status']) || $data['status'] != 'leave')) {
                        // If employee has leave but HR hasn't explicitly set it, auto-set to leave
                        if (!isset($data['clock_in']) && !isset($data['clock_out'])) {
                            $updateData['status'] = 'leave';
                        }
                    }

                    $updateData['is_late'] = $isLate;
                    $updateData['late_minutes'] = $lateMinutes;
                    $updateData['is_early_in'] = $isEarlyIn;
                    $updateData['early_in_minutes'] = $earlyInMinutes;
                    $updateData['is_early_leave'] = $isEarlyLeave;
                    $updateData['early_leave_minutes'] = $earlyLeaveMinutes;
                    $updateData['total_hours'] = $totalHours;

                    // Perform Update
                    $attendanceRecord = Attendance::updateOrCreate(
                        ['employee_id' => $empId, 'date' => $dateParsed->format('Y-m-d')],
                        $updateData
                    );

                    // Auto-generate daily payroll if checkout time was added
                    if (!empty($clockOut) && $attendanceRecord) {
                        $this->autoGenerateDailyPayroll($employee, $attendanceRecord);
                    }
                }
            }

            return response()->json([
                'success' => 'Attendance updated successfully.',
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Show the attendance kiosk page
     */
    public function kiosk()
    {
        if (! auth()->user()->can('hr.attendance.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('hr.attendance.kiosk');
    }

    /**
     * Mark attendance via kiosk (with photo)
     */
    public function markAttendance(Request $request)
    {
        if (! auth()->user()->can('hr.attendance.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required|in:check_in,check_out',
            'photo' => 'nullable|string',
            'employee_id' => 'nullable|exists:hr_employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->input('type'); // 'check_in' or 'check_out'
        $photo = $request->input('photo');

        // For now, we'll use a simple employee selection approach
        // In phase 2, we'll integrate face recognition to identify the employee

        // Get employee from session or use a demo employee
        $employeeId = $request->input('employee_id');

        if (! $employeeId) {
            // For demo purposes, get the first active employee
            // In production, this would be determined by face recognition
            $employee = Employee::where('status', 'active')->first();
            if (! $employee) {
                return response()->json(['error' => 'No employees found in system']);
            }
            $employeeId = $employee->id;
        }

        try {
            $employee = Employee::with(['department', 'shift'])->findOrFail($employeeId);
            $today = Carbon::today();
            $now = Carbon::now();

            // Check if today is a holiday
            if (Holiday::isHoliday($today)) {
                $holiday = Holiday::getHoliday($today);

                return response()->json([
                    'error' => 'Today is a holiday: '.$holiday->name,
                ]);
            }

            // Get or create today's attendance
            $attendance = Attendance::firstOrNew([
                'employee_id' => $employee->id,
                'date' => $today->format('Y-m-d'),
            ]);

            // Save photo
            $photoPath = null;
            if ($photo) {
                $photoData = explode(',', $photo);
                if (count($photoData) > 1) {
                    $imageData = base64_decode($photoData[1]);
                    $fileName = 'attendance_'.$employee->id.'_'.$type.'_'.time().'.jpg';
                    $path = 'uploads/attendance/'.date('Y/m/');

                    if (! file_exists(public_path($path))) {
                        mkdir(public_path($path), 0755, true);
                    }
                    file_put_contents(public_path($path.$fileName), $imageData);
                    $photoPath = $path.$fileName;
                }
            }

            $isLate = false;
            $lateMinutes = 0;
            $isEarlyLeave = false;
            $earlyLeaveMinutes = 0;

            if ($type === 'check_in') {
                // Check if already checked in
                if ($attendance->check_in_time) {
                    return response()->json([
                        'error' => 'Already checked in today at '.Carbon::parse($attendance->check_in_time)->format('h:i A'),
                    ]);
                }

                // Start - Late Check Restriction
                $shiftEndForCheck = $employee->getEndTime();
                $shiftLabel = $employee->custom_end_time ? 'Custom Shift' : 'Shift';
                $shiftEndTimeForCheck = Carbon::parse($today->format('Y-m-d').' '.Carbon::parse($shiftEndForCheck)->format('H:i:s'));

                if ($now->gt($shiftEndTimeForCheck)) {
                    return response()->json([
                        'error' => "Cannot check in. Your {$shiftLabel} ended at ".$shiftEndTimeForCheck->format('h:i A'),
                    ]);
                }
                // End - Late Check Restriction

                $attendance->check_in_time = $now->format('H:i:s');
                $attendance->check_in_photo = $photoPath;
                $attendance->status = 'present';

                // Check if late
                $shiftStart = $employee->getStartTime();
                $graceMinutes = $employee->getGraceMinutes();
                // Parse specifically for TODAY
                $shiftStartTime = Carbon::parse($today->format('Y-m-d').' '.Carbon::parse($shiftStart)->format('H:i:s'));
                $graceEndTime = $shiftStartTime->copy()->addMinutes($graceMinutes);

                if ($now->gt($graceEndTime)) {
                    $isLate = true;
                    $lateMinutes = $now->diffInMinutes($shiftStartTime);
                    $attendance->is_late = true;
                    $attendance->late_minutes = $lateMinutes;
                    $attendance->status = 'late';
                }
            } else {
                // Check out
                if (! $attendance->check_in_time) {
                    return response()->json([
                        'error' => 'Please check in first before checking out',
                    ]);
                }

                if ($attendance->check_out_time) {
                    return response()->json([
                        'error' => 'Already checked out today at '.Carbon::parse($attendance->check_out_time)->format('h:i A'),
                    ]);
                }

                $attendance->check_out_time = $now->format('H:i:s');
                $attendance->check_out_photo = $photoPath;

                // Calculate total hours
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = Carbon::parse($attendance->check_out_time);
                $attendance->total_hours = round($checkOut->diffInMinutes($checkIn) / 60, 2);

                // Check if early leave
                $shiftEnd = $employee->getEndTime();
                // Parse specificially for TODAY
                $shiftEndTime = Carbon::parse($today->format('Y-m-d').' '.Carbon::parse($shiftEnd)->format('H:i:s'));

                if ($now->lt($shiftEndTime)) {
                    $isEarlyLeave = true;
                    $earlyLeaveMinutes = $now->diffInMinutes($shiftEndTime);
                    $attendance->is_early_leave = true;
                    $attendance->early_leave_minutes = $earlyLeaveMinutes;
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error: '.$e->getMessage()], 400);
        }

        $attendance->save();

        // Auto-generate daily payroll when checking out
        if ($type === 'check_out') {
            $this->autoGenerateDailyPayroll($employee, $attendance);
        }

        return response()->json([
            'success' => true,
            'message' => $type === 'check_in' ?
                'Check-in recorded at '.$now->format('h:i A') :
                'Check-out recorded at '.$now->format('h:i A'),
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'is_early_leave' => $isEarlyLeave,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'total_hours' => $attendance->total_hours,
            'employee' => [
                'name' => $employee->full_name,
                'department' => $employee->department->name ?? 'N/A',
                'photo' => $employee->face_photo ? asset($employee->face_photo) : null,
            ],
        ]);
    }

    /**
     * Show my attendance page (for logged-in users)
     */
    public function myAttendance()
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->with(['department', 'designation', 'shift'])->first();

        $attendance = null;
        $requiresLocation = false;

        if ($employee) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', Carbon::today())
                ->first();

            // Check if employee's designation requires location
            $requiresLocation = $employee->designation && $employee->designation->requires_location;
        }

        return view('hr.attendance.my-attendance', compact('employee', 'attendance', 'requiresLocation'));
    }

    /**
     * Mark my own attendance
     */
    public function markMyAttendance(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'type' => 'required|in:check_in,check_out',
                'photo' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = auth()->user();
            $employee = Employee::where('user_id', $user->id)->with(['shift', 'designation'])->first();

            if (! $employee) {
                return response()->json(['error' => 'No employee profile found for your account'], 400);
            }

            $type = $request->input('type');
            $today = Carbon::today();
            $now = Carbon::now();

            // Check if today is a holiday
            if (Holiday::isHoliday($today)) {
                $holiday = Holiday::getHoliday($today);

                return response()->json([
                    'error' => 'Today is a holiday: '.$holiday->name,
                ]);
            }

            // Get or create today's attendance
            $attendance = Attendance::firstOrNew([
                'employee_id' => $employee->id,
                'date' => $today->format('Y-m-d'),
            ]);

            // Save photo if provided
            $photoPath = null;
            $photo = $request->input('photo');
            if ($photo) {
                $photoData = explode(',', $photo);
                if (count($photoData) > 1) {
                    $imageData = base64_decode($photoData[1]);
                    $fileName = 'my_attendance_'.$employee->id.'_'.$type.'_'.time().'.jpg';
                    $path = 'uploads/attendance/'.date('Y/m/');

                    if (! file_exists(public_path($path))) {
                        mkdir(public_path($path), 0755, true);
                    }
                    file_put_contents(public_path($path.$fileName), $imageData);
                    $photoPath = $path.$fileName;
                }
            }
            // Get location data
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $locationName = null;

            // Check if employee's designation requires location
            $requiresLocation = $employee->designation && $employee->designation->requires_location;

            if ($requiresLocation) {
                // Location is mandatory for this designation
                if (! $latitude || ! $longitude) {
                    return response()->json([
                        'error' => 'Location is required for your designation. Please enable GPS and try again.',
                    ]);
                }
                $locationName = $this->getLocationName($latitude, $longitude);
            } else {
                // Location is optional, default to "On-Site" if not provided
                if ($latitude && $longitude) {
                    $locationName = $this->getLocationName($latitude, $longitude);
                } else {
                    $locationName = 'On-Site';
                    $latitude = null;
                    $longitude = null;
                }
            }

            if ($type === 'check_in') {
                if ($attendance->check_in_time) {
                    return response()->json([
                        'error' => 'Already checked in today at '.Carbon::parse($attendance->check_in_time)->format('h:i A'),
                    ]);
                }

                // Start - Late Check Restriction
                $shiftEndForCheck = $employee->getEndTime();
                $shiftLabel = $employee->custom_end_time ? 'Custom Shift' : 'Shift';
                $shiftEndTimeForCheck = Carbon::parse($today->format('Y-m-d').' '.Carbon::parse($shiftEndForCheck)->format('H:i:s'));

                if ($now->gt($shiftEndTimeForCheck)) {
                    return response()->json([
                        'error' => "Cannot check in. Your {$shiftLabel} ended at ".$shiftEndTimeForCheck->format('h:i A'),
                    ]);
                }
                // End - Late Check Restriction

                $attendance->check_in_time = $now->format('H:i:s');
                $attendance->check_in_photo = $photoPath;
                $attendance->check_in_latitude = $latitude;
                $attendance->check_in_longitude = $longitude;
                $attendance->check_in_location = $locationName;
                $attendance->status = 'present';

                // Check if late
                $shiftStart = $employee->getStartTime();
                $graceMinutes = $employee->getGraceMinutes();
                $shiftStartTime = Carbon::parse($today->format('Y-m-d').' '.Carbon::parse($shiftStart)->format('H:i:s'));
                $graceEndTime = $shiftStartTime->copy()->addMinutes($graceMinutes);

                if ($now->gt($graceEndTime)) {
                    $attendance->is_late = true;
                    $attendance->late_minutes = $now->diffInMinutes($shiftStartTime);
                    $attendance->status = 'late';
                }
            } else {
                if (! $attendance->check_in_time) {
                    return response()->json([
                        'error' => 'Please check in first before checking out',
                    ]);
                }

                if ($attendance->check_out_time) {
                    return response()->json([
                        'error' => 'Already checked out today at '.Carbon::parse($attendance->check_out_time)->format('h:i A'),
                    ]);
                }

                $attendance->check_out_time = $now->format('H:i:s');
                $attendance->check_out_photo = $photoPath;
                $attendance->check_out_latitude = $latitude;
                $attendance->check_out_longitude = $longitude;
                $attendance->check_out_location = $locationName;

                // Calculate total hours
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = Carbon::parse($attendance->check_out_time);
                $attendance->total_hours = round($checkOut->diffInMinutes($checkIn) / 60, 2);

                // Check if early leave
                $shiftEnd = $employee->getEndTime();
                $shiftEndTime = Carbon::parse($today->format('Y-m-d').' '.Carbon::parse($shiftEnd)->format('H:i:s'));

                if ($now->lt($shiftEndTime)) {
                    $attendance->is_early_leave = true;
                    $attendance->early_leave_minutes = $now->diffInMinutes($shiftEndTime);
                }
            }

            $attendance->save();

            // Auto-generate daily payroll when checking out
            if ($type === 'check_out') {
                $this->autoGenerateDailyPayroll($employee, $attendance);
            }

            return response()->json([
                'success' => true,
                'message' => $type === 'check_in' ?
                    'Checked in at '.$now->format('h:i A') :
                    'Checked out at '.$now->format('h:i A').'. Total: '.$attendance->total_hours.' hrs',
                'location' => $locationName,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error: '.$e->getMessage()], 400);
        }
    }

    /**
     * Get location name from coordinates using OpenStreetMap
     */
    private function getLocationName($latitude, $longitude)
    {
        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=10";

            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: AttendanceApp/1.0',
                ],
            ];
            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);

            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['address'])) {
                    $address = $data['address'];
                    $parts = [];

                    if (isset($address['suburb'])) {
                        $parts[] = $address['suburb'];
                    } elseif (isset($address['neighbourhood'])) {
                        $parts[] = $address['neighbourhood'];
                    }

                    if (isset($address['city'])) {
                        $parts[] = $address['city'];
                    } elseif (isset($address['town'])) {
                        $parts[] = $address['town'];
                    } elseif (isset($address['county'])) {
                        $parts[] = $address['county'];
                    }

                    return implode(', ', $parts) ?: ($data['display_name'] ?? null);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Geocoding error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Pull attendance from all devices
     */
    public function pullFromDevices()
    {
        if (! auth()->user()->can('hr.biometric.devices.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        try {
            $devices = \App\Models\BiometricDevice::where('is_active', true)->get();
            $syncService = app(\App\Services\BiometricSyncService::class);

            $results = [
                'created' => 0,
                'duplicates' => 0,
                'failed' => 0,
            ];

            foreach ($devices as $device) {
                $result = $syncService->pullAttendanceFromDevice($device);
                $results['created'] += $result['created'];
                $results['duplicates'] += $result['duplicates'];
                $results['failed'] += $result['failed'];
            }

            return response()->json([
                'success' => true,
                'message' => "Pulled logs from {$devices->count()} devices. Created: {$results['created']}, Duplicates: {$results['duplicates']}.",
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Auto-generate or update daily payroll when employee checks out
     */
    private function autoGenerateDailyPayroll($employee, $attendance)
    {
        try {
            // Check if employee uses daily wages
            $employee->load('salaryStructure');
            
            if (!$employee->salaryStructure || !$employee->salaryStructure->use_daily_wages) {
                return;
            }

            // Check if payroll already exists for this date
            $month = Carbon::parse($attendance->date)->format('Y-m');
            $payroll = \App\Models\Hr\Payroll::where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('payroll_type', 'daily')
                ->whereDate('created_at', $attendance->date)
                ->first();

            $payrollService = app(PayrollCalculationService::class);
            
            \DB::beginTransaction();

            if ($payroll) {
                // UPDATE Existing Payroll
                // 1. Snapshot old values for delta calculation
                $oldCarriedForwardToNext = $payroll->carried_forward_to_next ?? 0;

                // 2. Temporarily set employee pending deductions to what it was for THIS payroll
                // This ensures re-calculation uses the correct starting point
                $currentGlobalPending = $employee->pending_deductions;
                $employee->pending_deductions = $payroll->carried_forward_deduction ?? 0;

                // 3. Recalculate
                $payrollData = $payrollService->calculateDailyPayroll($employee, $attendance);

                // 4. Restore employee global pending (memory only, DB update comes later)
                $employee->pending_deductions = $currentGlobalPending;

                // 5. Update Payroll Record
                $payroll->update(Arr::except($payrollData, [
                    'allowance_details', 'deduction_details', 'new_pending_deductions'
                ]));

                // 6. Refresh Details (Delete old, add new)
                $payroll->details()->delete();
                $payrollService->savePayrollDetails(
                    $payroll,
                    $payrollData['allowance_details'] ?? [],
                    $payrollData['deduction_details'] ?? []
                );

                // 7. Update Employee Pending Deductions (Delta Logic)
                // We apply the DIFFERENCE between new and old result to the current global balance
                $newCarriedForwardToNext = $payrollData['new_pending_deductions'] ?? 0;
                $delta = $newCarriedForwardToNext - $oldCarriedForwardToNext;

                if ($delta != 0) {
                    // Fetch fresh instance to ensure atomicity/accuracy
                    $freshEmployee = Employee::find($employee->id);
                    $freshEmployee->update([
                        'pending_deductions' => $freshEmployee->pending_deductions + $delta
                    ]);
                }

                \Log::info("Daily payroll updated for employee {$employee->full_name} (ID: {$employee->id}). Delta: {$delta}");

            } else {
                // CREATE New Payroll
                $payrollData = $payrollService->calculateDailyPayroll($employee, $attendance);

                $payroll = \App\Models\Hr\Payroll::create(array_merge(
                    ['employee_id' => $employee->id],
                    Arr::except($payrollData, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
                ));

                $payrollService->savePayrollDetails(
                    $payroll,
                    $payrollData['allowance_details'] ?? [],
                    $payrollData['deduction_details'] ?? []
                );

                $payrollService->updatePendingDeductions(
                    $employee,
                    $payrollData['new_pending_deductions'] ?? 0
                );
                
                \Log::info("Daily payroll auto-generated for employee {$employee->full_name} (ID: {$employee->id})");
            }

            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Auto-generate daily payroll failed: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger mark absent command
     */
    public function markAbsent()
    {
        if (! auth()->user()->can('hr.attendance.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('attendance:mark-absent');

            return response()->json([
                'success' => true,
                'message' => 'Absent marking process completed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
