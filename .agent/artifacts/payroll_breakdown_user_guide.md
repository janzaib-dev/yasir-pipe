# 📊 Payroll Breakdown - User Guide

## Quick Overview

The enhanced payroll system now provides detailed, transparent breakdowns of salary calculations. You can expand sections to see exactly how each amount is calculated.

---

## 🔍 Viewing Payroll Details

### Step 1: Open Payroll Details
1. Navigate to **HR** → **Payroll Management**
2. Find the payroll record you want to view
3. Click the **"Details"** button (👁️ icon)

### Step 2: View Payroll Period
At the top of the details modal, you'll see the **Payroll Period** badge:

**For Monthly Payroll:**
```
📅 Payroll Period: March 2026
```
Shows only the month and year.

**For Daily Payroll:**
```
📅 Payroll Period: 15 March 2026
```
Shows the exact date, month, and year.

---

## 💰 Understanding Allowances

### Default View
You'll see a summary row showing:
```
Allowances ........................... Rs. 12,000.00 ▼
```

### How to Expand
**Click anywhere on the allowance row** to see the detailed breakdown.

### Expanded View Shows
Each individual allowance assigned to the employee:

| Allowance Name          | Amount       |
|------------------------|--------------|
| ✅ Housing Allowance   | Rs. 8,000.00 |
| ✅ Transport Allowance | Rs. 4,000.00 |
| **Total**              | **Rs. 12,000.00** |

**Note:** Only allowances specifically assigned to this employee appear here.

---

## 📉 Understanding Attendance Deductions

### Default View
You'll see a summary row showing:
```
Attendance Deductions ............... Rs. 3,500.00 ▼
```

### How to Expand
**Click anywhere on the attendance deduction row** to see the complete breakdown.

### Expanded View - Monthly Payroll

Shows comprehensive attendance statistics:

```
┌─────────────────────────────────────────┐
│  📅 Total Working Days: 22              │
│  ✅ Days Present: 20                    │
│  ❌ Days Absent: 2                      │
│  ⏰ Late Check-ins: 3                   │
│  🏃 Early Check-outs: 1                 │
├─────────────────────────────────────────┤
│ Total Deduction Amount: Rs. 3,500.00    │
└─────────────────────────────────────────┘
```

**Explanation:**
- **Working Days**: Excludes weekends (Saturday & Sunday)
- **Days Present**: Actual attendance marked as "present"
- **Days Absent**: Days marked as "absent" 
- **Late Check-ins**: Number of times employee arrived late
- **Early Check-outs**: Number of times employee left early

### Expanded View - Daily Payroll

Shows deductions for that specific day:

```
┌─────────────────────────────────────────┐
│  📅 Date: 15 March 2026                 │
│  ⏰ Late Minutes: 30                    │
│  🏃 Early Checkout Minutes: 15          │
├─────────────────────────────────────────┤
│ Total Deduction Amount: Rs. 500.00      │
└─────────────────────────────────────────┘
```

---

## 🎨 Visual Indicators

### Color Coding

| Color  | Meaning | Example |
|--------|---------|---------|
| 🟢 Green | Positive/Success | Days Present |
| 🔴 Red | Negative/Deduction | Days Absent, Deductions |
| 🟡 Yellow | Warning/Attention | Late arrivals |
| 🔵 Blue | Information | Payroll Period |
| 🟣 Purple | Interactive | Expandable headers |

### Header States

**Collapsed State:**
```
┌────────────────────────────────────────┐
│ 💰 Allowances      Rs. 12,000.00  ▼   │  ← Gray background
└────────────────────────────────────────┘
```

**Expanded State:**
```
┌────────────────────────────────────────┐
│ 💰 Allowances      Rs. 12,000.00  ▲   │  ← Purple gradient
├────────────────────────────────────────┤
│  ✅ Housing Allowance   Rs. 8,000.00  │
│  ✅ Transport           Rs. 4,000.00  │
└────────────────────────────────────────┘
```

---

## ❓ Common Questions

### Q: Why don't I see an expandable section for allowances?
**A:** If the total allowances = Rs. 0.00, there's nothing to expand. The system only shows expandable sections when there's data to display.

### Q: Can I edit values from this view?
**A:** No, this is a **read-only view** for transparency. To make changes, use the "Edit" button on the payroll card.

### Q: What does "Details not available" mean?
**A:** This appears when attendance records are missing or incomplete for that period.

### Q: How are working days calculated?
**A:** The system automatically excludes Saturdays and Sundays. Only Monday-Friday are counted as working days.

### Q: Do monthly payrolls have attendance deductions?
**A:** No, based on the current configuration, monthly payrolls typically don't have attendance-based deductions. However, if configured, they would appear here.

---

## 📋 Reading a Complete Payroll Breakdown

### Example Walkthrough

**Employee:** John Smith  
**Period:** March 2026  
**Type:** Monthly

#### Step-by-Step Reading:

1. **Payroll Period Badge**
   ```
   📅 Payroll Period: March 2026
   ```

2. **Earnings Section**
   - Basic Salary: Rs. 50,000.00 (base pay)
   - 💰 **Allowances** (expand to see):
     - Housing: Rs. 8,000.00
     - Transport: Rs. 4,000.00
   - Manual Allowances: Rs. 0.00 (HR adjustments)
   - **Total Earnings: Rs. 62,000.00** ✅

3. **Deductions Section**
   - Fixed Deductions: Rs. 2,000.00 (e.g., tax, insurance)
   - 📉 **Attendance Deductions** (expand to see):
     - Working Days: 22
     - Present: 20
     - Absent: 2
     - Late: 3 times
     - Early: 1 time
     - Deduction: Rs. 3,500.00
   - Carried Forward: Rs. 0.00
   - Manual Deductions: Rs. 0.00
   - **Total Deductions: Rs. 5,500.00** ❌

4. **Final Calculation**
   ```
   Net Payable = Total Earnings - Total Deductions
                = Rs. 62,000.00 - Rs. 5,500.00
                = Rs. 56,500.00 ✅
   ```

---

## 💡 Tips for HR

### Best Practices

✅ **Always expand sections** to verify calculations before approving payroll  
✅ **Check attendance breakdown** to ensure it matches attendance records  
✅ **Review allowances** to confirm all entitled benefits are included  
✅ **Look for manual adjustments** (manual_allowances/deductions) and verify they're correct  

### Quick Verification

Before marking payroll as "Reviewed" or "Paid", verify:

- [ ] Payroll period is correct
- [ ] Employee designation shown is current
- [ ] All allowances are listed correctly
- [ ] Attendance deductions match HR records
- [ ] No unexpected manual adjustments
- [ ] Net payable amount seems reasonable

---

## 🆘 Troubleshooting

### Issue: Expandable section won't open
**Solution:** Try refreshing the page. If it persists, contact IT support.

### Issue: Numbers don't add up
**Solution:** Check for:
- Rounding differences (system uses 2 decimal places)
- Hidden manual adjustments
- Carried forward deductions from previous months

### Issue: Missing allowances
**Solution:** Verify:
1. Is this allowance assigned to this specific employee?
2. Is the allowance marked as "active" in salary structure?
3. Has the allowance start date passed?

### Issue: Attendance data seems wrong
**Solution:** 
1. Navigate to Attendance Management
2. Filter by employee and month
3. Verify attendance records match
4. If discrepancy found, regenerate payroll after correcting attendance

---

## 📞 Need Help?

If you encounter issues or have questions:
- Contact: HR System Administrator
- Email: hr-support@company.com
- Or raise a ticket in the support portal

---

**Last Updated:** January 2026  
**Version:** 1.0
