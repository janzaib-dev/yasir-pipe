# Employee Casual Leave Integration with Attendance

## Overview
When adding or editing an employee, you can now assign specific casual leave dates. These dates will automatically appear in the attendance system with "Leave" status, preventing HR from accidentally marking the employee absent.

## How It Works

### 1. Adding/Editing Employee with Casual Leaves

**Location:** `/hr/employees`

**Steps:**
1. Click "Add Employee" or edit an existing employee
2. Fill in the employee details as usual
3. Scroll to the "Casual Leave Dates" field
4. Click on the date picker field
5. Select one or multiple dates when the employee will be on casual leave
6. Save the employee

**Date Picker Features:**
- **Multiple Selection:** Click multiple dates to select them
- **Visual Feedback:** Selected dates are highlighted
- **No Past Dates:** Only future dates can be selected (from today onwards)
- **Easy Removal:** Click a selected date again to des select it
- **Format:** Dates are stored in YYYY-MM-DD format

### 2. Automated Leave Records

When you save an employee with casual leave dates:

**Backend Processing:**
1. Creates individual leave records for each selected date
2. Sets `leave_type` as "Casual"
3. Sets `status` as "approved" (auto-approved)
4. Sets both `start_date` and `end_date` to the same date (single-day leaves)
5. Adds reason: "Casual Leave assigned via Employee Form"

**Database:**
- Stored in `hr_leaves` table
- Each date creates one record
- Only manages single-day casual leaves from employee form
- Multi-day leave ranges can still be created via Leaves module

### 3. Attendance Integration

**When Mark Absent Command Runs:**
```bash
php artisan attendance:mark-absent
```

The system:
1. Gets all active employees without attendance
2. Checks each for approved casual leave
3. If casual leave exists → marks as "leave" ✅
4. If no leave → marks as "absent"

**Manual Attendance (HR):**
- Go to `/hr/attendance`
- Employees with casual leave show:
  - Blue info badge: "Casual Leave (Date - Date)"
  - Status automatically set to "Leave"
  - Time inputs disabled
  - Cannot be marked absent (will show error)

**Attendance Display:**
- Leave badge visible on attendance card
- Status: "Leave"
- Card border: Blue
- Clear indication of leave period

## Feature Highlights

### ✅ **Auto-Sync with Attendance**
- No manual leave application needed
- Instantly recognized by attendance system
- Automatically prevents absent marking

### ✅ **Edit Capability**
- When editing employee, existing leave dates load automatically
- Add new dates or remove existing ones
- Changes sync immediately to attendance

### ✅ **Smart Cleanup**
- Removing dates from employee form deletes those leave records
- Only affects single-day casual leaves (protects leave ranges)
- No orphaned records

### ✅ **Employee Deletion Safety**
- Deleting employee removes all their casual leaves
- Clean database with no leftover data

## Use Cases

### Use Case 1: Regular Weekly Off
**Scenario:** An employee has every Friday as casual leave

**Steps:**
1. Edit the employee
2. Select all future Fridays in the date picker
3. Save

**Result:** Every Friday automatically shows as "Leave" in attendance

### Use Case 2: Planned Leave  Days
**Scenario:** Employee plans casual leave on Jan 25, 27, 29

**Steps:**
1. Edit employee
2. Select Jan 25, 27, 29
3. Save

**Result:** Those three days show "Leave" in attendance

### Use Case 3: Canceling Leave
**Scenario:** Employee cancels leave for Jan 27

**Steps:**
1. Edit employee
2. Click Jan 27 again to deselect it
3. Save

**Result:** Jan 27 no longer shows as leave; can mark attendance normally

## Technical Details

### Files Modified

1. **`resources/views/hr/employees/index.blade.php`**
   - Replaced weekday badges with flatpickr date picker
   - Added hidden field for leave dates storage
   - Updated JavaScript to load/save leave dates
   - Integrated flatpickr library

2. **`app/Http/Controllers/Hr/EmployeeController.php`**  
   - Modified `store()` method to handle leave dates
   - Creates/updates/deletes leave records based on selection
   - Modified `index()` to eager load casual leaves
   - Modified `destroy()` to clean up casual leaves

3. **`app/Console/Commands/MarkAbsentEmployees.php`**
   - Already updated to check for approved leaves
   - Auto-marks as "leave" if casual leave exists

4. **`app/Http/Controllers/Hr/AttendanceController.php`**
   - Already updated to prevent absent marking when leave exists
   - Shows error message with leave details

5. **`resources/views/hr/attendance/index.blade.php`**
   - Already shows leave badge for employees on leave
   - Displays leave type and date range

### Database Schema

**hr_leaves table:**
```sql
- id
- employee_id (FK to hr_employees)
- leave_type: 'Casual' (from employee form)
- start_date: YYYY-MM-DD
- end_date: YYYY-MM-DD (same as start_date for single-day)
- reason: 'Casual Leave assigned via Employee Form'
- status: 'approved' (auto-approved)
- created_at
- updated_at
```

### Controller Logic (EmployeeController::store)

```php
// Get submitted dates from form
$submittedDates = explode(',', $request->casual_leave_days);

// Get existing casual leaves (only single-day)
$existingLeaves = $employee->leaves()
    ->where('leave_type', 'Casual')
    ->whereRaw('start_date = end_date')
    ->get();

// 1. Create new leaves for new dates
foreach ($submittedDates as $date) {
    if (!in_array($date, $existingDates)) {
        $employee->leaves()->create([...]);
    }
}

// 2. Delete removed leaves
foreach ($existingLeaves as $leave) {
    if (!in_array($leaveDate, $submittedDates)) {
        $leave->delete();
    }
}
```

## Testing Guide

### Test 1: Add Employee with Casual Leave
1. Go to `/hr/employees`
2. Click "Add Employee"
3. Fill in required fields
4. Click "Casual Leave Dates" field
5. Select 3 future dates
6. Save employee
7. Verify: 3 leave records created in `hr_leaves`
8. Verify: Dates shown when editing employee

### Test 2: Check Attendance Display
1. Navigate to `/hr/attendance`
2. Select a date with casual leave
3. Verify: Employee shows blue "Casual Leave" badge
4. Verify: Status is "Leave"
5. Try to change to "Absent"
6. Verify: Error message appears

### Test 3: Edit and Remove Dates
1. Edit the employee
2. Remove one date from picker
3. Add two new dates
4. Save
5. Verify: Old date removed from `hr_leaves`
6. Verify: New dates added to `hr_leaves`
7. Verify: Attendance updates accordingly

### Test 4: Mark Absent Command
1. Select a future date with casual leave
2. Run: `php artisan attendance:mark-absent [date]`
3. Verify: Output shows "Marked Leave: [Name] (Casual)"
4. Verify: Attendance record has `status='leave'`

### Test 5: Employee Deletion
1. Delete an employee with casual leaves
2. Verify: All their casual leave records deleted
3. Verify: No orphaned records in `hr_leaves`

## Known Limitations

1. **Single-Day Only:** Employee form only manages single-day leaves
   - Multi-day ranges must use Leaves module
   - This prevents conflicts between systems

2. **Future Dates:** Date picker restricted to today onwards
   - Cannot add past casual leaves via employee form
   - Historical leaves must use Leaves module

3. **Auto-Approved:** All casual leaves from employee form are auto-approved
   - No approval workflow for these leaves
   - For approval workflow, use Leaves module

## Benefits

✅ Quick casual leave assignment during employee onboarding
✅ No need to separately create leave requests
✅ Instant attendance integration
✅ Reduces HR workload
✅ Prevents accidental absent marking
✅ Clear visual indicators
✅ Easy to modify leave dates
✅ Clean synchronization between systems

## Future Enhancements (Optional)

1. **Bulk Date Selection:**
   - Select all Fridays in a month
   - Select date ranges

2. **Leave Type Selection:**
   - Choose between Casual, Sick, Annual
   - Different colors for different types

3. **Leave Quota Integration:**
   - Show remaining leave days
   - Warn when exceeding quota

4. **Recurring Patterns:**
   - "Every Monday for next 3 months"
   - Save and reuse patterns

5. **Calendar View:**
   - Visual calendar in employee form
   - Shows all existing leaves

6. **Import/Export:**
   - Import leave dates from Excel
   - Export leave calendars

## Support

If you encounter any issues:
1. Check browser console for JavaScript errors
2. Verify flatpickr library is loaded
3. Check `hr_leaves` table for leave records
4. Review attendance controller logic
5. Test with simple scenarios first

All casual leave functionality is now fully integrated with the attendance system!
