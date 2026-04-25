# Payroll Enhancement Implementation Plan

## Overview
Enhance the existing payroll module to support automatic generation, better separation between monthly and daily payroll, and improved UX/UI.

## Database Changes

### 1. Enhance `hr_payrolls` Table
Add new columns:
- `payroll_type` (enum: 'monthly', 'daily') - To distinguish between payroll types
- `allowances` (decimal) - Separate field for allowances
- `attendance_deductions` (decimal) - Deductions from attendance issues
- `manual_deductions` (decimal) - Manual deductions added by admin
- `manual_allowances` (decimal) - Manual allowances added by admin
- `carried_forward_deduction` (decimal) - Deduction carried from previous day (for daily)
- `gross_salary` (decimal) - Gross amount before deductions
- `notes` (text) - Admin notes
- `auto_generated` (boolean) - Whether it was auto-generated
- `reviewed_by` (foreignId nullable) - User who reviewed
- `reviewed_at` (datetime nullable) - When it was reviewed
- Modify `status` enum to: 'generated', 'reviewed', 'paid'

### 2. Create `hr_payroll_details` Table (Optional - for detailed breakdown)
- `id`
- `payroll_id` (foreignId)
- `type` (enum: 'allowance', 'deduction')
- `name` (string) - e.g., "Late Check-in - Jan 15"
- `amount` (decimal)
- `description` (text nullable)
- `timestamps`

### 3. Add Carry-Forward Tracking to Employees
Add to `hr_employees` or create new table:
- `pending_deductions` (decimal, default 0) - For daily wage employees

## Backend Implementation

### 1. Enhanced Payroll Model
- Add relationships for payroll details
- Add scopes: `monthly()`, `daily()`, `generated()`, `reviewed()`, `paid()`
- Add methods: `canEdit()`, `canMarkReviewed()`, `canMarkPaid()`
- Add accessors for detailed breakdown

### 2. PayrollController Enhancements
**New Methods:**
- `update($id)` - Edit payroll (only if not paid)
- `markReviewed($id)` - Mark as reviewed
- `getPayrollDetails($id)` - Get detailed breakdown
- `generateMonthly()` - Generate all monthly payrolls for current month
- `generateDaily($employeeId)` - Generate daily payroll on checkout

**Enhanced `generate()` Method:**
- Separate logic for monthly vs daily
- Better carry-forward handling
- Store detailed breakdown in payroll_details table

### 3. Scheduled Jobs
Create Jobs:
- `GenerateMonthlyPayrolls` - Runs on last day of month
- Task: Generate payroll for all salaried employees

### 4. Event Listeners
- Listen to `AttendanceCheckedOut` event
- Generate daily payroll automatically when employee checks out (if daily wage employee)

### 5. Services/Handlers
Create `PayrollCalculationService`:
- `calculateMonthlyPayroll($employee, $month)`
- `calculateDailyPayroll($employee, $attendance)`
- `applyDeductions($payroll, $deductions)`
- `applyCarryForward($employee, $payroll)`

## Frontend/UI Implementation

### 1. Enhanced Payroll Index View
**Tabs/Sections:**
- "Monthly Payroll" tab
- "Daily Payroll" tab
- Dashboard with stats for each type

**Each Payroll Card Shows:**
- Employee name, photo, designation
- Payroll type badge
- Month/Date
- Gross Amount
- Total Deductions (with breakdown icon)
- Net Payable
- Status badge (Generated/Reviewed/Paid)
- Actions: View Details, Edit (if not paid), Mark Reviewed, Mark Paid, Delete

**Filters:**
- By type (Monthly/Daily)
- By status (Generated/Reviewed/Paid)
- By month
- By employee
- Search

### 2. Payroll Details Modal
Shows complete breakdown:
- **Earnings Section:**
  - Base Salary / Daily Wage
  - Allowances (list each)
  - Manual Allowances (if any)
  - **Gross Total**

- **Deductions Section:**
  - Fixed Deductions (from salary structure)
  - Attendance Deductions (list each: late, early leave)
  - Carried Forward Deduction (if any)
  - Manual Deductions (if any)
  - **Total Deductions**

- **Net Payable** (highlighted)
- Notes
- Timeline (Generated → Reviewed → Paid with dates)

### 3. Edit Payroll Modal
Allow admin to:
- Add manual allowances (with description)
- Add manual deductions (with description)
- Edit notes
- Auto-recalculates net salary

### 4. Generate Payroll Modal
- Select payroll type (Monthly/Daily)
- If Monthly: Select employee(s) or "All Salaried Employees", select month
- If Daily: Select employee, select date range
- Preview calculation before generating

## Automation & Business Logic

### Monthly Payroll Auto-Generation
**Trigger:** Last day of month at 11:59 PM (scheduled job)
**Process:**
1. Find all employees with `salary_type` = 'salary' or 'both'
2. For each employee:
   - Check if payroll already exists for this month
   - Calculate: base_salary + allowances - fixed_deductions
   - **Do NOT** apply late/early deductions for monthly employees
   - Store as status: 'generated'
   - Set `auto_generated` = true
3. Send notification to HR

### Daily Payroll Auto-Generation
**Trigger:** When employee checks out (attendance record gets `clock_out`)
**Process:**
1. Check if employee uses daily wages (`use_daily_wages` = true)
2. Calculate:
   - Base earning = `daily_wages`
   - Apply late check-in deductions (from policy)
   - Apply early check-out deductions (from policy)
   - Check for carried forward deductions
   - If total deductions > daily_wages:
     - Set paid_amount = 0
     - Carry forward remaining to next day
     - Update employee's `pending_deductions`
   - Else:
     - Paid amount = daily_wages - deductions
     - Clear carried forward if any
3. Store as status: 'generated'
4. Set `auto_generated` = true

### Carry-Forward Logic (Daily Payroll)
```
employee.pending_deductions = 100 (from yesterday)
today's earning = 500
today's late deduction = 50

total_deductions = 100 + 50 = 150
net_payable = 500 - 150 = 350

payroll.carried_forward_deduction = 100
payroll.attendance_deductions = 50
payroll.net_salary = 350
employee.pending_deductions = 0 (cleared)

---

If today's earning = 80:
total_deductions = 100 + 50 = 150
net_payable = 80 - 150 = -70 → 0 (can't be negative)

payroll.net_salary = 0
payroll.carried_forward_deduction = 80 (what we could deduct)
employee.pending_deductions = 70 (carry to next day: 150 - 80)
```

## UI/UX Improvements

### Design Principles
1. **Color-coded cards:**
   - Monthly payroll: Blue gradient
   - Daily payroll: Green gradient

2. **Status indicators:**
   - Generated: Orange badge
   - Reviewed: Blue badge
   - Paid: Green badge with checkmark

3. **Interactive elements:**
   - Hover effects on cards
   - Smooth transitions
   - Animated number changes
   - Progress bars for payroll completion

4. **Dashboard stats:**
   - Total monthly payroll this month
   - Total daily payroll today/this month
   - Pending reviews count
   - Total paid amount

5. **Modern design:**
   - Clean cards with shadows
   - Gradient accents
   - Icon usage
   - Professional typography
   - Responsive layout

## Routes to Add
```php
// Payroll Management
Route::get('payroll/monthly', [PayrollController::class, 'monthly'])->name('payroll.monthly');
Route::get('payroll/daily', [PayrollController::class, 'daily'])->name('payroll.daily');
Route::get('payroll/{payroll}/details', [PayrollController::class, 'details'])->name('payroll.details');
Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
Route::patch('payroll/{payroll}/mark-reviewed', [PayrollController::class, 'markReviewed'])->name('payroll.mark-reviewed');

// Bulk generation
Route::post('payroll/generate-monthly', [PayrollController::class, 'generateMonthly'])->name('payroll.generate-monthly');
Route::post('payroll/generate-daily', [PayrollController::class, 'generateDaily'])->name('payroll.generate-daily');
```

## Testing Checklist
- [ ] Monthly payroll generates correctly at month end
- [ ] Daily payroll generates on employee checkout
- [ ] Late/early deductions apply correctly for daily payroll
- [ ] Late/early does NOT affect monthly payroll
- [ ] Carry-forward works when deductions exceed daily wage
- [ ] Manual allowances/deductions can be added
- [ ] Payroll can be edited before marked as paid
- [ ] Status workflow works: Generated → Reviewed → Paid
- [ ] UI filters and search work correctly
- [ ] Detailed breakdown displays correctly

## Migration Order
1. Add columns to hr_payrolls table
2. Create hr_payroll_details table
3. Add pending_deductions to hr_employees table
4. Seed/update existing data if needed

## Implementation Steps
1. ✅ Create migration for database changes
2. ✅ Update Payroll model with new fields and relationships
3. ✅ Create PayrollDetail model
4. ✅ Create PayrollCalculationService
5. ✅ Update PayrollController with new methods
6. ✅ Create scheduled job for monthly payroll
7. ✅ Create event listener for daily payroll
8. ✅ Update routes
9. ✅ Enhance payroll index view
10. ✅ Create payroll details modal
11. ✅ Create edit payroll modal
12. ✅ Test all functionality
