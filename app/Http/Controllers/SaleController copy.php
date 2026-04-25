<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = Sale::with(['customer_relation', 'product_relation'])->get();

        return view('admin_panel.sale.index', compact('sales'));
    }

    public function addsale()
    {
        $products = Product::get();
        $Customer = Customer::get();

        return view('admin_panel.sale.add_sale', compact('products', 'Customer'));
    }

    public function searchpname(Request $request)
    {
        $q = $request->get('q');

        $products = Product::with(['brand'])
            // only products with active discount
            ->where(function ($query) use ($q) {
                $query->where('item_name', 'like', "%{$q}%")
                    ->orWhere('item_code', 'like', "%{$q}%")
                    ->orWhere('barcode_path', 'like', "%{$q}%");
            })
            ->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $action = $request->input('action'); // 'booking' or 'sale'
        $booking_id = $request->booking_id;

        $branchId = (int) ($request->input('branch_id', 1));
        $warehouseId = (int) ($request->input('warehouse_id', 1));

        DB::beginTransaction();

        try {
            $saleMovements = [];

            // request arrays
            $product_ids = $request->product_id ?? [];
            $product_names = $request->product ?? $request->product_id;
            $product_codes = $request->item_code ?? [];
            $brands = $request->uom ?? [];
            $units = $request->unit ?? [];
            $prices = $request->price ?? [];
            $discounts = $request->item_disc ?? [];
            $quantities = $request->qty ?? [];
            $totals = $request->total ?? [];
            $colors = $request->color ?? [];

            $combined_products = $combined_codes = $combined_brands = $combined_units = [];
            $combined_prices = $combined_discounts = $combined_qtys = $combined_totals = $combined_colors = [];

            $total_items = 0;

            foreach ($product_ids as $index => $product_id_raw) {
                $product_id = (int) $product_id_raw;
                $qty = max(0.0, (float) ($quantities[$index] ?? 0));
                $price = max(0.0, (float) ($prices[$index] ?? 0));

                if (! $product_id || $qty <= 0 || $price <= 0) {
                    continue;
                }

                if ($action === 'sale') {
                    // lock and read stock row for product being sold
                    $stockRow = Stock::where('product_id', $product_id)
                        ->where('branch_id', $branchId)
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()
                        ->first();

                    $onHand = (float) ($stockRow->qty ?? 0);
                    $short = max(0.0, $qty - $onHand);

                    if ($short > 0) {
                        // decide FG vs PART
                        $isBomParent = DB::table('product_boms')->where('product_id', $product_id)->exists();
                        $isBomChild  = DB::table('product_boms')->where('part_id', $product_id)->exists();

                        \Log::info('SALE_AUTO_COVER_DECISION', [
                            'product_id'   => $product_id,
                            'product_name' => optional(Product::find($product_id))->item_name,
                            'isBomParent'  => $isBomParent,
                            'isBomChild'   => $isBomChild,
                            'qty_requested'=> $qty,
                            'onHand'       => $onHand,
                            'short'        => $short,
                            'branch'       => $branchId,
                            'warehouse'    => $warehouseId,
                        ]);

                        if ($isBomParent && ! $isBomChild) {
                            // FG sold — try assemble FG
                            app(AssemblyController::class)
                                ->ensureFgForSale($product_id, $short, $branchId, $warehouseId);
                        } else {
                            // PART sold — borrow only that part from best FG(s)
                            // IMPORTANT: borrowPartFromBestFg must NOT de-kit all parts.
                            app(AssemblyController::class)
                                ->borrowPartFromBestFg($product_id, $short, $branchId, $warehouseId);
                        }

                        // refresh same product stock row only
                        $stockRow = Stock::where('product_id', $product_id)
                            ->where('branch_id', $branchId)
                            ->where('warehouse_id', $warehouseId)
                            ->lockForUpdate()
                            ->first();

                        $onHand = (float) ($stockRow->qty ?? 0);
                        if ($onHand < $qty) {
                            throw new \Exception(
                                'Not enough stock even after auto-cover for product: ' . ($product_names[$index] ?? $product_id)
                            );
                        }
                    }

                    if (! $stockRow) {
                        throw new \Exception("Stock record not found for product ID {$product_id} (branch:{$branchId}, wh:{$warehouseId}).");
                    }

                    // decrement only the sold product
                    $stockRow->qty -= $qty;
                    $stockRow->save();

                    $saleMovements[] = [
                        'product_id' => $product_id,
                        'type'       => 'out',
                        'qty'        => -1 * (float) $qty,
                        'ref_type'   => 'SO',
                        'ref_id'     => null,
                        'note'       => 'POS sale',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // collect sale model fields
                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $prices[$index] ?? 0;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $quantities[$index] ?? 0;
                $combined_totals[] = $totals[$index] ?? 0;
                $combined_colors[] = json_encode($colors[$index] ?? []);

                $total_items += $qty;
            }

            // save sale / booking model
            $model = ($action === 'booking')
                ? ($booking_id ? ProductBooking::findOrFail($booking_id) : new ProductBooking)
                : new Sale;

            $model->customer = $request->customer;
            $model->reference = $request->reference;
            $model->product = implode(',', $combined_products);
            $model->product_code = implode(',', $combined_codes);
            $model->brand = implode(',', $combined_brands);
            $model->unit = implode(',', $combined_units);
            $model->per_price = implode(',', $combined_prices);
            $model->per_discount = implode(',', $combined_discounts);
            $model->qty = implode(',', $combined_qtys);
            $model->per_total = implode(',', $combined_totals);
            $model->color = json_encode($combined_colors);
            $model->total_amount_Words = $request->total_amount_Words;
            $model->total_bill_amount = $request->total_subtotal;
            $model->total_extradiscount = $request->total_extra_cost;
            $model->total_net = $request->total_net;
            $model->cash = $request->cash;
            $model->card = $request->card;
            $model->change = $request->change;
            $model->total_items = $total_items;
            $model->save();

            // attach ref_id and insert movements
            if ($action === 'sale' && ! empty($saleMovements)) {
                foreach ($saleMovements as &$m) {
                    $m['ref_id'] = $model->id;
                }
                unset($m);

                DB::table('stock_movements')->insert($saleMovements);
            }

            // ledger update for sale ...
            if ($action === 'sale') {
                $customer_id = $request->customer;
                $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance += $request->total_net;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $customer_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'closing_balance'  => $request->total_net,
                        'opening_balance'  => $request->total_net,
                    ]);
                }
            }

            DB::commit();

            return back()->with('success', $action === 'sale' ? 'Sale completed.' : 'Booking updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SALE_STORE_ERROR', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Convert booking to sale form prefill.
     */
    public function convertFromBooking($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $customers = Customer::all();

        // Decode fields
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
            // Find product name using item_code or product_name
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p, // This will appear in input box
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'uom'        => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit_id ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer'      => $customers,
            'booking'       => $booking,
            'bookingItems'  => $items,
        ]);
    }

    // sale return start
    public function saleretun($id)
    {
        $sale = Sale::findOrFail($id);
        $customers = Customer::all();

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes = explode(',', $sale->product_code);
        $brands = explode(',', $sale->brand);
        $units = explode(',', $sale->unit);
        $prices = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys = explode(',', $sale->qty);
        $totals = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''), // <-- change here
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.return.create', [
            'sale'      => $sale,
            'Customer'  => $customers,
            'saleItems' => $items,
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        DB::beginTransaction();

        try {
            // keep same location as sale (hidden fields in blade)
            $branchId = (int) ($request->input('branch_id', 1));
            $warehouseId = (int) ($request->input('warehouse_id', 1));

            $srMovements = [];

            $product_ids = $request->product_id ?? [];
            $product_names = $request->product ?? [];
            $product_codes = $request->item_code ?? [];
            $brands = $request->uom ?? [];
            $units = $request->unit ?? [];
            $prices = $request->price ?? [];
            $discounts = $request->item_disc ?? [];
            $quantities = $request->qty ?? [];
            $totals = $request->total ?? [];
            $colors = $request->color ?? [];

            $combined_products = $combined_codes = $combined_brands = $combined_units = [];
            $combined_prices = $combined_discounts = $combined_qtys = $combined_totals = $combined_colors = [];

            $total_items = 0;

            foreach ($product_ids as $index => $product_id) {
                $qty = max(0.0, (float) ($quantities[$index] ?? 0));
                $price = max(0.0, (float) ($prices[$index] ?? 0));

                if (! $product_id || $qty <= 0 || $price <= 0) {
                    continue;
                }

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $price;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $qty;
                $combined_totals[] = $totals[$index] ?? 0;

                $decodedColor = $colors[$index] ?? [];
                $combined_colors[] = is_array($decodedColor)
                    ? json_encode($decodedColor)
                    : json_encode((array) json_decode($decodedColor, true));

                // restore stock at SAME location (lock row to avoid race)
                $stock = Stock::where('product_id', $product_id)
                    ->where('branch_id', $branchId)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->qty += $qty;
                    $stock->save();
                } else {
                    Stock::create([
                        'product_id'   => $product_id,
                        'branch_id'    => $branchId,
                        'warehouse_id' => $warehouseId,
                        'qty'          => $qty,
                        'reserved_qty' => 0,
                    ]);
                }

                // movement queue (IN) → ref_id after save
                $srMovements[] = [
                    'product_id' => $product_id,
                    'type'       => 'in',
                    'qty'        => (float) $qty,
                    'ref_type'   => 'SR',
                    'ref_id'     => null,
                    'note'       => 'Sale return',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $total_items += $qty;
            }

            // create Sale Return first
            $saleReturn = new SalesReturn;
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
            $saleReturn->save();

            // insert movements with proper ref_id
            if (! empty($srMovements)) {
                foreach ($srMovements as &$m) {
                    $m['ref_id'] = $saleReturn->id;
                }
                unset($m);

                DB::table('stock_movements')->insert($srMovements);
            }

            // update original sale
            $sale = Sale::find($request->sale_id);
            if ($sale) {
                $sale_qtys = array_map('floatval', explode(',', $sale->qty));
                $sale_totals = array_map('floatval', explode(',', $sale->per_total));
                $sale_prices = array_map('floatval', explode(',', $sale->per_price));

                foreach ($product_ids as $index => $product_id) {
                    $return_qty = max(0.0, (float) ($quantities[$index] ?? 0));
                    if ($return_qty > 0 && isset($sale_qtys[$index])) {
                        $sale_qtys[$index] = max(0.0, $sale_qtys[$index] - $return_qty);
                        $price = $sale_prices[$index] ?? 0.0;
                        $sale_totals[$index] = $price * $sale_qtys[$index];
                    }
                }

                $sale->qty = implode(',', $sale_qtys);
                $sale->per_total = implode(',', $sale_totals);
                $sale->total_net = array_sum($sale_totals);
                $sale->total_bill_amount = $sale->total_net;
                $sale->total_items = array_sum($sale_qtys);
                $sale->save();
            }

            // ledger impact
            $customer_id = $request->customer;
            $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();

            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance = $ledger->closing_balance - $request->total_net;
                $ledger->save();
            } else {
                CustomerLedger::create([
                    'customer_id'      => $customer_id,
                    'admin_or_user_id' => auth()->id(),
                    'previous_balance' => 0,
                    'closing_balance'  => 0 - $request->total_net,
                    'opening_balance'  => 0 - $request->total_net,
                ]);
            }

            DB::commit();

            return redirect()->route('sale.index')->with('success', 'Sale return saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Sale return failed: '.$e->getMessage());
        }
    }

    public function salereturnview()
    {
        // Fetch all sale returns with the original sale and customer info
        $salesReturns = SalesReturn::with('sale.customer_relation')->orderBy('created_at', 'desc')->get();

        return view('admin_panel.sale.return.index', [
            'salesReturns' => $salesReturns,
        ]);
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes = explode(',', $sale->product_code);
        $brands = explode(',', $sale->brand);
        $units = explode(',', $sale->unit);
        $prices = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys = explode(',', $sale->qty);
        $totals = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.saleinvoice', [
            'sale'      => $sale,
            'saleItems' => $items,
        ]);
    }

    public function saleedit($id)
    {
        $sale = Sale::findOrFail($id);
        $customers = Customer::all();

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes = explode(',', $sale->product_code);
        $brands = explode(',', $sale->brand);
        $units = explode(',', $sale->unit);
        $prices = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys = explode(',', $sale->qty);
        $totals = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''), // <-- change here
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.saleedit', [
            'sale'      => $sale,
            'Customer'  => $customers,
            'saleItems' => $items,
        ]);
    }

    public function updatesale(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // --- Arrays from request ---
            $product_ids = $request->product_id;
            $product_names = $request->product ?? []; // ✅ ab match karega
            $product_codes = $request->item_code;
            $brands = $request->brand;  // ✅ request me brand aata hai
            $units = $request->unit;
            $prices = $request->price;
            $discounts = $request->item_disc;
            $quantities = $request->qty;
            $totals = $request->total;
            $colors = $request->color;

            $combined_products = [];
            $combined_codes = [];
            $combined_brands = [];
            $combined_units = [];
            $combined_prices = [];
            $combined_discounts = [];
            $combined_qtys = [];
            $combined_totals = [];
            $combined_colors = [];

            $total_items = 0;

            foreach ($product_ids as $index => $product_id) {
                $qty = $quantities[$index] ?? 0;
                $price = $prices[$index] ?? 0;

                if (! $product_id || ! $qty || ! $price) {
                    continue;
                }

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $prices[$index] ?? 0;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $quantities[$index] ?? 0;
                $combined_totals[] = $totals[$index] ?? 0;
                $combined_colors[] = json_encode($colors[$index] ?? []);

                $total_items += $qty;
            }

            // --- Find existing Sale ---
            $sale = Sale::findOrFail($id);

            // Save old total before update
            $old_total = $sale->total_net;

            // --- Fill fields ---
            $sale->customer = $request->customer;
            $sale->reference = $request->reference;
            $sale->product = implode(',', $combined_products);
            $sale->product_code = implode(',', $combined_codes);
            $sale->brand = implode(',', $combined_brands);
            $sale->unit = implode(',', $combined_units);
            $sale->per_price = implode(',', $combined_prices);
            $sale->per_discount = implode(',', $combined_discounts);
            $sale->qty = implode(',', $combined_qtys);
            $sale->per_total = implode(',', $combined_totals);
            $sale->color = json_encode($combined_colors);
            $sale->total_amount_Words = $request->total_amount_Words;
            $sale->total_bill_amount = $request->total_subtotal;
            $sale->total_extradiscount = $request->total_extra_cost;
            $sale->total_net = $request->total_net;
            $sale->cash = $request->cash;
            $sale->card = $request->card;
            $sale->change = $request->change;
            $sale->total_items = $total_items;
            $sale->save();

            // Ledger update
            $customer_id = $request->customer;
            $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();

            // Difference nikal lo
            $difference = $request->total_net - $old_total;

            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance = $ledger->closing_balance + $difference;
                $ledger->save();
            } else {
                CustomerLedger::create([
                    'customer_id'      => $customer_id,
                    'admin_or_user_id' => auth()->id(),
                    'previous_balance' => 0,
                    'closing_balance'  => $request->total_net,
                    'opening_balance'  => $request->total_net,
                ]);
            }

            DB::commit();

            return redirect()->route('sale.index')->with('success', 'Sale updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes = explode(',', $sale->product_code);
        $brands = explode(',', $sale->brand);
        $units = explode(',', $sale->unit);
        $prices = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys = explode(',', $sale->qty);
        $totals = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.saledc', [
            'sale'      => $sale,
            'saleItems' => $items,
        ]);
    }

    public function salerecepit($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);

        // Decode sale pivot or comma fields
        $products = explode(',', $sale->product);
        $codes = explode(',', $sale->product_code);
        $brands = explode(',', $sale->brand);
        $units = explode(',', $sale->unit);
        $prices = explode(',', $sale->per_price);
        $discounts = explode(',', $sale->per_discount);
        $qtys = explode(',', $sale->qty);
        $totals = explode(',', $sale->per_total);
        $colors_json = json_decode($sale->color, true);

        $items = [];

        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name'  => $product->item_name ?? $p,
                'item_code'  => $product->item_code ?? ($codes[$index] ?? ''),
                'brand'      => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit'       => $product->unit ?? ($units[$index] ?? ''),
                'price'      => floatval($prices[$index] ?? 0),
                'discount'   => floatval($discounts[$index] ?? 0),
                'qty'        => intval($qtys[$index] ?? 1),
                'total'      => floatval($totals[$index] ?? 0),
                'color'      => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.salerecepit', [
            'sale'      => $sale,
            'saleItems' => $items,
        ]);
    }
}
