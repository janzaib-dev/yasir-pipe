<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Inwardgatepass;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /** Keep stocks table in sync for a (branch,warehouse,product) */
    /** Keep warehouse_stocks table in sync for a (warehouse,product) */
    private function upsertStocks(int $productId, float $qtyPiecesDelta, int $branchId, int $warehouseId): void
    {
        // We ignore $branchId as WarehouseStock is warehouse-specific
        $stock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        // Get Product Master Data for Box Calculation
        $product = Product::find($productId);
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        if ($stock) {
            // ✅ Add new pieces directly to existing balance to prevent precision/sync loss
            $stock->total_pieces += $qtyPiecesDelta;
            
            // Recalculate Boxes for display only
            $stock->quantity = $stock->total_pieces / $ppb;
            
            $stock->save();
        } else {
            \App\Models\WarehouseStock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'total_pieces' => $qtyPiecesDelta,
                'quantity' => $qtyPiecesDelta / $ppb, // Initial Box Qty
                'price' => 0, // Should be fetched from Product or Purchase Item? 
            ]);
        }
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['branch', 'warehouse', 'vendor', 'items', 'returns']);

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status_purchase', $request->status);
        }
        
        // Add latest() for better ordering
        $Purchase = $query->latest()->get();
        
        // Calculate updated amounts after returns
        $Purchase->each(function ($purchase) {
            // Calculate total returned amount for this purchase
            $totalReturned = $purchase->returns->sum('net_amount');
            
            // Store calculated values as attributes
            $purchase->total_returned = $totalReturned;
            $purchase->updated_net_amount = max(0, $purchase->net_amount - $totalReturned);
            $purchase->updated_due_amount = max(0, $purchase->due_amount - $totalReturned);
            
            // Check if fully returned
            $purchase->is_fully_returned = $purchase->net_amount > 0 && $totalReturned >= $purchase->net_amount;
            $purchase->has_partial_return = $totalReturned > 0 && $totalReturned < $purchase->net_amount;
        });

        return view('admin_panel.purchase.index', compact('Purchase'));
    }

    public function addBill($gatepassId)
    {
        // Fetch the gatepass along with its related items and products
        $gatepass = InwardGatepass::with('items.product')->findOrFail($gatepassId);

        // Pass the gatepass data to the view
        return view('admin_panel.inward.add_bill', compact('gatepass'));
    }

    public function add_purchase()
    {
        // $userId = Auth::id();
        $Purchase = Purchase::get();
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        // Filter accounts to only show Cash (1) and Bank (2) heads to prevent logic errors
        $accounts = \App\Models\Account::whereIn('head_id', [1, 2])->get();

        $lastInvoice = Purchase::latest('id')->value('invoice_no');
        $nextInvoice = $lastInvoice
            ? 'PUR-'.str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 3, '0', STR_PAD_LEFT)
            : 'PUR-001';

        // Return new V2 view
        return view('admin_panel.purchase.add_purchase_v2', compact('Vendor', 'Warehouse', 'Purchase', 'accounts', 'nextInvoice'));
    }

    private function approvePurchase(Purchase $purchase)
    {
        // 1. Stock Movements & Warehouse Stock
        // We need to re-iterate items because we need product_id and qty
        // But the previous logic used $validated arrays which might process duplicates or specific logic.
        // However, since the PurchaseItems are already saved in DB for 'draft' or 'new',
        // we should rely on the SAVED items for approval to ensure consistency.

        $branchId = $purchase->branch_id;
        $warehouseId = $purchase->warehouse_id;

        // Check for Gatepass link (if linked, no stock movement needed usually, logic from store method)
        $hasGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

        if (! $hasGatepass) {
            $movRows = [];
            foreach ($purchase->items as $item) {
                // movements (+)
                $movRows[] = [
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $item->qty,
                    'ref_type' => 'PURCHASE',
                    'ref_id' => $purchase->id,
                    'note' => 'Purchase Confirmed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                // stocks
                $this->upsertStocks($item->product_id, +$item->qty, $branchId, $warehouseId);
            }

            if (! empty($movRows)) {
                DB::table('stock_movements')->insert($movRows);
            }
        }

        // 2. Vendor Ledger
        $netAmount = $purchase->net_amount;
        $prevClosing = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)
            ->value('closing_balance') ?? 0;

        \App\Models\VendorLedger::updateOrCreate(
            ['vendor_id' => $purchase->vendor_id],
            [
                'vendor_id' => $purchase->vendor_id,
                'admin_or_user_id' => auth()->id(),
                'previous_balance' => $prevClosing,
                'opening_balance' => $prevClosing,
                'closing_balance' => $prevClosing + $netAmount,
            ]
        );

        // 3. Accounting
        try {
            $transactionService = app(\App\Services\TransactionService::class);

            // A. Create Purchase Voucher
            $transactionService->createPurchaseVoucher($purchase);

            // B. Record Payment (This part is tricky if payments were passed solely in Request)
            // If payments were saved in a temp table or if we re-collect them, good.
            // BUT: distinct feature request "Confirm Purchase" usually implies later approval.
            // If payments were part of the initial 'store' request, they are lost if we didn't save them.
            // The user said "confirm purchase... don't create vouchers... just save...".
            // So if 'Draft', we did NOT run accounting. The payment inputs were ignored?
            // If we want to support payments on Confirm, we would need to store them or ask again.
            // For now, we will Assume no immediate payments on 'Draft -> Confirm' via separate button,
            // UNLESS we are in the immediate 'store' flow where Request data is available.

            // To handle both cases (immediate approve vs later approve), we can pass optional payment data.
            // But strict signature: approvePurchase(Purchase $purchase, array $paymentData = [])

        } catch (\Exception $e) {
            \Log::error('Purchase Accounting Error: '.$e->getMessage());
        }
    }

    public function confirm($id)
    {
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->findOrFail($id);

            if ($purchase->status_purchase !== 'draft') {
                return; // already approved or invalid state
            }

            // Run approval logic
            $this->approvePurchase($purchase);

            // Update status
            $purchase->update(['status_purchase' => 'approved']);
        });

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase confirmed successfully.',
                'invoice_url' => route('purchase.invoice', $id),
                'redirect_url' => route('Purchase.home'),
            ]);
        }

        return redirect()->back()->with('success', 'Purchase confirmed successfully.');
    }

    public function store(Request $request, $gatepassId = null)
    {
        // (A) Gatepass fetch if provided
        $gatepass = null;
        if ($gatepassId) {
            $gatepass = \App\Models\InwardGatepass::with('purchase')->findOrFail($gatepassId);
            if ($gatepass->purchase) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Gatepass already has a bill.'], 422);
                }

                return back()->with('error', 'This gatepass already has an associated bill.');
            }
        }

        // (B) Validation
        try {
            $validated = $request->validate([
                'invoice_no' => 'nullable|string',
                'vendor_id' => 'required|exists:vendors,id',
                'purchase_date' => 'nullable|date',
                'branch_id' => 'nullable|exists:branches,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'note' => 'nullable|string',
                'discount' => 'nullable|numeric|min:0',
                'extra_cost' => 'nullable|numeric|min:0',
                'product_id' => 'array',
                'product_id.*' => 'nullable', // Composite ID handling
                'qty' => 'array',
                'qty.*' => 'nullable|required_with:product_id.*|numeric|min:1',
                'price' => 'array',
                'price.*' => 'nullable|required_with:product_id.*|numeric|min:0',
                'unit' => 'array',
                'unit.*' => 'nullable|required_with:product_id.*|string',
                'item_discount' => 'nullable|array',
                'item_discount.*' => 'nullable|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'active' => false, 'errors' => $e->errors(), 'message' => 'Validation Error'], 422);
            }
            throw $e;
        }

        // Wrap in transaction... to allow returning $purchase outside
        $purchase = DB::transaction(function () use ($validated, $request, $gatepass) {

            // invoice number
            $lastInvoice = Purchase::latest('id')->value('invoice_no');
            $nextInvoice = $lastInvoice
                ? 'PUR-'.str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 3, '0', STR_PAD_LEFT)
                : 'PUR-001';

            $branchId = (int) ($validated['branch_id'] ?? 1);                 // ✅ use real branch
            $warehouseId = (int) $validated['warehouse_id'];

            // Status Logic
            $status = ($request->action === 'save_only') ? 'draft' : 'approved';

            // create header
            $purchase = Purchase::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? now(),
                'invoice_no' => $validated['invoice_no'] ?? $nextInvoice,
                'note' => $validated['note'] ?? null,
                'subtotal' => 0,
                'discount' => 0,
                'extra_cost' => 0,
                'net_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status_purchase' => $status,
            ]);

            $subtotal = 0;
            $pids = $validated['product_id'] ?? [];
            $qtys = $validated['qty'] ?? [];
            $prices = $validated['price'] ?? [];
            $units = $validated['unit'] ?? [];
            $itemDiscs = $validated['item_discount'] ?? [];

            // Snapshot fields
            $packageIds = $request->product_package_id ?? [];
            $sizeModes = $request->size_mode ?? [];
            $ppbs = $request->pieces_per_box ?? [];
            $ppm2 = $request->pieces_per_m2 ?? [];
            $boxesQtys = $request->boxes_qty ?? [];
            $looseQtys = $request->loose_qty ?? [];
            $lengths = $request->length ?? [];
            $widths = $request->width ?? [];

            foreach ($pids as $i => $pid) {
                $pid = (int) ($pid ?? 0);
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);
                if (! $pid || $qty <= 0 || $price < 0) {
                    continue;
                }

                $discPercent = (float) ($itemDiscs[$i] ?? 0);
                $unit = $units[$i] ?? null;

                // Calculate Line Total matching Frontend Logic
                $curSizeMode = $sizeModes[$i] ?? null;
                $curPPM2 = (float) ($ppm2[$i] ?? 0); // This is actually m2_per_piece if by_size

                if ($curSizeMode === 'by_size') {
                    // Frontend: pieces_per_m2 * totalPieces * price
                    // where pieces_per_m2 is effectively m2 per piece, and price is price per m2
                    $grossTotal = $curPPM2 * $qty * $price;
                } elseif ($curSizeMode === 'by_cartons' || $curSizeMode === 'by_carton') {
                    // For cartons, price is per carton, so divide by pieces_per_box to get price per piece
                    $ppb = isset($ppbs[$i]) && $ppbs[$i] > 0 ? (float) $ppbs[$i] : 1;
                    $grossTotal = $qty * ($price / $ppb);
                } else {
                    // Standard: pieces * price_per_piece
                    $grossTotal = $qty * $price;
                }

                // Calculate absolute discount from percentage
                $discAmount = $grossTotal * ($discPercent / 100);
                $lineTotal = $grossTotal - $discAmount;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $pid,
                    'product_package_id' => $packageIds[$i] ?? null,
                    'unit' => $unit,
                    'price' => $price,
                    'item_discount' => $discAmount, // Store calculated amount
                    'qty' => $qty,
                    'line_total' => $lineTotal, // Fixed logic

                    // Snapshots
                    'size_mode' => $sizeModes[$i] ?? null,
                    'pieces_per_box' => $ppbs[$i] ?? 1,
                    'pieces_per_m2' => $ppm2[$i] ?? 0,
                    'boxes_qty' => $boxesQtys[$i] ?? 0,
                    'loose_qty' => $looseQtys[$i] ?? 0,
                    'length' => $lengths[$i] ?? null,
                    'width' => $widths[$i] ?? null,
                ]);

                $subtotal += $lineTotal;
            }

            // totals
            $discount = (float) ($request->discount ?? 0);
            $extraCost = (float) ($request->extra_cost ?? 0);
            $netAmount = ($subtotal - $discount) + $extraCost;

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'extra_cost' => $extraCost,
                'net_amount' => $netAmount,
                'due_amount' => $netAmount,
            ]);

            // If NOT draft, run full approval
            if ($status === 'approved') {
                $purchase->load('items'); // Load items for approval logic logic

                $this->approvePurchase($purchase); // Basic Stock + Ledger + Voucher

                // B. Record Payment (Only available in immediate Request)
                try {
                    $transactionService = app(\App\Services\TransactionService::class);
                    $paymentAccountIds = $request->input('payment_account_id', []);
                    $paymentAmounts = $request->input('payment_amount', []);

                    if (! empty(array_filter($paymentAccountIds))) {
                        $transactionService->createPaymentForPurchase(
                            $purchase,
                            $paymentAccountIds,
                            $paymentAmounts
                        );
                    }
                } catch (\Exception $e) { /* Logged already */
                }
            }

            // link gatepass -> purchase (and keep status)
            if ($gatepass) {
                $gatepass->purchase_id = $purchase->id;
                $gatepass->status = 'linked';
                $gatepass->save();
            }

            return $purchase;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase saved successfully.',
                'invoice_url' => route('purchase.invoice', $purchase->id),
                'redirect_url' => route('Purchase.home'),
            ]);
        }

        return redirect()->route('Purchase.home')->with('success', 'Purchase saved successfully.');
    }

    // public function store(Request $request)
    // {
    //     // ✅ Validation
    //     $validated = $request->validate([
    //         'invoice_no'     => 'nullable|string',
    //         'vendor_id'      => 'nullable|exists:vendors,id',
    //         'purchase_date'  => 'nullable|date',
    //         'warehouse_id'   => 'nullable|exists:warehouses,id',
    //         'note'           => 'nullable|string',
    //         'discount'       => 'nullable|numeric|min:0',
    //         'extra_cost'     => 'nullable|numeric|min:0',

    //         // Purchase Items
    //         'product_id'       => 'nullable|array',
    //         'product_id.*'     => 'nullable|exists:products,id',
    //         'qty'              => 'nullable|array',
    //         'qty.*'            => 'nullable|numeric|min:1',
    //         'price'            => 'nullable|array',
    //         'price.*'          => 'nullable|numeric|min:0',
    //         'unit'             => 'nullable|array',
    //         'unit.*'           => 'nullable|string',
    //         'item_discount'    => 'nullable|array',
    //         'item_discount.*'  => 'nullable|numeric|min:0',
    //     ]);

    //     DB::transaction(function () use ($validated, $request) {

    //         // 🧾 Generate Next Invoice No
    //         $lastInvoice = Purchase::latest()->value('invoice_no');
    //         $nextInvoice = $lastInvoice
    //             ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //             : 'INV-00001';

    //         // ✍️ Create Purchase with temporary values
    //         $purchase = Purchase::create([
    //             'branch_id'     => auth()->user()->id,
    //             'warehouse_id'  => $validated['warehouse_id'],
    //             'vendor_id'     => $validated['vendor_id'] ?? null,
    //             'purchase_date' => $validated['purchase_date'] ?? now(),
    //             'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    //             'note'          => $validated['note'] ?? null,
    //             'subtotal'      => 0,
    //             'discount'      => 0,
    //             'extra_cost'    => 0,
    //             'net_amount'    => 0,
    //             'paid_amount'   => 0,
    //             'due_amount'    => 0,
    //         ]);

    //         $subtotal = 0;

    //         // 🧾 Purchase Items
    //         $productIds = $validated['product_id'] ?? [];
    //         foreach ($productIds as $index => $productId) {
    //             $qty   = $validated['qty'][$index] ?? null;
    //             $price = $validated['price'][$index] ?? null;

    //             if (empty($productId) || empty($qty) || empty($price)) {
    //                 continue;
    //             }

    //             $disc = $validated['item_discount'][$index] ?? 0; // ✅ Correct name
    //             $unit = $validated['unit'][$index] ?? null;

    //             $lineTotal = ($price * $qty) - $disc;

    //             PurchaseItem::create([
    //                 'purchase_id'   => $purchase->id,
    //                 'product_id'    => $productId,
    //                 'unit'          => $unit,
    //                 'price'         => $price,
    //                 'item_discount' => $disc,
    //                 'qty'           => $qty,
    //                 'line_total'    => $lineTotal,
    //             ]);

    //             $subtotal += $lineTotal;

    //             // 📦 Update Stock
    //             $stock = Stock::where('branch_id', auth()->user()->id)
    //                 ->where('warehouse_id', $validated['warehouse_id'])
    //                 ->where('product_id', $productId)
    //                 ->first();

    //             if ($stock) {
    //                 $stock->qty += $qty;
    //                 $stock->save();
    //             } else {
    //                 Stock::create([
    //                     'branch_id'     => auth()->user()->id,
    //                     'warehouse_id'  => $validated['warehouse_id'],
    //                     'product_id'    => $productId,
    //                     'qty'           => $qty,
    //                 ]);
    //             }
    //         }

    //         // 💵 Final Calculations (use values from request safely)
    //         $discount   = $request->discount ?? 0;
    //         $extraCost  = $request->extra_cost ?? 0;
    //         $netAmount  = ($subtotal - $discount) + $extraCost;

    //         $purchase->update([
    //             'subtotal'    => $subtotal,
    //             'discount'    => $discount,
    //             'extra_cost'  => $extraCost,
    //             'net_amount'  => $netAmount,
    //             'due_amount'  => $netAmount,
    //         ]);

    //         // 📘 Vendor Ledger Update
    //         $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //             ->value('closing_balance') ?? 0;

    //         $newClosingBalance = $previousBalance + $netAmount;

    //         VendorLedger::updateOrCreate(
    //             ['vendor_id' => $validated['vendor_id']],
    //             [
    //                 'vendor_id'         => $validated['vendor_id'],
    //                 'admin_or_user_id'  => auth()->id(),
    //                 'previous_balance'  => $subtotal,
    //                 'closing_balance'   => $newClosingBalance,
    //             ]
    //         );
    //     });

    //     return back()->with('success', 'Purchase saved successfully!');
    // }

    // public function store(Request $request)
    // {

    //         $validated = $request->validate([
    //             'invoice_no'     => 'nullable|string',
    //             'vendor_id'      => 'nullable|exists:vendors,id',
    //             // 'branch_id'      => 'required|exists:branches,id',
    //             'purchase_date'  => 'nullable|date',
    //             'warehouse_id'   => 'nullable|exists:warehouses,id',
    //             'note'           => 'nullable|string',
    //     'discount'       => 'nullable|numeric|min:0',
    //     'extra_cost'     => 'nullable|numeric|min:0',

    //             // Purchase Items
    //             'product_id'     => 'nullable|array',
    //             'product_id.*'   => 'nullable|exists:products,id',
    //             'qty'            => 'nullable|array',
    //             'qty.*'          => 'nullable|numeric|min:1',
    //             'price'          => 'nullable|array',
    //             'price.*'        => 'nullable|numeric|min:0',
    //             'unit'           => 'nullable|array',
    //             'unit.*'         => 'nullable|string',
    //             'item_discount'  => 'nullable|array',
    //             'item_discount.*'=> 'nullable|numeric|min:0',
    //         ]);
    // DB::transaction(function () use ($validated) {

    //     $lastInvoice = Purchase::latest()->value('invoice_no');

    //     $nextInvoice = $lastInvoice
    //         ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //         : 'INV-00001';

    //     // 1️⃣ Create purchase
    //     $purchase = Purchase::create([
    //         'branch_id'     => Auth()->user()->id,
    //         'warehouse_id'  => $validated['warehouse_id'],
    //         'vendor_id'     => $validated['vendor_id'] ?? null,
    //         'purchase_date' => $validated['purchase_date'] ?? now(),
    //         'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    //         'note'          => $validated['note'] ?? null,
    //         'subtotal'      => $validated['subtotal'] ?? 0,
    //         'discount'      => $validated['discount'] ?? 0,
    //         'extra_cost'    => $validated['extra_cost'] ?? 0,
    //         'net_amount'    => $validated['net_amount'] ?? 0,
    //         'paid_amount'   => 0,
    //         'due_amount'    => 0,

    //     ]);

    //     $subtotal = 0;

    //     // 2️⃣ Loop & filter rows
    //     $productIds = $validated['product_id'] ?? [];
    //     foreach ($productIds as $index => $productId) {
    //         $qty   = $validated['qty'][$index] ?? null;
    //         $price = $validated['price'][$index] ?? null;

    //         // Skip row if any essential field is empty
    //         if (empty($productId) || empty($qty) || empty($price)) {
    //             continue;
    //         }

    //         $disc = $validated['item_disc'][$index] ?? 0;
    //         $unit = $validated['unit'][$index] ?? null;

    //         $lineTotal = ($price * $qty) - $disc;

    //         // Save item
    //         PurchaseItem::create([
    //             'purchase_id'   => $purchase->id,
    //             'product_id'    => $productId,
    //             'unit'          => $unit,
    //             'price'         => $price,
    //             'item_discount' => $disc,
    //             'qty'           => $qty,
    //             'line_total'    => $lineTotal,
    //         ]);

    //         $subtotal += $lineTotal;

    //         // 3️⃣ Update stock
    //         $stock = Stock::where('branch_id', Auth()->user()->id)
    //             ->where('warehouse_id', $validated['warehouse_id'])
    //             ->where('product_id', $productId)
    //             ->first();

    //         if ($stock) {
    //             $stock->qty += $qty;
    //             $stock->save();
    //         } else {
    //             Stock::create([
    //                 'branch_id'     => Auth()->user()->id,
    //                 'warehouse_id'  => $validated['warehouse_id'],
    //                 'product_id'    => $productId,
    //                 'qty'           => $qty,
    //             ]);
    //         }
    //     }

    //     // 4️⃣ Update totals
    //     $purchase->update([
    //         'subtotal'    => $subtotal,
    //         'net_amount'  => $subtotal,
    //         'due_amount'  => $subtotal,
    //     ]);

    //     // 5️⃣ Vendor ledger
    //     $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //         ->value('closing_balance') ?? 0;

    //     $newClosingBalance = $previousBalance + $subtotal;

    //     VendorLedger::updateOrCreate(
    //         ['vendor_id' => $validated['vendor_id']],
    //         [
    //             'vendor_id' => $validated['vendor_id'],
    //             'admin_or_user_id' => Auth::id(),
    //             'previous_balance' => $subtotal,
    //             'closing_balance' => $newClosingBalance,
    //         ]
    //     );

    // });

    // // DB::transaction(function () use ($validated) {

    // // $lastInvoice = Purchase::latest()->value('invoice_no');

    // // // Agar last invoice mila to +1 karo, warna start karo INV-00001
    // // $nextInvoice = $lastInvoice
    // //     ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    // //     : 'INV-00001';

    // //     // 1️⃣ Save main Purchase
    // //     $purchase = Purchase::create([

    // //         'branch_id'     => Auth()->user()->id,
    // //         'warehouse_id'  => $validated['warehouse_id'],
    // //         'vendor_id'     => $validated['vendor_id'] ?? null,
    // //         'purchase_date' => $validated['purchase_date'] ?? now(),
    // //         'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    // //         'note'          => $validated['note'] ?? null,
    // //         'subtotal'      => 0,
    // //         'discount'      => 0,
    // //         'extra_cost'    => 0,
    // //         'net_amount'    => 0,
    // //         'paid_amount'   => 0,
    // //         'due_amount'    => 0,
    // //     ]);

    // //     $subtotal = 0;

    // //     // 2️⃣ Loop purchase items
    // //     foreach ($validated['product_id'] as $index => $productId) {
    // //         $qty     = $validated['qty'][$index];
    // //         $price   = $validated['price'][$index];
    // //         $disc    = $validated['item_discount'][$index] ?? 0;
    // //         $lineTotal = ($price * $qty) - $disc;

    // //         // Save purchase item
    // //         PurchaseItem::create([
    // //             'purchase_id'   => $purchase->id,
    // //             'product_id'    => $productId,
    // //             'unit'          => $validated['unit'][$index] ?? null,
    // //             'price'         => $price,
    // //             'item_discount' => $disc,
    // //             'qty'           => $qty,
    // //             'line_total'    => $lineTotal,
    // //         ]);

    // //         $subtotal += $lineTotal;

    // //         // 3️⃣ Update stock
    // //         $stock = Stock::where('branch_id',  Auth()->user()->id,)
    // //             ->where('warehouse_id', $validated['warehouse_id'])
    // //             ->where('product_id', $productId)
    // //             ->first();

    // //         if ($stock) {
    // //             $stock->qty += $qty;
    // //             $stock->save();
    // //         } else {
    // //             Stock::create([
    // //                 'branch_id'     => Auth()->user()->id,
    // //                 'warehouse_id'  => $validated['warehouse_id'],
    // //                 'product_id'    => $productId,
    // //                 'qty'           => $qty,
    // //             ]);
    // //         }
    // //     }

    // //     // 4️⃣ Update totals in purchase
    // //     $purchase->update([
    // //         'subtotal'    => $subtotal,
    // //         'net_amount'  => $subtotal,
    // //         'due_amount'  => $subtotal,
    // //     ]);

    // //     $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    // //         ->value('closing_balance') ?? 0; // If no previous balance, start from 0
    // //     // Calculate new balances

    // //     $newPreviousBalance = $subtotal;

    // //     $newClosingBalance = $previousBalance + $subtotal;
    // //     $userId = Auth::id();

    // //     // Update or create distributor ledger
    // //     VendorLedger::updateOrCreate(
    // //         ['vendor_id' => $validated['vendor_id']],
    // //         [
    // //             'vendor_id' => $validated['vendor_id'],
    // //             'admin_or_user_id' => $userId,
    // //             'previous_balance' => $newPreviousBalance,
    // //             'closing_balance' => $newClosingBalance,
    // //         ]
    // //     );

    // });

    //     return redirect()->back()->with('success', 'Purchase saved successfully!');
    // }

    public function edit($id)
    {
        $purchase = Purchase::with('items.product')->findOrFail($id);

        $Vendor = Vendor::all();
        $Warehouse = Warehouse::all();
        // Filter accounts to only show Cash (1) and Bank (2) heads
        $accounts = \App\Models\Account::whereIn('head_id', [1, 2])->get();

        return view('admin_panel.purchase.edit', compact('purchase', 'Vendor', 'Warehouse', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_no' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id',
            'purchase_date' => 'nullable|date',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'extra_cost' => 'nullable|numeric|min:0',

            'product_id' => 'array',
            'product_id.*' => 'nullable|exists:products,id',
            'qty' => 'array',
            'qty.*' => 'nullable|required_with:product_id.*|numeric|min:0',
            'price' => 'array',
            'price.*' => 'nullable|required_with:product_id.*|numeric|min:0',
            'unit' => 'array',
            'unit.*' => 'nullable|required_with:product_id.*|string',
            'item_discount' => 'nullable|array',
            'item_discount.*' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request, $id) {
            $purchase = Purchase::with('items')->findOrFail($id);

            $branchId = (int) ($validated['branch_id'] ?? $purchase->branch_id ?? 1);
            $warehouseId = (int) ($validated['warehouse_id'] ?? $purchase->warehouse_id);

            // Map old totals per product for Stock Delta Logic
            $oldMap = $purchase->items->groupBy('product_id')->map(fn ($g) => (float) $g->sum('qty'));

            // Delete old items
            $purchase->items()->delete();

            $subtotal = 0;
            $newMap = collect();

            // Arrays from request
            $pids = $validated['product_id'] ?? [];
            $qtys = $validated['qty'] ?? [];
            $prices = $validated['price'] ?? [];
            $units = $validated['unit'] ?? [];
            $itemDiscs = $validated['item_discount'] ?? [];

            // Snapshot fields (Raw Request)
            $sizeModes = $request->size_mode ?? [];
            $ppbs = $request->pieces_per_box ?? [];
            $ppm2 = $request->pieces_per_m2 ?? [];
            $boxesQtys = $request->boxes_qty ?? [];
            $looseQtys = $request->loose_qty ?? [];
            $lengths = $request->length ?? [];
            $widths = $request->width ?? [];

            foreach ($pids as $i => $pid) {
                $pid = (int) ($pid ?? 0);
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);

                if (! $pid || $qty <= 0) {
                    continue;
                }

                $discPercent = (float) ($itemDiscs[$i] ?? 0);
                $unit = $units[$i] ?? null;

                // --- Calculation Logic (Matches store()) ---
                $curSizeMode = $sizeModes[$i] ?? null;
                $curPPM2 = (float) ($ppm2[$i] ?? 0);

                if ($curSizeMode === 'by_size') {
                    // price is per m2. Gross = TotalPieces * m2_per_piece * price_per_m2
                    $grossTotal = $curPPM2 * $qty * $price;
                } elseif ($curSizeMode === 'by_cartons' || $curSizeMode === 'by_carton') {
                    // For cartons, price is per carton, so divide by pieces_per_box to get price per piece
                    $ppb = isset($ppbs[$i]) && $ppbs[$i] > 0 ? (float) $ppbs[$i] : 1;
                    $grossTotal = $qty * ($price / $ppb);
                } else {
                    // Standard
                    $grossTotal = $qty * $price;
                }

                // Calculate absolute discount from percentage
                $discAmount = $grossTotal * ($discPercent / 100);
                $lineTotal = $grossTotal - $discAmount;
                // ------------------------------------------

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $pid,
                    'unit' => $unit,
                    'price' => $price,
                    'item_discount' => $discAmount, // Store calculated amount
                    'qty' => $qty,
                    'line_total' => $lineTotal,

                    // Snapshots
                    'size_mode' => $sizeModes[$i] ?? null,
                    'pieces_per_box' => $ppbs[$i] ?? 1,
                    'pieces_per_m2' => $ppm2[$i] ?? 0,
                    'boxes_qty' => $boxesQtys[$i] ?? 0,
                    'loose_qty' => $looseQtys[$i] ?? 0,
                    'length' => $lengths[$i] ?? null,
                    'width' => $widths[$i] ?? null,
                ]);

                $subtotal += $lineTotal;
                $newMap[$pid] = ($newMap[$pid] ?? 0) + $qty;
            }

            // header update
            $purchase->update([
                'vendor_id' => $validated['vendor_id'] ?? $purchase->vendor_id,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'purchase_date' => $validated['purchase_date'] ?? $purchase->purchase_date,
                'invoice_no' => $validated['invoice_no'] ?? $purchase->invoice_no,
                'note' => $validated['note'] ?? $purchase->note,
            ]);

            // totals
            $discount = (float) ($request->discount ?? 0);
            $extraCost = (float) ($request->extra_cost ?? 0);
            $netAmount = ($subtotal - $discount) + $extraCost;

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'extra_cost' => $extraCost,
                'net_amount' => $netAmount,
                'due_amount' => $netAmount, // Assuming fully due? Or should we subtract paid?
                // Paid amount is separate (transactions).
                // The 'due_amount' usually tracks how much is left.
                // If we paid partial, tracking logic might need paid_amount check.
                // But standard approach here: due = net - paid
            ]);

            // Recalculate Due based on net - paid
            $paid = $purchase->paid_amount;
            $purchase->update(['due_amount' => $netAmount - $paid]);

            // If this purchase is linked to a gatepass => NO stock changes here
            $isLinkedToGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

            if (! $isLinkedToGatepass) {
                // deltas for movements + stocks
                $movs = [];
                $now = now();
                $all = $oldMap->keys()->merge($newMap->keys())->unique();
                foreach ($all as $pid) {
                    $oldQ = (float) ($oldMap[$pid] ?? 0);
                    $newQ = (float) ($newMap[$pid] ?? 0);
                    $delta = $newQ - $oldQ;
                    if ($delta == 0) {
                        continue;
                    }

                    $type = $delta > 0 ? 'in' : 'out';
                    $qty = abs($delta);

                    $movs[] = [
                        'product_id' => (int) $pid,
                        'type' => $type,
                        'qty' => $qty,
                        'ref_type' => 'PURCHASE_EDIT',
                        'ref_id' => $purchase->id,
                        'note' => 'Purchase edit delta',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $this->upsertStocks((int) $pid, ($type === 'in' ? +$qty : -$qty), $branchId, $warehouseId);
                }
                if (! empty($movs)) {
                    DB::table('stock_movements')->insert($movs);
                }
            }

            // Vendor ledger (simple overwrite pattern)
            $prevClosing = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)
                ->value('closing_balance') ?? 0;
            \App\Models\VendorLedger::updateOrCreate(
                ['vendor_id' => $purchase->vendor_id],
                [
                    'vendor_id' => $purchase->vendor_id,
                    'admin_or_user_id' => auth()->id(),
                    'previous_balance' => $prevClosing,
                    'opening_balance' => $prevClosing,
                    'closing_balance' => $prevClosing + $netAmount,
                ]
            );
        });

        return redirect()->route('Purchase.home')->with('success', 'Purchase updated successfully!');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->findOrFail($id);

            $branchId = (int) ($purchase->branch_id ?? 1);
            $warehouseId = (int) ($purchase->warehouse_id);

            // linked to gatepass? then NO stock changes
            $isLinkedToGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

            if (! $isLinkedToGatepass) {
                $movs = [];
                $now = now();

                foreach ($purchase->items as $it) {
                    $pid = (int) $it->product_id;
                    $qty = (float) $it->qty;

                    $movs[] = [
                        'product_id' => $pid,
                        'type' => 'out',
                        'qty' => $qty,
                        'ref_type' => 'PURCHASE_DELETE',
                        'ref_id' => $purchase->id,
                        'note' => 'Delete purchase (reverse)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // stocks rollback
                    $this->upsertStocks($pid, -$qty, $branchId, $warehouseId);
                }

                if (! empty($movs)) {
                    DB::table('stock_movements')->insert($movs);
                }
            }

            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }

    public function Invoice($id)
    {
        $purchase = Purchase::with(['items.product', 'vendor', 'warehouse'])->findOrFail($id);
        
        $balanceService = app(\App\Services\BalanceService::class);
        $vendor_balance = $balanceService->getVendorBalance($purchase->vendor_id);

        return view('admin_panel.purchase.Invoice', compact('purchase', 'vendor_balance'));
    }

    public function receipt($id)
    {
        $purchase = Purchase::with(['items.product', 'vendor'])->findOrFail($id);

        return view('admin_panel.purchase.receipt', compact('purchase'));
    }

    // purchase_reutun

    public function showReturnForm($id)
    {
        $purchase = Purchase::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);
        $accounts = Account::whereIn('head_id', [1, 2])->orderBy('title')->get();
        // Identify max returnable qty: Purchase Qty - Already Returned Qty
        // 1. Get all previous returns for this purchase
        $pastReturns = \App\Models\PurchaseReturn::where('purchase_id', $id)
            ->with('items')
            ->get();
        
        $returnedQtyMap = [];
        foreach ($pastReturns as $pr) {
            foreach ($pr->items as $prItem) {
                if (!isset($returnedQtyMap[$prItem->product_id])) {
                    $returnedQtyMap[$prItem->product_id] = 0;
                }
                $returnedQtyMap[$prItem->product_id] += $prItem->qty;
            }
        }
        
        $purchaseItems = [];
        $hasReturnableItems = false;
        
        foreach ($purchase->items as $item) {
            $alreadyReturned = $returnedQtyMap[$item->product_id] ?? 0;
            $remaining = max(0, $item->qty - $alreadyReturned);
            
            if ($remaining > 0) {
                 $hasReturnableItems = true;
            }
            
            $purchaseItems[] = [
                'product_id' => $item->product_id,
                'item_name' => optional($item->product)->item_name ?? 'Unknown Product',
                'brand' => optional(optional($item->product)->brand)->name ?? '',
                'item_code' => optional($item->product)->item_code ?? '',
                // Fix: Prioritize Product Master PPB to ensure correct Frontend Box calculation
                'pieces_per_box' => (optional($item->product)->pieces_per_box > 0) ? $item->product->pieces_per_box : ($item->pieces_per_box ?? 1),
                'size_mode' => $item->size_mode ?? optional($item->product)->size_mode ?? 'by_pieces',
                'pieces_per_m2' => $item->pieces_per_m2 ?? optional($item->product)->pieces_per_m2 ?? 0,
                'price' => $item->price,
                
                // Qty Logic for Partial Return
                'original_qty' => $item->qty,
                'returned_qty' => $alreadyReturned,
                'qty' => $remaining, // Current Limit
                
                'unit' => $item->unit ?? 'pc',
                'discount' => $item->item_discount,
            ];
        }
        
        // Block access if fully returned
        if (!$hasReturnableItems && $purchase->status_purchase == 'Returned') {
             // Or if total remaining is 0. 
             // Logic: If status is 'Returned', maybe it was fully returned? 
             // But partial return also sets status to 'Returned' in current logic (we might want to change that to 'Partial' later).
             // For now, relies on calculated remaining quantity.
             return redirect()->route('purchase.return.index')->with('error', 'This purchase has clearly been fully returned already.');
        }

        return view('admin_panel.purchase.purchase_return.create', compact('purchase', 'accounts', 'purchaseItems'));
    }

    // store return
    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'nullable|exists:purchases,id',
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'return_reason' => 'nullable|string|max:255',
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array', // Pieces (Total Pieces)
            'qty.*' => 'required|numeric|min:0', // Allow 0 if partial
            'price' => 'required|array',
            'payment_account_id' => 'nullable|array',
            'payment_amount' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // 1. Generate Return Invoice #
            $lastReturn = PurchaseReturn::latest()->first();
            $nextInvoice = 'PRTN-'.str_pad(optional($lastReturn)->id + 1 ?? 1, 5, '0', STR_PAD_LEFT);

            // 2. Create Purchase Return Record
            $purchase = $request->purchase_id ? Purchase::find($request->purchase_id) : null;
            $remarks = $request->return_reason;
            if ($purchase) {
                $remarks .= ' (Ref Invoice: '.$purchase->invoice_no.')';
            }

            $return = PurchaseReturn::create([
                'purchase_id' => $purchase ? $purchase->id : null, 
                'vendor_id' => $validated['vendor_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'return_invoice' => $nextInvoice,
                'return_date' => $validated['return_date'],
                'return_reason' => $validated['return_reason'],
                'remarks' => $remarks,
                'bill_amount' => 0, // calculated below
                'item_discount' => 0,
                'extra_discount' => $request->extra_discount ?? 0,
                'net_amount' => 0,
                'paid' => 0,
                'balance' => 0,
            ]);

            $subtotal = 0;
            $totalItemDiscount = 0;
            $movements = [];
            $now = now();

            // 3. Process Items & Stock
            foreach ($validated['product_id'] as $index => $productId) {
                $qty = (float) ($validated['qty'][$index] ?? 0); // Pieces
                $price = (float) ($validated['price'][$index] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                // Find original item to get snapshots
                $origItem = \App\Models\PurchaseItem::where('purchase_id', $purchase->id ?? 0)
                    ->where('product_id', $productId)
                    ->first();

                // Fallback to Product defaults if no original item
                $product = Product::find($productId);
                $ppb = $origItem ? ($origItem->pieces_per_box ?? 1) : ($product->pieces_per_box ?? 1);
                $sizeMode = $origItem ? ($origItem->size_mode ?? 'by_pieces') : ($product->size_mode ?? 'by_pieces');
                $ppm2 = $origItem ? ($origItem->pieces_per_m2 ?? 0) : ($product->pieces_per_m2 ?? 0);

                // Calculate Line Total Logic (Same as Purchase Store)
                if ($sizeMode === 'by_size') {
                    // price is per m2. Gross = TotalPieces * m2_per_piece * price_per_m2
                    $lineTotal = round($ppm2 * $qty * $price, 2);
                } elseif ($sizeMode === 'by_cartons' || $sizeMode === 'by_carton') {
                    // Price is per carton.
                    $ppbVal = $ppb > 0 ? $ppb : 1;
                    $lineTotal = round($qty * ($price / $ppbVal), 2);
                } else {
                    $lineTotal = $qty * $price;
                }

                $itemDisc = 0;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $productId,
                    'qty' => $qty, // Pieces
                    'price' => $price,
                    'item_discount' => $itemDisc,
                    'unit' => 'pc', // Default
                    'line_total' => $lineTotal,
                ]);

                // Update Stock (DECREMENT)
                $stock = WarehouseStock::where('warehouse_id', $validated['warehouse_id'])
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $product = Product::find($productId); // Ensure fresher product data
                    $ppbVal = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                    
                    // Robust Calculation: Derive current total pieces from quantity (Boxes) * PPB
                    // This handles cases where 'total_pieces' column might be out of sync or stored as boxes historically.
                    $currentTotalPieces = $stock->quantity * $ppbVal; 
                    
                    // Subtract Return Qty (Pieces)
                    $newTotalPieces = max(0, $currentTotalPieces - $qty);
                    
                    $stock->total_pieces = $newTotalPieces;
                    $stock->quantity = $newTotalPieces / $ppbVal; // Convert back to Boxes
                    $stock->save();
                } else {
                     // Should technically not happen for return, but handle gracefully
                     $product = Product::find($productId);
                     $ppbVal = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                     
                     WarehouseStock::create([
                        'warehouse_id' => $validated['warehouse_id'],
                        'product_id' => $productId,
                        'total_pieces' => -$qty, // Negative stock?
                        'quantity' => -$qty / $ppbVal,
                        'price' => 0
                     ]);
                }

                // Prepare Stock Movement
                $movements[] = [
                    'product_id' => $productId,
                    'type' => 'out', // Return OUT to vendor
                    'qty' => $qty,
                    'ref_type' => 'PURCHASE_RETURN',
                    'ref_id' => $return->id,
                    'note' => "Return #{$nextInvoice}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $subtotal += $lineTotal;
                $totalItemDiscount += $itemDisc;
            }

            // Bulk Insert Movements
            if (! empty($movements)) {
                DB::table('stock_movements')->insert($movements);
            }

            $netAmount = ($subtotal - $totalItemDiscount) - ($request->extra_discount ?? 0);

            // 4. Handle Refund Payment
            $totalPaid = 0;
            if (! empty($request->payment_account_id)) {
                $transactionService = app(\App\Services\TransactionService::class);
                // We create a RECEIPT Voucher for the refund received
                // Currently doing it manually or via Service if supported?
                // For simplicity, we create Receipt Voucher via loop below or Service if exists.
                // Service createReceiptVoucher takes Customer, so we might need Vendor support or use Journal.
                
                // Let's just update the return record 'paid' amount and handle Ledger manually for simplicity OR call service if adapted.
                // The User asked for "apply it everything... create journal entries".
                // If refund is received, it's Cash DEBIT, Vendor CREDIT (Wait.. Refund means Cash In).
                // Yes: Dr Cash, Cr Vendor (to offset the Return Debit Note).
                
                // Let's stick to simple ledger updates for Refund part unless we upgrade Service for Vendor Receipts.
                // The requested flow: Return -> Reduces Payable. Refund -> Increases Payable (since they paid us back).
                
                foreach ($request->payment_account_id as $idx => $accId) {
                    $amt = (float) ($request->payment_amount[$idx] ?? 0);
                    if ($accId && $amt > 0) {
                        $totalPaid += $amt;
                        
                         // Create Receipt Voucher (Cash In)
                         // We can use a simple wrapper or manual insert if TransactionService specific for Customers.
                         // Let's use TransactionService->createReceiptFromSale logic but adapted for Vendor Refund? 
                         // No, let's create a specific Receipt Voucher here for accuracy.
                         // Dr Cash, Cr Vendor.
                         
                        $rv = \App\Models\VoucherMaster::create([
                            'voucher_type' => \App\Models\VoucherMaster::TYPE_RECEIPT,
                            'date' => $validated['return_date'],
                            'status' => 'posted',
                            'party_type' => \App\Models\Vendor::class,
                            'party_id' => $validated['vendor_id'],
                            'total_amount' => $amt,
                            'remarks' => "Refund for Return #{$nextInvoice}",
                        ]);
                        
                        // Dr Cash
                        \App\Models\VoucherDetail::create([
                             'voucher_master_id' => $rv->id,
                             'account_id' => $accId, 
                             'debit' => $amt,
                             'credit' => 0,
                             'narration' => 'Cash Refund Received'
                        ]);
                        
                        // Cr Vendor (Accounts Payable - Reducing the Debit Note effect on Ledger, or just Record money in)
                        // Actually, Refund means Vendor gave money. 
                        // Accounts Payable is Liability (Credit). Debit reduces Liability.
                        // Return (Debit Note) = Debit AP.
                        // Refund = Cash Debit, Credit AP (Liability goes back up because they paid us? No.)
                        // Refund clears the "Debit Balance" we created on Vendor. 
                        // If we returned goods, Vendor owes us. (Debit Balance).
                        // They pay us cash. Cash Debit. Vendor Credit (Receivable decreases).
                        // So yes, Credit Vendor Account.
                        
                         $apId = app(\App\Services\BalanceService::class)->getAccountsPayableId();
                         \App\Models\VoucherDetail::create([
                             'voucher_master_id' => $rv->id,
                             'account_id' => $apId, 
                             'debit' => 0,
                             'credit' => $amt,
                             'narration' => 'Refund from Vendor'
                        ]);
                    }
                }
            }

            $return->update([
                'bill_amount' => $subtotal,
                'item_discount' => $totalItemDiscount,
                'net_amount' => $netAmount,
                'paid' => $totalPaid,
                'balance' => $netAmount - $totalPaid,
            ]);

            // Update Purchase Status (only if full return)
            if ($purchase) {
                $totalBought = $purchase->items->sum('qty');
                $totalReturned = \App\Models\PurchaseReturnItem::join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                    ->where('purchase_returns.purchase_id', $purchase->id)
                    ->sum('purchase_return_items.qty');
                
                if ($totalReturned >= $totalBought) {
                    $purchase->update(['status_purchase' => 'Returned']);
                }
            }

            // 5. Update Vendor Ledger & Accounting
            // A. Create General Ledger Voucher for Return (Debit Vendor, Credit Purchase Return)
            $transactionService = app(\App\Services\TransactionService::class);
            if (method_exists($transactionService, 'createPurchaseReturnVoucher')) {
                 $transactionService->createPurchaseReturnVoucher($return);
            }

            // B. Update Legacy Vendor Ledger
            // Logic: Return reduces Payable (Debit Vendor).
            // Refund increases Payable back (Credit Vendor) - effectively clearing the Debit.
            
            $balanceChange = -($netAmount - $totalPaid); // Reduces payable

            // Using VendorLedger table manual update for legacy views
            $ledger = \App\Models\VendorLedger::where('vendor_id', $validated['vendor_id'])->latest()->first();
            $currentClosing = $ledger ? $ledger->closing_balance : 0; // Current closing
            
            // Note: VendorLedger table structure does not support transaction history (no date/desc columns),
            // so we treat it as a Balance Snapshot.
            \App\Models\VendorLedger::updateOrCreate(
                ['vendor_id' => $validated['vendor_id']],
                [
                    'admin_or_user_id' => auth()->id(),
                    'opening_balance' => $ledger ? $ledger->opening_balance : 0, 
                    'previous_balance' => $currentClosing,
                    'closing_balance' => $currentClosing + $balanceChange,
                ]
            );

            DB::commit();

            return redirect()->route('purchase.return.index')->with('success', 'Purchase return processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing return: '.$e->getMessage());
        }
    }

    public function purchaseReturnIndex()
    {
        $returns = \App\Models\PurchaseReturn::with(['vendor', 'warehouse', 'purchase'])->latest()->get();
        
        // Calculate updated financial details for each return
        $returns->each(function ($return) {
            if ($return->purchase) {
                $purchase = $return->purchase;
                
                // Original Purchase Amounts
                $return->original_net_amount = $purchase->net_amount;
                $return->original_paid_amount = $purchase->paid_amount;
                $return->original_due_amount = $purchase->due_amount;
                
                // Calculate total returns for this purchase
                $totalReturned = \App\Models\PurchaseReturn::where('purchase_id', $purchase->id)
                    ->sum('net_amount');
                
                // New amounts after return(s)
                $return->new_net_amount = max(0, $purchase->net_amount - $totalReturned);
                $return->new_due_amount = max(0, $purchase->due_amount - $return->net_amount);
                $return->total_returned = $totalReturned;
            }
        });

        return view('admin_panel.purchase.purchase_return.index', compact('returns'));
    }

    public function viewReturn($id)
    {
        $return = \App\Models\PurchaseReturn::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);
        return view('admin_panel.purchase.purchase_return.show', compact('return'));
    }
}
