# Leave Logic Integration - Implementation Summary

## Overview
This implementation adds comprehensive leave logic to the attendance system. When an employee has an approved casual leave (one or multiple days), it will automatically show as "Leave" in the attendance records. HR cannot mark an employee absent if they have an approved leave for that date.

## Changes Made

### 1. **Leave Model Enhancements** (`app/Models/Hr/Leave.php`)
- Added date casting for `start_date` and `end_date` fields
- Added `hasApprovedLeave()` static method to check if an employee has approved leave on a specific date
- Added `getApprovedLeave()` static method to retrieve the approved leave record for a specific date

**Key Methods:**
```php
public static function hasApprovedLeave($employeeId, $date)
public static function getApprovedLeave($employeeId, $date)
```

### 2. **Mark Absent Command** (`app/Console/Commands/MarkAbsentEmployees.php`)
**Enhanced Logic:**
- Now imports `Leave` and `Holiday` models
- Checks if the date is a holiday before processing
- For each employee without attendance:
  - First checks if they have approved leave
  - If yes, creates attendance record with `status = 'leave'`
  - If no, creates attendance record with `status = 'absent'`
- Tracks and reports both absent and leave counts

**Output Example:**
```
Marking absent employees for: 2026-01-22
Marked Leave: John Doe (Casual)
Marked Absent: Jane Smith
Completed. Marked 5 employees as absent and 3 on leave.
```

### 3. **Attendance Controller** (`app/Http/Controllers/Hr/AttendanceController.php`)

#### Import Added:
- Added `use App\Models\Hr\Leave;`

#### Index Method Enhancement:
- Eager loads approved leaves for the selected date
- Allows the view to display leave information alongside attendance

```php
'leaves' => function ($q) use ($selectedDate) {
    $q->where('status', 'approved')
      ->whereDate('start_date', '<=', $selectedDate)
      ->whereDate('end_date', '>=', $selectedDate);
}
```

#### Store Method Enhancement:
**Leave Validation Logic:**
1. When HR tries to mark someone as absent:
   - System checks if employee has approved leave
   - If yes, returns error message with leave details
   - Prevents absent marking with user-friendly error

2. Auto-correction Logic:
   - If employee has approved leave but no time entries
   - Automatically sets status to 'leave'

**Error Response Example:**
```json
{
    "error": "Cannot mark John Doe as absent. Employee has approved Casual leave from Jan 20 to Jan 25, 2026."
}
```

### 4. **Attendance View** (`resources/views/hr/attendance/index.blade.php`)

#### Visual Indicators Added:
1. **Leave Badge Display:**
   - Shows for employees with approved leave
   - Displays leave type and date range
   - Color: Info blue badge with umbrella icon

```html
<span class="badge bg-info">
    <i class="fa fa-umbrella-beach me-1"></i>
    Casual Leave (Jan 20 - Jan 25)
</span>
```

2. **Auto-Status Setting:**
   - If employee has approved leave and no attendance
   - Status automatically set to 'leave'
   - Card styled with blue left border

## How It Works

### Scenario 1: Employee Applies for Leave
1. Employee/HR creates leave request in the Leaves module
2. HR approves the leave (status = 'approved')
3. Leave is now active for the date range

### Scenario 2: Daily Attendance (Automatic)
When the Mark Absent command runs daily:
1. System fetches all active employees
2. For employees without attendance:
   - Checks for approved leave first
   - If leave exists → marks as "leave"
   - If no leave → marks as "absent"

### Scenario 3: Manual HR Override (Prevented)
When HR tries to manually mark absent:
1. Employee with leave appears in attendance list
2. Leave badge visible with dates
3. If HR selects "Absent":
   - Form submission triggers validation
   - Error returned: "Cannot mark absent, employee has approved leave"
   - Page doesn't save the change

### Scenario 4: Viewing Attendance
On the attendance index page:
- Employees with approved leave show info badge
- Status dropdown shows "Leave" selected
- Card has blue left border
- Time inputs disabled (since on leave)

## Benefits

✅ **Prevents Errors:** HR cannot accidentally mark someone absent when they're on approved leave
✅ **Visual Clarity:** Clear indication of who's on leave with dates
✅ **Automation:** Mark absent command automatically respects leaves
✅ **Data Integrity:** Attendance records accurately reflect leave status
✅ **User Experience:** Clear error messages guide HR staff

## Testing Scenarios

### Test 1: Create and Approve Leave
1. Go to Leaves module
2. Create leave for an employee (e.g., Jan 22-24)
3. Approve the leave
4. Navigate to Attendance for Jan 22
5. Verify: Employee shows leave badge
6. Verify: Status is "Leave"

### Test 2: Try to Mark Absent
1. Go to Attendance page with employee on approved leave
2. Change their status dropdown from "Leave" to "Absent"
3. Click "Save"
4. Verify: Error message appears
5. Verify: Status remains "Leave"

### Test 3: Mark Absent Command
1. Run command: `php artisan attendance:mark-absent 2026-01-22`
2. Verify output shows leave counts
3. Check database: employees with leave have status='leave'
4. Check database: employees without leave have status='absent'

### Test 4: Holiday Check
1. Create a holiday for a date
2. Run mark-absent command for that date
3. Verify: Command skips processing with message

## Database Fields Used

**hr_attendances table:**
- `status` enum: 'present', 'absent', 'late', 'leave'

**hr_leaves table:**
- `employee_id`
- `leave_type` (Casual, Sick, Annual, etc.)
- `start_date`
- `end_date`
- `status` (pending, approved, rejected)

## API Endpoints Affected

### POST `/hr/attendance` (Store Attendance)
- **New Validation:** Checks for approved leaves before allowing absent status
- **Error Response:** 422 with detailed leave information

### POST `/hr/attendance/mark-absent` (Mark Absent Command Trigger)
- **Enhanced Logic:** Respects approved leaves
- **Success Response:** Includes both absent and leave counts

## Future Enhancements (Optional)

1. **Leave Quota Tracking:**
   - Track remaining leave days per employee
   - Warn when exceeding quota

2. **Leave Types Configuration:**
   - Admin configurable leave types
   - Different rules per leave type

3. **Leave Approval Workflow:**
   - Multi-level approval
   - Email notifications

4. **Half-Day Leaves:**
   - Support for partial day leaves
   - Time-based leave calculation

5. **Leave Calendar View:**
   - Visual calendar showing all leaves
   - Team availability overview

## Files Modified

1. `app/Models/Hr/Leave.php` - Added helper methods
2. `app/Console/Commands/MarkAbsentEmployees.php` - Added leave checking
3. `app/Http/Controllers/Hr/AttendanceController.php` - Added validation and eager loading
4. `resources/views/hr/attendance/index.blade.php` - Added visual indicators

## Configuration

No additional configuration required. The system uses existing:
- Leave status: 'approved' (from hr_leaves table)
- Attendance status: 'leave' (from hr_attendances enum)

## Notes

- Leave checking is date-based (checks if date falls within start_date and end_date)
- Only 'approved' leaves are considered
- 'pending' or 'rejected' leaves are ignored
- Holidays take precedence (no attendance records created on holidays)
- Leave status can still be manually set by HR when needed (for emergency leaves)
