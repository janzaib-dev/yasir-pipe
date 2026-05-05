<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\DNS1D;

class ProductController extends Controller
{
    public function getPrice(Request $request)
    {
        // $request->product_id will now contain product_id|package_id
        $parts = explode('|', $request->product_id);
        $productId = $parts[0] ?? null;
        $packageId = $parts[1] ?? null;

        $product = Product::find($productId);
        $package = \App\Models\ProductPackage::find($packageId);

        if (! $product || ! $package) {
            return response()->json(['retail_price' => 0]);
        }

        $p = $product;
        $pkg = $package;
        $allProductPackages = $p->packages ?? collect();
        $PC_SYMBOLS = ['pc', 'pcs', ''];
        $hasNonPcSymbol = $allProductPackages->contains(function ($aPkg) use ($PC_SYMBOLS) {
            return !in_array(strtolower($aPkg->symbol ?? ''), $PC_SYMBOLS);
        });

        $thisPkgSymbol = strtolower($pkg->symbol ?? '');
        if (!$allProductPackages->count() && !in_array($thisPkgSymbol, $PC_SYMBOLS)) {
            $hasNonPcSymbol = true;
        }

        $unitName  = strtolower($p->unit->name ?? '');
        $isUnitPc  = in_array($unitName, ['pc', 'pcs', 'piece', 'pieces']);
        $isPcBased = $isUnitPc && !$hasNonPcSymbol;

        if ($isPcBased) {
            $stockPieces = (float) \Illuminate\Support\Facades\DB::table('warehouse_stocks')
                            ->where('product_id', $p->id)
                            ->where('product_package_id', $pkg->id)
                            ->sum('total_pieces');
        } else {
            $stockPieces = (float) \App\Models\WarehouseStock::where('product_id', $p->id)->sum('total_pieces');
        }

        $cf     = $pkg->conversion_factor > 0 ? $pkg->conversion_factor : 1;
        $symbol = $pkg->symbol ?? '';
        $baseUom = $isPcBased ? 'pc' : strtolower($symbol ?: $unitName);

        if ($isPcBased) {
            $stockDisplay = number_format($stockPieces, 0) . ' ' . ($symbol ?: 'pcs');
        } elseif ($pkg->is_fraction) {
            $stockDisplayCount = $cf > 0 ? round($stockPieces / $cf, 2) : $stockPieces;
            $stockDisplay = number_format($stockDisplayCount, 2) . ' ' . $symbol;
        } else {
            $stockDisplayCount = $cf > 0 ? floor($stockPieces / $cf) : $stockPieces;
            $loose = fmod($stockPieces, $cf);
            $stockDisplay = $stockDisplayCount . ' ' . $symbol;
            if ($loose > 0 && $cf > 1) {
                $stockDisplay .= ' +' . round($loose, 2);
            }
        }

        return response()->json([
            'retail_price'          => $package->sale_price,
            'size_mode'             => $product->size_mode,
            'pieces_per_box'        => $package->conversion_factor,
            'price_per_m2'          => 0,
            'sale_price_per_box'    => $package->sale_price,
            'sale_price_per_piece'  => $package->sale_price,
            'height'                => $package->height ?? $product->height,
            'width'                 => $package->width ?? $product->width,
            'item_code'             => $package->code ?? $product->item_code,
            'purchase_discount_percent' => $product->purchase_discount_percent ?? 0,
            'sale_discount_percent'     => $product->sale_discount_percent ?? 0,
            'stock'                 => $stockDisplay,
            'base_stock'            => $stockPieces,
            'symbol'                => $symbol,
            'base_unit'             => $product->unit->name ?? 'pc',
        ]);
    }

    public function productget()
    {
        $products = Product::all();

        return response()->json($products);
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

    // ===== High Performance Select2 Search (Ajax) =====
    public function ajaxSearch(Request $request)
    {
        $term = $request->get('term') ?? $request->get('q') ?? '';

        $query = \App\Models\ProductPackage::with(['product' => function ($q) {
            $q->withSum('warehouseStocks', 'total_pieces')
              ->with('packages:id,product_id,symbol,conversion_factor');
        }])
        ->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%")
              ->orWhereHas('product', function ($q2) use ($term) {
                  $q2->where('item_name', 'like', "%{$term}%")
                     ->orWhere('item_code', 'like', "%{$term}%");
              });
        });

        $packages = $query->paginate(10);

        $results = $packages->map(function ($pkg) {
            $p = $pkg->product;
            if (!$p) return null;

            // === RULE: Determine if this product is "pc-based" ===
            // A product is pc-based (separate variant stock) ONLY if ALL its packages
            // use pc/pcs symbols (or have no symbol). If ANY package has a non-pc
            // symbol (kg, m, ft, m2, etc.) the product is NOT pc-based → aggregate stock.
            $allProductPackages = $p->packages ?? collect();
            $PC_SYMBOLS = ['pc', 'pcs', ''];
            $hasNonPcSymbol = $allProductPackages->contains(function ($aPkg) use ($PC_SYMBOLS) {
                return !in_array(strtolower($aPkg->symbol ?? ''), $PC_SYMBOLS);
            });

            // Also check current package's own symbol in case packages relation is empty
            $thisPkgSymbol = strtolower($pkg->symbol ?? '');
            if (!$allProductPackages->count() && !in_array($thisPkgSymbol, $PC_SYMBOLS)) {
                $hasNonPcSymbol = true;
            }

            // pc-based = Piece unit AND no non-pc package symbols exist
            $unitName  = strtolower($p->unit->name ?? '');
            $isUnitPc  = in_array($unitName, ['pc', 'pcs', 'piece', 'pieces']);
            $isPcBased = $isUnitPc && !$hasNonPcSymbol;

            // === STOCK FETCH ===
            if ($isPcBased) {
                // Separate variant stock — only this package's rows
                $stockPieces = (float) \Illuminate\Support\Facades\DB::table('warehouse_stocks')
                                ->where('product_id', $p->id)
                                ->where('product_package_id', $pkg->id)
                                ->sum('total_pieces');
            } else {
                // Aggregate stock — total for the whole product, then convert via CF
                $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);
            }

            // === DISPLAY ===
            $cf     = $pkg->conversion_factor > 0 ? $pkg->conversion_factor : 1;
            $symbol = $pkg->symbol ?? '';
            $baseUom = $isPcBased ? 'pc' : strtolower($symbol ?: $unitName);

            if ($isPcBased) {
                // Piece-based variant: show integer count + symbol
                $stockDisplay = number_format($stockPieces, 0) . ' ' . ($symbol ?: 'pcs');
            } elseif ($pkg->is_fraction) {
                // Fractional conversion (e.g., 0.5 kg per unit)
                $stockDisplayCount = $cf > 0 ? round($stockPieces / $cf, 2) : $stockPieces;
                $stockDisplay = number_format($stockDisplayCount, 2) . ' ' . $symbol;
            } else {
                // Standard non-pc: divide aggregate stock by CF
                // e.g., 150 kg ÷ 0.25 = 600 pcs; 150 kg ÷ 1 = 150 kg
                $stockDisplayCount = $cf > 0 ? floor($stockPieces / $cf) : $stockPieces;
                $loose = fmod($stockPieces, $cf);
                $stockDisplay = $stockDisplayCount . ' ' . $symbol;
                if ($loose > 0 && $cf > 1) {
                    $stockDisplay .= ' +' . round($loose, 2);
                }
            }

            $pkgSize = ($pkg->size && $pkg->size !== '0') ? $pkg->size : '';
            $displayName = $p->item_name . " - " . ($pkg->name ?? $pkg->code);
            if ($pkgSize) {
                $displayName .= " - " . $pkgSize;
            }

            return [
                'id' => $p->id . '|' . $pkg->id,
                'product_id' => $p->id,
                'package_id' => $pkg->id,
                'text' => $displayName . ($symbol ? " ({$symbol})" : ""),
                'sku' => ($pkg->sku && $pkg->sku !== '0') ? $pkg->sku : ($pkg->code ?: ($p->item_code ?? '')),
                'stock' => $stockDisplay,
                'stock_pieces' => $stockPieces,
                'base_uom' => $baseUom,
                'name' => $displayName,
                'variant_name' => $pkg->name,
                'size' => $pkgSize,
                'symbol' => $symbol,
                'base_unit' => $p->unit->name ?? 'pc',
                'size_mode' => $p->size_mode,
                'pieces_per_box' => $cf,
                'ppb' => $cf,
                'trade_price' => $pkg->purchase_price ?? 0,
                'purchase_price_per_piece' => $pkg->purchase_price ?? 0,
                'sale_price_per_piece' => $pkg->sale_price ?? 0,
                'height' => $pkg->height ?? $p->height ?? 0,
                'length' => $pkg->length ?? 0,
                'width' => $pkg->width ?? $p->width ?? 0,
                'pieces_per_m2' => $p->pieces_per_m2 ?? 0,
                'purchase_discount_percent' => $p->purchase_discount_percent ?? 0,
                'sale_discount_percent' => $p->sale_discount_percent ?? 0,
            ];
        })->filter()->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $packages->hasMorePages()],
        ]);
    }

    // ===== Product search (general) =====
    public function searchProducts(Request $request)
    {
        $term = $request->get('q', '');

        $products = Product::with('category_relation', 'sub_category_relation', 'brand')
            ->withSum('warehouseStocks', 'total_pieces')
            ->when($term, function ($query) use ($term) {
                $query->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhereHas('category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('sub_category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->limit(500) // limit for performance
            ->get();

        return response()->json($products->map(function ($p, $key) {
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);

            // Calculate Stock Display (Boxes vs Pieces)
            $stockDisplay = $stockPieces;
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            if (($p->size_mode === 'by_cartons' || $p->size_mode === 'by_size') && $p->pieces_per_box > 0) {
                $boxes = floor($stockPieces / $ppb);
                $loose = $stockPieces % $ppb;
                $stockDisplay = $loose > 0 ? "$boxes.$loose" : $boxes;
            }

            return [
                'id' => $p->id,
                'item_code' => $p->item_code,
                'item_name' => $p->item_name,
                'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                'category_name' => $p->category_relation->name ?? '-',
                'sub_category_name' => $p->sub_category_relation->name ?? '-',
                'height' => $p->height ?? null,
                'width' => $p->width ?? null,
                'pieces_per_box' => $ppb,
                'size_mode' => $p->size_mode,
                'stock' => $stockDisplay,
                'trade_price' => $p->purchase_price_per_piece ?? 0,
                'total_m2' => number_format($p->total_m2 ?? 0, 2),
                'price_per_m2' => number_format($p->price_per_m2 ?? 0, 2),
                'total_price' => number_format($p->total_price ?? 0, 2),
                'brand_name' => $p->brand->name ?? '-',
            ];
        }));
    }

    // ===== List page =====
    public function product()
    {
        $products = Product::with([
            'category_relation',
            'sub_category_relation',
            'unit',
            'brand',
        ])
            ->withSum('warehouseStocks', 'total_pieces')
            ->latest()
            ->paginate(10);

        $categories = Category::get();

        return view('admin_panel.product.index', compact('products', 'categories'));
    }

    public function productview($id)
    {
        $product = Product::with([
            'category_relation',
            'sub_category_relation',
            'brand',
            'unit',
            'warehouseStocks',
        ])->find($id);

        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Calculate derived fields
        $totalPieces = $product->warehouseStocks->sum('total_pieces');
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        $boxes = 0;
        $loose = 0;

        if ($product->size_mode === 'by_cartons' || $product->size_mode === 'by_size') {
            $boxes = floor($totalPieces / $ppb);
            $loose = $totalPieces % $ppb;
        } else {
            // For by_pieces, boxes is essentially the piece count if we treat it largely
            // But strict interpretation:
            $boxes = $totalPieces;
            $loose = 0;
        }

        // Append these purely for the view (not saved in DB)
        $product->setAttribute('calculated_total_stock_qty', $totalPieces);
        $product->setAttribute('calculated_boxes_quantity', $boxes);
        $product->setAttribute('calculated_loose_pieces', $loose);

        return response()->json($product);
    }

    // //////////////////////

    // /////////////////////////

    // ===== Create page =====
    public function view_store()
    {
        $categories = Category::select('id', 'name')->get();
        $units = Unit::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();

        return view('admin_panel.product.create', compact('categories', 'units', 'brands'));
    }

    // ===== Dependent subcategories =====
    public function getSubcategories($category_id)
    {
        $subcategories = Subcategory::where('category_id', $category_id)->get();

        return response()->json($subcategories);
    }

    // ===== Barcode =====
    public function generateBarcode(Request $request)
    {
        $barcodeNumber = $request->filled('code') ? $request->code : rand(100000000000, 999999999999);
        $barcodePNG = (new DNS1D)->getBarcodePNG($barcodeNumber, 'C39', 3, 50);
        $barcodeImage = 'data:image/png;base64,'.$barcodePNG;

        return response()->json([
            'barcode_number' => $barcodeNumber,
            'barcode_image' => $barcodeImage,
        ]);
    }

    // ===== Store product =====
    // ===== Store product =====
    public function store_product(Request $request)
    {
        if (! Auth::id()) {
            return $request->wantsJson()
                ? response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401)
                : redirect()->route('login');
        }

        // 1. Validate
        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validation->errors()], 422);
            }
            return redirect()->back()->withErrors($validation)->withInput();
        }

        $userId = Auth::id();

        // Auto item_code — use MAX(id)+1 to avoid extra full-table query
        $lastId  = Product::max('id') ?? 0;
        $nextCode = 'p-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);

        // Image upload (outside transaction — filesystem op)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file      = $request->file('image');
            $filename  = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = $filename;
        }

        $now = now();

        DB::transaction(function () use ($request, $userId, $nextCode, $imagePath, $now) {

            $product = Product::create([
                'creater_id'               => $userId,
                'category_id'              => $request->category_id,
                'sub_category_id'          => $request->sub_category_id,
                'item_code'                => $nextCode,
                'item_name'                => $request->product_name,
                'barcode_path'             => $request->barcode_path ?: rand(100000000000, 999999999999),
                'unit_id'                  => 1,
                'brand_id'                 => $request->brand_id,
                'model'                    => $request->model,
                'image'                    => $imagePath,
                'color'                    => $request->color ? json_encode($request->color) : null,
                'purchase_discount_percent'=> $request->purchase_discount_percent ?? 0,
                'sale_discount_percent'    => $request->sale_discount_percent ?? 0,
                'size_mode'                => 'by_pieces',
            ]);

            // Bulk-insert all packages in one query
            $pkgRows = [];
            foreach ($request->packages as $pkg) {
                $pkgCode = $pkg['code'] ?? null;
                if ($pkgCode && str_contains($pkgCode, 'p-000001')) {
                    $pkgCode = str_replace('p-000001', $nextCode, $pkgCode);
                }
                $pkgRows[] = [
                    'product_id'        => $product->id,
                    'code'              => $pkgCode,
                    'name'              => $pkg['name']              ?? null,
                    'size'              => $pkg['size']              ?? null,
                    'symbol'            => $pkg['symbol']            ?? null,
                    'is_fraction'       => isset($pkg['is_fraction']) ? (int)(bool)$pkg['is_fraction'] : 0,
                    'sku'               => $pkg['sku']               ?? null,
                    'weight'            => $pkg['weight']            ?? null,
                    'height'            => $pkg['height']            ?? null,
                    'width'             => $pkg['width']             ?? null,
                    'length'            => $pkg['length']            ?? null,
                    'barcode'           => $pkg['barcode']           ?? null,
                    'conversion_factor' => $pkg['conversion_factor'] ?? 1,
                    'purchase_price'    => $pkg['purchase_price']    ?? 0,
                    'sale_price'        => $pkg['sale_price']        ?? 0,
                    'is_base'           => isset($pkg['is_base']) ? (int)(bool)$pkg['is_base'] : 0,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
            if (!empty($pkgRows)) {
                \App\Models\ProductPackage::insert($pkgRows); // single query
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Product created successfully']);
        }
        return redirect()->back()->with('success', 'Product created successfully');
    }


    /*
    // ===== Parts search (for BOM modal) with real available qty =====
        public function searchPartName(Request $request)
    {
        $q = $request->get('q', '');

        $parts = Product::where('is_part', 1)
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->where(function ($x) use ($q) {
                $x->where('products.item_name', 'like', "%{$q}%")
                  ->orWhere('products.item_code', 'like', "%{$q}%");
            })
            ->groupBy('products.id', 'products.item_name', 'products.item_code', 'products.unit_id')
            ->selectRaw('products.id, products.item_name, products.item_code, products.unit_id, COALESCE(SUM(stocks.qty),0) as available_qty')
            ->limit(20)
            ->get();

        return response()->json($parts->map(function ($p) {
            return [
                'id'            => $p->id,
                'item_name'     => $p->item_name,
                'item_code'     => $p->item_code,
                'unit'          => optional(Unit::find($p->unit_id))->name ?? '',
                'available_qty' => (float)$p->available_qty,
            ];
        }));
    }
    */

    // ===== Update product =====
    public function update(Request $request, $id)
    {
        // Single validation pass (not double)
        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validation->errors()], 422);
            }
            return redirect()->back()->withErrors($validation)->withInput();
        }

        $userId = auth()->id();

        // Fetch existing image, item_code, and barcode in a single lightweight query
        $existingProduct = Product::select('item_code', 'image', 'barcode_path')->find($id);
        $imagePath = $existingProduct->image ?? null;

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/products'), $imageName);
            $imagePath = $imageName;
        }

        // Grab item_code and barcode before entering transaction
        $itemCode = $request->item_code ?: ($existingProduct->item_code ?? 'p-000001');
        $barcode  = $request->barcode_path ?: ($existingProduct->barcode_path ?: rand(100000000000, 999999999999));
        $now = now();

        DB::transaction(function () use ($request, $id, $userId, $imagePath, $itemCode, $barcode, $now) {

            Product::where('id', $id)->update([
                'creater_id'               => $userId,
                'category_id'              => $request->category_id,
                'sub_category_id'          => $request->sub_category_id,
                'item_code'                => $itemCode,
                'item_name'                => $request->product_name,
                'barcode_path'             => $barcode,
                'unit_id'                  => 1,
                'brand_id'                 => $request->brand_id,
                'model'                    => $request->model,
                'image'                    => $imagePath,
                'color'                    => $request->color ? json_encode($request->color) : null,
                'purchase_discount_percent'=> $request->purchase_discount_percent ?? 0,
                'sale_discount_percent'    => $request->sale_discount_percent ?? 0,
                'size_mode'                => 'by_pieces',
                'updated_at'               => $now,
            ]);

            // Delete old packages and bulk-insert new ones (2 queries total)
            \App\Models\ProductPackage::where('product_id', $id)->delete();

            $pkgRows = [];
            foreach ($request->packages as $pkg) {
                $pkgRows[] = [
                    'product_id'        => $id,
                    'code'              => $pkg['code']              ?? null,
                    'name'              => $pkg['name']              ?? null,
                    'size'              => $pkg['size']              ?? null,
                    'symbol'            => $pkg['symbol']            ?? null,
                    'is_fraction'       => isset($pkg['is_fraction']) ? (int)(bool)$pkg['is_fraction'] : 0,
                    'sku'               => $pkg['sku']               ?? null,
                    'weight'            => $pkg['weight']            ?? null,
                    'height'            => $pkg['height']            ?? null,
                    'width'             => $pkg['width']             ?? null,
                    'length'            => $pkg['length']            ?? null,
                    'barcode'           => $pkg['barcode']           ?? null,
                    'conversion_factor' => $pkg['conversion_factor'] ?? 1,
                    'purchase_price'    => $pkg['purchase_price']    ?? 0,
                    'sale_price'        => $pkg['sale_price']        ?? 0,
                    'is_base'           => isset($pkg['is_base']) ? (int)(bool)$pkg['is_base'] : 0,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
            if (!empty($pkgRows)) {
                \App\Models\ProductPackage::insert($pkgRows);
            }

            // Manual stock adjustment
            if ($request->filled('stock_adjust') && (float) $request->stock_adjust != 0) {
                $adjQty = (float) $request->stock_adjust;
                \App\Models\StockMovement::create([
                    'product_id' => $id,
                    'type'       => 'adjustment',
                    'qty'        => $adjQty,
                    'ref_type'   => 'ADJ',
                    'note'       => 'Manual stock adjustment',
                ]);
                $this->upsertStocks($id, $adjQty, 1, 1);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Product updated successfully']);
        }
        return redirect()->back()->with('success', 'Product updated successfully');
    }


    // ===== Edit view =====
    public function edit($id)
    {
        // Use withSum instead of loading all stock rows — SQL aggregation is faster
        $product = Product::with(['unit', 'brand', 'packages'])
            ->withSum('warehouseStocks as total_pieces_sum', 'total_pieces')
            ->findOrFail($id);

        // Lazy-load only needed relations for dropdowns (parallel-friendly)
        $categories    = Category::select('id', 'name')->get();
        $subcategories = SubCategory::select('id', 'name')->get();
        $brands        = Brand::select('id', 'name')->get();

        // Stock calc using the aggregated value (no PHP-side sum loop)
        $totalPieces = (int) ($product->total_pieces_sum ?? 0);
        $ppb         = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        if ($product->size_mode === 'by_cartons' || $product->size_mode === 'by_size') {
            $product->boxes_quantity = (int) floor($totalPieces / $ppb);
            $product->loose_pieces   = (int) ($totalPieces % $ppb);
        } else {
            $product->piece_quantity = $totalPieces;
            $product->boxes_quantity = 0;
            $product->loose_pieces   = 0;
        }

        return view('admin_panel.product.edit', compact('product', 'categories', 'subcategories', 'brands'));
    }

    // ===== Barcode view =====
    public function barcode($id)
    {
        $product = Product::findOrFail($id);

        return view('admin_panel.product.barcode', compact('product'));
    }

    // Shared validation rules
    private function validateProductRequest(Request $request)
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required',
            'sub_category_id' => 'nullable',
            'brand_id' => 'required',
            'base_uom' => 'required',
            'packages' => 'required|array|min:1',
            'purchase_discount_percent' => 'nullable|numeric|min:0|max:100',
            'sale_discount_percent' => 'nullable|numeric|min:0|max:100',
        ];

        return \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    }

    // AJAX Validation Endpoint
    public function validateForm(Request $request)
    {
        $validator = $this->validateProductRequest($request);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'Valid']);
    }
}
