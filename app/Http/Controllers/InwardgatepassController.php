<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InwardGatepass;
use App\Models\InwardGatepassItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InwardgatepassController extends Controller
{
    public function pdf($id)
    {
        $gatepass = InwardGatepass::with(['branch', 'warehouse', 'vendor', 'items.product'])->findOrFail($id);
        $pdf = Pdf::loadView('admin_panel.inward.pdf', compact('gatepass'));

        return $pdf->download('gatepass_'.$gatepass->id.'.pdf');
    }

    // LIST
    public function index()
    {
        $gatepasses = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')->latest()->get();

        return view('admin_panel.inward.index', compact('gatepasses'));
    }

    // CREATE FORM
    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $vendors = Vendor::orderBy('name')->get();

        return view('admin_panel.inward.create', compact('branches', 'warehouses', 'vendors'));
    }

    // STORE (movements + stocks)
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'vendor_id' => 'required|exists:vendors,id',
            'gatepass_date' => 'required|date',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:200',
            'transport_name' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($request) {
            $gatepass = InwardGatepass::create([
                'branch_id' => $request->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'vendor_id' => $request->vendor_id,
                'gatepass_date' => $request->gatepass_date,
                'note' => $request->note,
                'transport_name' => $request->transport_name,
                'created_by' => auth()->id(),
                'status' => 'pending',
            ]);

            $pids = $request->input('product_id', []);
            $qtys = $request->input('qty', []);

            $now = now();
            $movementRows = [];

            for ($i = 0; $i < count($pids); $i++) {
                $pid = (int) ($pids[$i] ?? 0);
                $q = (float) ($qtys[$i] ?? 0);
                if (! $pid || $q <= 0) {
                    continue;
                }

                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id' => $pid,
                    'qty' => $q,
                ]);

                // movement (+)
                $movementRows[] = [
                    'product_id' => $pid,
                    'type' => 'in',
                    'qty' => $q,
                    'ref_type' => 'INWARD',
                    'ref_id' => $gatepass->id,
                    'note' => 'Inward gatepass',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // stocks upsert
                $this->upsertStocks($pid, +$q, (int) $request->branch_id, (int) $request->warehouse_id);
            }

            if (! empty($movementRows)) {
                DB::table('stock_movements')->insert($movementRows);
            }
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Created Successfully');
    }

    // SHOW
    public function show($id)
    {
        $gatepass = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')->findOrFail($id);

        return view('admin_panel.inward.show', compact('gatepass'));
    }

    // EDIT FORM
    public function edit($id)
    {
        $gatepass = InwardGatepass::with('items')->findOrFail($id);
        $branches = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $vendors = Vendor::orderBy('name')->get();

        return view('admin_panel.inward.edit', compact('gatepass', 'branches', 'warehouses', 'vendors'));
    }

    // UPDATE (delta movements + stocks)
    public function update(Request $request, $id)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'vendor_id' => 'required|exists:vendors,id',
            'gatepass_date' => 'required|date',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:200',
            'transport_name' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($request, $id) {
            $gatepass = InwardGatepass::with('items')->findOrFail($id);
            $oldBranch = (int) $gatepass->branch_id;
            $oldWh = (int) $gatepass->warehouse_id;

            // old totals per product
            $oldMap = $gatepass->items->groupBy('product_id')->map(fn ($g) => (float) $g->sum('qty'));

            // new items map
            $pids = $request->input('product_id', []);
            $qtys = $request->input('qty', []);
            $newMap = collect();
            for ($i = 0; $i < count($pids); $i++) {
                $pid = (int) ($pids[$i] ?? 0);
                $q = (float) ($qtys[$i] ?? 0);
                if (! $pid || $q <= 0) {
                    continue;
                }
                $newMap[$pid] = ($newMap[$pid] ?? 0) + $q;
            }

            // header update
            $gatepass->update([
                'branch_id' => $request->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'vendor_id' => $request->vendor_id,
                'gatepass_date' => $request->gatepass_date,
                'note' => $request->note,
                'transport_name' => $request->transport_name,
            ]);

            // replace items
            InwardGatepassItem::where('inward_gatepass_id', $gatepass->id)->delete();
            foreach ($newMap as $pid => $q) {
                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id' => $pid,
                    'qty' => $q,
                ]);
            }

            // deltas
            $now = now();
            $movs = [];
            $allKeys = $oldMap->keys()->merge($newMap->keys())->unique();

            foreach ($allKeys as $pid) {
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
                    'ref_type' => 'INWARD_EDIT',
                    'ref_id' => $gatepass->id,
                    'note' => 'Inward edit delta',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // stocks adjust on NEW branch/wh (simple approach).
                // If branch/wh changed, you may also want to reverse old and add to new; for now we apply on new header.
                $this->upsertStocks((int) $pid, ($type === 'in' ? +$qty : -$qty), (int) $request->branch_id, (int) $request->warehouse_id);
            }

            if (! empty($movs)) {
                DB::table('stock_movements')->insert($movs);
            }
        });

        return redirect()->route('InwardGatepass.home')->with('success', 'Inward Gatepass Updated Successfully');
    }

    // DELETE (reverse movements + stocks)
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $gatepass = InwardGatepass::with('items')->findOrFail($id);
            $now = now();
            $movs = [];

            foreach ($gatepass->items as $item) {
                // log reverse movement
                $movs[] = [
                    'product_id' => (int) $item->product_id,
                    'type' => 'out',
                    'qty' => (float) $item->qty,
                    'ref_type' => 'INWARD_DELETE',
                    'ref_id' => $gatepass->id,
                    'note' => 'Delete inward (reverse)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // direct stock rollback (NO upsertStocks)
                $stock = \App\Models\WarehouseStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $gatepass->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $newQty = max(0, $stock->quantity - $item->qty);
                    $stock->quantity = $newQty;
                    $stock->save();
                }
            }

            if (! empty($movs)) {
                DB::table('stock_movements')->insert($movs);
            }

            InwardGatepassItem::where('inward_gatepass_id', $gatepass->id)->delete();
            $gatepass->delete();
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Deleted Successfully');
    }

    // PRODUCT SEARCH (grouped where fix)
    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');
        $products = Product::with('brand')
            ->where(function ($x) use ($q) {
                $x->where('item_name', 'like', "%{$q}%")
                    ->orWhere('item_code', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    // --- small helper (same as ProductController) ---
    private function upsertStocks(int $productId, float $qtyDelta, int $branchId, int $warehouseId): void
    {
        $stock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->quantity += $qtyDelta;
            $stock->save();
        } else {
            \App\Models\WarehouseStock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => $qtyDelta,
                'price' => 0,
            ]);
        }
    }
}
