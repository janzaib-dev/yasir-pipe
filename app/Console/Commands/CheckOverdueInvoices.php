<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'sales:check-overdue';

    protected $description = 'Check for overdue sales invoices and notify admins/HR';

    public function handle()
    {
        // Find sales that became overdue yesterday (have credit_days set)
        $overdueSales = Sale::with('customer_relation')
            ->whereDate('due_date', '=', now()->subDay()->toDateString())
            ->whereNotNull('credit_days')
            ->get();

        if ($overdueSales->isEmpty()) {
            $this->info('No new overdue invoices found.');

            return 0;
        }

        // Get Admin/HR users
        try {
            $users = User::role(['Super Admin', 'HR', 'Manager'])->get();
        } catch (\Exception $e) {
            // Fallback if roles not configured
            $users = User::permission('sales.view')->get();
        }

        if ($users->isEmpty()) {
            $this->warn('No users found to notify.');

            return 0;
        }

        foreach ($overdueSales as $sale) {
            $customerName = $sale->customer_relation->customer_name ?? 'Unknown';

            foreach ($users as $user) {
                SystemNotification::create([
                    'user_id' => $user->id,
                    'title' => 'Invoice Overdue',
                    'message' => "Invoice #{$sale->invoice_no} (Customer: {$customerName}) is now overdue.",
                    'type' => 'warning',
                    'source_id' => $sale->id,
                    'source_type' => 'App\\Models\\Sale',
                    'action_url' => route('sales.invoice', $sale->id),
                    'is_read' => false,
                ]);
            }

            $this->info("✓ Sent notifications for Invoice #{$sale->invoice_no}");
        }

        $this->info("\nTotal: {$overdueSales->count()} overdue invoices processed.");

        return 0;
    }
}
