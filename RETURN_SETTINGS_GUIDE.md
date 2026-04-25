# Return Deadline Settings - Super Admin Configuration

## ✅ IMPLEMENTED

Super Admin can now configure return policy settings through a beautiful settings interface!

## 🎯 Features

### **1. Dynamic Return Deadline Configuration**
Super Admin can set the return deadline to **any value**:
- **0 days** = No returns allowed (completely disabled)
- **1 day** = Same-day returns only
- **7 days** = One week return window
- **30 days** = One month (default)
- **90 days** = Three months
- **Up to 365 days** = One year

### **2. Manager Approval Toggle**
- Enable/Disable manager approval requirement
- Beautiful toggle switch interface
- Real-time status display

### **3. Auto-Approve Threshold**
- Set amount threshold for automatic approval
- Returns under this amount skip manager approval
- Set to 0 to disable auto-approval

---

## 📍 Access Settings

**URL**: `/settings/return-policy`

**Route Name**: `settings.return-policy`

**Direct Link**: `http://localhost:8000/settings/return-policy`

---

## 🎨 Settings Interface

### **Beautiful Modern UI** with:
- Gradient header
- Toggle switches
- Input validation
- Helpful examples
- Success notifications
- Responsive design

### **Settings Available**:

#### 1. **Return Deadline (Days)**
- Input field with number validation
- Min: 0, Max: 365
- Shows examples: 0 = No returns | 1 = Same day | 7 = One week | 30 = One month

#### 2. **Require Manager Approval**
- Toggle switch (ON/OFF)
- Shows current status (Enabled/Disabled)
- Description explains the feature

#### 3. **Auto-Approve Threshold**
- Currency input (PKR)
- Set amount for automatic approval
- Example: Set to 5000 to auto-approve returns under PKR 5,000

---

## 🗄️ Database Structure

### **Table**: `system_settings`

```sql
CREATE TABLE system_settings (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255) UNIQUE,
    value TEXT,
    type VARCHAR(255) DEFAULT 'string',
    group VARCHAR(255) DEFAULT 'general',
    label VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### **Default Settings** (Auto-inserted):
```sql
- return_deadline_days: 30
- return_require_approval: 1 (true)
- return_auto_approve_threshold: 0
```

---

## 🔧 How It Works

### **Backend Flow**:

1. **Settings are stored in database** (`system_settings` table)
2. **Cached for performance** (1 hour cache)
3. **Used throughout the application**:
   - `SaleController::saleretun()` - Checks deadline when showing return form
   - `SaleController::storeSaleReturn()` - Validates deadline before processing
   - Return form view - Shows deadline warning banner

### **Code Usage**:

```php
// Get setting value
$deadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);

// Set setting value
\App\Models\SystemSetting::set('return_deadline_days', 45);
```

### **Type Casting**:
- `integer` → Automatically cast to int
- `boolean` → Automatically cast to true/false
- `string` → Returned as-is
- `json` → Automatically decoded

---

## 📋 Files Created/Modified

### **New Files**:
1. `database/migrations/2026_02_02_013524_create_system_settings_table.php` ✅
2. `app/Models/SystemSetting.php` ✅
3. `resources/views/admin_panel/settings/return_policy.blade.php` ✅

### **Modified Files**:
1. `app/Http/Controllers/SettingsController.php` - Added `returnSettings()` and `updateReturnSettings()`
2. `app/Http/Controllers/SaleController.php` - Updated to use `SystemSetting::get()` instead of `config()`
3. `routes/web.php` - Added return policy settings routes

---

## 🧪 Testing

### **Test Scenarios**:

#### **Scenario 1: Disable Returns**
1. Go to `/settings/return-policy`
2. Set "Return Deadline" to **0**
3. Save
4. Try to create a return → Should show: "Returns are currently disabled by store policy"

#### **Scenario 2: Same-Day Returns Only**
1. Set "Return Deadline" to **1**
2. Save
3. Try to return a sale from yesterday → Should show: "Return period expired"
4. Try to return today's sale → Should work ✅

#### **Scenario 3: 10-Day Return Window**
1. Set "Return Deadline" to **10**
2. Save
3. Sale from 5 days ago → Shows "5 days remaining" ✅
4. Sale from 15 days ago → Shows "Return period expired" ❌

#### **Scenario 4: Auto-Approval**
1. Set "Auto-Approve Threshold" to **5000**
2. Save
3. Return worth PKR 3,000 → Auto-approved ✅
4. Return worth PKR 7,000 → Requires manager approval (pending status)

---

## 🎓 User Guide for Super Admin

### **How to Change Return Policy**:

1. **Login** as Super Admin
2. **Navigate** to Settings → Return Policy
   - Or directly visit: `/settings/return-policy`
3. **Configure** your desired settings:
   - **Return Deadline**: Enter number of days (0-365)
   - **Manager Approval**: Toggle ON/OFF
   - **Auto-Approve**: Enter threshold amount
4. **Click** "Save Settings"
5. **Success!** Settings are applied immediately

### **Examples**:

**Strict Policy** (No returns):
- Return Deadline: **0 days**
- Manager Approval: ON
- Auto-Approve: 0

**Lenient Policy** (Long return window):
- Return Deadline: **90 days**
- Manager Approval: OFF
- Auto-Approve: 10000

**Balanced Policy** (Standard retail):
- Return Deadline: **30 days**
- Manager Approval: ON
- Auto-Approve: 5000

---

## 🚀 Benefits

### **For Super Admin**:
✅ Full control over return policy  
✅ No code changes needed  
✅ Instant updates  
✅ Easy to understand interface  

### **For Business**:
✅ Flexible policy management  
✅ Seasonal adjustments (e.g., extend during holidays)  
✅ Risk management (disable returns during inventory)  
✅ Customer satisfaction (clear policies)  

### **For Staff**:
✅ Clear guidelines  
✅ Automatic validation  
✅ Reduced errors  
✅ Faster processing  

---

## 💡 Advanced Usage

### **Seasonal Adjustments**:
```
Normal: 30 days
Holiday Season (Dec): 60 days
Clearance Sale: 7 days
Final Sale: 0 days (no returns)
```

### **Risk Management**:
```
During Inventory Count: Set to 0 (disable returns temporarily)
After Inventory: Restore to 30 days
```

### **Customer Tiers** (Future Enhancement):
```
VIP Customers: 90 days
Regular Customers: 30 days
New Customers: 15 days
```

---

## 📊 Settings Impact

### **When you change settings**:

1. **Return Deadline**:
   - Affects all future return requests
   - Existing returns not affected
   - Validation happens in real-time

2. **Manager Approval**:
   - New returns follow new rule
   - Pending returns still need approval
   - Can be toggled anytime

3. **Auto-Approve Threshold**:
   - Applies to new returns only
   - Existing pending returns unchanged
   - Can be adjusted based on business needs

---

## ✅ Status: PRODUCTION READY

All features implemented and tested!

**Access Now**: `http://localhost:8000/settings/return-policy`

**Super Admin** has full control over return policies! 🎉

---

## 📝 Quick Reference

| Setting | Type | Default | Range | Purpose |
|---------|------|---------|-------|---------|
| return_deadline_days | Integer | 30 | 0-365 | Days allowed for returns |
| return_require_approval | Boolean | true | ON/OFF | Require manager approval |
| return_auto_approve_threshold | Numeric | 0 | 0+ | Auto-approve under this amount |

---

**Implementation Date**: February 2, 2026  
**Version**: 3.0  
**Status**: ✅ Complete & Ready
