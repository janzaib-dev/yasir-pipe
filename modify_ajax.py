import re

with open('app/Http/Controllers/ProductController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace ajaxSearch
old_ajax_search = r"""    public function ajaxSearch\(Request \$request\)
    \{
        \$term = \$request->get\('term'\) \?\? \$request->get\('q'\) \?\? '';

        \$query = Product::query\(\)
            ->select\('id', 'item_name', 'item_code', 'barcode_path', 'size_mode', 'height', 'width', 'pieces_per_box', 'purchase_price_per_m2', 'purchase_price_per_piece', 'pieces_per_m2', 'purchase_discount_percent', 'sale_discount_percent'\)
            ->withSum\('warehouseStocks', 'total_pieces'\) /\* Sum PIECES, not boxes \*/
            ->where\(function \(\$q\) use \(\$term\) \{
                \$q->where\('item_name', 'like', "%\{\$term\}%"\)
                    ->orWhere\('item_code', 'like', "%\{\$term\}%"\)
                    ->orWhere\('barcode_path', 'like', "%\{\$term\}%"\);
            \}\);

        \$products = \$query->paginate\(10\); // Lazy loading \(10 per request\)

        \$results = \$products->map\(function \(\$p\) \{
            // Get total pieces from warehouse stocks
            \$stockPieces = \(float\) \(\$p->warehouse_stocks_sum_total_pieces \?\? 0\);
            \$ppb = \$p->pieces_per_box > 0 \? \$p->pieces_per_box : 1;

            // Calculate Stock Display \(Boxes.Loose vs Pieces\)
            \$stockDisplay = \$stockPieces;
            if \(\(\$p->size_mode === 'by_cartons' \|\| \$p->size_mode === 'by_size'\) && \$ppb > 1\) \{
                // For box-based products, show as "Boxes.Loose"
                \$boxes = floor\(\$stockPieces / \$ppb\);
                \$loose = \$stockPieces % \$ppb;
                \$stockDisplay = \$loose > 0 \? "\$boxes.\$loose" : \$boxes;
            \}

            return \[
                'id' => \$p->id,
                'text' => \$p->item_name." \(SKU: \{\$p->item_code\}\)", // Enhanced text for selection
                // Custom attributes for template
                'sku' => \$p->item_code \?\? '',
                'stock' => \$stockDisplay,
                'stock_pieces' => \$stockPieces, // Raw pieces for validation
                'name' => \$p->item_name,
                'size_mode' => \$p->size_mode,
                'pieces_per_box' => \$ppb,
                'ppb' => \$ppb, // Legacy
                'trade_price' => \$p->purchase_price_per_piece \?\? 0,
                'purchase_price_per_m2' => \$p->purchase_price_per_m2 \?\? 0,
                'purchase_price_per_piece' => \$p->purchase_price_per_piece \?\? 0,
                'height' => \$p->height \?\? 0,
                'length' => \$p->height \?\? 0, // Alias for purchase snapshot
                'width' => \$p->width \?\? 0,
                'pieces_per_m2' => \$p->pieces_per_m2 \?\? 0,
                'purchase_discount_percent' => \$p->purchase_discount_percent \?\? 0,
                'sale_discount_percent' => \$p->sale_discount_percent \?\? 0,
            \];
        \}\);

        return response\(\)->json\(\[
            'results' => \$results,
            'pagination' => \['more' => \$products->hasMorePages\(\)\],
        \]\);
    \}"""

new_ajax_search = """    public function ajaxSearch(Request $request)
    {
        $term = $request->get('term') ?? $request->get('q') ?? '';

        $query = \App\Models\ProductPackage::with(['product' => function ($q) {
            $q->withSum('warehouseStocks', 'total_pieces');
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

            // Get total pieces from warehouse stocks
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);
            
            // Calculate Stock Display based on conversion factor
            $cf = $pkg->conversion_factor > 0 ? $pkg->conversion_factor : 1;
            $stockDisplay = floor($stockPieces / $cf); // Available packages
            $loose = $stockPieces - ($stockDisplay * $cf);
            if ($loose > 0 && $cf > 1) {
                $stockDisplay = "{$stockDisplay} (rem: {$loose} base)";
            }

            return [
                'id' => $p->id . '|' . $pkg->id, // Send both product ID and package ID
                'product_id' => $p->id,
                'package_id' => $pkg->id,
                'text' => $p->item_name . " - " . ($pkg->name ?? $pkg->code) . " ({$pkg->symbol})",
                'sku' => $pkg->sku ?? $p->item_code ?? '',
                'stock' => $stockDisplay,
                'stock_pieces' => $stockPieces, 
                'name' => $p->item_name . " - " . ($pkg->name ?? $pkg->code),
                'size_mode' => 'by_pieces', // Always by pieces logically now
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
    }"""

# Replace getPrice
old_get_price = r"""    public function getPrice\(Request \$request\)
    \{
        \$product = Product::find\(\$request->product_id\);

        if \(! \$product\) \{
            return response\(\)->json\(\['retail_price' => 0\]\);
        \}

        // Determine price based on mode
        \$price = 0;
        if \(\$product->size_mode === 'by_size'\) \{
            \$price = \$product->price_per_m2;
        \} else \{
            // For by_cartons or by_pieces, use the box/piece price
            \$price = \$product->sale_price_per_box;
        \}

        return response\(\)->json\(\[
            'retail_price'          => \$price,
            'size_mode'             => \$product->size_mode,
            'pieces_per_box'        => \$product->pieces_per_box,
            'price_per_m2'          => \$product->price_per_m2,
            'sale_price_per_box'    => \$product->sale_price_per_box,
            'sale_price_per_piece'  => \$product->sale_price_per_piece,
            'height'                => \$product->height,
            'width'                 => \$product->width,
            'item_code'             => \$product->item_code,
            'purchase_discount_percent' => \$product->purchase_discount_percent \?\? 0,
            'sale_discount_percent'     => \$product->sale_discount_percent \?\? 0,
        \]\);
    \}"""

new_get_price = """    public function getPrice(Request $request)
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

        return response()->json([
            'retail_price'          => $package->sale_price,
            'size_mode'             => 'by_pieces',
            'pieces_per_box'        => $package->conversion_factor,
            'price_per_m2'          => 0,
            'sale_price_per_box'    => $package->sale_price,
            'sale_price_per_piece'  => $package->sale_price,
            'height'                => $package->height ?? $product->height,
            'width'                 => $package->width ?? $product->width,
            'item_code'             => $package->code ?? $product->item_code,
            'purchase_discount_percent' => $product->purchase_discount_percent ?? 0,
            'sale_discount_percent'     => $product->sale_discount_percent ?? 0,
        ]);
    }"""

content = re.sub(old_ajax_search, new_ajax_search, content)
content = re.sub(old_get_price, new_get_price, content)

with open('app/Http/Controllers/ProductController.php', 'w', encoding='utf-8') as f:
    f.write(content)
