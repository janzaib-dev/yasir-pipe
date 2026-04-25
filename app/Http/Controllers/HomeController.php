<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $usertype = Auth::user()->usertype;
        $userId = Auth::id();

        if ($usertype == 'user') {
            return view('user_panel.dashboard', compact('userId'));
        } elseif ($usertype == 'admin') {
            // Counts
            $categoryCount = Auth::user()->can('categories.view') ? DB::table('categories')->count() : 0;
            $subcategoryCount = Auth::user()->can('subcategories.view') ? DB::table('subcategories')->count() : 0;
            $productCount = Auth::user()->can('products.view') ? DB::table('products')->count() : 0;
            $customerscount = Auth::user()->can('customers.view') ? DB::table('customers')->count() : 0;

            // Stats
            $totalPurchases = Auth::user()->can('purchases.view') ? DB::table('purchases')->sum('net_amount') : 0;
            $totalPurchaseReturns = Auth::user()->can('purchase.returns.view') ? DB::table('purchase_returns')->sum('net_amount') : 0;
            $totalSales = Auth::user()->can('sales.view') ? DB::table('sales')->sum('total_net') : 0;
            $totalSalesReturns = Auth::user()->can('sales.returns.view') ? DB::table('sale_returns')->sum('net_amount') : 0;

            // Financial Summary (Accounting Based)
            $financialSummary = [];
            if (Auth::user()->can('purchases.view') || Auth::user()->can('sales.view')) {
                try {
                    $balanceService = app(\App\Services\BalanceService::class);
                    $fromDate = request('from_date', now()->startOfMonth()->format('Y-m-d'));
                    $toDate = request('to_date', now()->endOfMonth()->format('Y-m-d'));
                    $financialSummary = $balanceService->getFinancialSummary($fromDate, $toDate);
                } catch (\Exception $e) {
                     \Log::error("Dashboard Financial Summary Error: " . $e->getMessage());
                }
            }

            // ===== SALES REPORT CHARTS =====
            $salesChartStats = ['daily' => ['series' => [], 'categories' => []]];
            if (Auth::user()->can('sales.view')) {
                // DAILY (last 7 days)
                $dailyLabels = collect(range(6, 0))->map(fn($i) => \Carbon\Carbon::today()->subDays($i)->format('Y-m-d'));
                $dailyData = $dailyLabels->map(function ($date) {
                    return DB::table('sales')
                        ->whereDate('created_at', $date)
                        ->sum('total_net');
                });

                // WEEKLY (This + Last 2 weeks)
                $weeklyLabels = ['This Week', 'Last Week', '2 Weeks Ago'];
                $weeklyData = collect([0, 1, 2])->map(function ($i) {
                    $start = \Carbon\Carbon::now()->startOfWeek()->subWeeks($i);
                    $end = $start->copy()->endOfWeek();
                    return DB::table('sales')
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('total_net');
                })->reverse()->values();

                // MONTHLY (Jan → Current month)
                $months = range(1, \Carbon\Carbon::now()->month);
                $monthLabels = collect($months)->map(fn($m) => \Carbon\Carbon::create()->month($m)->format('F'));
                $monthlyData = collect($months)->map(function ($month) {
                    return DB::table('sales')
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', \Carbon\Carbon::now()->year)
                        ->sum('total_net');
                });

                $salesChartStats = [
                    'daily' => [
                        'categories' => $dailyLabels,
                        'series' => [['name' => 'Sales', 'data' => $dailyData]]
                    ],
                    'weekly' => [
                        'categories' => $weeklyLabels,
                        'series' => [['name' => 'Sales', 'data' => $weeklyData]]
                    ],
                    'monthly' => [
                        'categories' => $monthLabels,
                        'series' => [['name' => 'Sales', 'data' => $monthlyData]]
                    ]
                ];
            }

            // ===== PURCHASE CHARTS =====
            $purchaseChartStats = ['daily' => ['series' => [], 'categories' => []]];
            if (Auth::user()->can('purchases.view')) {
                // DAILY
                $purchaseDailyLabels = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i)->format('Y-m-d'));
                $purchaseDailySeries = [[
                    'name' => 'Purchases',
                    'data' => $purchaseDailyLabels->map(function ($date) {
                        return DB::table('purchases')
                            ->whereDate('created_at', $date)
                            ->sum('net_amount');
                    })
                ]];

                // WEEKLY
                $purchaseWeeklyLabels = ['This Week', 'Last Week', '2 Weeks Ago'];
                $purchaseWeeklySeries = [[
                    'name' => 'Purchases',
                    'data' => collect([0, 1, 2])->map(function ($i) {
                        $start = Carbon::now()->startOfWeek()->subWeeks($i);
                        $end = $start->copy()->endOfWeek();
                        return DB::table('purchases')
                            ->whereBetween('created_at', [$start, $end])
                            ->sum('net_amount');
                    })->reverse()->values()
                ]];

                // MONTHLY
                $months = range(1, Carbon::now()->month);
                $purchaseMonthLabels = collect($months)->map(fn($m) => Carbon::create()->month($m)->format('F'));
                $purchaseMonthlySeries = [[
                    'name' => 'Purchases',
                    'data' => collect($months)->map(function ($month) {
                        return DB::table('purchases')
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', Carbon::now()->year)
                            ->sum('net_amount');
                    })
                ]];

                $purchaseChartStats = [
                    'daily' => [
                        'categories' => $purchaseDailyLabels,
                        'series' => $purchaseDailySeries
                    ],
                    'weekly' => [
                        'categories' => $purchaseWeeklyLabels,
                        'series' => $purchaseWeeklySeries
                    ],
                    'monthly' => [
                        'categories' => $purchaseMonthLabels,
                        'series' => $purchaseMonthlySeries
                    ]
                ];
            }

            // ===== PAYMENT IN / OUT STATS =====
            $now = Carbon::now();
            $monthStart = $now->copy()->startOfMonth();
            $monthEnd = $now->copy()->endOfMonth();

            // 1. Payment IN (Monthly)
            $v2ReceiptsMonth = DB::table('voucher_masters')->where('voucher_type', 'receipt')->whereBetween('date', [$monthStart, $monthEnd])->sum('total_amount');
            $v1ReceiptsMonth = DB::table('receipts_vouchers')->whereBetween('receipt_date', [$monthStart, $monthEnd])->sum('total_amount');
            $custPaymentsMonth = DB::table('customer_payments')->whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount');
            $paymentInMonth = $v2ReceiptsMonth + $v1ReceiptsMonth + $custPaymentsMonth;

            // 2. Payment IN (Overall)
            $v2ReceiptsAll = DB::table('voucher_masters')->where('voucher_type', 'receipt')->sum('total_amount');
            $v1ReceiptsAll = DB::table('receipts_vouchers')->sum('total_amount');
            $custPaymentsAll = DB::table('customer_payments')->sum('amount');
            $paymentInOverall = $v2ReceiptsAll + $v1ReceiptsAll + $custPaymentsAll;

            // 3. Payment OUT (Monthly)
            $v2PaymentsMonth = DB::table('voucher_masters')->whereIn('voucher_type', ['payment', 'expense'])->whereBetween('date', [$monthStart, $monthEnd])->sum('total_amount');
            $v1PaymentsMonth = DB::table('payment_vouchers')->whereBetween('receipt_date', [$monthStart, $monthEnd])->sum('total_amount');
            $v1ExpensesMonth = DB::table('expense_vouchers')->whereBetween('entry_date', [$monthStart, $monthEnd])->sum('total_amount');
            $vendorPaymentsMonth = DB::table('vendor_payments')->whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount');
            $paymentOutMonth = $v2PaymentsMonth + $v1PaymentsMonth + $v1ExpensesMonth + $vendorPaymentsMonth;

            // 4. Payment OUT (Overall)
            $v2PaymentsAll = DB::table('voucher_masters')->whereIn('voucher_type', ['payment', 'expense'])->sum('total_amount');
            $v1PaymentsAll = DB::table('payment_vouchers')->sum('total_amount');
            $v1ExpensesAll = DB::table('expense_vouchers')->sum('total_amount');
            $vendorPaymentsAll = DB::table('vendor_payments')->sum('amount');
            $paymentOutOverall = $v2PaymentsAll + $v1PaymentsAll + $v1ExpensesAll + $vendorPaymentsAll;

            // ===== TOP 10 PRODUCTS & CUSTOMERS (Needs Sales Permission) =====
            $topProducts = collect();
            $topCustomers = collect();

            if (Auth::user()->can('sales.view')) {
                $topProducts = DB::table('sale_items')
                    ->select('product_name', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(total) as total_revenue'))
                    ->groupBy('product_name')
                    ->orderByDesc('total_qty')
                    ->limit(10)
                    ->get();

                $topCustomers = DB::table('sales')
                    ->join('customers', 'sales.customer_id', '=', 'customers.id')
                    ->select('customers.customer_name', DB::raw('COUNT(sales.id) as total_orders'), DB::raw('SUM(sales.total_net) as total_sales'))
                    ->groupBy('customers.id', 'customers.customer_name')
                    ->orderByDesc('total_sales')
                    ->limit(10)
                    ->get();
            }

            return view('admin_panel.dashboard', compact(
                'categoryCount',
                'subcategoryCount',
                'productCount',
                'customerscount',
                'totalPurchases',
                'totalPurchaseReturns',
                'totalSales',
                'totalSalesReturns',
                'salesChartStats',
                'purchaseChartStats',
                'financialSummary',
                'paymentInMonth',
                'paymentInOverall',
                'paymentOutMonth',
                'paymentOutOverall',
                'topProducts',
                'topCustomers'
            ));
        } else {
            return redirect()->back()->with('error', 'Unauthorized access');
        }
    }
}
