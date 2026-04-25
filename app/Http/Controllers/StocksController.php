<?php

namespace App\Http\Controllers;

use App\Models\Product;

class StocksController extends Controller
{
    public function getStock($productId)
    {
        $product = Product::with('stock')->find($productId);
     

        if (! $product) {
            return response()->json([
                'stock' => 0,
                'sales_price' => 0,
                'retail_price' => 0,
            ]);
        }

        return response()->json([
            'stock' => $product->stock->qty ?? 0,
            'sales_price' => $product->wholesale_price ?? 0,
            'retail_price' => $product->price ?? 0,
        ]);
    }
}
