<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    // Return warehouses for a given product_id
    public function getWarehouses(Request $request)
    {
        $productId = $request->input('product_id');

        // Get all warehouses first
        $allWarehouses = Warehouse::all();
        
        // Get stock entries for this product
        $warehouseStocks = WarehouseStock::with(['stockWarehouse', 'product'])
            ->where('product_id', $productId)
            ->get()
            ->keyBy('warehouse_id');

        $response = $allWarehouses->map(function ($warehouse) use ($warehouseStocks, $productId) {
            $ws = $warehouseStocks->get($warehouse->id);
            $stockVal = 0;
            
            if ($ws) {
                $ppb = ($ws->product && $ws->product->pieces_per_box > 0) ? $ws->product->pieces_per_box : 1;
                
                // Robust Calculation: 
                // Trust 'quantity' (Boxes) as the primary source if it exists, as users usually trade in boxes.
                // Recalculate pieces from quantity to ensure consistency.
                $calcPieces = $ws->quantity * $ppb;
                
                // Use calculated pieces if it differs significantly from stored total_pieces (e.g. data sync issue)
                // or if total_pieces is 0 but quantity > 0.
                if (abs($calcPieces - $ws->total_pieces) > 0.1) {
                     $stockVal = $calcPieces;
                } else {
                     $stockVal = $ws->total_pieces;
                }
            }

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name,
                'stock' => $stockVal, // Total pieces
                'boxes' => $ws ? $ws->quantity : 0, // Actual box quantity from DB
                'ppb' => $ws && $ws->product ? $ws->product->pieces_per_box : 1,
                'size_mode' => $ws && $ws->product ? $ws->product->size_mode : 'std',
            ];
        });

        return response()->json($response);
    }

    // VendorController.php aur WarehouseController.php same hoga
    public function index()
    {
        if (! auth()->user()->can('warehouse.view')) {
            abort(403, 'Unauthorized action.');
        }
        $warehouses = Warehouse::with('user')->get(); // ya $warehouses = Warehouse::all();

        return view('admin_panel.warehouses.index', compact('warehouses')); // ya warehouses.index
    }

    public function store(Request $request)
    {
        if ($request->id) {
            if (! auth()->user()->can('warehouse.edit')) {
                return back()->with('error', 'Unauthorized action.');
            }
            Warehouse::findOrFail($request->id)->update($request->all());

            return back()->with('success', 'Warehouse Updated Successfully');
        } else {
            if (! auth()->user()->can('warehouse.create')) {
                return back()->with('error', 'Unauthorized action.');
            }
            Warehouse::create($request->all());

            return back()->with('success', 'Warehouse Created Successfully');
        }
    }

    public function delete($id)
    {
        if (! auth()->user()->can('warehouse.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        Warehouse::findOrFail($id)->delete();

        return response()->json([
            'success' => 'Warehouse Deleted Successfully',
            'reload' => true,
        ]);
    }
}
