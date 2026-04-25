# HR Module - Complete Integration Guide

A fully portable, production-ready HR Management System for Laravel projects. Includes Employee Management, Self-Service Attendance, Face Recognition Kiosk, Shift Management, Holiday Calendar, Salary Structure with Tiered Commission, Leave Management, and Payroll.

**Version:** 3.1  
**Last Updated:** January 18, 2026  
**Compatibility:** Laravel 10+, PHP 8.1+

---

## 🎯 Features Overview

| Module | Features |
|--------|----------|
| **Employees** | CRUD, Documents, User linking, Department/Designation assignment |
| **Attendance** | Self-service check-in/out, GPS Location Tracking, Face recognition kiosk, Late detection, Filters |
| **Shifts** | Templates (Morning/Evening/Night), Grace periods, Custom schedules |
| **Holidays** | Company holidays, Yearly calendar, Auto-block on holidays |
| **Salary** | Fixed/Commission/Both, Tiered commission rates, Custom allowances |
| **Leaves** | Leave requests, Approval workflow |
| **Payroll** | Monthly generation, Integration with salary structure |

---

## 📁 Files to Copy

### 1. Database Migrations
**Source:** `database/migrations/`

```
2026_01_17_000000_create_hr_module_tables.php      # Core tables
2026_01_17_000001_create_designations_table.php    # Designations
2026_01_17_000003_create_hr_employee_documents_table.php
2026_01_17_000004_create_hr_salary_structures_table.php
2026_01_17_000005_add_commission_tiers_to_hr_salary_structures.php
2026_01_17_000006_create_hr_shifts_table.php
2026_01_17_000007_create_hr_holidays_table.php
2026_01_17_000008_update_hr_attendance_system.php
2026_01_17_000009_add_location_to_hr_attendances.php
```

### 2. Models
**Source:** `app/Models/Hr/`

```
├── Department.php
├── Designation.php
├── Employee.php
├── EmployeeDocument.php
├── Attendance.php
├── Leave.php
├── Payroll.php
├── SalaryStructure.php
├── Shift.php
└── Holiday.php
```

### 3. Controllers
**Source:** `app/Http/Controllers/Hr/`

```
├── DepartmentController.php
├── DesignationController.php
├── EmployeeController.php
├── AttendanceController.php
├── LeaveController.php
├── PayrollController.php
├── SalaryStructureController.php
├── ShiftController.php
└── HolidayController.php
```

### 4. Views
**Source:** `resources/views/hr/`

```
├── departments/
│   └── index.blade.php
├── designations/
│   └── index.blade.php
├── employees/
│   └── index.blade.php
├── attendance/
│   ├── index.blade.php          # Admin dashboard with filters
│   ├── kiosk.blade.php          # Face recognition full-screen kiosk
│   └── my-attendance.blade.php  # Self-service for employees
├── leaves/
│   └── index.blade.php
├── payroll/
│   └── index.blade.php
├── salary-structure/
│   ├── index.blade.php
│   └── edit.blade.php
├── shifts/
│   └── index.blade.php
└── holidays/
    └── index.blade.php
```

### 5. Routes
**Source:** `routes/hr.php`

---

## ⚙️ Installation Steps

### Step 1: Copy Files

#### PowerShell (Windows)
```powershell
# Set paths
$SOURCE = "C:\path\to\source\project"
$TARGET = "C:\path\to\target\project"

# Copy migrations
Copy-Item "$SOURCE\database\migrations\2026_01_17_*" -Destination "$TARGET\database\migrations\" -Force

# Copy models
New-Item -ItemType Directory -Force -Path "$TARGET\app\Models\Hr"
Copy-Item "$SOURCE\app\Models\Hr\*" -Destination "$TARGET\app\Models\Hr\" -Force

# Copy controllers
New-Item -ItemType Directory -Force -Path "$TARGET\app\Http\Controllers\Hr"
Copy-Item "$SOURCE\app\Http\Controllers\Hr\*" -Destination "$TARGET\app\Http\Controllers\Hr\" -Force

# Copy views
Copy-Item "$SOURCE\resources\views\hr" -Destination "$TARGET\resources\views\" -Recurse -Force

# Copy routes
Copy-Item "$SOURCE\routes\hr.php" -Destination "$TARGET\routes\" -Force

Write-Host "HR Module copied successfully!" -ForegroundColor Green
```

#### Bash (Linux/Mac)
```bash
# Set paths
SOURCE="/path/to/source/project"
TARGET="/path/to/target/project"

# Copy migrations
cp $SOURCE/database/migrations/2026_01_17_* $TARGET/database/migrations/

# Copy models
mkdir -p $TARGET/app/Models/Hr
cp $SOURCE/app/Models/Hr/* $TARGET/app/Models/Hr/

# Copy controllers
mkdir -p $TARGET/app/Http/Controllers/Hr
cp $SOURCE/app/Http/Controllers/Hr/* $TARGET/app/Http/Controllers/Hr/

# Copy views
cp -r $SOURCE/resources/views/hr $TARGET/resources/views/

# Copy routes
cp $SOURCE/routes/hr.php $TARGET/routes/

echo "HR Module copied successfully!"
```

### Step 2: Register Routes

Add to `routes/web.php`:
```php
require __DIR__.'/hr.php';
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

### Step 4: Update Layout Reference

**Find in all HR views:**
```blade
@extends('admin_panel.layout.app')
```

**Replace with your layout:**
```blade
@extends('layouts.app')
```

### Step 5: Add My Attendance Button to Navbar

Add this to your navbar (inside the nav list):
```html
<li class="nav-item">
    <a href="{{ route('my-attendance') }}" class="nav-link" 
       style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border-radius: 8px; padding: 8px 16px;">
        <i class="fa fa-fingerprint"></i> My Attendance
    </a>
</li>
```

### Step 6: Add HR Menu to Sidebar/Navbar

```html
<li class="nav-item">
    <a href="#" class="nav-link">
        <i class="fa fa-users"></i>
        <span>HR Management</span>
        <i class="fa fa-chevron-down"></i>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('hr.departments.index') }}"><i class="fa fa-building"></i> Departments</a></li>
        <li><a href="{{ route('hr.designations.index') }}"><i class="fa fa-id-badge"></i> Designations</a></li>
        <li><a href="{{ route('hr.employees.index') }}"><i class="fa fa-user-tie"></i> Employees</a></li>
        <li><a href="{{ route('hr.shifts.index') }}"><i class="fa fa-clock"></i> Shifts</a></li>
        <li><a href="{{ route('hr.holidays.index') }}"><i class="fa fa-calendar-alt"></i> Holidays</a></li>
        <li><a href="{{ route('hr.attendance.index') }}"><i class="fa fa-clipboard-check"></i> Attendance</a></li>
        <li><a href="{{ route('hr.attendance.kiosk') }}" target="_blank"><i class="fa fa-camera"></i> Kiosk</a></li>
        <li><a href="{{ route('hr.leaves.index') }}"><i class="fa fa-calendar-minus"></i> Leaves</a></li>
        <li><a href="{{ route('hr.payroll.index') }}"><i class="fa fa-money-check-alt"></i> Payroll</a></li>
        <li><a href="{{ route('hr.salary-structure.index') }}"><i class="fa fa-coins"></i> Salary Structure</a></li>
    </ul>
</li>
```

---

## 📋 Database Schema

### Tables Created

| Table | Description |
|-------|-------------|
| `hr_departments` | Company departments (name, description) |
| `hr_designations` | Job titles (name, department_id) |
| `hr_employees` | Employee records (personal info, salary, shift, face data) |
| `hr_employee_documents` | Uploaded documents (CV, degrees, certificates) |
| `hr_attendances` | Daily attendance (check-in/out, late status, hours) |
| `hr_leaves` | Leave requests (type, dates, status, reason) |
| `hr_payrolls` | Monthly payroll (salary, deductions, net pay) |
| `hr_salary_structures` | Salary config (type, allowances, commission tiers) |
| `hr_shifts` | Work shifts (start/end time, grace period) |
| `hr_holidays` | Company holidays (date, name, type) |

### Key Columns in `hr_employees`

| Column | Type | Description |
|--------|------|-------------|
| `user_id` | FK | Links to users table for self-attendance |
| `department_id` | FK | Employee's department |
| `designation_id` | FK | Employee's job title |
| `shift_id` | FK | Assigned shift template |
| `custom_start_time` | TIME | Override shift start |
| `custom_end_time` | TIME | Override shift end |
| `face_encoding` | JSON | Face recognition data |
| `face_photo` | STRING | Reference photo path |

### Key Columns in `hr_attendances`

| Column | Type | Description |
|--------|------|-------------|
| `check_in_time` | TIME | Actual check-in time |
| `check_out_time` | TIME | Actual check-out time |
| `is_late` | BOOL | Late flag |
| `late_minutes` | INT | Minutes late |
| `is_early_leave` | BOOL | Left early flag |
| `total_hours` | DECIMAL | Calculated work hours |
| `check_in_photo` | STRING | Photo at check-in |
| `device_id` | STRING | For device integration |
| `check_in_location` | STRING | GPS Location address |
| `check_in_latitude` | DECIMAL | GPS Latitude |
| `check_in_longitude` | DECIMAL | GPS Longitude |
| `check_out_location` | STRING | GPS Location address |
| `check_out_latitude` | DECIMAL | GPS Latitude |
| `check_out_longitude` | DECIMAL | GPS Longitude |

---

## 🔗 Route Reference

### HR Routes (Protected by Auth)
| Route | Method | Name | Description |
|-------|--------|------|-------------|
| `/hr/departments` | GET/POST | `hr.departments.*` | Department CRUD |
| `/hr/designations` | GET/POST | `hr.designations.*` | Designation CRUD |
| `/hr/employees` | GET/POST | `hr.employees.*` | Employee CRUD |
| `/hr/shifts` | GET/POST | `hr.shifts.*` | Shift management |
| `/hr/holidays` | GET/POST | `hr.holidays.*` | Holiday management |
| `/hr/attendance` | GET/POST | `hr.attendance.*` | Attendance dashboard |
| `/hr/attendance/kiosk` | GET | `hr.attendance.kiosk` | Face recognition kiosk |
| `/hr/leaves` | GET/POST | `hr.leaves.*` | Leave management |
| `/hr/payroll` | GET/POST | `hr.payroll.*` | Payroll generation |
| `/hr/salary-structure` | GET/PUT | `hr.salary-structure.*` | Salary configuration |

### Self-Attendance Routes (No Permission Required)
| Route | Method | Name | Description |
|-------|--------|------|-------------|
| `/my-attendance` | GET | `my-attendance` | Personal attendance page |
| `/my-attendance/mark` | POST | `my-attendance.mark` | Mark check-in/out |

---

## 🛡️ Permissions (Optional)

Add these to your permissions system if using role-based access:

```php
// HR Permissions
'hr.departments.view', 'hr.departments.create', 'hr.departments.edit', 'hr.departments.delete',
'hr.designations.view', 'hr.designations.create', 'hr.designations.edit', 'hr.designations.delete',
'hr.employees.view', 'hr.employees.create', 'hr.employees.edit', 'hr.employees.delete',
'hr.attendance.view', 'hr.attendance.create', 'hr.attendance.mark',
'hr.shifts.view', 'hr.shifts.create', 'hr.shifts.edit', 'hr.shifts.delete',
'hr.holidays.view', 'hr.holidays.create', 'hr.holidays.edit', 'hr.holidays.delete',
'hr.leaves.view', 'hr.leaves.approve',
'hr.payroll.view', 'hr.payroll.generate',
'hr.salary-structure.view', 'hr.salary-structure.edit',
```

---

## 📦 Dependencies

### Required (Already in Laravel)
- Bootstrap 5
- jQuery 3.7+
- Carbon (date handling)

### Recommended CDN Includes
```html
<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- DataTables for tables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
```

---

## 📁 Storage Paths

| Content | Path |
|---------|------|
| Employee Documents | `public/uploads/human_resource/documents/` |
| Attendance Photos | `public/uploads/attendance/YYYY/MM/` |
| Employee Face Photos | `public/uploads/employees/faces/` |

**Create directories:**
```bash
mkdir -p public/uploads/human_resource/documents
mkdir -p public/uploads/attendance
mkdir -p public/uploads/employees/faces
chmod -R 775 public/uploads
```

---

## 🔧 Configuration Tips

### 1. Link Employee to User
For self-attendance to work, employees must be linked to users:
```php
// In Employee model, ensure this relationship exists:
public function user()
{
    return $this->belongsTo(User::class);
}

// When creating employee, set user_id
$employee->user_id = $user->id;
```

### 2. Default Shift
Create a default shift for employees without assigned shifts:
```sql
INSERT INTO hr_shifts (name, start_time, end_time, grace_minutes, is_default, created_at, updated_at)
VALUES ('Default', '09:00:00', '18:00:00', 15, 1, NOW(), NOW());
```

### 3. Face Recognition (Phase 2)
For full face recognition, add `face-api.js`:
```html
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
```

---

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Routes not found | Add `require __DIR__.'/hr.php';` to `web.php` |
| Class not found | Run `composer dump-autoload` |
| Views error | Update `@extends()` to your layout path |
| Permission denied | Run `chmod -R 775 public/uploads` |
| My Attendance shows "No Profile" | Link employee to user via `user_id` |
| Late detection not working | Create shifts with proper `grace_minutes` |
| Camera not working in kiosk | Use HTTPS in production |

---

## 📝 Changelog

### v3.1 (January 18, 2026)
- ✅ GPS Location Tracking on Check-in/out
- ✅ Automatic address resolution
- ✅ Enhanced Camera UI with countdown
- ✅ Improved Permission handling

### v3.0 (January 17, 2026)
- ✅ Added self-service "My Attendance" for all users
- ✅ Added navbar quick-access button
- ✅ Minimal modern UI redesign
- ✅ Late/early leave detection
- ✅ Total hours calculation

### v2.0
- ✅ Face recognition kiosk
- ✅ Shift management
- ✅ Holiday calendar
- ✅ Attendance filters (date, department, designation, status)

### v1.0
- ✅ Core HR module (Employees, Departments, Designations)
- ✅ Salary structure with tiered commission
- ✅ Basic attendance
- ✅ Leave management
- ✅ Payroll generation

---

## 📞 Support

For issues or feature requests, check:
1. Migration ran successfully: `php artisan migrate:status`
2. Routes are registered: `php artisan route:list | grep hr`
3. Models exist: `ls app/Models/Hr/`
4. Views exist: `ls resources/views/hr/`

---

**Built for Laravel** | **Copy. Paste. Ready.**
