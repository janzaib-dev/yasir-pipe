<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    // Discount List Page
    public function index()
    {
        $discounts = ProductDiscount::with('product.category_relation', 'product.sub_category_relation', 'product.unit', 'product.brand')
            ->orderByDesc('id')->get();

        return view('admin_panel.product.discount.discount_index', compact('discounts'));
    }

    // Show Create Discount Page
    public function create(Request $request)
    {
        $productIds = $request->products ? explode(',', $request->products) : [];
        $products = Product::with(['category_relation', 'sub_category_relation', 'unit', 'brand', 'stock'])
            ->whereIn('id', $productIds)->get();

        return view('admin_panel.product.discount.discount_create', compact('products'));
    }

    // Store Discount
    // public function store(Request $request)
    // {
        
    //     foreach ($request->product_id as $key => $productId) {
    //         $product = Product::find($productId);

    //         $discountPercentage = $request->discount_percentage[$key] ?? 0;
    //         $discountAmount = $request->discount_amount[$key] ?? 0;
    //         $status = $request->status[$key] ?? 1;

    //         $finalPrice = $product->price; // original price
    //         if ($discountPercentage > 0) {
    //             $finalPrice = $product->price - ($product->price * $discountPercentage / 100);
    //         } elseif ($discountAmount > 0) {
    //             $finalPrice = $product->price - $discountAmount;
    //         }

    //         ProductDiscount::updateOrCreate(
    //             ['product_id' => $productId],
    //             [
    //                 'actual_price' => $product->price,
    //                 'discount_percentage' => $discountPercentage,
    //                 'discount_amount' => $discountAmount,
    //                 'total_discount'     => $totalDiscount,
    //                 'final_price' => $finalPrice,
    //                 'date'               => $date,  
    //                 'status' => $status
    //             ]
    //         );
    //     }

    //     return redirect()->route('discount.index')->with('success', 'Discounts saved successfully.');
    // }
// Store Discount
public function store(Request $request)
{
    $request->validate([
        'product_id.*'          => ['required','integer','exists:products,id'],
        'discount_percentage.*' => ['nullable','numeric','min:0','max:100'],
        'discount_amount.*'     => ['nullable','numeric','min:0'],
        'date.*'                => ['required','date'],
        'status.*'              => ['required','in:0,1'],
    ]);

    foreach ($request->product_id as $key => $productId) {
        $product = Product::findOrFail($productId);

        $discountPercentage = (float)($request->discount_percentage[$key] ?? 0);
        $discountAmount     = (float)($request->discount_amount[$key] ?? 0);
        $status             = (int)($request->status[$key] ?? 1);
        $date               = $request->date[$key];

        $percDiscount  = round($product->price * $discountPercentage / 100, 2);
        $totalDiscount = round($percDiscount + $discountAmount, 2);

        if ($totalDiscount > $product->price) {
            return back()
                ->withErrors([
                    "discount_percentage.$key" => "Total discount exceeds original price for '{$product->item_name}'.",
                    "discount_amount.$key"     => "Total discount exceeds original price for '{$product->item_name}'.",
                ])
                ->withInput();
        }

        $finalPrice = round($product->price - $totalDiscount, 2);

        ProductDiscount::updateOrCreate(
            ['product_id' => $productId],
            [
                'actual_price'        => $product->price,
                'discount_percentage' => $discountPercentage,
                'discount_amount'     => $discountAmount,
                'total_discount'      => $totalDiscount,
                'final_price'         => $finalPrice,
                'date'                => $date,  // ✅ only one line
                'status'              => $status,
            ]
        );
    }

    return redirect()->route('discount.index')->with('success', 'Discounts saved successfully.');
}


    // Toggle Status Active/Inactive
    public function toggleStatus($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        $discount->status = !$discount->status;
        $discount->save();

        return redirect()->back()->with('success', 'Discount status updated.');
    }

    // Discount Barcode Page
    public function barcode($id)
    {
        $discount = ProductDiscount::with('product')->findOrFail($id);
        return view('admin_panel.product.discount.discount_barcode', compact('discount'));
    }
}
