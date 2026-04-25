# Payroll Module - Quick Reference Guide

## 🎯 Overview

The payroll system now supports:
- ✅ **Monthly Payroll** - Fixed salary for salaried employees (late/early doesn't affect)
- ✅ **Daily Payroll** - Daily wage-based with attendance deductions
- ✅ **Auto-generation** - Can automatically create payrolls
- ✅ **Carry-forward** - Daily deductions carry to next day if exceeded
- ✅ **3-step workflow** - Generated → Reviewed → Paid

---

## 📊 Payroll Types Comparison

| Feature | Monthly Payroll | Daily Payroll |
|---------|----------------|---------------|
| **Basis** | Fixed salary | Daily wage |
| **Late check-in** | ❌ No effect | ✅ Deduction applied |
| **Early checkout** | ❌ No effect | ✅ Deduction applied |
| **Generation** | Manual or scheduled | Auto or manual |
| **Frequency** | Once per month | Every working day |
| **Carry-forward** | N/A | ✅ If deductions > wage |
| **Editable** | ✅ Until paid | ✅ Until paid |

---

## 🔧 How to Use

### Generate Monthly Payroll (Bulk)

```
1. Click "Generate Monthly" button
2. Select month (e.g., "2026-01")
3. Click "Generate All"
→ Creates payroll for ALL active salaried employees
```

### Generate Monthly Payroll (Individual)

```
1. Click "Generate Payroll" button
2. Select type: "Monthly"
3. Select employee
4. Select month
5. Click "Generate"
```

### Generate Daily Payroll (Manual)

```
1. Click "Generate Payroll" button
2. Select type: "Daily"
3. Select employee
4. Select date (must have completed checkout)
5. Click "Generate"
```

### Review Payroll

```
1. Find payroll in list (orange "Generated" badge)
2. Click "Details" to view full breakdown
3. Click "Edit" if adjustments needed
4. Click "Review" button
→ Status changes to "Reviewed" (blue badge)
```

### Pay Payroll

```
1. Find reviewed payroll (blue "Reviewed" badge)
2. Click "Pay" button
3. Confirm action
→ Status changes to "Paid" (green badge)
→ Payroll is now LOCKED (cannot edit/delete)
```

### Edit Payroll

```
1. Find payroll with "Generated" or "Reviewed" status
2. Click "Edit" button
3. Add:
   - Manual Allowances (extra payments)
   - Manual Deductions (extra deductions)
   - Notes
4. Click "Save Changes"
→ Net salary recalculates automatically
```

### Search & Filter

```
Tabs:
- "All Payrolls" - Shows both monthly and daily
- "Monthly" - Only monthly payrolls
- "Daily" - Only daily payrolls

Status Filters:
- "All" - All statuses
- "Generated" - Just created
- "Reviewed" - Checked by admin
- "Paid" - Payment completed

Search:
- Type employee name in search box
```

---

## 💰 How Salary is Calculated

### Monthly Payroll Formula

```
Gross Salary = Base Salary + Allowances + Manual Allowances

Total Deductions = Fixed Deductions + Manual Deductions

Net Payable = Gross Salary - Total Deductions
```

**Example:**
```
Base Salary: PKR 50,000
Allowances: PKR 5,000 (Transport + Mobile)
Manual Allowances: PKR 2,000 (Bonus)
----------------------------------------
Gross Salary: PKR 57,000

Fixed Deductions: PKR 1,000 (Insurance)
Manual Deductions: PKR 0
----------------------------------------
Total Deductions: PKR 1,000

Net Payable: PKR 56,000
```

### Daily Payroll Formula

```
Gross = Daily Wage

Attendance Deductions = Late Deduction + Early Leave Deduction

Total Deductions = Attendance Deductions + Carried Forward + Manual Deductions

If Total Deductions <= Gross:
    Net Payable = Gross - Total Deductions
    Carry Forward to Next Day = 0
Else:
    Net Payable = 0
    Carry Forward to Next Day = Total Deductions - Gross
```

**Example 1 (Normal Day):**
```
Daily Wage: PKR 1,000
Late Deduction: PKR 100 (15 minutes late)
Early Leave Deduction: PKR 50 (10 minutes early)
Carried Forward: PKR 0
----------------------------------------
Total Deductions: PKR 150
Net Payable: PKR 850
Carry Forward: PKR 0
```

**Example 2 (Heavy Deductions):**
```
Daily Wage: PKR 1,000
Late Deduction: PKR 500 (very late)
Early Leave Deduction: PKR 700 (left very early)
Carried Forward: PKR 0
----------------------------------------
Total Deductions: PKR 1,200
Since 1,200 > 1,000:
    Net Payable: PKR 0
    Carry Forward to Next Day: PKR 200
```

**Example 3 (With Carry Forward):**
```
Daily Wage: PKR 1,000
Late Deduction: PKR 100
Carried Forward from Yesterday: PKR 200
----------------------------------------
Total Deductions: PKR 300
Net Payable: PKR 700
Carry Forward: PKR 0 (cleared)
```

---

## 🔐 Status Workflow

```
┌─────────────┐
│  GENERATED  │  ← Just created (auto or manual)
│   (Orange)  │  ← Editable ✅ | Deletable ✅
└──────┬──────┘
       │ Click "Review" button
       ▼
┌─────────────┐
│  REVIEWED   │  ← Checked by admin
│   (Blue)    │  ← Editable ✅ | Deletable ✅
└──────┬──────┘
       │ Click "Pay" button
       ▼
┌─────────────┐
│    PAID     │  ← Payment completed
│   (Green)   │  ← Locked 🔒 | NOT editable ❌ | NOT deletable ❌
└─────────────┘
```

---

## ⚙️ Settings & Configuration

### For Monthly Employees

In **Salary Structure**:
```
- Salary Type: "Salary" or "Both"
- Base Salary: PKR 50,000
- Allowances: Add active allowances
- Deductions: Add active deductions
- Use Daily Wages: ❌ NO (unchecked)
```

### For Daily Wage Employees

In **Salary Structure**:
```
- Use Daily Wages: ✅ YES (checked)
- Daily Wages: PKR 1,000
- Attendance Deduction Policy:
  - Late Rules: Configure time ranges and amounts
  - Early Rules: Configure time ranges and amounts
- Carry Forward Deductions: ✅ YES (if you want carry-forward)
```

### Deduction Policy Example

```json
Late Rules:
  [
    {
      "min_minutes": 1,
      "max_minutes": 15,
      "amount": 50,
      "type": "fixed"
    },
    {
      "min_minutes": 16,
      "max_minutes": 30,
      "amount": 100,
      "type": "fixed"
    },
    {
      "min_minutes": 31,
      "max_minutes": null,
      "amount": 10,
      "type": "percentage"
    }
  ]
```

Meaning:
- 1-15 minutes late: Deduct PKR 50
- 16-30 minutes late: Deduct PKR 100
- More than 30 minutes: Deduct 10% of daily wage

---

## 🚨 Common Issues & Solutions

### Issue: "Payroll already generated for this month"
**Solution:** Payroll exists for this employee and month. Delete old one first (if not paid) or edit existing.

### Issue: "Employee has no salary structure"
**Solution:** Go to Salary Structure module and configure employee's salary first.

### Issue: Daily payroll not auto-generating
**Possible causes:**
- Employee doesn't have `use_daily_wages` enabled
- Checkout not completed (no `clock_out` time)
- Auto-generation not set up (optional feature)

### Issue: Carry-forward not working
**Check:**
- `carry_forward_deductions` is enabled in salary structure
- Employee has `pending_deductions` value
- Database migration was run

### Issue: Can't edit paid payroll
**This is by design:** Once marked as paid, payroll is locked to prevent fraud. If you must change it:
1. Delete the paid payroll (requires database access or special permission)
2. Generate new one
3. Mark as paid again

---

## 📅 Monthly Workflow (Recommended)

### Day 28 of Month
- ✅ System auto-generates monthly payrolls (if scheduled)
- ✅ OR HR clicks "Generate Monthly" button

### Day 29-31 of Month
- 👀 HR reviews all generated payrolls
- ✏️ Makes edits/adjustments as needed
- ✔️ Marks payrolls as "Reviewed"

### Day 1-5 of Next Month
- 💰 Finance processes actual payments
- ✅ HR marks payrolls as "Paid" in system
- 🔒 Records are locked

### Daily (for Daily Wage Employees)
- ⏰ Employee checks out
- 🤖 System auto-generates daily payroll (if enabled)
- 👀 HR can review and pay daily or in batches

---

## 📊 Reports & Stats

The dashboard shows:
- **Total Payrolls** - All time count
- **Generated** - Waiting for review
- **Reviewed** - Waiting for payment
- **Paid** - Completed
- **Total Amount** - Sum of all net salaries

Filters allow you to see:
- This month's payrolls
- Specific employee's payrolls
- Monthly vs Daily breakdown
- Status-wise breakdown

---

## ✅ Best Practices

1. **Review before paying**: Always check generated payrolls before marking as paid
2. **Use notes**: Add notes to explain manual adjustments
3. **Batch process**: Review and pay multiple payrolls together at month end
4. **Regular backups**: Export/backup payroll data monthly
5. **Audit trail**: System tracks who reviewed and when
6. **Test first**: Test with one employee before bulk generating
7. **Schedule wisely**: Run auto-generation on 28th (covers all months including Feb)

---

## 🎓 Training Checklist

- [ ] Understanding monthly vs daily payroll
- [ ] Generating payrolls (individual and bulk)
- [ ] Viewing detailed breakdowns
- [ ] Making manual adjustments
- [ ] Status workflow (Generated → Reviewed → Paid)
- [ ] Using filters and search
- [ ] Understanding carry-forward logic
- [ ] Configuring salary structures correctly

---

## 🆘 Support

For technical issues:
- Check `storage/logs/laravel.log`
- Verify database migrations are run
- Ensure permissions are correct
- Contact dev team if auto-generation fails

For business logic questions:
- Refer to this guide
- Review implementation summary document
- Consult with HR manager

---

**Remember:** 
- 🔵 Monthly payroll = Fixed, no attendance impact
- 🟢 Daily payroll = Variable, with attendance deductions
- 🔒 Once paid, it's locked forever
- 📝 Always review before paying!
