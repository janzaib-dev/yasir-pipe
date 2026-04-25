<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\ExpenseVoucher;
use App\Models\Narration;
use App\Models\PaymentVoucher;
use App\Models\ReceiptsVoucher;
use App\Models\VendorLedger;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function index($type)
    {

        // Sirf selected type ka data laa lo
        $vouchers = Voucher::where('voucher_type', $type)->latest()->get();
        $narration = Narration::where('expense_head', $type)->get();

        return view('admin_panel.accounts.expenses', [
            'vouchers' => $vouchers,
            'type' => $type,
            'narration' => $narration
        ]);
    }


    public function store(Request $request)
    {
        // Validate that arrays are present and match in length
        $request->validate([
            'date' => 'required',
            'type' => 'required',
            'person' => 'required',
            'narration' => 'required',
            'amount' => 'required',
        ]);

        // Loop through each row and create a voucher
        foreach ($request->date as $index => $date) {
            Voucher::create([
                'voucher_type' => $request->sub_head,
                'sales_officer' => auth()->user()->name,
                'date' => $date,
                'type' => $request->type[$index],
                'person' => $request->person[$index],
                'sub_head' => $request->sub_head[$index] ?? null,
                'narration' => $request->narration[$index],
                'amount' => $request->amount[$index],
                'status' => 'draft',
            ]);
        }

        return back()->with('success', 'Vouchers saved successfully!');
    }


    /**
     * Display the specified resource.
     */
    public function show(Voucher $voucher)
    {
        //
    }
    public function receipt($id)
    {
        $voucher = Voucher::findOrFail($id);

        $customerName = $voucher->person; // Default
        $customerAddress = '-';
        $closingBalance = 0;

        //yahan accounts bhi show karwayn all heads 
        // bank cash  
        if ($voucher->type === 'Main Customer' && $voucher->mainCustomer) {
            $customerName = $voucher->mainCustomer->customer_name;
            $customerAddress = $voucher->mainCustomer->address;
            $closingBalance = $voucher->mainCustomer->closing_balance;
        } elseif ($voucher->type === 'Sub Customer' && $voucher->subCustomer) {
            $customerName = $voucher->subCustomer->customer_name;
            $customerAddress = $voucher->subCustomer->address;
            $closingBalance = $voucher->subCustomer->closing_balance;
        }

        return view('admin_panel.accounts.receipt', compact('voucher', 'customerName', 'customerAddress', 'closingBalance'));
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        //
    }

    public function all_recepit_vochers()
    {
        $receipts = \App\Models\ReceiptsVoucher::orderBy('id', 'DESC')->get();

        foreach ($receipts as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            // 🧩 Check if type is numeric → account-based
            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')
                    ->where('id', $voucher->party_id)
                    ->where('customer_type', 'Walking Customer')
                    ->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            // Attach new properties to the object
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;
        }

        return view('admin_panel.vochers.all_recepit_vochers', compact('receipts'));
    }


    public function print($id)
    {
        $voucher = ReceiptsVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // Rows build
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ If type is numeric → means from Account Head
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            // ✅ If vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ If customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ If walkin
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

     public function getAccountsByHead($headId)
    {
        $accounts = Account::where('head_id', $headId)->where('status', 1)->get();
        // echo "<pre>";
        // print_r($accounts);
        // echo "</pre>";
        // dd();
        return response()->json($accounts);
    }
public function getOpeningBalance($type, $id)
{
    if ($type === 'customer' || $type === 'walkin') {
        $customer = Customer::find($id);
        echo "<pre>";
        print_r($customer);
        echo "<pre>";
        dd();
        return response()->json([
            'opening_balance' => $customer->opening_balance ?? 0
        ]);
    }

    // Account case
    $account = AccountHead::find($id);
    return response()->json([
        'opening_balance' => $account->opening_balance ?? 0
    ]);
}






    public function recepit_vochers()
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Receipts Voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        // echo "<pre>";
        // print_r($AccountHeads) ;
        // echo "<pre>";
        // dd();

        // Last RVID nikalna
        $lastVoucher = \App\Models\ReceiptsVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextRvid = 'RVID-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.reciepts_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }


    public function store_rec_vochers(Request $request)
    {
        // echo "<pre>";
        // print_r($request->remarks);
        // dd();
        DB::beginTransaction();
        try {
            $rvid = $request->rvid ?: \App\Models\ReceiptsVoucher::generateRVID(auth()->id());
            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && !empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Receipts Voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Receipts Voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration'    => $manualText,
                    ]);

                    $narrationIds[] = (string)$new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string)$narrId; // force string format
                }
            }


            $voucherData = [
                'rvid'             => $rvid,
                'receipt_date'     => $request->receipt_date,
                'entry_date'       => $request->entry_date,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,

                'narration_id' => json_encode($narrationIds),
                'reference_no'     => json_encode($request->reference_no),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id'   => json_encode($request->row_account_id),
                'discount_value'   => json_encode($request->discount_value),
                // 'kg'               => json_encode($request->kg),
                'rate'             => json_encode($request->rate),
                'amount'           => json_encode($request->amount),
                'total_amount'     => $request->total_amount,
                'processed'        => true,
            ];

            ReceiptsVoucher::create($voucherData);

            // ✅ Ledger update logic
            $amount = (float)$request->total_amount;

            if ($request->vendor_type === 'vendor') {
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance - $amount;
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => now(),
                        'description'      => "Receipt Voucher #$rvid",
                        'opening_balance'  => 0,
                        'debit'            => 0,
                        'credit'           => $amount,
                        'previous_balance' => 0,
                        'closing_balance'  => -$amount,
                    ]);
                }
            } elseif ($request->vendor_type == 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance - $amount;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance'  => 0,
                        'closing_balance'  => -$amount,
                    ]);
                }
            } else {
                // Bank/Head case → pehle vendor/account side minus
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->opening_balance = $account->opening_balance - $amount;
                    $account->save();
                }
            }

            // ✅ Har case me row_account_id ka + hona zaroori hai
          // ✅ Row account posting (Debit / Credit aware)
if ($request->row_account_id && $request->amount) {
    foreach ($request->row_account_id as $index => $accId) {

        $rowAmount = isset($request->amount[$index])
            ? (float)$request->amount[$index]
            : 0;

        if ($rowAmount <= 0) continue;

        $rowAccount = Account::find($accId);
        if (! $rowAccount) continue;

        if ($rowAccount->type === 'Debit') {
            // Debit account → increase
            $rowAccount->opening_balance += $rowAmount;
        } else {
            // Credit account → decrease
            $rowAccount->opening_balance -= $rowAmount;
        }

        $rowAccount->save();
    }
}


            DB::commit();
            return back()->with('success', 'Receipt Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function Payment_vochers()
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Payment voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();
        // echo"<pre>";
        // print_r($AccountHeads);
        // echo"</pre>";
        // dd();

        // Last RVID nikalna
        $lastVoucher = \App\Models\PaymentVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextPVID = 'PVID-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.payment_vochers.payment_vouchers', compact('narrations', 'AccountHeads', 'nextPVID'));
    }

    public function store_Pay_vochers(Request $request)
    {
            // echo "<pre>";
            // print_r($request->all());
            // dd();
        DB::beginTransaction();
        try {
            $pvid = PaymentVoucher::generateInvoiceNo();
            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && !empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Payment voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Payment voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration'    => $manualText,
                    ]);

                    $narrationIds[] = (string)$new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string)$narrId; // force string format
                }
            }
            $voucherData = [
                'pvid'             => $pvid,
                'receipt_date'     => $request->receipt_date,
                'entry_date'       => $request->entry_date,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,
                'narration_id' => json_encode($narrationIds),
                'reference_no'     => json_encode($request->reference_no),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id'   => json_encode($request->row_account_id),
                'discount_value'   => json_encode($request->discount_value),
                // 'kg'               => json_encode($request->kg),
                // 'rate'             => json_encode($request->rate),
                'amount'           => json_encode($request->amount),
                'total_amount'     => $request->total_amount,
            ];

            PaymentVoucher::create($voucherData);

            $amount = (float)$request->total_amount;
            /**
             * STEP 1: Row accounts → MINUS (opposite of receipt voucher)
             */
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float)$request->amount[$index] : 0;

                    if ($rowAmount > 0) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = $rowAccount->opening_balance - $rowAmount;
                            $rowAccount->save();
                        }
                    }
                }
            }

            /**
             * STEP 2: Party side (Vendor / Customer / Account Head) → PLUS
             */
            if ($request->vendor_type === 'vendor') {
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $amount;
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => now(),
                        'description'      => "Payment Voucher #$pvid",
                        'opening_balance'  => 0,
                        'debit'            => $amount,
                        'credit'           => 0,
                        'previous_balance' => 0,
                        'closing_balance'  => $amount,
                    ]);
                }
            } elseif ($request->vendor_type === 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $amount;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance'  => 0,
                        'closing_balance'  => $amount,
                    ]);
                }
            } else {
                // agar vendor_type me account head/account ki id ayi
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->opening_balance = $account->opening_balance + $amount;
                    $account->save();
                }
            }

            DB::commit();
            return back()->with('success', 'Payment Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

     public function all_Payment_vochers()
    {
        $receipts = \App\Models\PaymentVoucher::orderBy('id', 'DESC')->get();

        foreach ($receipts as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            // 🧩 If type is numeric → Account Head / Account
            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')
                    ->where('id', $voucher->party_id)
                    ->where('customer_type', 'Walking Customer')
                    ->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            // Attach extra fields for Blade
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;
        }

        return view('admin_panel.vochers.payment_vochers.all_payment_vochers', compact('receipts'));
    }

    public function Paymentprint($id)
    {
        $voucher = \App\Models\PaymentVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Build detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ Account Head type (numeric)
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;

            // ✅ Vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Walking customer
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.payment_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }


    public function expense_vochers()
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Expense voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        // Last RVID nikalna
        $lastVoucher = \App\Models\ExpenseVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextRvid = 'EVID-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.expense_vochers.expense_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }

    public function store_expense_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $evid = ExpenseVoucher::generateInvoiceNo();
            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && !empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Expense voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Expense voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration'    => $manualText,
                    ]);

                    $narrationIds[] = (string)$new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string)$narrId; // force string format
                }
            }
            $voucherData = [
                'evid'             => $evid,
                'entry_date'       => $request->entry_date,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,
                'narration_id' => json_encode($narrationIds),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id'   => json_encode($request->row_account_id),
                'amount'           => json_encode($request->amount),
                'total_amount'     => $request->total_amount,
            ];

            ExpenseVoucher::create($voucherData);

            $amount = (float)$request->total_amount;

            /**
             * STEP 1: Expense Accounts (row side) → PLUS
             */
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float)$request->amount[$index] : 0;

                    if ($rowAmount > 0) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = $rowAccount->opening_balance + $rowAmount; // PLUS
                            $rowAccount->save();
                        }
                    }
                }
            }

            /**
             * STEP 2: Party side → MINUS
             */
            if ($request->vendor_type === 'vendor') {
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance - $amount; // MINUS
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => now(),
                        'description'      => "Expense Voucher #$evid",
                        'opening_balance'  => 0,
                        'debit'            => 0,
                        'credit'           => $amount,
                        'previous_balance' => 0,
                        'closing_balance'  => -$amount,
                    ]);
                }
            } elseif ($request->vendor_type === 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance - $amount; // MINUS
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance'  => 0,
                        'closing_balance'  => -$amount,
                    ]);
                }
            } else {
                // yahan vendor_type numeric (1,2,3) hai → matlab Account ID
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->opening_balance = $account->opening_balance - $amount; // MINUS
                    $account->save();
                }
            }

            DB::commit();
            return back()->with('success', 'Expense Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function all_expense_vochers()
    {
        $receipts = \App\Models\ExpenseVoucher::orderBy('id', 'DESC')->get();

        foreach ($receipts as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            // 🧩 If type is numeric → Account Head / Account
            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')
                    ->where('id', $voucher->party_id)
                    ->where('customer_type', 'Walking Customer')
                    ->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            // 🔗 Attach extra fields for Blade
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;
        }

        return view('admin_panel.vochers.expense_vochers.all_expense_vochers', compact('receipts'));
    }



    public function expenseprint($id)
    {
        $voucher = \App\Models\ExpenseVoucher::findOrFail($id);

        // Decode JSON arrays safely
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Prepare detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party Setup Based on Type
        $party = null;
        $previousBalance = 0;

        if (is_numeric($voucher->type)) {
            // ✅ Account Head type (numeric)
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;
        } elseif ($voucher->type === 'vendor') {
            // ✅ Vendor Type
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'customer') {
            // ✅ Customer Type
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'walkin') {
            // ✅ Walking Customer
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.expense_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }
}
