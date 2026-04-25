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
            'narration' => $narration,
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

        // yahan accounts bhi show karwayn all heads
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
        // V2: Fetch Receipt AND Journal Vouchers (to show Sales Invoices + Receipts)
        $receipts = \App\Models\VoucherMaster::whereIn('voucher_type', [
            \App\Models\VoucherMaster::TYPE_RECEIPT,
            \App\Models\VoucherMaster::TYPE_JOURNAL,
        ])
            ->with('party') // Eager load the polymorphic party
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($receipts as $voucher) {
            $typeLabel = '-';
            $partyName = '-';

            if ($voucher->party) {
                // Determine Label from Class Name
                $class = get_class($voucher->party);
                if (str_contains($class, 'Customer')) {
                    $typeLabel = 'Customer';
                    $partyName = $voucher->party->customer_name ?? $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Vendor')) {
                    $typeLabel = 'Vendor';
                    $partyName = $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Account')) {
                    $typeLabel = 'Account';
                    $partyName = $voucher->party->title ?? '-';
                } else {
                    $typeLabel = class_basename($class);
                    $partyName = $voucher->party->name ?? '-';
                }
            }

            // Attach for View
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;

            // Map old fields to new fields for View compatibility (or update View)
            // View uses: rvid, receipt_date, entry_date
            $voucher->rvid = $voucher->voucher_no;
            $voucher->receipt_date = $voucher->date->format('Y-m-d');
            $voucher->entry_date = $voucher->created_at->format('Y-m-d');

            // Fix: Map total_amount to amount for View compatibility
            if (! isset($voucher->amount)) {
                $voucher->amount = $voucher->total_amount;
            }
        }

        return view('admin_panel.vochers.all_recepit_vochers', compact('receipts'));
    }

    public function print($id)
    {
        \Log::info('Print Voucher Requested. ID: '.$id);

        // 1. Try V2 VoucherMaster First
        $voucherV2 = \App\Models\VoucherMaster::find($id);

        if ($voucherV2) {
            \Log::info('Found V2 Voucher: '.$voucherV2->voucher_no);

            // Lazy load relationships to avoid eager loading weirdness
            $voucherV2->load(['details.account', 'party']);

            // -- Adapter for V2 to V1 View --
            $voucher = (object) [
                'rvid' => $voucherV2->voucher_no,
                'receipt_date' => $voucherV2->date->format('Y-m-d'),
                'total_amount' => $voucherV2->amount,
                'remarks' => $voucherV2->remarks,
                'type' => 'unknown', // Default
            ];

            if (! isset($voucher->total_amount)) {
                $voucher->total_amount = $voucherV2->total_amount;
            }

            $rows = [];
            foreach ($voucherV2->details as $detail) {
                $rows[] = [
                    'narration' => $detail->narration,
                    'reference' => '-',
                    'account_head' => $detail->account->account_head_id ?? '-',
                    'account_name' => $detail->account->title ?? '-',
                    'account_code' => $detail->account->account_code ?? '-',
                    'amount' => $detail->credit > 0 ? $detail->credit : $detail->debit,
                ];
            }

            // Party Logic
            $party = $voucherV2->party;
            $previousBalance = 0;

            if ($party) {
                if ($party instanceof \App\Models\Customer) {
                    $voucher->type = ($party->customer_type == 'Walking Customer') ? 'walkin' : 'customer';

                    // Ensure fields expected by view exist
                    $party->name = $party->customer_name; // Fallback
                    $party->address = $party->address ?? '-';
                    $party->mobile = $party->mobile ?? '-';

                    $previousBalance = \App\Models\CustomerLedger::where('customer_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Vendor) {
                    $voucher->type = 'vendor';
                    $party->address = $party->address ?? '-';
                    $party->phone = $party->phone ?? '-'; // View uses phone

                    $previousBalance = \App\Models\VendorLedger::where('vendor_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Account) {
                    $voucher->type = '1'; // Numeric triggers Account Block
                    $party->name = $party->title;
                    $party->phone = $party->account_code;
                    $party->head_name = $party->accountHead->name ?? 'Account';

                    $previousBalance = $party->opening_balance;
                }
            } else {
                $previousBalance = 0;
            }

            return view('admin_panel.vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
        }

        // 2. Fallback to V1 Legacy (Original Code)
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
            $amount = (float) ($amounts[$index] ?? 0);

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
                $party = (object) [
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

            // echo "<pre>";
            // print_r($customer);
            // echo "<pre>";
            // dd();
            return response()->json([
                'opening_balance' => $customer->opening_balance ?? 0,
            ]);
        }

        // Account case
        $account = AccountHead::find($id);

        return response()->json([
            'opening_balance' => $account->opening_balance ?? 0,
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
        $nextRvid = 'RVID-'.str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.reciepts_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }

    public function store_rec_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $rvid = $request->rvid ?: \App\Models\ReceiptsVoucher::generateRVID(auth()->id());
            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && ! empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Receipts Voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Receipts Voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration' => $manualText,
                    ]);

                    $narrationIds[] = (string) $new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string) $narrId; // force string format
                }
            }

            $voucherData = [
                'rvid' => $rvid,
                'receipt_date' => $request->receipt_date,
                'entry_date' => $request->entry_date,
                'type' => $request->vendor_type,
                'party_id' => $request->vendor_id,
                'tel' => $request->tel,
                'remarks' => $request->remarks,

                'narration_id' => json_encode($narrationIds),
                'reference_no' => json_encode($request->reference_no),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id' => json_encode($request->row_account_id),
                'discount_value' => json_encode($request->discount_value),
                // 'kg'               => json_encode($request->kg),
                'rate' => json_encode($request->rate),
                'amount' => json_encode($request->amount),
                'total_amount' => $request->total_amount,
                'processed' => true,
            ];

            $rec = ReceiptsVoucher::create($voucherData);
            // ✅ V2 VOUCHER INTEGRATION (Primary Logic Now)
            try {
                \Log::info('V2 Integration Start. Type: '.$request->vendor_type.', ID: '.$request->vendor_id);

                $vType = strtolower($request->vendor_type);
                $partyType = null;
                $creditAccountId = null;
                $balanceService = app(\App\Services\BalanceService::class);

                if ($vType == 'customer' || $vType == 'walkin') {
                    $partyType = \App\Models\Customer::class;
                    $creditAccountId = $balanceService->getAccountsReceivableId();
                } elseif ($vType == 'vendor') {
                    $partyType = \App\Models\Vendor::class;
                    $creditAccountId = $balanceService->getAccountsPayableId();
                } else {
                    $partyType = \App\Models\Account::class;
                    $creditAccountId = $request->vendor_id;
                }

                if ($creditAccountId) {
                    $v2Lines = [];
                    // DEBIT SIDE (Cash/Bank) - From Row Inputs
                    if ($request->row_account_id && $request->amount) {
                        foreach ($request->row_account_id as $idx => $accId) {
                            $amt = (float) ($request->amount[$idx] ?? 0);
                            if ($amt > 0) {
                                $v2Lines[] = [
                                    'account_id' => $accId,
                                    'debit' => $amt,
                                    'credit' => 0,
                                    'narration' => $request->narration_text[$idx] ?? 'Receipt',
                                ];
                            }
                        }
                    }

                    // CREDIT SIDE (Customer/AR) - Total Amount
                    $totalAmt = (float) $request->total_amount;
                    if ($totalAmt > 0) {
                        $v2Lines[] = [
                            'account_id' => $creditAccountId,
                            'debit' => 0,
                            'credit' => $totalAmt,
                            'narration' => 'Receipt from '.$request->vendor_type,
                        ];
                    }

                    if (! empty($v2Lines)) {
                        app(\App\Services\VoucherService::class)->createVoucher([
                            'voucher_type' => 'receipt',
                            'date' => $request->receipt_date,
                            'status' => 'posted',
                            'party_type' => $partyType,
                            'party_id' => $request->vendor_id,
                            'remarks' => $request->remarks." (Ref: $rvid)",
                        ], $v2Lines, auth()->id());

                        \Log::info('V2 Voucher Created Successfully.');
                    } else {
                        \Log::warning('V2 Lines Empty. Total Amt: '.$totalAmt);
                    }
                } else {
                    \Log::warning("Credit Account ID missing for type: $vType");
                }
            } catch (\Exception $e) {
                \Log::error('V2 Sync Error: '.$e->getMessage());
                // Silently fail or return error message if preferred, but usually we log.
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
        $nextPVID = 'PVID-'.str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.payment_vochers.payment_vouchers', compact('narrations', 'AccountHeads', 'nextPVID'));
    }

    public function store_Pay_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $pvid = PaymentVoucher::generateInvoiceNo();
            $narrationIds = [];

            // Narration handling (assuming multiple narrations from table)
            if ($request->narration_id) {
                foreach ($request->narration_id as $index => $narrId) {
                    $manualText = $request->narration_text[$index] ?? null;
                    if (empty($narrId) && ! empty($manualText)) {
                        $new = \App\Models\Narration::create([
                            'expense_head' => 'Payment voucher',
                            'narration' => $manualText,
                        ]);
                        $narrationIds[] = (string) $new->id;
                    } else {
                        $narrationIds[] = (string) $narrId;
                    }
                }
            }

            // In this new design:
            // Header = Source (Account) -> row_account_id (Single)
            // Table = Destination (Party) -> vendor_type[], vendor_id[] (Multiple)

            $voucherData = [
                'pvid' => $pvid,
                'receipt_date' => $request->receipt_date,
                'entry_date' => $request->entry_date,

                // Store Header Source as single values
                'row_account_head' => $request->header_account_head,
                'row_account_id' => $request->header_account_id,
                'remarks' => $request->remarks,

                // Store Table Destinations as JSON
                'type' => json_encode($request->vendor_type),
                'party_id' => json_encode($request->vendor_id),
                'narration_id' => json_encode($narrationIds),
                'reference_no' => json_encode($request->reference_no),
                'discount_value' => json_encode($request->discount_value),
                'rate' => json_encode($request->rate),
                'amount' => json_encode($request->amount),
                'total_amount' => $request->total_amount,
            ];

            $payment = PaymentVoucher::create($voucherData);

            $totalAmount = (float) $request->total_amount;

            /**
             * STEP 1: Header Source (Account) -> MINUS (Money Leaving)
             */
            if ($request->header_account_id) {
                $sourceAccount = Account::find($request->header_account_id);
                if ($sourceAccount) {
                    $sourceAccount->current_balance = $sourceAccount->current_balance - $totalAmount;
                    $sourceAccount->save();

                    // Credit Cash/Bank because money is going out
                    app(\App\Services\JournalEntryService::class)->recordEntry(
                        $payment,
                        $request->header_account_id,
                        0, // Debit
                        $totalAmount, // Credit
                        "Payment Voucher #$pvid",
                        $request->entry_date ?? date('Y-m-d')
                    );
                }
            }

            /**
             * STEP 2: Table Destinations (Parties) -> PLUS (Getting Paid)
             */
            if ($request->vendor_id && $request->amount) {
                foreach ($request->vendor_id as $index => $partyId) {
                    $type = $request->vendor_type[$index] ?? null;
                    $rowAmount = isset($request->amount[$index]) ? (float) $request->amount[$index] : 0;

                    if ($rowAmount <= 0) {
                        continue;
                    }

                    if ($type === 'vendor') {
                        $balanceService = app(\App\Services\BalanceService::class);
                        $ledger = VendorLedger::where('vendor_id', $partyId)->latest()->first();
                        $bal = $ledger ? $ledger->closing_balance : 0;
                        VendorLedger::create([
                            'vendor_id'         => $partyId,
                            'admin_or_user_id'  => auth()->id(),
                            'opening_balance'   => 0,
                            'previous_balance'  => $bal,
                            'closing_balance'   => $bal - $rowAmount, // ✅ MINUS: payment reduces vendor balance
                        ]);

                        // Journal Entry: Debit Vendor Control Account (Liability decreases)
                        $journalService = app(\App\Services\JournalEntryService::class);
                        $payablesAccount = $balanceService->getAccountsPayableId();

                        $journalService->recordEntry(
                            $payment,
                            $payablesAccount,
                            $rowAmount, // Debit → Liability decreases
                            0,
                            "Payment #$pvid",
                            $request->entry_date ?? date('Y-m-d'),
                            \App\Models\Vendor::find($partyId)
                        );

                    } elseif ($type === 'customer') {
                        $ledger = CustomerLedger::where('customer_id', $partyId)->latest()->first();
                        $bal = $ledger ? $ledger->closing_balance : 0;
                        CustomerLedger::create([
                            'customer_id'      => $partyId,
                            'admin_or_user_id' => auth()->id(),
                            'previous_balance' => $bal,
                            'opening_balance'  => 0,
                            'closing_balance'  => $bal - $rowAmount, // ✅ MINUS: payment reduces customer balance
                        ]);
                    } elseif ($type) {
                        // Account ID in table
                        $acc = Account::find($partyId);
                        if ($acc) {
                            $acc->current_balance = $acc->current_balance + $rowAmount;
                            $acc->save();
                        }
                    }
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
        // V2: Fetch from VoucherMaster where type is PAYMENT
        $receipts = \App\Models\VoucherMaster::where('voucher_type', \App\Models\VoucherMaster::TYPE_PAYMENT)
            ->with('party') // Eager load the polymorphic party
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($receipts as $voucher) {
            $typeLabel = '-';
            $partyName = '-';

            if ($voucher->party) {
                // Determine Label from Class Name
                $class = get_class($voucher->party);
                if (str_contains($class, 'Customer')) {
                    $typeLabel = 'Customer';
                    $partyName = $voucher->party->customer_name ?? $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Vendor')) {
                    $typeLabel = 'Vendor';
                    $partyName = $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Account')) {
                    $typeLabel = 'Account';
                    $partyName = $voucher->party->title ?? '-';
                } else {
                    $typeLabel = class_basename($class);
                    $partyName = $voucher->party->name ?? '-';
                }
            }

            // Attach for View
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;

            // Map fields for View compatibility
            $voucher->pvid = $voucher->voucher_no;
            $voucher->receipt_date = $voucher->date->format('Y-m-d');
            $voucher->entry_date = $voucher->created_at->format('Y-m-d');

            // Fix for view expecting 'amount' field
            if (! isset($voucher->amount)) {
                $voucher->amount = $voucher->total_amount;
            }
        }

        return view('admin_panel.vochers.payment_vochers.all_payment_vochers', compact('receipts'));
    }

    public function Paymentprint($id)
    {
        // 1. Try V2 VoucherMaster First
        $voucherV2 = \App\Models\VoucherMaster::find($id);

        if ($voucherV2) {
            // Lazy load relationships
            $voucherV2->load(['details.account', 'party']);

            // -- Adapter for V2 to V1 View --
            $voucher = (object) [
                'pvid' => $voucherV2->voucher_no,
                'receipt_date' => $voucherV2->date->format('Y-m-d'),
                'total_amount' => $voucherV2->amount,
                'remarks' => $voucherV2->remarks,
                'type' => 'unknown', // Default
            ];

            if (! isset($voucher->total_amount)) {
                $voucher->total_amount = $voucherV2->total_amount;
            }

            $rows = [];
            foreach ($voucherV2->details as $detail) {
                // Determine account name/code/head
                $headName = $detail->account->accountHead->name ?? '-';
                $accName = $detail->account->title ?? '-';
                $accCode = $detail->account->account_code ?? '-';

                // For Payment: Logic is typically Debit the party/expense?
                // But here rows show where money went?
                // Legacy view shows "account_head", "account_name".

                $rows[] = [
                    'narration' => $detail->narration,
                    'reference' => '-',
                    'account_head' => $headName,
                    'account_name' => $accName,
                    'account_code' => $accCode,
                    'amount' => $detail->debit > 0 ? $detail->debit : $detail->credit,
                ];
            }

            // Party Logic
            $party = $voucherV2->party;
            $previousBalance = 0;

            if ($party) {
                if ($party instanceof \App\Models\Customer) {
                    $voucher->type = ($party->customer_type == 'Walking Customer') ? 'walkin' : 'customer';

                    $party->name = $party->customer_name;
                    $party->address = $party->address ?? '-';
                    $party->mobile = $party->mobile ?? '-'; // View uses mobile/phone? View uses mobile for customer

                    $previousBalance = \App\Models\CustomerLedger::where('customer_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Vendor) {
                    $voucher->type = 'vendor';
                    $party->address = $party->address ?? '-';
                    $party->phone = $party->phone ?? '-'; // View uses phone

                    $previousBalance = \App\Models\VendorLedger::where('vendor_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Account) {
                    $voucher->type = '1'; // Numeric triggers Account Block
                    $party->name = $party->title;
                    $party->phone = $party->account_code; // View uses phone
                    $party->head_name = $party->accountHead->name ?? 'Account';

                    $previousBalance = $party->opening_balance;
                }
            }

            return view('admin_panel.vochers.payment_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
        }

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
            $amount = (float) ($amounts[$index] ?? 0);

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
                $party = (object) [
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

    public function partyList(Request $request)
    {
        $type = $request->type;
        $data = [];

        try {
            $balanceService = app(\App\Services\BalanceService::class);

            if ($type == 'vendor') {
                $vendors = \Illuminate\Support\Facades\DB::table('vendors')->select('id', 'name as text', 'phone as mobile', 'address', 'opening_balance')->get();
                foreach ($vendors as $vendor) {
                    $vendor->closing_balance = $balanceService->getVendorBalance($vendor->id);
                    $bal = number_format(abs($vendor->closing_balance), 0);
                    $lbl = $vendor->closing_balance >= 0 ? 'Cr' : 'Dr';
                    $vendor->text = $vendor->text . " (Bal: {$bal} {$lbl})";
                    $data[] = $vendor;
                }
            } elseif ($type == 'customer') {
                $customers = \App\Models\Customer::where('customer_type', '!=', 'Walking Customer')
                    ->get(['id', 'customer_name', 'mobile', 'address', 'status', 'opening_balance']);

                foreach ($customers as $customer) {
                    $customer->closing_balance = $balanceService->getCustomerBalance($customer->id);
                    $bal = number_format(abs($customer->closing_balance), 0);
                    $lbl = $customer->closing_balance >= 0 ? 'Dr' : 'Cr';
                    
                    $customer->text = $customer->customer_name . " (Bal: {$bal} {$lbl})";
                    $customer->remarks = $customer->status;
                    $data[] = $customer;
                }
            } elseif ($type == 'walkin') {
                $customers = \App\Models\Customer::where('customer_type', 'Walking Customer')
                    ->get(['id', 'customer_name', 'mobile', 'address', 'status', 'opening_balance']);

                foreach ($customers as $customer) {
                    $customer->closing_balance = $balanceService->getCustomerBalance($customer->id);
                    $bal = number_format(abs($customer->closing_balance), 0);
                    $lbl = $customer->closing_balance >= 0 ? 'Dr' : 'Cr';

                    $customer->text = $customer->customer_name . " (Bal: {$bal} {$lbl})";
                    $customer->remarks = $customer->status;
                    $data[] = $customer;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Party List Error: '.$e->getMessage());

            return response()->json([]); // Return empty on error to avoid breaking JS
        }

        return response()->json($data);
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
        $nextRvid = 'EVID-'.str_pad($nextId, 3, '0', STR_PAD_LEFT);

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

                if (empty($narrId) && ! empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Expense voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Expense voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration' => $manualText,
                    ]);

                    $narrationIds[] = (string) $new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string) $narrId; // force string format
                }
            }
            $voucherData = [
                'evid' => $evid,
                'entry_date' => $request->entry_date,
                'type' => $request->vendor_type,
                'party_id' => $request->vendor_id,
                'tel' => $request->tel,
                'remarks' => $request->remarks,
                'reference_no' => $request->ref_no_header,
                'narration_id' => json_encode($narrationIds),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id' => json_encode($request->row_account_id),
                'amount' => json_encode($request->amount),
                'total_amount' => $request->total_amount,
            ];

            $expense = ExpenseVoucher::create($voucherData);

            $amount = (float) $request->total_amount;

            $journalService = app(\App\Services\JournalEntryService::class);
            $balanceService = app(\App\Services\BalanceService::class);

            /**
             * STEP 1: Expense Accounts (row side) → PLUS (Debit)
             */
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float) $request->amount[$index] : 0;

                    if ($rowAmount > 0) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->current_balance = $rowAccount->current_balance + $rowAmount; // PLUS
                            $rowAccount->save();

                            $journalService->recordEntry(
                                $expense,
                                $accId,
                                $rowAmount, // Debit Expense
                                0, // Credit
                                "Expense Voucher #$evid",
                                $request->entry_date ?? date('Y-m-d')
                            );
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
                    $ledger->closing_balance = $ledger->closing_balance - $amount; // MINUS
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id' => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'opening_balance' => 0,
                        'previous_balance' => 0,
                        'closing_balance' => -$amount,
                    ]);
                }

                // Credit Vendor Liability Side
                $journalService->recordEntry(
                    $expense,
                    $balanceService->getAccountsPayableId(),
                    0, // Debit
                    $amount, // Credit vendor liability
                    "Expense Voucher #$evid",
                    $request->entry_date ?? date('Y-m-d'),
                    \App\Models\Vendor::find($request->vendor_id)
                );
            } elseif ($request->vendor_type === 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance = $ledger->closing_balance - $amount; // MINUS
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id' => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance' => 0,
                        'closing_balance' => -$amount,
                    ]);
                }
            } else {
                // yahan vendor_type numeric (1,2,3) hai → matlab Account ID
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->current_balance = $account->current_balance - $amount; // MINUS
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
            $amount = (float) ($amounts[$index] ?? 0);

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
                $party = (object) [
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

    public function fetchReceiptVouchers(Request $request)
    {

        // Fetch all accounts for the dropdown
        $accounts = \Illuminate\Support\Facades\DB::table('accounts')
            ->select('id', 'title', 'head_id')
            ->orderBy('title')
            ->get()
            ->map(function ($account) {
                // Get account head name
                $headName = \Illuminate\Support\Facades\DB::table('account_heads')
                    ->where('id', $account->head_id)
                    ->value('name');

                return [
                    'id' => $account->id,
                    'title' => $account->title,
                    'head_name' => $headName,
                    'display_name' => ($headName ? $headName.' - ' : '').$account->title,
                ];
            });

        return response()->json([
            'accounts' => $accounts,
        ]);
    }
}
