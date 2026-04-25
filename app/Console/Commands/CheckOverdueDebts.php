<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Setting;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOverdueDebts extends Command
{
    protected $signature = 'debt:check';
    protected $description = 'Check for overdue customer debts and create notifications';

    public function handle()
    {
        $this->info('Starting debt check...');

        // Get thresholds from settings
        $warningDays = Setting::get('debt_warning_days', 7);
        $criticalDays = Setting::get('debt_critical_days', 10);

        $this->info("Thresholds: Warning={$warningDays} days, Critical={$criticalDays} days");

        // Find unpaid sales
        $unpaidSales = Sale::where(function ($query) {
                $query->where('sale_status', '!=', 'paid')
                      ->orWhereRaw('(total_net - COALESCE(cash, 0) - COALESCE(card, 0)) > 0');
            })
            ->with('customer_relation')
            ->get();

        $this->info("Found {$unpaidSales->count()} unpaid sales");

        $warningCount = 0;
        $criticalCount = 0;

        foreach ($unpaidSales as $sale) {
            $daysOld = Carbon::parse($sale->created_at)->diffInDays(now());
            
            // Determine notification type (prioritize critical over warning)
            $notificationType = null;
            $notificationTitle = null;
            
            if ($daysOld >= $criticalDays) {
                $notificationType = 'critical';
                $notificationTitle = '🔴 Critical: Payment Overdue';
                $criticalCount++;
            } elseif ($daysOld >= $warningDays) {
                $notificationType = 'warning';
                $notificationTitle = '⚠️ Warning: Payment Due';
                $warningCount++;
            }

            if (!$notificationType) {
                continue;
            }

            // Check if notification already exists (any type, to prevent duplicates)
            $existingNotification = SystemNotification::where('source_id', $sale->id)
                ->where('source_type', 'Sale')
                ->exists();

            if ($existingNotification) {
                continue;
            }

            // Prepare notification data
            $customerName = $sale->customer_relation->customer_name ?? 'Unknown Customer';
            $balance = $sale->total_net - ($sale->cash + $sale->card);
            
            $message = "Invoice #{$sale->invoice_no} from {$customerName} is {$daysOld} days overdue. Outstanding balance: PKR " . number_format($balance, 2);

            $notificationData = [
                'title' => $notificationTitle,
                'message' => $message,
                'type' => $notificationType,
                'source_id' => $sale->id,
                'source_type' => 'Sale',
                'action_url' => '/sales/' . $sale->id . '/invoice',
            ];

            // Get recipients
            $recipients = $this->getRecipients($sale);
            
            // Create notifications
            SystemNotification::createForUsers($recipients, $notificationData);
            
            $this->info("Created {$notificationType} notification for Sale #{$sale->invoice_no}");
        }

        $this->info("✅ Debt check complete!");
        $this->info("Created {$warningCount} warning and {$criticalCount} critical notifications");

        return 0;
    }

    /**
     * Get user IDs who should receive this notification
     */
    private function getRecipients(Sale $sale): array
    {
        $recipients = [];

        try {
            // Try to get Super Admin and HR users
            $adminUsers = User::role(['Super Admin', 'HR'])->pluck('id')->toArray();
            $recipients = array_merge($recipients, $adminUsers);
        } catch (\Exception $e) {
            // If roles don't exist, fallback to admin email or all users
            $adminUsers = User::where('email', 'admin@admin.com')
                ->orWhere('email', 'LIKE', '%admin%')
                ->pluck('id')
                ->toArray();
            
            if (empty($adminUsers)) {
                // Last resort: notify all users
                $adminUsers = User::limit(5)->pluck('id')->toArray();
            }
            
            $recipients = array_merge($recipients, $adminUsers);
        }

        // Get Sales Officer if assigned
        if (isset($sale->sales_officer_id)) {
            $recipients[] = $sale->sales_officer_id;
        }

        return array_unique($recipients);
    }
}
