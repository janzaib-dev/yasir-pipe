<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function onhand()
    {
        $rows = Product::leftJoin('v_stock_onhand as soh', 'soh.product_id', '=', 'products.id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->selectRaw('
                products.id,
                products.item_code,
                products.item_name,
                COALESCE(brands.name, "") as brand_name,
                COALESCE(units.name, "") as unit_name,
                COALESCE(soh.onhand_qty, 0) as onhand_qty
            ')
            ->orderBy('products.item_name')
            ->get();

        return view('admin_panel.Reporting.onhand', compact('rows'));
    }

    public function item_stock_report()
    {
        $products = Product::orderBy('item_name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin_panel.reporting.item_stock_report', compact('products', 'categories'));
    }

    // AJAX endpoint to fetch report rows
    public function fetchItemStock(Request $request)
    {
        $productId  = $request->product_id;
        $categoryId = $request->category_id;

        $productsQuery = Product::with('warehouseStocks');
        if ($productId && $productId !== 'all') {
            $productsQuery->where('id', $productId);
        }
        if ($categoryId && $categoryId !== 'all') {
            $productsQuery->where('category_id', $categoryId);
        }
        $products = $productsQuery->orderBy('item_name')->get();

        $rows = [];
        $grandTotalValue = 0;

        foreach ($products as $product) {

            // ✅ REAL-TIME balance straight from WarehouseStock
            $balance = (float) $product->warehouseStocks->sum('total_pieces');

            // Purchased qty & amount (for historical display only)
            $purchaseData = DB::table('purchase_items')
                ->where('product_id', $product->id)
                ->select(DB::raw('COALESCE(SUM(qty),0) as total_qty'), DB::raw('COALESCE(SUM(line_total),0) as total_amount'))
                ->first();

            $purchased      = (float) $purchaseData->total_qty;
            $purchaseAmount = (float) $purchaseData->total_amount;

            // Sold qty & amount (Net)
            $saleStats = DB::table('sale_items')
                ->where('product_id', $product->id)
                ->selectRaw('COALESCE(SUM(total_pieces),0) as total_qty, COALESCE(SUM(total),0) as total_amount')
                ->first();

            $sold       = (float) $saleStats->total_qty;
            $saleAmount = (float) $saleStats->total_amount;

            // Returned qty (from stock movements ref_type SR or SALE_RETURN)
            $returnedQty = (float) DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->whereIn('ref_type', ['SR', 'SALE_RETURN'])
                ->where('type', 'in')
                ->sum('qty');

            // Initial (opening) stock = balance - purchased + (sold_net - returned)
            // But since sold_net already reflects (Gross - Returned), we just need:
            // balance - purchased + sold_net
            $initial = max(0, $balance - $purchased + $sold);

            // Determine default purchase price per piece
            $productPurchPrice = 0;
            if ($product->size_mode === 'by_size') {
                $m2PerPiece         = (float) ($product->pieces_per_m2 ?? 0);
                $purchPerM2         = (float) ($product->purchase_price_per_m2 ?? 0);
                $productPurchPrice  = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($product->purchase_price_per_piece ?? 0);
            }

            // Weighted Average Purchase Price
            $initialAmount  = $initial * $productPurchPrice;
            $totalQtyIn     = $initial + $purchased;
            $totalAmountIn  = $initialAmount + $purchaseAmount;
            $averagePrice   = $totalQtyIn > 0 ? ($totalAmountIn / $totalQtyIn) : $productPurchPrice;

            // Stock value = Live Balance × Avg Purchase Price
            $stockValue       = $balance * $averagePrice;
            $grandTotalValue += $stockValue;

            // Cartons / Loose
            $ppb = (float) ($product->pieces_per_box ?? 1);
            if ($ppb > 1) {
                $cartons = floor($balance / $ppb);
                $loose   = $balance % $ppb;
            } else {
                $cartons = '-';
                $loose   = $balance;
            }

            $rows[] = [
                'id'              => $product->id,
                'item_code'       => $product->item_code,
                'item_name'       => $product->item_name,
                'initial_stock'   => $initial,
                'purchased'       => $purchased,
                'purchase_amount' => $purchaseAmount,
                'sold'            => $sold,
                'sale_amount'     => $saleAmount,
                'returned_qty'    => $returnedQty,
                'balance'         => $balance,
                'cartons'         => $cartons,
                'loose'           => $loose,
                'average_price'   => $averagePrice,
                'stock_value'     => $stockValue,
            ];
        }

        return response()->json([
            'data'        => $rows,
            'grand_total' => $grandTotalValue,
        ]);
    }

    public function profit_loss_report()
    {
        $products = Product::orderBy('item_name')->get();
        $categories = Category::orderBy('name')->get();
        $customers = DB::table('customers')->orderBy('customer_name')->get();

        return view('admin_panel.reporting.profit_loss_report', compact('products', 'categories', 'customers'));
    }

    public function fetchProfitLoss(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $productId = $request->product_id;
        $categoryId = $request->category_id;
        $customerId = $request->customer_id;

        $productsQuery = Product::query();
        if ($productId && $productId !== 'all') {
            $productsQuery->where('id', $productId);
        }
        if ($categoryId && $categoryId !== 'all') {
            $productsQuery->where('category_id', $categoryId);
        }
        
        $products = $productsQuery->get();
        $productStats = [];
        $totalGrossProfit = 0;

        foreach ($products as $product) {
            // 1. Calculate Weighted Average Purchase Price (Same logic as Stock Report)
            $initial = (float) DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->where('ref_type', 'INIT')
                ->sum('qty');

            $purchaseData = DB::table('purchase_items')
                ->where('product_id', $product->id)
                ->select(DB::raw('COALESCE(SUM(qty),0) as total_qty'), DB::raw('COALESCE(SUM(line_total),0) as total_amount'))
                ->first();

            $purchased = (float) $purchaseData->total_qty;
            $purchaseAmount = (float) $purchaseData->total_amount;

            $productPurchPrice = 0;
            if ($product->size_mode === 'by_size') {
                $m2PerPiece = (float) ($product->pieces_per_m2 ?? 0);
                $purchPerM2 = (float) ($product->purchase_price_per_m2 ?? 0);
                $productPurchPrice = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($product->purchase_price_per_piece ?? 0);
            }

            $initialAmount = $initial * $productPurchPrice;
            $totalQtyIn = $initial + $purchased;
            $totalAmountIn = $initialAmount + $purchaseAmount;
            $averagePrice = $totalQtyIn > 0 ? ($totalAmountIn / $totalQtyIn) : $productPurchPrice;

            // 2. Calculate Sales in the period
            $saleQuery = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $product->id);
            
            if ($start && $end) {
                $saleQuery->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end]);
            }

            if ($customerId && $customerId !== 'all') {
                $saleQuery->where('sales.customer_id', $customerId);
            }

            $saleStats = $saleQuery->selectRaw('COALESCE(SUM(total_pieces),0) as sold_qty_pieces, COALESCE(SUM(qty),0) as sold_qty, COALESCE(SUM(total),0) as sold_amount')
                ->first();

            $soldQty = (float) $saleStats->sold_qty;
            $soldQtyPieces = (float) $saleStats->sold_qty_pieces;
            $soldAmount = (float) $saleStats->sold_amount;
            
            // Calculate Returns for this period
            $returnQuery = DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->whereIn('ref_type', ['SR', 'SALE_RETURN'])
                ->where('type', 'in');
            
            if ($start && $end) {
                $returnQuery->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
            }
            $returnedQtyPieces = (float) $returnQuery->sum('qty');
            $returnedAmount = $returnedQtyPieces * $product->sale_price_per_piece; // Estimated return value

            $costOfGoodsSold = $soldQtyPieces * $averagePrice;
            $grossProfit = $soldAmount - $costOfGoodsSold;

            if ($soldQty > 0 || $returnedQtyPieces > 0) {
                 $productStats[] = [
                    'item_code' => $product->item_code,
                    'item_name' => $product->item_name,
                    'sold_qty' => $soldQty,
                    'returned_qty' => $returnedQtyPieces,
                    'revenue' => $soldAmount,
                    'avg_cost' => $averagePrice,
                    'cogs' => $costOfGoodsSold,
                    'profit' => $grossProfit
                ];
                $totalGrossProfit += $grossProfit;
            }
        }

        // 3. Calculate Expenses
        $expenseQueryV1 = DB::table('expense_vouchers');
        $expenseQueryV2 = DB::table('voucher_masters')->where('voucher_type', 'expense');

        if ($start && $end) {
            $expenseQueryV1->whereBetween('entry_date', [$start, $end]);
            $expenseQueryV2->whereBetween('date', [$start, $end]);
        }

        $totalExpenses = $expenseQueryV1->sum('total_amount') + $expenseQueryV2->sum('total_amount');

        // 4. Top 10 Customers by Profit
        $allCustomers = DB::table('customers')->get();
        $customerProfits = [];

        // Build a map of average prices per product (reuse from above)
        $avgPriceMap = [];
        foreach ($products as $product) {
            $initial = (float) DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->where('ref_type', 'INIT')
                ->sum('qty');

            $purchaseData = DB::table('purchase_items')
                ->where('product_id', $product->id)
                ->select(DB::raw('COALESCE(SUM(qty),0) as total_qty'), DB::raw('COALESCE(SUM(line_total),0) as total_amount'))
                ->first();

            $purchased = (float) $purchaseData->total_qty;
            $purchaseAmount = (float) $purchaseData->total_amount;

            $productPurchPrice = 0;
            if ($product->size_mode === 'by_size') {
                $m2PerPiece = (float) ($product->pieces_per_m2 ?? 0);
                $purchPerM2 = (float) ($product->purchase_price_per_m2 ?? 0);
                $productPurchPrice = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($product->purchase_price_per_piece ?? 0);
            }

            $initialAmount = $initial * $productPurchPrice;
            $totalQtyIn = $initial + $purchased;
            $totalAmountIn = $initialAmount + $purchaseAmount;
            $avgPriceMap[$product->id] = $totalQtyIn > 0 ? ($totalAmountIn / $totalQtyIn) : $productPurchPrice;
        }

        $balanceService = app(\App\Services\BalanceService::class);
        foreach ($allCustomers as $customer) {
            $custSaleQuery = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.customer_id', $customer->id);

            if ($start && $end) {
                $custSaleQuery->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end]);
            }

            $custSaleItems = $custSaleQuery->select(
                'sale_items.product_id', 
                DB::raw('SUM(sale_items.total_pieces) as sold_qty_pieces'), 
                DB::raw('SUM(sale_items.total) as sold_amount')
            )
                ->groupBy('sale_items.product_id')
                ->get();

            $custRevenue = 0;
            $custCogs = 0;

            foreach ($custSaleItems as $item) {
                $avgPrice = $avgPriceMap[$item->product_id] ?? 0;
                $custRevenue += (float) $item->sold_amount;
                $custCogs += (float) $item->sold_qty_pieces * $avgPrice;
            }

            $custProfit = $custRevenue - $custCogs;

            if ($custRevenue > 0) {
                $customerProfits[] = [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'balance' => $balanceService->getCustomerBalance($customer->id),
                    'revenue' => round($custRevenue, 2),
                    'cogs' => round($custCogs, 2),
                    'profit' => round($custProfit, 2),
                ];
            }
        }

        // Sort by profit descending and take top 10
        usort($customerProfits, function($a, $b) {
            return $b['profit'] <=> $a['profit'];
        });
        $topCustomers = array_slice($customerProfits, 0, 10);

        return response()->json([
            'products' => $productStats,
            'total_gross_profit' => round($totalGrossProfit, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($totalGrossProfit - $totalExpenses, 2),
            'top_customers' => $topCustomers
        ]);
    }

    public function purchase_report()
    {
        $products = \App\Models\product::orderBy('item_name')->get();
        $vendors = \App\Models\Vendor::orderBy('name')->get();
        return view('admin_panel.reporting.purchase_report', compact('products', 'vendors'));
    }

    public function fetchPurchaseReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $productId = $request->product_id;
        $vendorId = $request->vendor_id;

        $query = DB::table('purchases')
            ->join('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->join('vendors', 'purchases.vendor_id', '=', 'vendors.id') // join vendor table
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'purchases.purchase_date',
                'purchases.invoice_no',
                'vendors.name as vendor_name', // vendor name
                'products.item_code',
                'products.item_name',
                'purchase_items.qty',
                DB::raw('COALESCE(units.name, purchase_items.unit, "-") as unit'), // Fix null unit
                'purchase_items.price',
                'purchase_items.item_discount',
                'purchase_items.line_total',
                'purchases.subtotal',
                'purchases.discount',
                'purchases.extra_cost',
                'purchases.net_amount',
                'purchases.paid_amount',
                'purchases.due_amount'
            );

        if ($startDate && $endDate) {
            $query->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
        }
        if ($productId && $productId !== 'all') {
            $query->where('purchase_items.product_id', $productId);
        }
        if ($vendorId && $vendorId !== 'all') {
            $query->where('purchases.vendor_id', $vendorId);
        }

        $results = $query->orderBy('purchases.purchase_date', 'asc')->get();

        // Attach returns to each row
        $rows = $results->map(function ($row) {
            $returns = DB::table('purchase_return_items')
                ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                ->join('products', 'products.id', '=', 'purchase_return_items.product_id')
                ->where('purchase_returns.purchase_id', DB::table('purchases')->where('invoice_no', $row->invoice_no)->value('id'))
                ->where('purchase_return_items.product_id', DB::table('products')->where('item_code', $row->item_code)->value('id'))
                ->select('products.item_name', 'purchase_return_items.qty', 'purchase_return_items.line_total')
                ->get();

            $row->returns = $returns;
            return $row;
        });

        return response()->json([
            'data' => $rows
        ]);
    }

    public function sale_report()
    {
        return view('admin_panel.reporting.sale_report');
    }

    public function fetchsaleReport(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start_date;
            $end = $request->end_date;

            // Use Eloquent to handle relations and new table structure
            $query = \App\Models\Sale::with(['customer_relation', 'items.product', 'returns']);

            if ($start && $end) {
                $query->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
            }

            $sales = $query->orderBy('created_at', 'asc')->get();

            // Transform to match the structure expected by the frontend (CSV strings)
            $transformed = $sales->map(function ($sale) {
                // Construct comma-separated strings for legacy frontend support
                $productNames = $sale->items->map(function ($item) {
                    return $item->product ? $item->product->item_name : 'Unknown';
                })->implode(',');

                // Use SKU or Name as per preference, usually Name for reports
                $productCodes = $sale->items->map(function ($item) {
                    return $item->product ? $item->product->item_code : '-';
                })->implode(',');

                $qtys = $sale->items->pluck('qty')->implode(',');
                $prices = $sale->items->pluck('price')->implode(','); // Unit Price
                $totals = $sale->items->pluck('total')->implode(','); // Line Total

                return [
                    'id' => $sale->id,
                    'reference' => $sale->reference ?? '-',
                    'product' => $productNames,      // Names
                    'product_code' => $productCodes, // Codes
                    'brand' => '-',                  // Could extract from items if needed
                    'unit' => '-',                   // Could extract
                    'per_price' => $prices,
                    'per_discount' => 0,             
                    'qty' => $qtys,
                    'per_total' => $totals,
                    'total_net' => $sale->total_net,
                    'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
                    'customer_name' => $sale->customer_relation ? $sale->customer_relation->customer_name : 'Walk-in',
                    'returns' => $sale->returns->map(function($ret) {
                         // Robust return display handling both legacy strings and new relation items
                         $retItems = $ret->items;
                         if ($retItems && $retItems->count() > 0) {
                             $pNames = $retItems->map(fn($i) => $i->product->item_name ?? 'Unknown')->implode(', ');
                             $pQtys = $retItems->pluck('qty')->implode(', ');
                             $pTotal = $retItems->sum('line_total');
                         } else {
                             $pNames = $ret->product ?? '-';
                             $pQtys = $ret->qty ?? 0;
                             $pTotal = $ret->net_amount ?? 0;
                         }

                         return [
                            'product' => $pNames,
                            'qty' => $pQtys,
                            'per_total' => $pTotal
                         ];
                    })
                ];
            });

            return response()->json($transformed);
        }

        return view('admin_panel.reporting.sale_report');
    }

    public function customer_ledger_report()
    {
        $customers = DB::table('customers')->select('id', 'customer_name', 'zone')->get();
        $zones = \App\Models\Zone::orderBy('zone')->get();

        return view('admin_panel.reporting.customer_ledger_report', compact('customers', 'zones'));
    }

    public function fetch_customer_ledger(Request $request)
    {
        $customerId = $request->customer_id;
        $zoneId = $request->zone_id;
        $start = $request->start_date ?: '2000-01-01';
        $end = $request->end_date ?: date('Y-m-d');

        $balanceService = app(\App\Services\BalanceService::class);

        // If "all" or empty, fetch for ALL customers
        if (!$customerId || $customerId === 'all') {
            // Get all customers who have journal entries
            $customerIds = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->distinct()
                ->pluck('party_id')
                ->toArray();

            // Also include customers with opening balance
            $obCustomerIds = \App\Models\Customer::where('opening_balance', '>', 0)
                ->pluck('id')
                ->toArray();

            $allIds = array_unique(array_merge($customerIds, $obCustomerIds));

            // Apply zone filter if provided
            if ($zoneId) {
                // Filter allIds to only those customers who belong to the selected zone
                $validZoneIds = \App\Models\Customer::where('zone', $zoneId)->pluck('id')->toArray();
                $allIds = array_intersect($allIds, $validZoneIds);
            }

            $allTransactions = [];
            $totalOpening = 0;
            $totalClosing = 0;

            foreach ($allIds as $cid) {
                $ledgerData = $balanceService->getCustomerLedger($cid, $start, $end);
                $customerName = $ledgerData['customer']->customer_name ?? 'Unknown';
                $totalOpening += $ledgerData['opening_balance'];

                foreach ($ledgerData['transactions'] as $row) {
                    $desc = $row['description'] ?? '';

                    // Try to find payment account name for receipt entries
                    $accountName = '';
                    if ($row['credit'] > 0 && $row['source_type']) {
                        $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
                    }
                    if ($accountName) {
                        $desc .= ' [A/C: ' . $accountName . ']';
                    }

                    $ref = '-';
                    if (preg_match('/Invoice #(\S+)/', $desc, $matches)) {
                        $ref = $matches[1];
                    } elseif (preg_match('/Receipt #(\S+)/', $desc, $matches)) {
                        $ref = $matches[1];
                    }

                    $entryDate = $row['date'];
                    if ($entryDate instanceof \Carbon\Carbon) {
                        $formattedDate = $entryDate->format('d-M-Y');
                        $sortDate = $entryDate->format('Y-m-d');
                    } else {
                        $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
                        $sortDate = \Carbon\Carbon::parse($entryDate)->format('Y-m-d');
                    }

                    $allTransactions[] = [
                        'sort_date' => $sortDate,
                        'date' => $formattedDate,
                        'invoice' => $ref,
                        'description' => $desc,
                        'customer_name' => $customerName,
                        'debit' => $row['debit'] ?? 0,
                        'credit' => $row['credit'] ?? 0,
                        'balance' => $row['balance'] ?? 0,
                    ];
                }

                $totalClosing += $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'];
            }

            // Sort by date
            usort($allTransactions, function ($a, $b) {
                return strcmp($a['sort_date'], $b['sort_date']);
            });

            // Recalculate running balance across all
            $running = $totalOpening;
            foreach ($allTransactions as &$t) {
                $running += ($t['debit'] - $t['credit']);
                $t['balance'] = $running;
            }

            return response()->json([
                'customer' => (object)['customer_name' => 'All Customers'],
                'opening_balance' => $totalOpening,
                'closing_balance' => $totalClosing,
                'transactions' => $allTransactions,
                'report_period' => "$start to $end",
            ]);
        }

        // Single customer
        $customer = DB::table('customers')->where('id', $customerId)->first();
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 400);
        }

        $ledgerData = $balanceService->getCustomerLedger($customerId, $start, $end);

        $transactions = collect($ledgerData['transactions'])->map(function ($row) {
            $desc = $row['description'] ?? '';

            // Try to find payment account name for receipt entries
            $accountName = '';
            if ($row['credit'] > 0 && ($row['source_type'] ?? null)) {
                $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
            }
            if ($accountName) {
                $desc .= ' [A/C: ' . $accountName . ']';
            }

            $ref = '-';
            if (preg_match('/Invoice #(\S+)/', $desc, $matches)) {
                $ref = $matches[1];
            } elseif (preg_match('/Receipt #(\S+)/', $desc, $matches)) {
                $ref = $matches[1];
            }

            $entryDate = $row['date'];
            if ($entryDate instanceof \Carbon\Carbon) {
                $formattedDate = $entryDate->format('d-M-Y');
            } else {
                $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
            }

            return [
                'date' => $formattedDate,
                'invoice' => $ref,
                'description' => $desc,
                'debit' => $row['debit'] ?? 0,
                'credit' => $row['credit'] ?? 0,
                'balance' => $row['balance'] ?? 0,
            ];
        });

        return response()->json([
            'customer' => $customer,
            'opening_balance' => $ledgerData['opening_balance'],
            'closing_balance' => $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'],
            'transactions' => $transactions,
            'report_period' => "$start to $end",
        ]);
    }

    /**
     * Get the payment account name from voucher source
     */
    private function getPaymentAccountName($sourceType, $sourceId)
    {
        try {
            if ($sourceType === \App\Models\VoucherMaster::class && $sourceId) {
                // Look at VoucherDetail for the debit side (cash/bank account)
                $voucherDetail = \App\Models\VoucherDetail::where('voucher_master_id', $sourceId)
                    ->where('debit', '>', 0)
                    ->first();
                if ($voucherDetail && $voucherDetail->account_id) {
                    $account = \App\Models\Account::find($voucherDetail->account_id);
                    return $account ? $account->title : '';
                }
            } elseif ($sourceType === \App\Models\PaymentVoucher::class && $sourceId) {
                $pv = \App\Models\PaymentVoucher::find($sourceId);
                if ($pv && $pv->row_account_id) {
                    $account = \App\Models\Account::find($pv->row_account_id);
                    return $account ? $account->title : '';
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        return '';
    }

    public function vendor_ledger_report()
    {
        $vendors = DB::table('vendors')->select('id', 'name')->orderBy('name')->get();

        return view('admin_panel.reporting.vendor_ledger_report', compact('vendors'));
    }

    public function fetch_vendor_ledger(Request $request)
    {
        $vendorId = $request->vendor_id;
        $start = $request->start_date ?: '2000-01-01';
        $end = $request->end_date ?: date('Y-m-d');

        $balanceService = app(\App\Services\BalanceService::class);

        // If "all" or empty, fetch for ALL vendors
        if (!$vendorId || $vendorId === 'all') {
            $vendorIds = \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
                ->distinct()
                ->pluck('party_id')
                ->toArray();

            // Also include vendors with opening balance
            $obVendorIds = \App\Models\Vendor::where('opening_balance', '>', 0)
                ->pluck('id')
                ->toArray();

            $allIds = array_unique(array_merge($vendorIds, $obVendorIds));

            $allTransactions = [];
            $totalOpening = 0;
            $totalClosing = 0;

            foreach ($allIds as $vid) {
                $ledgerData = $balanceService->getVendorLedger($vid, $start, $end);
                $vendorName = $ledgerData['vendor']->name ?? 'Unknown';
                $totalOpening += $ledgerData['opening_balance'];

                foreach ($ledgerData['transactions'] as $row) {
                    $desc = $row['description'] ?? '';

                    $accountName = '';
                    if ($row['debit'] > 0 && ($row['source_type'] ?? null)) {
                        $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
                    }
                    if ($accountName) {
                        $desc .= ' [A/C: ' . $accountName . ']';
                    }

                    $ref = '-';
                    if (preg_match('/PUR-(\S+)/', $desc, $matches)) {
                        $ref = 'PUR-' . $matches[1];
                    } elseif (preg_match('/Payment #(\S+)/', $desc, $matches)) {
                        $ref = $matches[1];
                    } elseif (preg_match('/Purchase #(\S+)/', $desc, $matches)) {
                        $ref = $matches[1];
                    }

                    $entryDate = $row['date'];
                    if ($entryDate instanceof \Carbon\Carbon) {
                        $formattedDate = $entryDate->format('d-M-Y');
                        $sortDate = $entryDate->format('Y-m-d');
                    } else {
                        $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
                        $sortDate = \Carbon\Carbon::parse($entryDate)->format('Y-m-d');
                    }

                    $allTransactions[] = [
                        'sort_date' => $sortDate,
                        'date' => $formattedDate,
                        'invoice' => $ref,
                        'description' => $desc,
                        'vendor_name' => $vendorName,
                        'debit' => $row['debit'] ?? 0,
                        'credit' => $row['credit'] ?? 0,
                        'balance' => $row['balance'] ?? 0,
                    ];
                }

                $totalClosing += $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'];
            }

            // Sort by date
            usort($allTransactions, function ($a, $b) {
                return strcmp($a['sort_date'], $b['sort_date']);
            });

            // Recalculate running balance across all
            $running = $totalOpening;
            foreach ($allTransactions as &$t) {
                $running += ($t['credit'] - $t['debit']);
                $t['balance'] = $running;
            }

            return response()->json([
                'vendor' => (object)['name' => 'All Vendors'],
                'opening_balance' => $totalOpening,
                'closing_balance' => $totalClosing,
                'transactions' => $allTransactions,
                'report_period' => "$start to $end",
            ]);
        }

        // Single vendor
        $vendor = DB::table('vendors')->where('id', $vendorId)->first();
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 400);
        }

        $ledgerData = $balanceService->getVendorLedger($vendorId, $start, $end);

        $transactions = collect($ledgerData['transactions'])->map(function ($row) {
            $desc = $row['description'] ?? '';

            $accountName = '';
            if ($row['debit'] > 0 && ($row['source_type'] ?? null)) {
                $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
            }
            if ($accountName) {
                $desc .= ' [A/C: ' . $accountName . ']';
            }

            $ref = '-';
            if (preg_match('/PUR-(\S+)/', $desc, $matches)) {
                $ref = 'PUR-' . $matches[1];
            } elseif (preg_match('/Payment #(\S+)/', $desc, $matches)) {
                $ref = $matches[1];
            } elseif (preg_match('/Purchase #(\S+)/', $desc, $matches)) {
                $ref = $matches[1];
            }

            $entryDate = $row['date'];
            if ($entryDate instanceof \Carbon\Carbon) {
                $formattedDate = $entryDate->format('d-M-Y');
            } else {
                $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
            }

            return [
                'date' => $formattedDate,
                'invoice' => $ref,
                'description' => $desc,
                'debit' => $row['debit'] ?? 0,
                'credit' => $row['credit'] ?? 0,
                'balance' => $row['balance'] ?? 0,
            ];
        });

        return response()->json([
            'vendor' => $vendor,
            'opening_balance' => $ledgerData['opening_balance'],
            'closing_balance' => $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'],
            'transactions' => $transactions,
            'report_period' => "$start to $end",
        ]);
    }

    public function balance_sheet_report()
    {
        return view('admin_panel.reporting.balance_sheet');
    }

    public function fetch_balance_sheet(Request $request)
    {
        $date = $request->date ?: date('Y-m-d');
        // Let's get "As Of Date" balances. If a future date is given, it still acts up to that point.
        // We will include ending day inclusive so we add time OR use <=
        $dateEnd = $date . ' 23:59:59';

        $balanceService = app(\App\Services\BalanceService::class);

        // 1. Current Assets
        // Cash in Hand (head_id = 1)
        // Cash at Bank (Usually head_id = 1, we can split them if we can identify them, but we'll show all assets)
        $assetAccounts = DB::table('accounts')->whereIn('head_id', [1, 2])->get();
        $cashAccounts = [];
        $totalCashBank = 0;

        foreach ($assetAccounts as $acc) {
            // Need balance up to Date from Journal Entries
            $balance = DB::table('journal_entries')
                ->where('account_id', $acc->id)
                ->where('entry_date', '<=', $dateEnd)
                ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
                ->value('total') ?? 0;
            
            $computed_balance = $acc->opening_balance + $balance;
            if ($computed_balance != 0) {
                $cashAccounts[] = [
                    'name' => $acc->title,
                    'balance' => $computed_balance
                ];
                $totalCashBank += $computed_balance;
            }
        }

        // Account Receivables
        $customers = DB::table('customers')->get();
        $totalReceivables = 0;
        foreach ($customers as $c) {
            $bal = $balanceService->getCustomerBalanceBeforeDate($c->id, $dateEnd);
            $totalReceivables += $bal;
        }

        // Stock in Trade
        // Current Stock = Initial + Purchased - Sold
        $products = DB::table('products')->get();
        $totalInventory = 0;
        foreach ($products as $p) {
            // Initial
            $initial = (float) DB::table('stock_movements')
                ->where('product_id', $p->id)
                ->where('ref_type', 'INIT')
                ->where('created_at', '<=', $dateEnd)
                ->sum('qty');

            // Purchased
            $purchaseData = DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchase_items.product_id', $p->id)
                ->where('purchases.purchase_date', '<=', $dateEnd)
                ->select(DB::raw('COALESCE(SUM(purchase_items.qty),0) as total_qty'), DB::raw('COALESCE(SUM(purchase_items.line_total),0) as total_amount'))
                ->first();

            $purchased = (float) $purchaseData->total_qty;
            $purchaseAmount = (float) $purchaseData->total_amount;

            // Sold
            $saleStats = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $p->id)
                ->where('sales.created_at', '<=', $dateEnd)
                ->selectRaw('COALESCE(SUM(sale_items.qty),0) as total_qty')
                ->first();

            $sold = (float) $saleStats->total_qty;

            $balance = $initial + $purchased - $sold;

            // Average Cost
            $productPurchPrice = 0;
            if ($p->size_mode === 'by_size') {
                $m2PerPiece = (float) ($p->pieces_per_m2 ?? 0);
                $purchPerM2 = (float) ($p->purchase_price_per_m2 ?? 0);
                $productPurchPrice = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($p->purchase_price_per_piece ?? 0);
            }

            $initialAmount = $initial * $productPurchPrice;
            $totalQtyIn = $initial + $purchased;
            $avgCost = $totalQtyIn > 0 ? ($initialAmount + $purchaseAmount) / $totalQtyIn : $productPurchPrice;

            if ($balance > 0) {
                $totalInventory += ($balance * $avgCost);
            }
        }

        $currentAssetsTotal = $totalCashBank + $totalReceivables + $totalInventory;
        $fixedAssetsTotal = 0; // if you have fixed assets head, you can add here
        $totalAssets = $currentAssetsTotal + $fixedAssetsTotal;

        // 2. Liabilities
        $vendors = DB::table('vendors')->get();
        $totalPayables = 0;
        foreach ($vendors as $v) {
            $bal = $balanceService->getVendorBalanceBeforeDate($v->id, $dateEnd);
            $totalPayables += $bal; // Vendor Balance (Cr is positive from BalanceService view)
        }
        $currentLiabilitiesTotal = $totalPayables;

        // 3. Owner's Equity (Equity = Assets - Liabilities)
        $equityTotal = $totalAssets - $currentLiabilitiesTotal;
        $totalLiabilitiesAndEquity = $currentLiabilitiesTotal + $equityTotal;

        return response()->json([
            'date' => date('d-M-Y', strtotime($date)),
            'assets' => [
                'cash_bank' => $cashAccounts,
                'total_cash_bank' => $totalCashBank,
                'receivables' => $totalReceivables,
                'inventory' => $totalInventory,
                'current_total' => $currentAssetsTotal,
                'fixed_total' => $fixedAssetsTotal,
                'total' => $totalAssets
            ],
            'liabilities' => [
                'payables' => $totalPayables,
                'current_total' => $currentLiabilitiesTotal,
                'equity' => $equityTotal,
                'total' => $totalLiabilitiesAndEquity
            ]
        ]);
    }

    public function recovery_report()
    {
        return view('admin_panel.reporting.recovery_report');
    }

    public function fetch_recovery(Request $request)
    {
        $startDate = $request->start_date ?: '2000-01-01';
        $endDate = $request->end_date ?: date('Y-m-d');
        $dateEndParams = $endDate . ' 23:59:59';
        
        $balanceService = app(\App\Services\BalanceService::class);
        $customers = DB::table('customers')->orderBy('customer_name')->get();
        
        $rows = [];
        $totalOpening = 0;
        $totalSales = 0;
        $totalReceived = 0;
        $totalFinal = 0;

        foreach ($customers as $index => $c) {
            $opening = $balanceService->getCustomerBalanceBeforeDate($c->id, $startDate);
            
            $periodStats = DB::table('journal_entries')
                ->where('party_type', \App\Models\Customer::class)
                ->where('party_id', $c->id)
                ->whereBetween('entry_date', [$startDate, $dateEndParams])
                ->selectRaw('COALESCE(SUM(debit), 0) as debits, COALESCE(SUM(credit), 0) as credits')
                ->first();

            $sales = (float) $periodStats->debits;
            $received = (float) $periodStats->credits;
            
            $final = $opening + $sales - $received;

            if (abs($opening) > 0 || abs($sales) > 0 || abs($received) > 0 || abs($final) > 0) {
                $rows[] = [
                    'sr' => count($rows) + 1,
                    'party' => $c->customer_name,
                    'opening' => $opening,
                    'sales' => $sales,
                    'received' => $received,
                    'final' => $final
                ];

                $totalOpening += $opening;
                $totalSales += $sales;
                $totalReceived += $received;
                $totalFinal += $final;
            }
        }

        return response()->json([
            'date_range' => date('d-m-Y', strtotime($startDate)) . ' to ' . date('d-m-Y', strtotime($endDate)),
            'rows' => $rows,
            'totals' => [
                'opening' => $totalOpening,
                'sales' => $totalSales,
                'received' => $totalReceived,
                'final' => $totalFinal
            ]
        ]);
    }

    public function payable_report()
    {
        return view('admin_panel.reporting.payable_report');
    }

    public function fetch_payable(Request $request)
    {
        $startDate = $request->start_date ?: '2000-01-01';
        $endDate = $request->end_date ?: date('Y-m-d');
        $dateEndParams = $endDate . ' 23:59:59';
        
        $balanceService = app(\App\Services\BalanceService::class);
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $apId = $balanceService->getAccountsPayableId();
        
        $rows = [];
        $totalOpening = 0;
        $totalPurchases = 0;
        $totalPaid = 0;
        $totalFinal = 0;

        foreach ($vendors as $v) {
            // Opening: Balance before Start Date
            $opening = $balanceService->getVendorBalanceBeforeDate($v->id, $startDate);
            
            // Purchases in range (Table-based)
            $purchasesRaw = DB::table('purchases')
                ->where('vendor_id', $v->id)
                ->where('status_purchase', 'approved')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->sum('net_amount');

            // Returns in range (Table-based)
            $returnsRaw = DB::table('purchase_returns')
                ->where('vendor_id', $v->id)
                ->whereBetween('return_date', [$startDate, $endDate])
                ->sum('net_amount');
            
            // Net Purchases (Purchases - Returns)
            $purchases = (float) $purchasesRaw - (float) $returnsRaw;

            // Payments in range (Journal-based, only for Accounts Payable account)
            $paid = (float) \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
                ->where('party_id', $v->id)
                ->where('account_id', $apId)
                ->whereBetween('entry_date', [$startDate, $dateEndParams])
                ->sum('debit');
            
            $final = $opening + $purchases - $paid;

            if (abs($opening) > 0.01 || abs($purchases) > 0.01 || abs($paid) > 0.01 || abs($final) > 0.01) {
                $rows[] = [
                    'sr' => count($rows) + 1,
                    'party' => $v->name,
                    'opening' => $opening,
                    'purchases' => $purchases,
                    'paid' => $paid,
                    'final' => $final
                ];

                $totalOpening += $opening;
                $totalPurchases += $purchases;
                $totalPaid += $paid;
                $totalFinal += $final;
            }
        }

        return response()->json([
            'date_range' => date('d-m-Y', strtotime($startDate)) . ' to ' . date('d-m-Y', strtotime($endDate)),
            'rows' => $rows,
            'totals' => [
                'opening' => $totalOpening,
                'purchases' => $totalPurchases,
                'paid' => $totalPaid,
                'final' => $totalFinal
            ]
        ]);
    }

    public function parties_balance_report()
    {
        return view('admin_panel.reporting.parties_balance_report');
    }

    public function fetch_parties_balance(Request $request)
    {
        $reportType = $request->report_type ?: 'BOTH'; // RECEIVABLE, PAYABLE, BOTH
        $showZero = $request->show_zero == 'true';
        $searchParty = $request->party_name;
        $searchMobile = $request->mobile;
        
        $balanceService = app(\App\Services\BalanceService::class);
        
        $parties = [];
        
        // Fetch Customers
        if ($reportType == 'BOTH' || $reportType == 'RECEIVABLE') {
            $customers = DB::table('customers')->get();
            foreach ($customers as $c) {
                if ($searchParty && stripos($c->customer_name, $searchParty) === false) continue;
                if ($searchMobile && stripos($c->mobile, $searchMobile) === false) continue;
                
                $balance = $balanceService->getCustomerBalance($c->id);
                $parties[] = [
                    'code' => sprintf("C%04d", $c->id),
                    'title' => $c->customer_name,
                    'mobile' => $c->mobile,
                    'balance' => $balance,
                    'type' => 'customer'
                ];
            }
        }

        // Fetch Vendors
        if ($reportType == 'BOTH' || $reportType == 'PAYABLE') {
            $vendors = DB::table('vendors')->get();
            foreach ($vendors as $v) {
                if ($searchParty && stripos($v->name, $searchParty) === false) continue;
                if ($searchMobile && stripos($v->phone, $searchMobile) === false) continue;
                
                $balance = $balanceService->getVendorBalance($v->id);
                // For vendors, positive balance means we owe them (Payable)
                // Let's invert it for standard representation: positive = receivable, negative = payable
                // Actually vendor $balance > 0 means Payable, < 0 means Receivable.
                $parties[] = [
                    'code' => sprintf("V%04d", $v->id),
                    'title' => $v->name,
                    'mobile' => $v->phone,
                    'balance' => -$balance, // so negative is Payable, positive is Receivable
                    'type' => 'vendor'
                ];
            }
        }

        $rows = [];
        $totalReceivable = 0;
        $totalPayable = 0;
        $sr = 1;

        foreach ($parties as $p) {
            $bal = $p['balance'];
            
            $receivable = 0;
            $payable = 0;

            if ($bal > 0) {
                $receivable = $bal;
            } elseif ($bal < 0) {
                $payable = abs($bal);
            }

            // Apply strict type filters if there's any overflow
            if ($reportType == 'RECEIVABLE' && $receivable == 0 && !$showZero) continue;
            if ($reportType == 'PAYABLE' && $payable == 0 && !$showZero) continue;
            
            if (!$showZero && $receivable == 0 && $payable == 0) continue;

            $rows[] = [
                'sr' => $sr++,
                'code' => $p['code'],
                'title' => $p['title'],
                'mobile' => $p['mobile'] ?? '-',
                'receivable' => $receivable,
                'payable' => $payable,
                'notes' => ''
            ];

            $totalReceivable += $receivable;
            $totalPayable += $payable;
        }

        return response()->json([
            'rows' => $rows,
            'totals' => [
                'receivable' => $totalReceivable,
                'payable' => $totalPayable
            ]
        ]);
    }
    public function aging_report()
    {
        return view('admin_panel.reporting.aging_report');
    }

    public function fetch_aging(Request $request)
    {
        $type     = $request->type ?: 'receivable'; // receivable | payable
        $asOfDate = $request->as_of_date ?: date('Y-m-d');
        $today    = \Carbon\Carbon::parse($asOfDate);

        $balanceService = app(\App\Services\BalanceService::class);

        $rows = [];
        $grandTotal     = 0;
        $grandCurrent   = 0;
        $grand15        = 0;
        $grand30        = 0;
        $grand45        = 0;
        $grand60        = 0;
        $grand75        = 0;
        $grand90plus    = 0;

        if ($type === 'receivable') {
            // Customer Aging: Each Sale invoice outstanding
            $customers = DB::table('customers')->get();

            foreach ($customers as $c) {
                $totalBalance = $balanceService->getCustomerBalance($c->id);
                if ($totalBalance <= 0) continue; // skip if no balance

                // Get all sale invoices for this customer
                $invoices = DB::table('sales')
                    ->where('customer_id', $c->id)
                    ->where('created_at', '<=', $asOfDate . ' 23:59:59')
                    ->select('id', 'invoice_no', 'total_net', 'created_at')
                    ->orderBy('created_at')
                    ->get();

                // Distribute total balance across invoices by age (FIFO)
                $remaining = $totalBalance;

                $bucket_current = 0;
                $bucket_15 = 0;
                $bucket_30 = 0;
                $bucket_45 = 0;
                $bucket_60 = 0;
                $bucket_75 = 0;
                $bucket_90plus = 0;

                foreach ($invoices as $inv) {
                    if ($remaining <= 0) break;
                    $invAmt  = min((float) $inv->total_net, $remaining);
                    $days    = (int) \Carbon\Carbon::parse($inv->created_at)->diffInDays($today);
                    $remaining -= $invAmt;

                    if ($days == 0)      $bucket_current += $invAmt;
                    elseif ($days <= 15) $bucket_15      += $invAmt;
                    elseif ($days <= 30) $bucket_30      += $invAmt;
                    elseif ($days <= 45) $bucket_45      += $invAmt;
                    elseif ($days <= 60) $bucket_60      += $invAmt;
                    elseif ($days <= 75) $bucket_75      += $invAmt;
                    else                 $bucket_90plus  += $invAmt;
                }

                // Any remaining (from opening balance) goes to 90+
                if ($remaining > 0) $bucket_90plus += $remaining;

                $rows[] = [
                    'name'      => $c->customer_name,
                    'mobile'    => $c->mobile ?? '',
                    'total'     => $totalBalance,
                    'current'   => $bucket_current,
                    '15d'       => $bucket_15,
                    '30d'       => $bucket_30,
                    '45d'       => $bucket_45,
                    '60d'       => $bucket_60,
                    '75d'       => $bucket_75,
                    '90plus'    => $bucket_90plus,
                ];

                $grandTotal   += $totalBalance;
                $grandCurrent += $bucket_current;
                $grand15      += $bucket_15;
                $grand30      += $bucket_30;
                $grand45      += $bucket_45;
                $grand60      += $bucket_60;
                $grand75      += $bucket_75;
                $grand90plus  += $bucket_90plus;
            }
        } else {
            // Vendor Aging (Payable)
            $vendors = DB::table('vendors')->get();

            foreach ($vendors as $v) {
                $totalBalance = $balanceService->getVendorBalance($v->id);
                if ($totalBalance <= 0) continue; // we owe them

                $invoices = DB::table('purchases')
                    ->where('vendor_id', $v->id)
                    ->where('purchase_date', '<=', $asOfDate)
                    ->where('status_purchase', 'approved')
                    ->select('id', 'invoice_no', 'net_amount', 'purchase_date')
                    ->orderBy('purchase_date')
                    ->get();

                $remaining = $totalBalance;

                $bucket_current = 0;
                $bucket_15 = 0;
                $bucket_30 = 0;
                $bucket_45 = 0;
                $bucket_60 = 0;
                $bucket_75 = 0;
                $bucket_90plus = 0;

                foreach ($invoices as $inv) {
                    if ($remaining <= 0) break;
                    $invAmt  = min((float) $inv->net_amount, $remaining);
                    $days    = (int) \Carbon\Carbon::parse($inv->purchase_date)->diffInDays($today);
                    $remaining -= $invAmt;

                    if ($days == 0)      $bucket_current += $invAmt;
                    elseif ($days <= 15) $bucket_15      += $invAmt;
                    elseif ($days <= 30) $bucket_30      += $invAmt;
                    elseif ($days <= 45) $bucket_45      += $invAmt;
                    elseif ($days <= 60) $bucket_60      += $invAmt;
                    elseif ($days <= 75) $bucket_75      += $invAmt;
                    else                 $bucket_90plus  += $invAmt;
                }

                if ($remaining > 0) $bucket_90plus += $remaining;

                $rows[] = [
                    'name'      => $v->name,
                    'mobile'    => $v->phone ?? '',
                    'total'     => $totalBalance,
                    'current'   => $bucket_current,
                    '15d'       => $bucket_15,
                    '30d'       => $bucket_30,
                    '45d'       => $bucket_45,
                    '60d'       => $bucket_60,
                    '75d'       => $bucket_75,
                    '90plus'    => $bucket_90plus,
                ];

                $grandTotal   += $totalBalance;
                $grandCurrent += $bucket_current;
                $grand15      += $bucket_15;
                $grand30      += $bucket_30;
                $grand45      += $bucket_45;
                $grand60      += $bucket_60;
                $grand75      += $bucket_75;
                $grand90plus  += $bucket_90plus;
            }
        }

        return response()->json([
            'as_of_date' => $today->format('d-M-Y'),
            'rows'       => $rows,
            'totals'     => [
                'total'     => $grandTotal,
                'current'   => $grandCurrent,
                '15d'       => $grand15,
                '30d'       => $grand30,
                '45d'       => $grand45,
                '60d'       => $grand60,
                '75d'       => $grand75,
                '90plus'    => $grand90plus,
            ]
        ]);
    }
    public function executive_report()
    {
        return view('admin_panel.reporting.executive_report');
    }

    public function fetch_executive_report(Request $request)
    {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        // Sales
        $salesToday = DB::table('sales')->whereDate('created_at', $today)->sum('total_net');
        $salesMonth = DB::table('sales')->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])->sum('total_net');

        // Purchases
        $purchasesToday = DB::table('purchases')->whereDate('purchase_date', $today)->sum('net_amount');
        $purchasesMonth = DB::table('purchases')->whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->sum('net_amount');

        // Expenses
        $expensesTodayV1 = DB::table('expense_vouchers')->where('entry_date', $today)->sum('total_amount');
        $expensesTodayV2 = DB::table('voucher_masters')->where('voucher_type', 'expense')->where('date', $today)->sum('total_amount');
        $expensesToday = $expensesTodayV1 + $expensesTodayV2;

        $expensesMonthV1 = DB::table('expense_vouchers')->whereBetween('entry_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $expensesMonthV2 = DB::table('voucher_masters')->where('voucher_type', 'expense')->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $expensesMonth = $expensesMonthV1 + $expensesMonthV2;

        // Cash & Bank Balances
        $cashAccounts = DB::table('accounts')
            ->join('account_heads', 'accounts.head_id', '=', 'account_heads.id')
            ->where('account_heads.name', 'like', '%Cash%')
            ->select('accounts.title', 'accounts.current_balance')
            ->get();

        $bankAccounts = DB::table('accounts')
            ->join('account_heads', 'accounts.head_id', '=', 'account_heads.id')
            ->where('account_heads.name', 'like', '%Bank%')
            ->select('accounts.title', 'accounts.current_balance')
            ->get();

        $balanceService = app(\App\Services\BalanceService::class);
        
        // Receivables (Customers)
        $customers = DB::table('customers')->get();
        $totalReceivables = 0;
        foreach ($customers as $c) {
            $totalReceivables += $balanceService->getCustomerBalance($c->id);
        }

        // Payables (Vendors)
        $vendors = DB::table('vendors')->get();
        $totalPayables = 0;
        foreach ($vendors as $v) {
            $totalPayables += $balanceService->getVendorBalance($v->id);
        }

        // Top 10 Customers by Profit
        $customerProfits = [];
        foreach ($customers as $c) {
            $saleStats = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.customer_id', $c->id)
                ->where('sales.sale_status', 'posted')
                ->selectRaw('
                    SUM(sale_items.total) as revenue, 
                    SUM(sale_items.total_pieces * products.purchase_price_per_piece) as cogs
                ')
                ->first();

            $revenue = (float) $saleStats->revenue;
            $cogs = (float) $saleStats->cogs;
            $profit = $revenue - $cogs;

            if ($revenue > 0) {
                $customerProfits[] = [
                    'id' => $c->id,
                    'name' => $c->customer_name,
                    'profit' => $profit,
                    'revenue' => $revenue,
                    'balance' => $balanceService->getCustomerBalance($c->id)
                ];
            }
        }

        // Sort by profit descending
        usort($customerProfits, fn($a, $b) => $b['profit'] <=> $a['profit']);
        $topCustomers = array_slice($customerProfits, 0, 10);

        return response()->json([
            'sales' => [
                'today' => $salesToday,
                'month' => $salesMonth,
            ],
            'purchases' => [
                'today' => $purchasesToday,
                'month' => $purchasesMonth,
            ],
            'expenses' => [
                'today' => $expensesToday,
                'month' => $expensesMonth,
            ],
            'accounts' => [
                'cash' => $cashAccounts,
                'bank' => $bankAccounts,
            ],
            'receivables' => $totalReceivables,
            'payables' => $totalPayables,
            'top_customers' => $topCustomers,
        ]);
    }
}
