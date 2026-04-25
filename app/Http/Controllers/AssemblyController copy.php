<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssemblyController extends Controller
{
    public function adjustForm()
    {
        return view('admin_panel.assembly.adjust');
    }

    // ========== NEW: choose best FG to borrow part from ==========
    // App\Http\Controllers\AssemblyController.php (or similar)
    // App\Http\Controllers\AssemblyController.php (or similar)
    // App\Http\Controllers\AssemblyController.php
    public function borrowPartFromBestFg(int $partId, float $shortQty, int $branchId, int $warehouseId)
    {
        if ($shortQty <= 0) {
            return;
        }

        DB::beginTransaction();
        try {
            $candidates = DB::table('product_boms as pb')
                ->leftJoin('warehouse_stocks as s', function ($join) use ($warehouseId) {
                    $join->on('s.product_id', '=', 'pb.product_id')
                        ->where('s.warehouse_id', '=', $warehouseId);
                })
                ->select('pb.product_id as fg_id', 'pb.qty_per_unit', DB::raw('COALESCE(s.quantity,0) as fg_stock'))
                ->where('pb.part_id', $partId)
                ->orderByDesc('fg_stock')
                ->get();

            if ($candidates->isEmpty()) {
                throw new \Exception("No FG contains part {$partId}");
            }

            $remaining = $shortQty;

            foreach ($candidates as $cand) {
                if ($remaining <= 0) {
                    break;
                }

                $fgId = (int) $cand->fg_id;
                $perFg = (float) $cand->qty_per_unit;
                if ($perFg <= 0) {
                    continue;
                }

                // lock fg stock
                $fgStock = \App\Models\WarehouseStock::where('product_id', $fgId)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                $fgOnHand = (float) ($fgStock->quantity ?? 0);
                if ($fgOnHand <= 0) {
                    continue;
                }

                $fgNeeded = (int) ceil($remaining / $perFg);
                $fgToConsume = min($fgNeeded, (int) $fgOnHand);
                if ($fgToConsume <= 0) {
                    continue;
                }

                // decrement FG
                $fgStock->quantity -= $fgToConsume;
                $fgStock->save();

                // increment only requested part
                $partsObtained = $fgToConsume * $perFg;

                // create or lock part stock
                \DB::table('warehouse_stocks')->insertOrIgnore([
                    'product_id' => $partId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'price' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $partStock = \App\Models\WarehouseStock::where('product_id', $partId)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $partStock) {
                    $partStock = \App\Models\WarehouseStock::create([
                        'product_id' => $partId,
                        'warehouse_id' => $warehouseId,
                        'quantity' => 0,
                        'price' => 0,
                    ]);
                }

                $partStock->quantity += $partsObtained;
                $partStock->save();

                // write movements: FG out, PART in
                DB::table('stock_movements')->insert([
                    [
                        'product_id' => $fgId,
                        'type' => 'out',
                        'qty' => -1 * $fgToConsume,
                        'ref_type' => 'AUTO_PLUCK',
                        'ref_id' => null,
                        'note' => "Auto-pluck: FG {$fgId} -> create part {$partId}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'product_id' => $partId,
                        'type' => 'in',
                        'qty' => (float) $partsObtained,
                        'ref_type' => 'AUTO_PLUCK',
                        'ref_id' => null,
                        'note' => "Auto-pluck: FG {$fgId} -> part {$partId}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

                $remaining = max(0, $remaining - $partsObtained);
            }

            if ($remaining > 0) {
                throw new \Exception("Unable to auto-cover {$shortQty} of part {$partId}, remaining {$remaining}");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('borrowPartFromBestFg failed', ['part' => $partId, 'err' => $e->getMessage()]);
            throw $e;
        }
    }

    // ========== NEW: ensure FG available by auto-assembling ==========
    // private function ensureFgForSale(int $fgId, float $saleUnitsNeeded, int $branchId, int $warehouseId): float
    public function ensureFgForSale(int $fgId, float $saleUnitsNeeded, int $branchId, int $warehouseId): float
    {
        if ($saleUnitsNeeded <= 0) {
            return 0.0;
        }

        // How many FG currently on-hand?
        $fgOnHand = (float) (DB::table('warehouse_stocks')->where('product_id', $fgId)->where('warehouse_id', $warehouseId)->sum('quantity') ?? 0);
        $shortUnits = max(0.0, $saleUnitsNeeded - $fgOnHand);
        if ($shortUnits <= 0) {
            return 0.0;
        }

        // How many units can we assemble now?
        $bom = DB::table('product_boms as pb')
            ->leftJoin('warehouse_stocks as v', function ($join) use ($warehouseId) {
                $join->on('v.product_id', '=', 'pb.part_id')
                    ->where('v.warehouse_id', '=', $warehouseId);
            })
            ->where('pb.product_id', $fgId)
            ->select('pb.part_id', 'pb.qty_per_unit', DB::raw('COALESCE(v.quantity,0) as avail'))
            ->get();

        if ($bom->isEmpty()) {
            return 0.0;
        }

        $maxBuild = (int) floor(collect($bom)->map(function ($r) {
            $rpu = (float) $r->qty_per_unit;
            $av = (float) $r->avail;

            return $rpu > 0 ? floor($av / $rpu) : INF;
        })->min());

        if ($maxBuild <= 0) {
            return 0.0;
        }

        $buildNow = (float) min($shortUnits, $maxBuild);
        // Reuse existing performAssembly
        $this->performAssembly($fgId, $buildNow, $branchId, $warehouseId);

        return $buildNow;
    }

    private function upsertStocks(int $productId, float $qtyDelta, int $branchId = 1, int $warehouseId = 1): void
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

    // ===== List page of assembled products (uses snapshot) =====
    public function index()
    {
        $products = Product::where('is_assembled', 1)->get();
        $rows = $products->map(fn ($p) => $this->snapshot($p->id));

        return view('admin_panel.assembly.index', compact('rows'));
    }

    // ===== Single product report (AUTO-ASSEMBLE on load) =====
    public function show(Product $product, Request $request)
    {
        abort_unless($product->is_assembled, 404);

        $branchId = (int) ($request->input('branch_id', 1));
        $warehouseId = (int) ($request->input('warehouse_id', 1));

        // BEFORE snapshot
        $snap = $this->snapshot($product->id);

        // Only assemble when user explicitly asks ?auto=1
        if ($request->boolean('auto')) {
            $qtyToBuild = (float) ($snap['assemble_possible'] ?? 0);
            if ($qtyToBuild > 0) {
                $this->performAssembly($product->id, $qtyToBuild, $branchId, $warehouseId);
                session()->flash('success', 'Auto-assembled '.$qtyToBuild.' unit(s).');
                // AFTER snapshot
                $snap = $this->snapshot($product->id);
            } else {
                session()->flash('info', 'No units can be assembled right now.');
            }
        }

        return view('admin_panel.assembly.show', compact('product', 'snap'));
    }

    // ===== Shared assembly routine =====
    private function performAssembly(int $productId, float $qtyToBuild, int $branchId, int $warehouseId): void
    {
        DB::transaction(function () use ($productId, $qtyToBuild, $branchId, $warehouseId) {
            $bom = DB::table('product_boms')->where('product_id', $productId)->get();

            // consume parts
            foreach ($bom as $row) {
                $consume = $qtyToBuild * (float) $row->qty_per_unit;

                DB::table('stock_movements')->insert([
                    'product_id' => $row->part_id,
                    'type' => 'assembly_out',
                    'qty' => -1 * $consume,
                    'ref_type' => 'ASSEMBLY',
                    'ref_id' => $productId,
                    'note' => 'Assembly consume (auto)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->upsertStocks((int) $row->part_id, -1 * $consume, $branchId, $warehouseId);
            }

            // add finished good
            DB::table('stock_movements')->insert([
                'product_id' => $productId,
                'type' => 'assembly_in',
                'qty' => $qtyToBuild,
                'ref_type' => 'ASSEMBLY',
                'ref_id' => null,
                'note' => 'Assembly produced (auto)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->upsertStocks($productId, $qtyToBuild, $branchId, $warehouseId);
        });
    }

    // ===== NEW: Auto-cover part sale from a specific FG (NO manual pluck needed) =====
    // Call this from your POS before reducing part stock.
    // It will automatically borrow (de-kit) parts from the given assembled product to cover shortage.
    public function ensurePartForSale(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id', // FG to borrow from
            'part_id' => 'required|integer',
            'sale_qty' => 'required|numeric|gt:0',       // how many parts are being sold right now
            'branch_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
        ]);

        $productId = (int) $request->product_id; // FG
        $partId = (int) $request->part_id;
        $saleQty = (float) $request->sale_qty;
        $branchId = (int) ($request->branch_id ?? 1);
        $warehouseId = (int) ($request->warehouse_id ?? 1);

        // BOM lookup
        $bomRow = DB::table('product_boms')
            ->where('product_id', $productId)
            ->where('part_id', $partId)
            ->first();

        if (! $bomRow || (float) $bomRow->qty_per_unit <= 0) {
            // this FG cannot provide that part; return as-is
            return response()->json([
                'covered_from_fg' => 0.0,
                'remaining_shortage' => 0.0, // we don't decide shortage if FG irrelevant
                'message' => 'Part not in FG BOM; no auto-borrow performed.',
            ]);
        }

        $qtyPerUnit = (float) $bomRow->qty_per_unit;

        // On-hand part
        $partOnHand = (float) (DB::table('warehouse_stocks')
            ->where('product_id', $partId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity') ?? 0);

        // Shortage against sale qty
        $shortage = max(0.0, $saleQty - $partOnHand);
        if ($shortage <= 0) {
            return response()->json([
                'covered_from_fg' => 0.0,
                'remaining_shortage' => 0.0,
                'message' => 'Sufficient part stock; no auto-borrow needed.',
            ]);
        }

        // How many FG units can be converted to cover shortage?
        $unitsNeeded = $shortage / $qtyPerUnit; // fractional units ok
        $fgOnHand = (float) (DB::table('warehouse_stocks')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity') ?? 0);

        if ($fgOnHand <= 0) {
            return response()->json([
                'covered_from_fg' => 0.0,
                'remaining_shortage' => $shortage,
                'message' => 'No finished stock to borrow from.',
            ], 409);
        }

        // We can only cover up to available FG
        $unitsToConvert = min($unitsNeeded, $fgOnHand);
        $partsToAdd = $unitsToConvert * $qtyPerUnit; // <= this is how many part units we can create now

        DB::transaction(function () use ($productId, $partId, $partsToAdd, $unitsToConvert, $branchId, $warehouseId) {
            // FG down (auto-borrow)
            DB::table('stock_movements')->insert([
                'product_id' => $productId,
                'type' => 'out',
                'qty' => -1 * $unitsToConvert,
                'ref_type' => 'AUTO_BORROW',
                'ref_id' => $partId,
                'note' => 'Auto-borrow for part sale',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->upsertStocks($productId, -1 * $unitsToConvert, $branchId, $warehouseId);

            // Part up
            DB::table('stock_movements')->insert([
                'product_id' => $partId,
                'type' => 'in',
                'qty' => $partsToAdd,
                'ref_type' => 'AUTO_BORROW',
                'ref_id' => $productId,
                'note' => 'Auto-borrow for part sale',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->upsertStocks($partId, $partsToAdd, $branchId, $warehouseId);
        });

        $remainingShort = max(0.0, $shortage - $partsToAdd);

        return response()->json([
            'covered_from_fg' => $partsToAdd,       // part qty auto-created right now
            'remaining_shortage' => $remainingShort,   // if >0, even FG couldn't fully cover
            'message' => $remainingShort > 0
                                    ? 'Partially covered from FG; still short.'
                                    : 'Fully covered from FG; proceed to sell.',
        ]);
    }

    // ===== Snapshot helper =====
    private function snapshot(int $productId): array
    {
        $product = Product::findOrFail($productId);

        // Use global sum for snapshot overview or hardcode warehouse 1 (Assuming 1 for overview)
        // Better: sum all warehouses
        $ready = (float) (DB::table('warehouse_stocks')
            ->where('product_id', $productId)
            ->sum('quantity') ?? 0);

        $parts = DB::table('product_boms as pb')
            ->join('products as p', 'p.id', '=', 'pb.part_id')
            ->leftJoin('warehouse_stocks as v', 'v.product_id', '=', 'pb.part_id')
            ->where('pb.product_id', $productId)
            ->groupBy('pb.id', 'pb.product_id', 'pb.part_id', 'pb.qty_per_unit', 'p.item_name', 'p.item_code')
            ->selectRaw('
                pb.part_id,
                p.item_name,
                p.item_code,
                pb.qty_per_unit,
                COALESCE(SUM(v.quantity),0) as available_qty
            ')
            ->get();

        // Assemblable
        $assemblePossible = $parts->count()
            ? (int) floor(collect($parts)->map(function ($r) {
                return $r->qty_per_unit > 0 ? floor($r->available_qty / $r->qty_per_unit) : INF;
            })->min())
            : 0;

        // Shortages NEXT unit
        $shortagesNext = [];
        $targetUnits = $assemblePossible + 1;
        foreach ($parts as $r) {
            $needTarget = $targetUnits * (float) $r->qty_per_unit;
            $short = max(0, $needTarget - (float) $r->available_qty);
            if ($short > 0) {
                $shortagesNext[] = [
                    'part_id' => $r->part_id,
                    'name' => $r->item_name,
                    'code' => $r->item_code,
                    'need' => $needTarget,
                    'have' => (float) $r->available_qty,
                    'shortage' => $short,
                ];
            }
        }

        // Shortages for 1 unit (current)
        $shortages1 = [];
        foreach ($parts as $r) {
            $need1 = (float) $r->qty_per_unit;
            $short = max(0, $need1 - (float) $r->available_qty);
            if ($short > 0) {
                $shortages1[] = [
                    'part_id' => $r->part_id,
                    'name' => $r->item_name,
                    'code' => $r->item_code,
                    'need' => $need1,
                    'have' => (float) $r->available_qty,
                    'shortage' => $short,
                ];
            }
        }

        // NEW: Max pluckable per part from current FG ready
        $fgReady = $ready;
        $pluckable = [];
        $sellableNow = []; // Available part + pluckable
        foreach ($parts as $r) {
            $rpu = (float) $r->qty_per_unit; // per FG unit
            $maxFromFg = $rpu > 0 ? $fgReady * $rpu : 0.0;
            $pluckable[$r->part_id] = $maxFromFg;
            $sellableNow[$r->part_id] = (float) $r->available_qty + $maxFromFg;
        }

        return [
            'product_id' => $productId,
            'product_name' => $product->item_name,
            'ready_stock' => $ready,
            'assemble_possible' => $assemblePossible,
            'total_sellable' => $ready + $assemblePossible,
            'parts' => $parts,
            'shortages_for_1' => $shortages1,
            'shortages_for_next' => $shortagesNext,
            'pluckable_from_fg' => $pluckable,     // max parts derivable from FG
            'sellable_parts_now' => $sellableNow,   // available + pluckable (no manual action)
        ];
    }
}
