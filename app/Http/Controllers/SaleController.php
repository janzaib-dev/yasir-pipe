<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['customer_relation', 'items.product', 'items.package', 'returns'])
            ->whereIn('sale_status', ['draft', 'booked', 'posted', 'returned'])
            ->latest()
            ->get();

        return view('admin_panel.sale.index', compact('sales'));
    }

    public function addsale()
    {
        $customer = Customer::all();
        $warehouse = Warehouse::all();
        $nextInvoiceNumber = Sale::generateInvoiceNo();

        // Filter accounts (Cash/Bank) for Payment Voucher
        $accounts = \App\Models\Account::whereIn('head_id', [1, 2])->get();

        return view('admin_panel.sale.add_sale222', compact('warehouse', 'customer', 'nextInvoiceNumber', 'accounts'));
    }

    public function searchpname(Request $request)
    {
        $q = $request->get('q');
        $warehouseId = $request->get('warehouse_id', 1);

        $products = Product::with(['brand'])
            ->leftJoin('warehouse_stocks', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'warehouse_stocks.product_id')
                    ->where('warehouse_stocks.warehouse_id', $warehouseId);
            })
            ->where(function ($query) use ($q) {
                $query->where('products.item_name', 'like', "%{$q}%")
                    ->orWhere('products.item_code', 'like', "%{$q}%")
                    ->orWhere('products.barcode_path', 'like', "%{$q}%");
            })
            ->select(
                'products.*',
                'warehouse_stocks.total_pieces as wh_stock',
                'warehouse_stocks.quantity as wh_box_qty'
            )
            ->limit(50)
            ->get()
            ->map(function ($product) {
                return $product;
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        return $this->processSale($request, new Sale);
    }

    public function edit(Sale $sale)
    {
        //
    }

    public function convertFromBooking($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $customers = Customer::all();
        $products = explode(',', $booking->product);
        $codes = explode(',', $booking->product_code);
        $brands = explode(',', $booking->brand);
        $units = explode(',', $booking->unit);
        $prices = explode(',', $booking->per_price);
        $discounts = explode(',', $booking->per_discount);
        $qtys = explode(',', $booking->qty);
        $totals = explode(',', $booking->per_total);
        $colors_json = json_decode($booking->color, true);

        $items = [];
        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name' => $product->item_name ?? $p,
                'item_code' => $product->item_code ?? ($codes[$index] ?? ''),
                'uom' => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit' => $product->unit_id ?? ($units[$index] ?? ''),
                'price' => floatval($prices[$index] ?? 0),
                'discount' => floatval($discounts[$index] ?? 0),
                'qty' => intval($qtys[$index] ?? 1),
                'total' => floatval($totals[$index] ?? 0),
                'color' => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer' => $customers,
            'booking' => $booking,
            'bookingItems' => $items,
        ]);
    }

    public function saleretun($id)
    {
        $sale = Sale::with(['items.product.unit', 'items.product.brand', 'customer_relation'])->findOrFail($id);
        $customers = Customer::all();
        $items = $this->_getSaleItems($sale);

        // Get Cash/Bank accounts for payment voucher
        $accounts = \App\Models\Account::whereIn('head_id', [1, 2])->orderBy('title')->get();

        // Calculate return deadline from database settings
        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);
        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        // Get already returned quantities for this sale
        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed'])
            ->get();

        // Calculate max returnable for each item
        foreach ($items as &$item) {
            $returned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = explode(',', $return->product_code);
                $returnQtys = explode(',', $return->qty);

                foreach ($returnProductIds as $idx => $code) {
                    if (trim($code) === $item['item_code']) {
                        $returned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $item['already_returned'] = $returned;
            $item['max_returnable'] = max(0, $item['qty'] - $returned);
        }

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'accounts' => $accounts,
            'returnDeadline' => $returnDeadline,
            'isWithinDeadline' => $isWithinDeadline,
            'returnDeadlineDays' => $returnDeadlineDays,
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        // Validation
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'payment_account_id' => 'required|array|min:1',
            'payment_amount' => 'required|array|min:1',
            'quality_status' => 'nullable|in:good,damaged,defective,pending_inspection',
        ]);

        // Load the sale
        $sale = Sale::findOrFail($request->sale_id);

        // --- VALIDATION 1: Return Deadline Policy ---
        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);

        // Check if returns are disabled (0 days = no returns allowed)
        if ($returnDeadlineDays == 0) {
            return back()->with('error', 'Returns are currently disabled by store policy.');
        }

        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        // Check if return is past deadline
        if (! $isWithinDeadline) {
            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('Super Admin');
            $canApprovePastDeadline = $user->can_approve_past_deadline_returns ?? false;

            // Only Super Admin or users with special permission can approve past deadline returns
            if (! $isSuperAdmin && ! $canApprovePastDeadline) {
                $daysLate = now()->diffInDays($returnDeadline);

                return back()->with('error', "Return period expired! This sale is {$daysLate} days past the {$returnDeadlineDays}-day return deadline (Sale Date: {$sale->created_at->format('d-M-Y')}). Only Super Admin can approve past deadline returns.");
            }

            // Log that this is a past-deadline return approved by authorized user
            \Log::info("Past deadline return approved by {$user->name} (ID: {$user->id}) for Sale #{$sale->id}");
        }

        // --- VALIDATION 2: Partial Return - Prevent returning more than sold ---
        $product_ids = $request->product_id ?? [];
        $quantities = $request->qty ?? [];

        // Get already returned quantities
        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed', 'pending']) // Include pending to prevent duplicate submissions
            ->get();

        foreach ($product_ids as $index => $product_id) {
            $returnQty = (float) ($quantities[$index] ?? 0);

            if ($returnQty <= 0) {
                continue;
            }

            // Find original sale item
            $saleItem = $sale->items->where('product_id', $product_id)->first();

            if (! $saleItem) {
                return back()->with('error', "Product ID {$product_id} was not found in the original sale.");
            }

            // Calculate already returned quantity for this product
            $previouslyReturned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = $request->product_id;
                $returnQtys = explode(',', $return->qty);

                // Match by product_id in the combined string
                $productCodes = explode(',', $return->product_code);
                foreach ($productCodes as $idx => $code) {
                    $product = \App\Models\Product::where('item_code', trim($code))->first();
                    if ($product && $product->id == $product_id) {
                        $previouslyReturned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $maxReturnable = $saleItem->total_pieces - $previouslyReturned;

            if ($returnQty > $maxReturnable) {
                $productName = $saleItem->product_name ?? "Product #{$product_id}";

                return back()->with('error', "Cannot return {$returnQty} pieces of '{$productName}'. Maximum returnable: {$maxReturnable} pieces (Sold: {$saleItem->total_pieces}, Already Returned: {$previouslyReturned}).");
            }
        }

        DB::beginTransaction();

        try {
            $warehouseId = (int) ($request->input('warehouse_id', 1));

            $product_names = $request->product ?? [];
            $product_codes = $request->item_code ?? [];
            $brands = $request->uom ?? [];
            $units = $request->unit ?? [];
            $prices = $request->price ?? [];
            $discounts = $request->item_disc ?? [];
            $totals = $request->total ?? [];
            $colors = $request->color ?? [];

            $combined_products = $combined_codes = $combined_brands = $combined_units = [];
            $combined_prices = $combined_discounts = $combined_qtys = $combined_totals = $combined_colors = [];

            $jsonItems = [];
            $total_items = 0;

            // Process each returned item logic
            foreach ($product_ids as $index => $product_id) {
                $qty = max(0.0, (float) ($quantities[$index] ?? 0));
                $price = max(0.0, (float) ($prices[$index] ?? 0));

                if (! $product_id || $qty <= 0) {
                    continue;
                }

                // Add to JSON structure for later processing
                $jsonItems[] = [
                    'product_id' => $product_id,
                    'qty' => $qty,
                    'price' => $price,
                ];

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $price;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $qty;
                $combined_totals[] = $totals[$index] ?? 0;

                $decodedColor = $colors[$index] ?? [];
                $combined_colors[] = is_array($decodedColor) ? json_encode($decodedColor) : json_encode((array) json_decode($decodedColor, true));

                // NOTE: Stock updates are now deferred to the approval step
                $total_items += $qty;
            }

            // Prepare Payment Details
            $paymentAccountIds = $request->payment_account_id ?? [];
            $paymentAmounts = $request->payment_amount ?? [];
            $jsonPayments = [];

            foreach ($paymentAccountIds as $idx => $id) {
                if (($paymentAmounts[$idx] ?? 0) > 0) {
                    $jsonPayments[] = ['account_id' => $id, 'amount' => $paymentAmounts[$idx]];
                }
            }

            // Create Sale Return Record
            $saleReturn = new SaleReturn;
            $saleReturn->sale_id = $request->sale_id;
            $saleReturn->customer = $request->customer;
            $saleReturn->reference = $request->reference;
            $saleReturn->product = implode(',', $combined_products);
            $saleReturn->product_code = implode(',', $combined_codes);
            $saleReturn->brand = implode(',', $combined_brands);
            $saleReturn->unit = implode(',', $combined_units);
            $saleReturn->per_price = implode(',', $combined_prices);
            $saleReturn->per_discount = implode(',', $combined_discounts);
            $saleReturn->qty = implode(',', $combined_qtys);
            $saleReturn->per_total = implode(',', $combined_totals);
            $saleReturn->color = json_encode($combined_colors);
            $saleReturn->total_amount_Words = $request->total_amount_Words;
            $saleReturn->total_bill_amount = $request->total_subtotal;
            $saleReturn->total_extradiscount = $request->total_extra_cost;
            $saleReturn->total_net = $request->total_net;
            $saleReturn->cash = $request->cash;
            $saleReturn->card = $request->card;
            $saleReturn->change = $request->change;
            $saleReturn->total_items = $total_items;
            $saleReturn->return_note = $request->return_note;

            // Store comprehensive data for later processing
            $saleReturn->refund_details = json_encode([
                'items' => $jsonItems,
                'payments' => $jsonPayments,
                'warehouse_id' => $warehouseId,
                'customer_id' => $request->customer,
                'total_net' => $request->total_net,
            ]);

            // --- WORKFLOW STATUS FIELDS ---
            $autoApproveThreshold = \App\Models\SystemSetting::get('return_auto_approve_threshold', 0);
            $returnAmount = (float) $request->total_net;
            $requireApproval = \App\Models\SystemSetting::get('return_require_approval', true);

            if (($autoApproveThreshold > 0 && $returnAmount <= $autoApproveThreshold) || ! $requireApproval) {
                $saleReturn->return_status = 'approved';
                // Dates set in _processApproval
            } else {
                $saleReturn->return_status = 'pending';
            }

            // Quality Status
            $saleReturn->quality_status = $request->quality_status ?? 'pending_inspection';
            if ($request->quality_status && in_array($request->quality_status, ['good', 'damaged', 'defective'])) {
                $saleReturn->inspected_by = auth()->id();
            }

            // Return Deadline
            $saleReturn->return_deadline = $returnDeadline;
            $saleReturn->is_within_deadline = $isWithinDeadline;

            $saleReturn->save();

            // If Approved, process transactions immediately
            if ($saleReturn->return_status === 'approved') {
                $this->_processApproval($saleReturn);
            }

            DB::commit();

            // Create notification for super admins
            try {
                \App\Models\SystemNotification::createSaleReturnNotification($saleReturn, $sale);
            } catch (\Exception $e) {
                \Log::error('Notification creation failed: '.$e->getMessage());
                // Don't fail the return process if notification fails
            }

            return redirect()->route('sale.index')->with('success', 'Sale return processed successfully with journal entries and payment voucher.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sale Return Error: '.$e->getMessage());

            return back()->with('error', 'Sale return failed: '.$e->getMessage());
        }
    }

    public function salereturnview()
    {
        $SaleReturns = SaleReturn::with(['sale.customer_relation', 'customer_relation'])->orderBy('created_at', 'desc')->get();

        // Calculate stats
        $stats = [
            'total' => $SaleReturns->count(),
            'pending' => $SaleReturns->where('return_status', 'pending')->count(),
            'approved' => $SaleReturns->where('return_status', 'approved')->count(),
            'rejected' => $SaleReturns->where('return_status', 'rejected')->count(),
            'completed' => $SaleReturns->where('return_status', 'completed')->count(),
        ];

        return view('admin_panel.sale.return.index', compact('SaleReturns', 'stats'));
    }

    /**
     * Approve a sale return
     */
    public function approveReturn($id)
    {
        try {
            $return = SaleReturn::findOrFail($id);

            // Check if already processed
            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            DB::beginTransaction();

            // If we have refund_details, it means we used the new workflow where
            // actions were deferred until approval.
            if (! empty($return->refund_details)) {
                $this->_processApproval($return);
            } else {
                // Legacy support: If no refund_details, it means stock/accounting
                // were likely done at creation time (old behavior), so just update status.
                $return->return_status = 'approved';
                $return->approved_by = auth()->id();
                $return->approved_at = now();
                $return->save();
            }

            DB::commit();

            return back()->with('success', 'Return approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Return approval failed: '.$e->getMessage());

            return back()->with('error', 'Failed to approve return: '.$e->getMessage());
        }
    }

    /**
     * Process the physical and financial transactions for a return
     */
    private function _processApproval($saleReturn)
    {
        $data = is_string($saleReturn->refund_details) ? json_decode($saleReturn->refund_details, true) : $saleReturn->refund_details;

        if (! $data) {
            throw new \Exception('Invalid return data for processing');
        }

        $warehouseId = $data['warehouse_id'] ?? 1; // Default to 1 if missing
        $items = $data['items'] ?? [];
        $payments = $data['payments'] ?? [];
        $srMovements = [];

        // 1. Update Stock & Original Sale Items
        $sale = Sale::find($saleReturn->sale_id);

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $qty = (float) $item['qty'];

            if ($qty <= 0) {
                continue;
            }

            // Update Warehouse Stock
            $stock = \App\Models\WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if ($stock) {
                $stock->total_pieces += $qty;
                $prod = Product::find($productId);
                $ppb = $prod->pieces_per_box > 0 ? $prod->pieces_per_box : 1;
                $stock->quantity += ($qty / $ppb);
                $stock->save();
            }

            // Prepare Stock Movement
            $srMovements[] = [
                'product_id' => $productId,
                'type' => 'in',
                'qty' => $qty,
                'ref_type' => 'SR',
                'ref_id' => $saleReturn->id,
                'note' => 'Sale return approved',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Update Sales Item (Decrement sold quantity and revenue)
            if ($sale && $sale->items) {
                $saleItem = $sale->items->where('product_id', $productId)->first();
                if ($saleItem) {
                    $returnPrice = (float) ($saleItem->price ?? 0);
                    // Calculate discount per piece to ensure net total is correctly reduced
                    $discountPerPiece = $saleItem->total_pieces > 0 ? ($saleItem->discount_amount / $saleItem->total_pieces) : 0;
                    
                    $saleItem->total_pieces = max(0, $saleItem->total_pieces - $qty);
                    $prod = Product::find($productId);
                    $ppb = $prod->pieces_per_box > 0 ? $prod->pieces_per_box : 1;
                    $saleItem->qty = $saleItem->total_pieces / $ppb;
                    $saleItem->loose_pieces = $saleItem->total_pieces % $ppb;
                    
                    // Recalculate item total after return
                    $newGross = $saleItem->total_pieces * $saleItem->price;
                    $newDiscount = $saleItem->total_pieces * $discountPerPiece;
                    $saleItem->discount_amount = $newDiscount;
                    $saleItem->total = max(0, $newGross - $newDiscount);
                    
                    $saleItem->save();
                }
            }
        }

        // 2. Update Sale Header Totals
        if ($sale) {
            $allSaleItems = \App\Models\SaleItem::where('sale_id', $sale->id)->get();
            $sale->total_bill_amount = $allSaleItems->sum('total');
            // Recalculate Net (Accounting for extra discount if any)
            $sale->total_net = max(0, $sale->total_bill_amount - ($sale->total_extradiscount ?? 0));
            $sale->total_items = $allSaleItems->sum('total_pieces');
            $sale->save();
        }

        // Inert Stock Movements
        if (! empty($srMovements)) {
            DB::table('stock_movements')->insert($srMovements);
        }

        // 2. Journal Entries
        $customer = Customer::find($data['customer_id'] ?? $saleReturn->customer);
        $returnAmount = (float) $saleReturn->total_net;
        $date = now()->format('Y-m-d');
        $journalService = app(\App\Services\JournalEntryService::class);
        $balanceService = app(\App\Services\BalanceService::class);

        // Accounts
        $arAccountId = $balanceService->getAccountsReceivableId();
        $salesAccountId = $balanceService->getSalesRevenueId();

        if ($returnAmount > 0) {
            // Debit Sales Return (or Sales Revenue)
            $journalService->recordEntry(
                $saleReturn,
                $salesAccountId,
                $returnAmount,
                0,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date
            );

            // Credit Customer (AR)
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                0,
                $returnAmount,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date,
                $customer
            );
        }

        // 3. Process Payments (Refunds)
        $totalPaid = 0;
        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            $accountId = $payment['account_id'];

            if ($amount <= 0 || ! $accountId) {
                continue;
            }

            $totalPaid += $amount;

            // Credit Cash/Bank (Money Out)
            $journalService->recordEntry(
                $saleReturn,
                $accountId,
                0,
                $amount,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date
            );
        }

        // Debit Customer (AR) for the refund amount (since we paid them back)
        // Logic: Return credited AR (balance down). Refund debits AR (balance up/neutralized).
        // Net result: Sales reversed, Cash paid out. Customer balance neutral.
        if ($totalPaid > 0) {
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                $totalPaid,
                0,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date,
                $customer
            );

            // Create Payment Voucher Record
            \App\Models\CustomerPayment::create([
                'customer_id' => $customer->id,
                'admin_or_user_id' => auth()->id(),
                'voucher_no' => 'SR-'.$saleReturn->id,
                'payment_date' => $date,
                'payment_method' => 'Cash',
                'amount' => $totalPaid,
                'note' => "Refund for Sale Return #{$saleReturn->id}",
                'type' => 'refund',
            ]);

            // Auto-Generate Payment Voucher (PV) for Refund
            try {
                $pvid = \App\Models\PaymentVoucher::generateInvoiceNo();
                // Extract accounts and amounts
                $pvAccounts = [];
                $pvAmounts = [];
                foreach ($payments as $p) {
                    if (($p['amount'] ?? 0) > 0) {
                        $pvAccounts[] = $p['account_id'];
                        $pvAmounts[] = $p['amount'];
                    }
                }

                \App\Models\PaymentVoucher::create([
                    'pvid' => $pvid,
                    'party_id' => $customer->id,
                    'type' => 'customer',
                    'total_amount' => $totalPaid,
                    'payment_date' => $date,
                    'row_account_id' => json_encode($pvAccounts),
                    'row_account_head' => json_encode([]),
                    'row_amount' => json_encode($pvAmounts),
                    'remarks' => "Refund for Sale Return #{$saleReturn->id}",
                ]);
            } catch (\Exception $e) {
                \Log::error('Refund PV Creation Failed: '.$e->getMessage());
            }
        }

        // 4. Update Status
        $saleReturn->return_status = 'approved';
        $saleReturn->approved_by = auth()->id();
        $saleReturn->approved_at = now();
        $saleReturn->save();
    }

    /**
     * Reject a sale return
     */
    public function rejectReturn(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $return = SaleReturn::findOrFail($id);

            // Check if already processed
            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            // Update return status
            $return->return_status = 'rejected';
            $return->approved_by = auth()->id();
            $return->approved_at = now();
            $return->rejection_reason = $request->rejection_reason;
            $return->save();

            return back()->with('success', 'Return rejected successfully!');

        } catch (\Exception $e) {
            \Log::error('Return rejection failed: '.$e->getMessage());

            return back()->with('error', 'Failed to reject return: '.$e->getMessage());
        }
    }

    /**
     * Show detailed sale return information
     */
    public function saleReturnDetail($id)
    {
        $saleReturn = SaleReturn::with('customer_relation')->findOrFail($id);
        $sale = Sale::with(['customer_relation', 'items.product'])->findOrFail($saleReturn->sale_id);

        // Parse return items
        $returnItems = [];
        $productNames = explode(',', $saleReturn->product);
        $productCodes = explode(',', $saleReturn->product_code);
        $brands = explode(',', $saleReturn->brand);
        $units = explode(',', $saleReturn->unit);
        $prices = explode(',', $saleReturn->per_price);
        $discounts = explode(',', $saleReturn->per_discount);
        $quantities = explode(',', $saleReturn->qty);
        $totals = explode(',', $saleReturn->per_total);

        for ($i = 0; $i < count($productNames); $i++) {
            $returnItems[] = [
                'product_name' => $productNames[$i] ?? '',
                'product_code' => $productCodes[$i] ?? '',
                'brand' => $brands[$i] ?? '',
                'unit' => $units[$i] ?? '',
                'price' => $prices[$i] ?? 0,
                'discount' => $discounts[$i] ?? 0,
                'quantity' => $quantities[$i] ?? 0,
                'total' => $totals[$i] ?? 0,
            ];
        }

        // Get payment details
        $payments = \App\Models\CustomerPayment::where('note', 'like', "%Sale Return #{$saleReturn->id}%")->get();

        // Get journal entries
        $journalEntries = \App\Models\JournalEntry::where('source_type', 'App\Models\SaleReturn')
            ->where('source_id', $saleReturn->id)
            ->with('account')
            ->get();

        // Get approver and inspector info
        $approver = $saleReturn->approved_by ? \App\Models\User::find($saleReturn->approved_by) : null;
        $inspector = $saleReturn->inspected_by ? \App\Models\User::find($saleReturn->inspected_by) : null;

        return view('admin_panel.sale.return.detail', compact(
            'saleReturn',
            'sale',
            'returnItems',
            'payments',
            'journalEntries',
            'approver',
            'inspector'
        ));
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with(['customer_relation.salesOfficer'])->findOrFail($id);
        $items = $this->_getSaleItems($sale);
        $isEstimate = request()->query('type') === 'estimate';

        // Calculate Balances for Invoice
        $previousBalance = 0;
        $currentBalance = 0;

        // Find the Journal Entry for this Sale (Debit side)
        // We look for where source is Sale and it's a Debit (Customer side)
        $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
            ->where('source_id', $sale->id)
            ->where('debit', '>', 0) // The debit to customer
            ->first();

        if ($journalEntry && $sale->customer_id) {
            // Calculate Previous Balance: Sum of (Dr - Cr) for all entries BEFORE this one
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            // Current Balance (after this bill, before payment if payment is separate)
            $currentBalance = $previousBalance + $sale->total_net;

            // Note: If payment was made, it's usually a separate receipt voucher (even if immediate).
            // So "Current Balance" here naturally excludes the payment unless we look for payment next.
            // But the user wants "Previous Bal +/- Current Amount = Net".
        } else {
            // Fallback if no journal entry (legacy or draft)
            $customer = $sale->customer_relation;
            $previousBalance = $customer->opening_balance ?? 0;
            // This is an estimate if we can't find the entry
        }

        return view('admin_panel.sale.saleinvoice', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
            'isEstimate' => $isEstimate,
        ]);
    }

    public function saleedit($id)
    {
        // 1. Fetch Sale with relations (including nested items.product for pre-fill)
        // Eager load warehouseStocks to display stock in edit view
        $sale = Sale::with(['items.product.warehouseStocks', 'customer_relation'])->findOrFail($id);

        // 2. Data for Dropdowns (Same as addsale)
        $customer = Customer::all();
        $warehouse = Warehouse::all();
        // Filter accounts (Cash/Bank) for Receipt Voucher
        $accounts = \App\Models\Account::whereIn('head_id', [1, 2])->orderBy('title')->get();

        // 3. Reuse nextInvoiceNumber var for current invoice no (view expects this variable name)
        $nextInvoiceNumber = $sale->invoice_no;

        // 4. Return the Edit Sale View
        return view('admin_panel.sale.edit_sale', compact('warehouse', 'customer', 'nextInvoiceNumber', 'accounts', 'sale'));
    }

    public function updatesale(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        if (in_array($sale->sale_status, ['posted', 'cancelled', 'returned'])) {
            return redirect()->back()->with('error', 'Cannot edit a '.$sale->sale_status.' sale.');
        }

        return $this->processSale($request, $sale);
    }

    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function saledcThermal($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc_thermal', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function salereceipt($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        // Logic for Previous Balance (copied from saleinvoice)
        $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
            ->where('source_id', $sale->id)
            ->where('debit', '>', 0) // The debit to customer
            ->first();

        $previousBalance = 0;
        $currentBalance = 0;

        if ($journalEntry && $sale->customer_id) {
            // Calculate Previous Balance: Sum of (Dr - Cr) for all entries BEFORE this one
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            // Current Balance
            $currentBalance = $previousBalance + $sale->total_net;
        } else {
            // Fallback if no journal entry (legacy or draft)
            $customer = $sale->customer_relation;
            if ($customer) {
                // Try to get balance from ledger or master (simplified fallback)
                $previousBalance = $customer->previous_balance ?? 0;
            }
            $currentBalance = $previousBalance + $sale->total_net;
        }

        return view('admin_panel.sale.salereceipt', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function postFinal(Request $request)
    {
        // If the request contains full form data, we process it as an update + post
        // If it only contains an ID, we just transition state?
        // Based on previous code, it receives form data.
        $request->merge(['action' => 'post']);

        if ($request->booking_id) {
            $sale = Sale::findOrFail($request->booking_id);
            if ($sale->sale_status === 'posted') {
                return response()->json(['ok' => true, 'msg' => 'Already Posted', 'invoice_url' => route('sales.invoice', $sale->id)]);
            }

            return $this->processSale($request, $sale);
        }

        return $this->processSale($request, new Sale);
    }

    private function processSale(Request $request, Sale $sale)
    {
        // 1. Validation
        $request->validate([
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'nullable', // Composite ID validation handled in loop
            'qty' => 'required|array|min:1',
            'warehouse_id' => 'required|array',
        ]);



        $status = $request->action === 'post' ? 'posted' : 'booked';

        // Concurrency Safe Transaction
        try {
            return DB::transaction(function () use ($request, $sale, $status) {

                // 2. Prepare Header Data
                $isNew = ! $sale->exists;
                $sale->customer_id = $request->customer;
                $sale->reference = $request->reference;
                $sale->total_amount_Words = $request->total_amount_Words;
                $sale->sale_status = $status;

                // Credit Days & Due Date
                if ($request->filled('credit_days') && $request->credit_days > 0) {
                    $creditDays = (int) $request->credit_days;
                    $sale->credit_days = $creditDays;
                    $baseDate = $sale->created_at ? $sale->created_at->copy() : now();
                    $sale->due_date = $baseDate->addDays($creditDays);
                } else {
                    $sale->credit_days = null;
                    $sale->due_date = null;
                }

                if ($isNew) {
                    if ($request->filled('invoice_no')) {
                        $manualInvoice = trim($request->invoice_no);
                        $exists = Sale::where('invoice_no', $manualInvoice)->exists();
                        if ($exists) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'invoice_no' => "Invoice number '{$manualInvoice}' already exists.",
                            ]);
                        }
                        $sale->invoice_no = $manualInvoice;
                    } else {
                        $sale->invoice_no = $this->generateUniqueInvoiceNo();
                    }
                }

                $total_bill = 0;
                $total_items = 0;
                $sale->save();

                // 3. Process Items
                if (! $isNew) {
                    SaleItem::where('sale_id', $sale->id)->delete();
                }

                $productIds = $request->product_id;
                $packageIds = $request->product_package_id;
                $quantities = $request->qty; 
                $warehouses = $request->warehouse_id;
                $discounts = $request->item_disc ?? [];

                foreach ($productIds as $index => $pid) {
                    if (! $pid) continue;

                    $qtyInput = (float) ($quantities[$index] ?? 0);
                    if ($qtyInput <= 0) continue;

                    $product = Product::findOrFail($pid);
                    $packageId = $packageIds[$index] ?? null;
                    $inputPrice = (float) ($request->price_per_piece[$index] ?? 0);
                    $dbPrice = $inputPrice > 0 ? $inputPrice : ($product->sale_price_per_piece > 0 ? $product->sale_price_per_piece : 0);

                    // Conversion Factor
                    $cf = 1;
                    if ($packageId) {
                        $package = \App\Models\ProductPackage::find($packageId);
                        if ($package) $cf = $package->conversion_factor > 0 ? $package->conversion_factor : 1;
                    } else {
                        $cf = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                    }

                    $totalPieces = $qtyInput * $cf;
                    $loose = (float) ($request->loose_pieces[$index] ?? 0);
                    $totalPieces += $loose;

                    $discount = (float) ($discounts[$index] ?? 0);
                    $discType = $request->discount_type[$index] ?? 'percent';

                    $lineTotal = $totalPieces * $dbPrice;
                    if ($discType === 'pkr') {
                        $calcDiscountAmount  = $discount;
                        $calcDiscountPercent = $lineTotal > 0 ? round(($discount / $lineTotal) * 100, 4) : 0;
                    } else {
                        $calcDiscountPercent = $discount;
                        $calcDiscountAmount  = round($lineTotal * $discount / 100, 2);
                    }

                    $lineTotal = max(0, $lineTotal - $calcDiscountAmount);

                    $saleItem = new SaleItem;
                    $saleItem->sale_id = $sale->id;
                    $saleItem->product_id = $pid;
                    $saleItem->product_package_id = $packageId;
                    $saleItem->warehouse_id = $warehouses[$index] ?? 1;
                    $saleItem->product_name = $product->item_name;
                    $saleItem->qty = ($cf > 0 ? ($totalPieces / $cf) : 0);
                    $saleItem->total_pieces = $totalPieces;
                    $saleItem->loose_pieces = $loose;
                    $saleItem->price = $dbPrice;
                    $saleItem->discount_percent = $calcDiscountPercent;
                    $saleItem->discount_amount = $calcDiscountAmount;
                    $saleItem->total = $lineTotal;
                    $saleItem->brand_id = $product->brand_id;
                    $saleItem->unit_id = $product->unit_id;
                    $saleItem->size_mode = $product->size_mode;
                    $saleItem->save();

                    $total_bill += $lineTotal;
                    $total_items += $totalPieces;
                }

                $sale->total_bill_amount = $total_bill;
                $sale->total_extradiscount = $request->total_extra_cost ?? 0;
                $sale->total_net = $total_bill - $sale->total_extradiscount;
                $sale->total_items = $total_items;
                $sale->cash = $request->cash ?? 0;
                $sale->change = ($sale->cash - $sale->total_net);
                $sale->save();

                if ($status === 'posted') {
                    $this->handleStockImpact($sale, 'out');
                    $this->updateLedger($sale);

                    try {
                        $balanceService = app(\App\Services\BalanceService::class);
                        $date = $sale->created_at->format('Y-m-d');
                        $custForVoucher = $sale->customer_relation ?? \App\Models\Customer::find($sale->customer_id);

                        if ($custForVoucher) {
                            $balanceService->createSaleVoucher($custForVoucher, $sale->total_net, $sale->invoice_no, $date);
                        }

                        $transactionService = app(\App\Services\TransactionService::class);
                        $transactionService->createReceiptFromSale(
                            $sale,
                            $request->input('receipt_account_id', []),
                            $request->input('receipt_amount', [])
                        );
                    } catch (\Exception $e) {
                        \Log::error('Accounting Error: '.$e->getMessage());
                    }
                }

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'ok' => true,
                        'booking_id' => $sale->id,
                        'msg' => 'Sale '.ucfirst($status).' Successfully',
                        'invoice_url' => route('sales.invoice', $sale->id),
                    ]);
                }

                return redirect()->route('sale.index')->with('success', 'Sale saved as '.$status);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Sale Process Error: ' . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'error' => 'Unable to process sale: ' . $e->getMessage()
            ]);
        }
    }

    private function handleStockImpact(Sale $sale, $type = 'out')
    {
        // Type: 'out' (Sale Posted), 'in' (Sale Cancelled), 'return' (Returned)

        // Load items relationship if not already loaded
        if (! $sale->relationLoaded('items')) {
            $sale->load('items.product');
        }

        foreach ($sale->items as $item) {
            $stock = WarehouseStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $item->warehouse_id)
                ->lockForUpdate() // LOCK ROW
                ->first();

            if (! $stock) {
                // Create if missing? Or fail? User said "Validate warehouse stock".
                throw new \Exception('Stock not found for product: '.$item->product_name);
            }

            // Convert everything to pieces for calculation
            $qtyPieces = $item->total_pieces;

            if ($type === 'out') {
                // Deduct
                if ($stock->total_pieces < $qtyPieces) {
                    $uom = optional($item->product->unit)->name ?? 'pcs';
                    throw new \Exception("Insufficient stock for '{$item->product_name}'. Requested: {$qtyPieces} {$uom}, Available: {$stock->total_pieces} {$uom} in selected warehouse.");
                }
                $stock->total_pieces -= $qtyPieces;
                // Update approx boxes for display
                $ppb = $item->product->pieces_per_box ?? 1;
                $stock->quantity = round($stock->total_pieces / ($ppb > 0 ? $ppb : 1), 2);
                $stock->save();

                // Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'qty' => -$qtyPieces, // Negative for OUT
                    'ref_type' => 'sale',
                    'ref_id' => $sale->id,
                    'note' => 'Sale Posted #'.$sale->invoice_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($type === 'in' || $type === 'return') {
                // Restore (Cancel or Return)
                $stock->total_pieces += $qtyPieces;
                // Update approx boxes
                $ppb = $item->product->pieces_per_box ?? 1;
                $stock->quantity = round($stock->total_pieces / ($ppb > 0 ? $ppb : 1), 2);
                $stock->save();

                // Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $qtyPieces,
                    'ref_type' => 'sale_'.$type, // sale_in (cancel), sale_return
                    'ref_id' => $sale->id,
                    'note' => 'Sale '.ucfirst($type).' #'.$sale->invoice_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function updateLedger(Sale $sale)
    {
        $customer_id = $sale->customer_id;
        if (! $customer_id) {
            return;
        }

        $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
        // Fallback: If no ledger, check Customer Master
        if (! $ledger) {
            $cust = \App\Models\Customer::find($customer_id);
            $prev_bal = $cust->previous_balance ?? 0;
        } else {
            $prev_bal = $ledger->closing_balance;
        }

        $new_bal = $prev_bal + $sale->total_net;

        \Log::info("Legacy Ledger (Invoice): Customer #{$customer_id}. Prev: {$prev_bal} + Sale: {$sale->total_net} = New: {$new_bal}");

        CustomerLedger::create([
            'customer_id' => $customer_id,
            'admin_or_user_id' => auth()->id() ?? 1,
            'description' => 'Sale Invoice #'.$sale->invoice_no,
            'previous_balance' => $prev_bal,
            'closing_balance' => $new_bal,
            'opening_balance' => 0, // Schema might require this
        ]);

        // Update Customer Master
        $cust = \App\Models\Customer::find($customer_id);
        if ($cust) {
            $cust->previous_balance = $new_bal;
            $cust->save();
        }
    }

    /**
     * Auto-generate receipt voucher when sale is posted
     */
    private function autoGenerateReceiptVoucher(Sale $sale, Request $request)
    {
        // Get account IDs from request (from receipt voucher section)
        $accountIds = $request->input('receipt_account_id', []);
        $amounts = $request->input('receipt_amount', []);

        // If no accounts selected, use default cash account
        if (empty($accountIds) || empty(array_filter($accountIds))) {
            $accountIds = [1]; // Default to account ID 1 (Cash)
            $amounts = [$sale->cash];
        }

        // Generate unique RVID
        $lastRV = \App\Models\ReceiptsVoucher::orderBy('id', 'desc')->first();
        $nextNumber = $lastRV ? (int) filter_var($lastRV->rvid, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
        $rvid = 'RV-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Create receipt voucher
        \App\Models\ReceiptsVoucher::create([
            'rvid' => $rvid,
            'party_id' => $sale->customer_id,
            'type' => 'customer',
            'total_amount' => $sale->cash,
            'receipt_date' => now()->format('Y-m-d'),
            'row_account_id' => json_encode($accountIds),
            'row_account_head' => json_encode([]), // Can be populated if needed
            'row_amount' => json_encode($amounts),
            'remarks' => 'Auto-generated from Sale Invoice #'.$sale->invoice_no,
            'processed' => true, // Mark as processed since it's linked to sale
        ]);
    }

    private function _getSaleItems($sale)
    {
        // Legacy support wrapper or direct relation
        // Re-implementing correctly based on new structure
        return $sale->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'item_name' => $item->product_name ?? $item->product->item_name ?? 'Item',
                'item_code' => $item->product->item_code ?? '',
                'brand' => $item->product->brand->name ?? '',
                'unit' => $item->product->unit->name ?? '', // Access name if relation exists
                'qty' => (int) $item->total_pieces, // Use Pieces for Return Logic (Matches Price-Per-Piece)
                'qty_box' => (float) $item->qty, // Store Box Count separately
                'total_pieces' => (int) $item->total_pieces,
                'loose_pieces' => (int) $item->loose_pieces,
                'price' => (float) $item->price, // Price Per Piece
                'discount' => (float) $item->discount_percent, // Legacy
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'total' => (float) $item->total,
                'color' => json_decode($item->color, true) ?? [],
                'pieces_per_box' => $item->product->pieces_per_box ?? 1,
                'price_per_piece' => ($item->total_pieces > 0) ? ($item->total / $item->total_pieces) : 0,
                // Add dimension and m² data from product
                'height' => $item->product->height ?? 0,
                'width' => $item->product->width ?? 0,
                'pieces_per_m2' => $item->product->pieces_per_m2 ?? 0,
                'size_mode' => $item->size_mode ?? $item->product->size_mode ?? 'std',
            ];
        });
    }

    /**
     * Generate a unique invoice number with duplicate checking
     */
    private function generateUniqueInvoiceNo()
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $lastSale = Sale::orderBy('id', 'desc')->first();

            if (! $lastSale || ! $lastSale->invoice_no) {
                $invoiceNo = 'INV-0001';
            } else {
                // Extract numeric part
                $lastNumber = (int) str_replace('INV-', '', $lastSale->invoice_no);
                // Increment + format
                $invoiceNo = 'INV-'.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            // Check if this invoice number already exists
            $exists = Sale::where('invoice_no', $invoiceNo)->exists();

            if (! $exists) {
                return $invoiceNo;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // Fallback: use timestamp-based unique number
        return 'INV-'.date('YmdHis');
    }
}
