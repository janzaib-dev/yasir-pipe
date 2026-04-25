# Monthly Payroll Auto-Generation Scheduled Job Guide

## Overview
This guide explains how to set up automatic monthly payroll generation at the end of each month using Laravel's task scheduling.

## Step 1: Create the Console Command

```bash
php artisan make:command GenerateMonthlyPayrolls
```

## Step 2: Define the Command

Edit `app/Console/Commands/GenerateMonthlyPayrolls.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyPayrolls extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payroll:generate-monthly {month?}';

    /**
     * The console command description.
     */
    protected $description = 'Generate monthly payroll for all active salaried employees';

    protected $payrollService;

    public function __construct(PayrollCalculationService $payrollService)
    {
        parent::__construct();
        $this->payrollService = $payrollService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get month (use argument or default to current month)
        $month = $this->argument('month') ?? Carbon::now()->format('Y-m');
        
        $this->info("Generating monthly payrolls for: {$month}");
        
        // Get all active salaried employees
        $employees = Employee::with('salaryStructure')
            ->whereHas('salaryStructure', function ($q) {
                $q->whereIn('salary_type', ['salary', 'both']);
            })
            ->where('status', 'active')
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No salaried employees found.');
            return 0;
        }

        $this->info("Found {$employees->count()} salaried employees.");
        
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($employees->count());
        $progressBar->start();

        foreach ($employees as $employee) {
            try {
                // Skip if payroll already exists
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('payroll_type', 'monthly')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                DB::beginTransaction();

                // Calculate payroll
                $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $month);

                // Create payroll record
                $payroll = Payroll::create(array_merge(
                    ['employee_id' => $employee->id],
                    array_except($payrollData, ['allowance_details', 'deduction_details'])
                ));

                // Save detailed breakdown
                $this->payrollService->savePayrollDetails(
                    $payroll,
                    $payrollData['allowance_details'] ?? [],
                    $payrollData['deduction_details'] ?? []
                );

                DB::commit();
                $generated++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $failed++;
                $this->error("\nFailed for {$employee->full_name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->table(
            ['Status', 'Count'],
            [
                ['Generated', $generated],
                ['Skipped (already exists)', $skipped],
                ['Failed', $failed],
                ['Total', $employees->count()],
            ]
        );

        if ($generated > 0) {
            $this->info("✓ Successfully generated {$generated} monthly payroll(s).");
        }

        if ($failed > 0) {
            $this->warn("⚠ {$failed} payroll(s) failed to generate. Check error messages above.");
        }

        return 0;
    }
}
```

## Step 3: Schedule the Command

Edit `app/Console/Kernel.php`:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate monthly payroll on the 28th of each month at 11:00 PM
        // This ensures it's done before month end
        $schedule->command('payroll:generate-monthly')
            ->monthlyOn(28, '23:00')
            ->timezone('Asia/Karachi') // Adjust to your timezone
            ->emailOutputOnFailure('hr@example.com'); // Optional: send email on failure
        
        // Alternative: Run on last day of month
        // $schedule->command('payroll:generate-monthly')
        //     ->when(function () {
        //         return now()->isLastOfMonth();
        //     })
        //     ->dailyAt('23:00')
        //     ->timezone('Asia/Karachi');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```

## Step 4: Set Up Cron Job (on your server)

For the scheduler to work, you need to add this to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### On Windows (for development):

Use Task Scheduler to run this command every minute:
```
php C:\path\to\project\artisan schedule:run
```

### Using Laravel Forge or Similar:
Most hosting platforms have a scheduler interface where you can enable Laravel's scheduler.

## Testing the Command

### Test Manually

```bash
# Generate for current month
php artisan payroll:generate-monthly

# Generate for specific month
php artisan payroll:generate-monthly 2026-01

# View output
php artisan payroll:generate-monthly --verbose
```

### Test the Scheduler Locally

```bash
# Run the scheduler manually to test
php artisan schedule:run

# List all scheduled tasks
php artisan schedule:list
```

## Advanced Options

### Option 1: Add Email Notification

Create a notification when payroll is generated:

```php
// In the command's handle() method, after generation:
if ($generated > 0) {
    \Mail::to('hr@example.com')->send(new MonthlyPayrollGenerated($month, $generated));
}
```

### Option 2: Add Slack Notification

```php
// In Kernel.php:
$schedule->command('payroll:generate-monthly')
    ->monthlyOn(28, '23:00')
    ->thenPing('https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK');
```

### Option 3: Queue the Generation

For better performance with many employees:

```php
// Instead of generating inline, dispatch jobs:
foreach ($employees as $employee) {
    GenerateEmployeePayroll::dispatch($employee, $month);
}
```

## Monitoring and Logging

### View Schedule in Database

Install `spatie/laravel-schedule-monitor`:

```bash
composer require spatie/laravel-schedule-monitor
```

Configure to track schedule execution.

### Check Logs

View scheduler logs:
```bash
tail -f storage/logs/laravel.log | grep payroll
```

### Create Dashboard Metric

Add to your admin dashboard:
```php
$lastGenerationDate = DB::table('schedule_logs')
    ->where('command', 'payroll:generate-monthly')
    ->latest()
    ->first();
```

## What Day to Run It?

### Option 1: 28th of Each Month (Recommended)
- Covers all months (even February)
- Gives time to review before month end
- Schedule: `->monthlyOn(28, '23:00')`

### Option 2: Last Day of Month
- More accurate for variable-length months
- Requires daily check
- Schedule: See "when" example above

### Option 3: 1st of Next Month
- Generate for previous month
- More time for final attendance
- Needs date adjustment in command

## Customization Examples

### Generate for Previous Month (on 1st of new month)

```php
// In the command:
$month = $this->argument('month') ?? Carbon::now()->subMonth()->format('Y-m');
```

### Only Generate for Specific Departments

```php
$employees = Employee::with('salaryStructure')
    ->whereHas('salaryStructure', function ($q) {
        $q->whereIn('salary_type', ['salary', 'both']);
    })
    ->whereIn('department_id', [1, 2, 3]) // Specific departments
    ->where('status', 'active')
    ->get();
```

### Send Individual Employee Notifications

```php
foreach ($employees as $employee) {
    // ... generate payroll ...
    
    if ($payroll) {
        // Send payslip email to employee
        \Mail::to($employee->email)->send(new PayslipGenerated($payroll));
    }
}
```

## Rollback Command (Optional)

Create a command to delete payrolls if something goes wrong:

```bash
php artisan make:command DeleteMonthlyPayrolls
```

```php
protected $signature = 'payroll:delete-monthly {month}';

public function handle()
{
    $month = $this->argument('month');
    
    if (!$this->confirm("Delete all monthly payrolls for {$month}?")) {
        return;
    }
    
    $count = Payroll::where('month', $month)
        ->where('payroll_type', 'monthly')
        ->where('status', 'generated') // Only delete generated, not paid
        ->delete();
    
    $this->info("Deleted {$count} payroll(s).");
}
```

## Production Checklist

- [ ] Command tested manually
- [ ] Scheduler tested locally
- [ ] Cron job configured on server
- [ ] Timezone set correctly
- [ ] Notification email configured (optional)
- [ ] Monitoring/logging set up
- [ ] Rollback command ready (optional)
- [ ] Team notified about automation

## Troubleshooting

### Scheduler not running
- Check cron job is set up
- Run `php artisan schedule:run` manually
- Check `storage/logs/laravel.log`

### Command fails silently
- Add `--verbose` flag when testing
- Check database connection
- Verify employee data integrity

### Wrong timezone
- Set timezone in `config/app.php`
- Override in schedule: `->timezone('Asia/Karachi')`

---

**Best Practice**: Run the command manually for the first month to ensure everything works correctly, then enable the scheduled version.

## Example Usage Timeline

**January 28, 11:00 PM**: Command runs automatically
- Generates payroll for all salaried employees for January
- HR receives notification email
- Payrolls appear in system with status "Generated"

**January 29-31**: HR reviews payrolls
- Edits any incorrect entries
- Adds manual adjustments
- Marks as "Reviewed"

**February 1-5**: HR processes payments
- Marks payrolls as "Paid"
- System locks those records

**February 28, 11:00 PM**: Repeat for February...
