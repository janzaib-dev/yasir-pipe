import re

with open('app/Http/Controllers/ProductController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace validateProductRequest function
new_validate = """    private function validateProductRequest(Request $request)
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

        return \\Illuminate\\Support\\Facades\\Validator::make($request->all(), $rules);
    }
"""
content = re.sub(r'    private function validateProductRequest\(Request \$request\).*?    }\n', new_validate, content, flags=re.DOTALL)

# Replace store_product
new_store = """    public function store_product(Request $request)
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

        // Auto item_code (base code)
        $lastProduct = Product::orderBy('id', 'desc')->first();
        $nextCode = $lastProduct ? ('ITEM-'.str_pad($lastProduct->id + 1, 4, '0', STR_PAD_LEFT)) : 'ITEM-0001';

        // Image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = $filename;
        } else {
            $imagePath = null;
        }

        DB::transaction(function () use ($request, $userId, $nextCode, $imagePath) {

            // Create product
            $product = Product::create([
                'creater_id' => $userId,
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $nextCode,
                'item_name' => $request->product_name,
                'barcode_path' => $request->barcode_path ?? rand(100000000000, 999999999999),
                'unit_id' => 1, // Default or find unit by base_uom
                'brand_id' => $request->brand_id,
                'model' => $request->model,
                'image' => $imagePath,
                'color' => $request->color ? json_encode($request->color) : null,
                'purchase_discount_percent' => $request->purchase_discount_percent ?? 0,
                'sale_discount_percent' => $request->sale_discount_percent ?? 0,
                'size_mode' => 'by_pieces', // legacy compatibility
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save packages
            foreach ($request->packages as $pkg) {
                \\App\\Models\\ProductPackage::create([
                    'product_id' => $product->id,
                    'code' => $pkg['code'] ?? null,
                    'name' => $pkg['name'] ?? null,
                    'symbol' => $pkg['symbol'] ?? null,
                    'is_fraction' => isset($pkg['is_fraction']) ? (bool)$pkg['is_fraction'] : false,
                    'sku' => $pkg['sku'] ?? null,
                    'weight' => $pkg['weight'] ?? null,
                    'height' => $pkg['height'] ?? null,
                    'width' => $pkg['width'] ?? null,
                    'length' => $pkg['length'] ?? null,
                    'barcode' => $pkg['barcode'] ?? null,
                    'conversion_factor' => $pkg['conversion_factor'] ?? 1,
                    'purchase_price' => $pkg['purchase_price'] ?? 0,
                    'sale_price' => $pkg['sale_price'] ?? 0,
                    'is_base' => isset($pkg['is_base']) ? (bool)$pkg['is_base'] : false,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Product created successfully']);
        }

        return redirect()->back()->with('success', 'Product created successfully');
    }
"""
content = re.sub(r'    public function store_product\(Request \$request\).*?    }\n\n    /\*', new_store + "\n    /*", content, flags=re.DOTALL)

with open('app/Http/Controllers/ProductController.php', 'w', encoding='utf-8') as f:
    f.write(content)
