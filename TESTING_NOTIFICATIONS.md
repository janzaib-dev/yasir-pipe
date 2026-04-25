# Testing Automatic Debt Notifications

## Quick Test (Immediate)

Run this command to test the debt check logic:

```bash
php test_automatic_debt_check.php
```

This will:
1. Create 3 test sales (5, 8, and 12 days old)
2. Run the debt check command
3. Show you which notifications were created
4. Verify the logic is working correctly

---

## Testing Automatic Scheduling

The system is configured to run **automatically every day at 9:00 AM**. Here's how to verify it's scheduled:

### Check Schedule Configuration

```bash
php artisan schedule:list
```

You should see:
```
0 9 * * * php artisan debt:check ........... Next Due: Tomorrow at 9:00 AM
```

---

## Setting Up Automatic Execution

For the scheduler to run automatically, you need to set up a system task:

### Windows (Task Scheduler)

1. Open **Task Scheduler**
2. Create New Task:
   - **Name**: Laravel Scheduler
   - **Trigger**: Daily, repeat every 1 minute
   - **Action**: Start a program
     - **Program**: `C:\path\to\php.exe`
     - **Arguments**: `artisan schedule:run`
     - **Start in**: `C:\Users\SURFACE\OneDrive\Desktop\lararv-proj\Three_stars_medical`

### Linux/Mac (Crontab)

```bash
* * * * * cd /path/to/Three_stars_medical && php artisan schedule:run >> /dev/null 2>&1
```

---

## Manual Testing

### Test 1: Create Old Sale Manually

```sql
-- Make an existing sale appear 8 days old
UPDATE sales 
SET created_at = DATE_SUB(NOW(), INTERVAL 8 DAY),
    sale_date = DATE_SUB(NOW(), INTERVAL 8 DAY)
WHERE id = 1;  -- Replace with actual sale ID

-- Ensure it's unpaid
UPDATE sales 
SET remaining_amount = total_amount - paid_amount
WHERE id = 1;
```

Then run:
```bash
php artisan debt:check
```

### Test 2: Change Threshold Settings

1. Go to `/settings` in your browser
2. Change "Debt Warning Days" from 7 to 5
3. Click "Save Settings"
4. Run: `php artisan debt:check`
5. Verify it now alerts for 5+ day old sales

---

## Verification Checklist

✅ **Command runs successfully**
```bash
php artisan debt:check
# Should output: "Starting debt check..." and "Debt check complete!"
```

✅ **Notifications are created**
- Check database: `SELECT * FROM system_notifications WHERE source_type = 'debt_check';`
- Check browser: Bell icon shows badge count

✅ **Role-based targeting works**
- Super Admin: Gets ALL notifications
- HR: Gets ALL notifications  
- Sales Officers: Get only their customer notifications

✅ **No duplicates**
- Run `php artisan debt:check` twice
- Should NOT create duplicate notifications for same sale

✅ **Scheduler is configured**
```bash
php artisan schedule:list
# Should show debt:check scheduled for 9:00 AM daily
```

---

## Monitoring in Production

### Check Last Run
```bash
# View Laravel log
tail -f storage/logs/laravel.log
```

### View Notification History
```sql
SELECT 
    sn.created_at,
    sn.type,
    sn.title,
    u.name as user_name,
    sn.is_read
FROM system_notifications sn
JOIN users u ON sn.user_id = u.id
WHERE sn.source_type = 'debt_check'
ORDER BY sn.created_at DESC
LIMIT 20;
```

### Test Scheduler Manually
```bash
# This simulates what the cron job does
php artisan schedule:run
```

---

## Troubleshooting

**Problem**: No notifications created
- Check if there are unpaid sales older than threshold
- Verify settings: `SELECT * FROM settings WHERE key LIKE 'debt%';`
- Check command output for errors

**Problem**: Scheduler not running
- Verify cron/task scheduler is set up
- Check system time is correct
- Test manually: `php artisan schedule:run`

**Problem**: Wrong users getting notifications
- Check user roles in database
- Verify role names match: 'Super Admin', 'HR', 'Sales Officer'
- Check sales have correct `user_id` assigned

---

## Clean Up Test Data

After testing, remove test data:

```sql
-- Remove test sales
DELETE FROM sales WHERE invoice_number LIKE 'TEST-%';

-- Remove test notifications
DELETE FROM system_notifications WHERE source_type = 'debt_check';
```
