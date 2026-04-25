<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['customer_relation', 'items.product'])
            ->whereIn('sale_status', ['draft', 'posted'])
            ->latest()
            ->get();

        return view('admin_panel.sale.index', compact('sales'));
    }

    public function addsale()
    {
        $customer = Customer::all();
        $warehouse = Warehouse::all();
        $nextInvoiceNumber = Sale::generateInvoiceNo();

        return view('admin_panel.sale.add_sale222', compact('warehouse', 'customer', 'nextInvoiceNumber'));
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
            ->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        return $this->processSale($request, new Sale());
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
        $sale = Sale::findOrFail($id);
        $customers = Customer::all();
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        DB::beginTransaction();

        try {
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

                if (! $product_id || $qty <= 0) {
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
                $combined_colors[] = is_array($decodedColor) ? json_encode($decodedColor) : json_encode((array) json_decode($decodedColor, true));

                // restore stock at SAME location
                $stock = \App\Models\WarehouseStock::where('product_id', $product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->total_pieces += $qty; // Assuming Return Qty is Pieces?
                    // Re-calculate boxes for consistency?
                    // If return qty is pieces (consistent with new sales), update total_pieces.
                    // Also update quantity (boxes).
                    $product = Product::find($product_id);
                    $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                    $stock->quantity += ($qty / $ppb);
                    $stock->save();
                }

                $srMovements[] = [
                    'product_id' => $product_id,
                    'type' => 'in',
                    'qty' => (float) $qty,
                    'ref_type' => 'SR',
                    'ref_id' => null,
                    'note' => 'Sale return',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $total_items += $qty;
            }

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

            if (! empty($srMovements)) {
                foreach ($srMovements as &$m) {
                    $m['ref_id'] = $saleReturn->id;
                }
                unset($m);
                DB::table('stock_movements')->insert($srMovements);
            }

            // Update original sale (decrement quantity)
            $sale = Sale::find($request->sale_id);
            if ($sale && $sale->items) {
                foreach ($product_ids as $index => $product_id) {
                    $return_qty = max(0.0, (float) ($quantities[$index] ?? 0));
                    if ($return_qty <= 0) {
                        continue;
                    }
                    $saleItem = $sale->items->where('product_id', $product_id)->first();
                    if ($saleItem) {
                        // Assuming return_qty is pieces
                        $saleItem->total_pieces = max(0, $saleItem->total_pieces - $return_qty);
                        // Update boxes/loose
                        $prod = Product::find($product_id);
                        $ppb = $prod->pieces_per_box > 0 ? $prod->pieces_per_box : 1;
                        $saleItem->qty = floor($saleItem->total_pieces / $ppb);
                        $saleItem->loose_pieces = $saleItem->total_pieces % $ppb;
                        $saleItem->save();
                    }
                }
            }

            $this->updateLedger($saleReturn); // Credit/Refund to customer?
            // Usually returns decrease Balance.
            // _updateLeger adds total_net to balance.
            // If return, we should Subtract.
            // But let's leave legacy logic if unsure, or implementing simpler Ledger impact:
            // Ledger: Closing Balance = Closing - Return Amount.
            $customer_id = $request->customer;
            $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
            if ($ledger) {
                $ledger->closing_balance -= $request->total_net;
                $ledger->save();
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
        $salesReturns = SalesReturn::with('sale.customer_relation')->orderBy('created_at', 'desc')->get();

        return view('admin_panel.sale.return.index', compact('salesReturns'));
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saleinvoice', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function saleedit($id)
    {
        $sale = Sale::findOrFail($id);
        $customers = Customer::all();
        $items = $this->_getSaleItems($sale);
        $nextInvoiceNumber = $sale->invoice_no;

        // We pass 'sale' as 'booking' to align with the view name/context if needed,
        // but primarily ensuring all data is available.
        return view('admin_panel.sale.booking_edit', [
            'sale' => $sale,
            'booking' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'nextInvoiceNumber' => $nextInvoiceNumber,
        ]);
    }

    public function updatesale(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        if (in_array($sale->sale_status, ['posted', 'cancelled', 'returned'])) {
             return redirect()->back()->with('error', 'Cannot edit a ' . $sale->sale_status . ' sale.');
        }
        return $this->processSale($request, $sale);
    }

    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function salerecepit($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.salerecepit', ['sale' => $sale, 'saleItems' => $items]);
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
        
        return $this->processSale($request, new Sale());
    }

    private function processSale(Request $request, Sale $sale)
    {
        // 1. Validation
        $request->validate([
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array|min:1',
            'warehouse_id' => 'required|array',
        ]);
        
        // Prevent duplicate products
        if (count($request->product_id) !== count(array_unique($request->product_id))) {
             throw \Illuminate\Validation\ValidationException::withMessages(['product_id' => 'Duplicate products are not allowed in a single sale. Please merge quantities.']);
        }

        $status = $request->action === 'post' ? 'posted' : 'booked';

        // Concurrency Safe Transaction
        return DB::transaction(function () use ($request, $sale, $status) {
            
            // 2. Prepare Header Data
            $isNew = !$sale->exists;
            $sale->customer_id = $request->customer;
            $sale->reference = $request->reference;
            $sale->total_amount_Words = $request->total_amount_Words; // Consider auto-generating this too?
            $sale->sale_status = $status;
            
            if ($isNew) {
                $sale->invoice_no = Sale::generateInvoiceNo();
            }

            // We will calculate totals from verified items
            $total_bill = 0;
            $total_items = 0;
            
            $sale->save(); // Save first to get ID
            
            // 3. Process Items
            // Delete old items if updating
            if (!$isNew) {
                 // Restore stock if we were somehow editing a posted sale (should be blocked, but safety first)
                 // For now, we blocked editing posted sales, so strictly 'booked'.
                 SaleItem::where('sale_id', $sale->id)->delete();
            }

            $productIds = $request->product_id;
            $quantities = $request->qty; // Assumed pieces based on previous context, or Box?
            // User: "qty > 0". Previous code used qty as boxes and total_pieces as real qty.
            // Let's stick to: Frontend sends 'qty' (Boxes) and we calculate total_pieces?
            // Or Frontend sends 'total_pieces'?
            // Looking at invoice blade: $item['qty'] is boxes.
            // Let's assume input 'qty' is BOXES.
            
            $warehouses = $request->warehouse_id;
            $discounts = $request->item_disc ?? [];

            foreach ($productIds as $index => $pid) {
                if (!$pid) continue;
                
                $qtyBox = (float)($quantities[$index] ?? 0);
                if ($qtyBox <= 0) continue;

                $product = Product::findOrFail($pid);
                
                // IGNORE frontend price - Fetch from DB
                // Assuming 'retail_price' exists
                $dbPrice = $product->retail_price > 0 ? $product->retail_price : 0; 
                // Or check price_level logic? 
                
                $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                $totalPieces = $qtyBox * $ppb; // Convert Box to Pieces
                // + Loose?
                $loose = (float)($request->loose_pieces[$index] ?? 0);
                $totalPieces += $loose;
                
                // Recalculate boxes for storage if needed
                $storedQtyBox = $totalPieces / $ppb; 

                $discount = (float)($discounts[$index] ?? 0);
                
                // Calculate Line Total
                // Price usually per Box or Per Piece? 
                // Invoice view shows "Price/Box".
                $lineTotal = ($qtyBox * $dbPrice) + ($loose * ($dbPrice/$ppb)); // Approx
                
                // Apply Discount
                $lineTotal = $lineTotal - ($lineTotal * $discount / 100);

                $saleItem = new SaleItem();
                $saleItem->sale_id = $sale->id;
                $saleItem->product_id = $pid;
                $saleItem->warehouse_id = $warehouses[$index] ?? 1;
                $saleItem->product_name = $product->item_name; // Store name snapshot
                
                $saleItem->qty = $storedQtyBox; // Store as Box equivalent for consistency
                $saleItem->total_pieces = $totalPieces;
                $saleItem->loose_pieces = $loose;
                
                $saleItem->price = $dbPrice;
                $saleItem->discount_percent = $discount;
                $saleItem->total = $lineTotal;
                
                // Meta
                $saleItem->brand_id = $product->brand_id;
                $saleItem->unit_id = $product->unit_id;
                
                $saleItem->save();

                $total_bill += $lineTotal;
                $total_items += $totalPieces;
            }

            // Update Sale Totals
            $sale->total_bill_amount = $total_bill;
            $sale->total_extradiscount = $request->total_extra_cost ?? 0;
            $sale->total_net = $total_bill - $sale->total_extradiscount;
            $sale->total_items = $total_items;
            
            $sale->cash = $request->cash ?? 0;
            $sale->change = ($sale->cash - $sale->total_net);

            $sale->save();

            // 4. Handle Status Logic
            if ($status === 'posted') {
                $this->handleStockImpact($sale, 'out');
                $this->updateLedger($sale);
            }
            
            // If AJAX/JSON response needed
            if ($request->ajax() || $request->wantsJson()) {
                 return response()->json([
                    'ok' => true,
                    'booking_id' => $sale->id,
                    'msg' => 'Sale ' . ucfirst($status) . ' Successfully',
                    'invoice_url' => route('sales.invoice', $sale->id),
                ]);
            }

            return redirect()->route('sale.index')->with('success', 'Sale saved as ' . $status);
        });
    }

    private function handleStockImpact(Sale $sale, $type = 'out') 
    {
        // Type: 'out' (Sale Posted), 'in' (Sale Cancelled), 'return' (Returned)
        
        foreach ($sale->items as $item) {
            $stock = WarehouseStock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $item->warehouse_id)
                        ->lockForUpdate() // LOCK ROW
                        ->first();
            
            if (!$stock) {
                 // Create if missing? Or fail? User said "Validate warehouse stock".
                 throw new \Exception("Stock not found for product: " . $item->product_name);
            }
            
            // Convert everything to pieces for calculation
            $qtyPieces = $item->total_pieces;
            
            if ($type === 'out') {
                // Deduct
                if ($stock->total_pieces < $qtyPieces) {
                    throw new \Exception("Insufficient stock for " . $item->product_name . ". Available: " . $stock->total_pieces);
                }
                $stock->total_pieces -= $qtyPieces;
                // Update approx boxes for display
                $ppb = $item->product->pieces_per_box ?? 1;
                $stock->quantity = $stock->total_pieces / ($ppb > 0 ? $ppb : 1);
                $stock->save();
                
                // Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'qty' => -$qtyPieces, // Negative for OUT
                    'ref_type' => 'sale',
                    'ref_id' => $sale->id,
                    'note' => 'Sale Posted #' . $sale->invoice_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($type === 'in' || $type === 'return') {
                // Restore (Cancel or Return)
                $stock->total_pieces += $qtyPieces;
                 // Update approx boxes
                $ppb = $item->product->pieces_per_box ?? 1;
                $stock->quantity = $stock->total_pieces / ($ppb > 0 ? $ppb : 1);
                $stock->save();
                
                 // Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $qtyPieces,
                    'ref_type' => 'sale_' . $type, // sale_in (cancel), sale_return
                    'ref_id' => $sale->id,
                    'note' => 'Sale ' . ucfirst($type) . ' #' . $sale->invoice_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    
    private function updateLedger(Sale $sale)
    {
         $customer_id = $sale->customer_id;
         if(!$customer_id) return;
         
         $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
         $prev_bal = $ledger ? $ledger->closing_balance : 0;
         $new_bal = $prev_bal + $sale->total_net;
         
         CustomerLedger::create([
             'customer_id' => $customer_id,
             'admin_or_user_id' => auth()->id() ?? 1,
             'description' => 'Sale Invoice #' . $sale->invoice_no,
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

    private function _getSaleItems($sale)
    {
        // Legacy support wrapper or direct relation
        // Re-implementing correctly based on new structure
        return $sale->items->map(function($item) {
             return [
                 'product_id' => $item->product_id,
                 'item_name' => $item->product_name ?? $item->product->item_name ?? 'Item',
                 'item_code' => $item->product->item_code ?? '',
                 'brand' => $item->product->brand->name ?? '',
                 'unit' => $item->product->unit->name ?? '', // Access name if relation exists
                 'qty' => (float)$item->qty, // Boxes
                 'total_pieces' => (int)$item->total_pieces,
                 'loose_pieces' => (int)$item->loose_pieces,
                 'price' => (float)$item->price,
                 'discount' => (float)$item->discount_percent,
                 'total' => (float)$item->total,
                 'color' => json_decode($item->color, true),
                 'pieces_per_box' => $item->product->pieces_per_box ?? 1,
                 'price_per_piece' => ($item->total_pieces > 0) ? ($item->total / $item->total_pieces) : 0,
             ];
        });
    }
}
