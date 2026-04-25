<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorLedger;
use App\Models\VendorPayment;
use App\Models\VendorBilty;
use App\Models\Purchase;

class VendorController extends Controller
{
    // Show all vendors
    public function index() {
        $vendors = Vendor::all();
        return view('admin_panel.vendors.index', compact('vendors'));
    }

    // Store or update vendor information
    public function store(Request $request)
    {
        if ($request->id) {
            // Update existing vendor (prevent balance update)
            Vendor::findOrFail($request->id)->update($request->except('opening_balance'));
        } else {
            // Create a new vendor and ledger entry
            $vendor = Vendor::create($request->all());

            // Create ledger entry
            VendorLedger::create([
                'vendor_id' => $vendor->id,
                'admin_or_user_id' => Auth::id(),
                'opening_balance' => $request->opening_balance ?? 0,
                'closing_balance' => $request->opening_balance ?? 0,
                'previous_balance' => $request->previous_balance ?? 0,
            ]);

            // ✅ Record Journal Entry for Opening Balance (Accounting)
            if ($request->opening_balance > 0) {
                try {
                    $balanceService = app(\App\Services\BalanceService::class);
                    $journalService = app(\App\Services\JournalEntryService::class);
                    $apId = $balanceService->getAccountsPayableId();

                    $journalService->recordEntry(
                        $vendor,
                        $apId,
                        0, // Debit
                        $request->opening_balance, // Credit (Liability)
                        "Opening Balance",
                        now()->format('Y-m-d'),
                        $vendor
                    );
                } catch (\Exception $e) {
                    \Log::error("Vendor Opening Balance Journal Error: " . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Saved Successfully');
    }

    // Soft delete vendor and related ledger entry
    public function delete($id) {
    // Find the vendor by id, along with the related ledger entry using the 'ledger' relationship
    $vendor = Vendor::with('ledger')->findOrFail($id);

    // The vendor's ledger will be automatically deleted due to cascading delete
    $vendor->delete(); // Soft delete vendor

    return back()->with('success', 'Deleted Successfully');
}


    // Show vendor ledger for the authenticated user
    public function vendors_ledger()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            // Get all ledgers
            $VendorLedgers = VendorLedger::where('admin_or_user_id', $userId)->with('vendor')->get();
            
            // Recalculate balances from Journal Entries
            $balanceService = app(\App\Services\BalanceService::class);
            
            foreach ($VendorLedgers as $ledger) {
                // Calculate actual closing balance from journal entries
                // Note: BalanceService::getVendorBalance returns positive for Credit (Payable)
                $ledger->formatted_closing_balance = $balanceService->getVendorBalance($ledger->vendor_id);
            }
            
            return view('admin_panel.vendors.vendors_ledger', compact('VendorLedgers'));
        } else {
            return redirect()->back();
        }
    }

    // Show all vendor payments
    public function vendor_payments()
    {
        $userId = Auth::id();
        $payments = VendorPayment::with('vendor')
            ->where('admin_or_user_id', $userId)
            ->orderByDesc('payment_date')
            ->get();

        $vendors = Vendor::all();
        return view('admin_panel.vendors.vendor_payments', compact('payments', 'vendors'));
    }

    // Store vendor payment and update ledger
    public function store_vendor_payment(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'adjustment_type' => 'required|in:plus,minus',
        ]);

        // Save the vendor payment
        VendorPayment::create([
            'vendor_id' => $request->vendor_id,
            'admin_or_user_id' => Auth::id(),
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        // Update vendor ledger
        $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->first();
        if ($ledger) {
            $ledger->closing_balance += ($request->adjustment_type === 'minus' ? -1 : 1) * $request->amount;
            $ledger->save();
        }

        return redirect()->back()->with('success', 'Vendor payment recorded.');
    }

    // Show all vendor bilties
    public function vendor_bilties()
    {
        $bilties = VendorBilty::with(['vendor', 'purchase'])->orderByDesc('id')->get();
        $vendors = Vendor::all();
        $purchases = Purchase::all();
        return view('admin_panel.vendors.vendor_bilties', compact('bilties', 'vendors', 'purchases'));
    }

    // Store vendor bilty information
    public function store_vendor_bilty(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'bilty_no' => 'nullable|string',
            'vehicle_no' => 'nullable|string',
            'transporter_name' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        VendorBilty::create($request->all());

        return back()->with('success', 'Vendor bilty saved successfully.');
    }

    // Get vendor balance by vendor id
    public function getVendorBalance($id)
    {
        $ledger = VendorLedger::where('vendor_id', $id)->first();
        return response()->json([
            'closing_balance' => $ledger ? $ledger->closing_balance : 0
        ]);
    }

    /**
     * Show vendor ledger (journal-based)
     */
    public function ledger($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        
        // Get date range from request or default to current month
        $startDate = request('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = request('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $balanceService = app(\App\Services\BalanceService::class);
        $ledgerData = $balanceService->getVendorLedger($vendorId, $startDate, $endDate);
        
        return view('admin_panel.vendors.ledger', $ledgerData);
    }
    /**
     * Get vendor ledger as JSON (AJAX)
     */
    public function getVendorLedgerJson($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $balanceService = app(\App\Services\BalanceService::class);
        
        // Default range: last 30 days
        $startDate = request('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = request('end_date', now()->format('Y-m-d'));
        
        $ledgerData = $balanceService->getVendorLedger($vendorId, $startDate, $endDate);
        
        // Add current balance to response
        $ledgerData['current_balance'] = $balanceService->getVendorBalance($vendorId);
        
        return response()->json($ledgerData);
    }
}
