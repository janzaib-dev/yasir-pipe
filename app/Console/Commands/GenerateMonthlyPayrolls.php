<?php

namespace App\Console\Commands;

use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyPayrolls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate-monthly {month? : The month to generate payroll for (YYYY-MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly payrolls for all salaried employees';

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
        $month = $this->argument('month') ?: Carbon::now()->format('Y-m');
        
        $this->info("Generating monthly payrolls for: {$month}");

        $employees = Employee::with('salaryStructure')
            ->whereHas('salaryStructure', function ($q) {
                $q->whereIn('salary_type', ['salary', 'both']);
            })
            ->where('status', 'active')
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No salaried employees found.');
            return;
        }

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($employees as $employee) {
            try {
                // Check if already exists
                $exists = Payroll::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('payroll_type', 'monthly')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                DB::transaction(function () use ($employee, $month) {
                    $payrollData = $this->payrollService->calculateMonthlyPayroll($employee, $month);

                    $payroll = Payroll::create(array_merge(
                        ['employee_id' => $employee->id],
                        Arr::except($payrollData, ['allowance_details', 'deduction_details'])
                    ));

                    $this->payrollService->savePayrollDetails(
                        $payroll,
                        $payrollData['allowance_details'] ?? [],
                        $payrollData['deduction_details'] ?? []
                    );
                });

                $generated++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError generating for {$employee->full_name}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Completed: {$generated} generated, {$skipped} skipped, {$errors} errors.");
    }
}
