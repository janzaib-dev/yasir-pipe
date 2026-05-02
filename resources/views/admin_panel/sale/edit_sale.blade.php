@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Loader Overlay -->
    <div id="pageLoader"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column gap-3 justify-content-center align-items-center"
        style="background: rgba(255,255,255,0.9); z-index: 1055; position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: flex;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="fw-bold text-primary fs-5">Loading...</div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* ================= RESPONSIVE SALES UI ================= */

        /* allow smooth horizontal scroll on small devices */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* base table width */
        .sales-table {
            min-width: 1000px;
        }

        /* 🔹 DISCOUNT COLUMN – THORI SI BARI */
        .sales-table td.large-col {
            min-width: 95px;
            width: 95px;
            padding: 4px;
        }

        /* 🔹 DISCOUNT LAYOUT */
        .discount-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: nowrap;
        }

        /* 🔹 INPUT – NOT TOO SMALL */
        .discount-wrapper .discount-value {
            width: 60px;
            min-width: 60px;
            font-size: 0.8rem;
            padding: 4px 6px;
        }

        /* 🔹 PLUS ICON – NEAT & SMALL */
        .discount-wrapper .discount-plus {
            width: 22px;
            height: 22px;
            padding: 0;
            font-size: 13px;
            line-height: 1;
        }

        /* 🔹 DROPDOWN */
        .discount-wrapper .discount-type {
            position: absolute;
            right: 0;
            top: 115%;
            width: 65px;
            font-size: 0.75rem;
            z-index: 30;
        }



        /* ---------- TABLET (<= 992px) ---------- */
        @media (max-width: 992px) {

            .main-container {
                max-width: 100%;
            }

            .sales-table {
                min-width: 1000px;
            }

            .minw-350 {
                min-width: 100%;
            }

        }

        /* ---------- MOBILE (<= 768px) ---------- */
        @media (max-width: 768px) {

            .header-text {
                font-size: 1rem;
            }

            .btn {
                padding: .35rem .5rem;
            }

            /* stack header buttons */
            .d-flex.justify-content-between.align-items-center {
                flex-wrap: wrap;
                gap: 8px;
            }

            /* customer + invoice panel full width */
            .minw-350 {
                width: 100%;
            }

            /* reduce input font */
            .form-control,
            .form-select {
                font-size: .8rem;
            }

        }

        /* ---------- VERY SMALL DEVICES ---------- */
        @media (max-width: 576px) {

            .sales-table {
                min-width: 950px;
            }

            .discount-wrapper .discount-value {
                min-width: 90px;
            }

        }
    </style>
    <style>
        .main-container {
            font-size: .85rem;
            max-width: 98%;
            /* Widen container */
        }

        .header-text {
            font-size: 1.1rem;
        }

        .form-control,
        .form-select,
        .btn {
            font-size: .82rem;
            /* Slightly smaller for density */
            padding: .3rem .4rem;
            /* Reduce padding */
            height: auto;
        }

        .invalid-cell {
            background-color: #fff5f5 !important;
            /* soft red */
            border: 1px solid #e3342f !important;
            /* red border */
        }

        .invalid-select,
        .invalid-input {
            border-color: #e3342f !important;
            box-shadow: none !important;
        }

        .input-readonly {
            background: #f9fbff;
        }

        .section-title {
            font-weight: 700;
            color: #6c757d;
            letter-spacing: .3px;
        }

        .table {
            --bs-table-padding-y: .35rem;
            --bs-table-padding-x: .5rem;
            font-size: .85rem;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8f9fa;
            text-align: center;
        }

        .table-responsive {
            /* max-height removed to allow expansion */
            overflow-x: auto;
            overflow-y: visible;
            border: 1px solid #eee;
            border-radius: .5rem;
            min-height: 200px;
        }

        .minw-350 {
            min-width: 360px;
        }

        .w-70 {
            width: 70px
        }

        .w-90 {
            width: 90px
        }

        .w-110 {
            width: 110px
        }

        .w-120 {
            width: 120px
        }

        .w-150 {
            width: 150px
        }

        .totals-card {
            background: #fcfcfe;
            border: 1px solid #eee;
            border-radius: .5rem;
        }

        .totals-card .row+.row {
            border-top: 1px dashed #e5e7eb;
        }

        .badge-soft {
            background: #eef2ff;
            color: #3730a3;
        }
    </style>
    <style>
        /* ===== Sales Table UI Fix ===== */
        .sales-table {
            min-width: 1500px;
            /* Ensure enough space for all columns */
        }

        .sales-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            vertical-align: middle;
            color: #495057;
        }

        /* Column Widths */
        .col-product {
            min-width: 220px;
        }

        /* Slightly reduced */
        .col-warehouse {
            min-width: 160px;
        }

        .col-qty {
            width: 90px;
        }


        .col-price {
            width: 110px;
        }

        /* Retail Price */
        .col-disc {
            width: 130px;
        }

        /* Disc % + input */
        .col-disc-amt {
            width: 90px;
        }

        /* New Columns */
        .col-pieces {
            width: 100px;
        }

        .col-price-p {
            width: 100px;
        }

        .col-price-m2 {
            width: 100px;
        }

        .col-amount {
            width: 120px;
        }

        .col-action {
            width: 50px;
            text-align: center;
        }

        .input-readonly {
            background: #f8f9fa;
            color: #6c757d;
            font-weight: 500;
            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        /* Premium Table Look */
        .table-bordered> :not(caption)>*>* {
            border-width: 1px;
            border-color: #e9ecef;
        }
    </style>


    <div class="container-fluid py-2">
        <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3">
            <div id="alertBox" class="alert d-none mb-3" role="alert"></div>
            <form id="saleForm">
                @csrf
                {{-- No method PUT needed here if we handle update via same endpoint or different. 
                     Typically Laravel edit form uses PUT. 
                     We are using AJAX save, so method usually handled in JS. 
                     But let's stick to the existing structure. --}}
                <input type="hidden" name="booking_id" id="booking_id" value="">

                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div>
                        <small class="text-secondary" id="entryDateTime">Entry Date_Time: --</small> <br>
                        <a href="{{ route('sale.index') }}" target="_blank" rel="noopener"
                            class="btn btn-sm btn-outline-secondary" title="Sales List (opens new tab)">
                            Sales List
                        </a>
                    </div>

                    <h2 class="header-text text-secondary fw-bold mb-0">Sales (Edit)</h2>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btnHeaderPosted"
                            disabled>Sale</button>
                    </div>
                </div>

                <div class="d-flex gap-3 align-items-start border-bottom py-3">
                    {{-- LEFT: Invoice & Customer --}}
                    <div class="p-3 border rounded-3 minw-350">
                        <div class="section-title mb-3">Invoice & Customer</div>

                        {{-- Invoice No --}}
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-0">Invoice No</label>
                            <input type="text" class="form-control bg-light" name="Invoice_no"
                                value="{{ $nextInvoiceNumber ?? ($sale->invoice_no ?? '') }}" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-0">M. Inv#</label>
                            <input type="text" class="form-control" name="Invoice_main" placeholder="Manual invoice">
                        </div>

                        {{-- Type toggle --}}
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-1 d-block">Type</label>
                            <div class="btn-group" role="group" id="partyTypeGroup">
                                <input type="radio" class="btn-check" name="partyType" id="typeCustomers"
                                    value="Main Customer" checked>
                                <label class="btn btn-outline-primary btn-sm" for="typeCustomers">Customers</label>

                                <input type="radio" class="btn-check" name="partyType" id="typeWalkin"
                                    value="Walking Customer">
                                <label class="btn btn-outline-primary btn-sm" for="typeWalkin">Walk-in</label>
                            </div>
                        </div>

                        <!-- CUSTOMER SELECT -->
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-1">Select Customer</label>
                            <select class="form-select" id="customerSelect" name="customer">
                                <option value="" disabled {{ !isset($sale) ? 'selected' : '' }}>Select Customer
                                </option>
                                @foreach ($customer as $c)
                                    <option value="{{ $c->id }}"
                                        {{ isset($sale) && $sale->customer_id == $c->id ? 'selected' : '' }}>
                                        {{ $c->customer_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="customerCountHint"></small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold mb-0">Details</label>
                            <input type="text" class="form-control form-control-sm mb-1" id="address"
                                placeholder="Address" value="{{ optional($sale->customer_relation)->address }}" readonly
                                tabindex="-1">
                            <input type="text" class="form-control form-control-sm mb-1" id="tel"
                                placeholder="Phone" value="{{ optional($sale->customer_relation)->mobile }}" readonly
                                tabindex="-1">
                            <input type="text" class="form-control form-control-sm" id="remarks" placeholder="Remarks"
                                value="{{ optional($sale->customer_relation)->status }}" readonly tabindex="-1">
                        </div>

                        <div class="mb-2 d-flex justify-content-between">
                            <span>Previous Balance</span>
                            <input type="text" class="form-control w-25 text-end" id="previousBalance"
                                value="{{ number_format(optional($sale->customer_relation)->previous_balance ?? 0, 2) }}"
                                readonly>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Range Balance</span>
                            <input type="text" class="form-control w-25 text-end" id="rangeBalance"
                                value="{{ number_format(optional($sale->customer_relation)->balance_range ?? 0, 2) }}"
                                readonly>
                        </div>

                        <div class="text-end mt-3">
                            <button id="clearCustomerData" type="button" class="btn btn-sm btn-secondary">Clear</button>
                        </div>
                    </div>

                    {{-- RIGHT: Items --}}
                    <div class="flex-grow-1" style="min-width: 0;"> <!-- Fix flex overflow -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="section-title mb-0">Items</div>
                            <button type="button" class="btn btn-sm btn-primary" id="btnAdd">Add Row</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered sales-table mb-0">

                                <thead>
                                    <tr>
                                        <th class="col-product">Product</th>
                                        <th style="width: 140px;">Size (H x W)</th>
                                        <th style="width: 100px;">Mode</th>
                                        <th class="col-warehouse">Warehouse</th>
                                        <th class="col-qty qty-header">Total Boxes</th>
                                        <th class="col-qty pack-size-col">Conv Factor</th>
                                        <th class="col-pieces boxes-col">Pieces</th>
                                        <th class="col-price-p price-pc-header">Retail Price</th>
                                        <th class="col-disc">Disc %</th>
                                        <th class="col-disc-amt">Disc Amt</th>
                                        <th class="col-amount">Amount</th>
                                        <th class="col-action">—</th>
                                    </tr>
                                </thead>
                                <tbody id="salesTableBody">
                                    @if (isset($sale) && $sale->items)
                                        @foreach ($sale->items as $item)
                                            @php
                                                $prod = $item->product;
                                                // Identify Pack Size - Robust Check
                                                // Prioritize Item Snapshot for Mode & PPB
                                                $sizeMode = $item->size_mode ?? ($prod->size_mode ?? 'std');

                                                $ppb = 1;
                                                if ($item->pieces_per_box > 0) {
                                                    $ppb = $item->pieces_per_box;
                                                } elseif ($prod && $prod->pieces_per_box > 0) {
                                                    $ppb = $prod->pieces_per_box;
                                                }

                                                $loose = $item->loose_pieces ?? 0;

                                                // Prepare Main Qty Display
                                                $qtyDisplay = $item->total_pieces;

                                                // Standard calculation for Box.Loose format if applicable
                                                if (in_array($sizeMode, ['by_cartons', 'by_size']) && $ppb > 0) {
                                                    // Strictly recalculate from Total Pieces
                                                    $boxes = floor($item->total_pieces / $ppb);
                                                    $calcLoose = $item->total_pieces % $ppb;
                                                    $qtyDisplay = $calcLoose > 0 ? "$boxes.$calcLoose" : $boxes;
                                                }

                                                // Calculate Warehouse Stock Display for the SELECTED warehouse
                                                $selStockDisp = '';
                                                if ($item->warehouse_id) {
                                                    $selWs = $prod->warehouseStocks
                                                        ->where('warehouse_id', $item->warehouse_id)
                                                        ->first();
                                                    if ($selWs) {
                                                        $stk = (float) $selWs->total_pieces;
                                                        if ($stk <= 0 && $selWs->quantity > 0) {
                                                            $stk = $selWs->quantity * $ppb;
                                                        }

                                                        $b = floor($stk / $ppb);
                                                        $l = $stk % $ppb;

                                                        $selStockDisp =
                                                            in_array($sizeMode, ['by_cartons', 'by_size']) && $ppb > 0
                                                                ? ($l > 0
                                                                    ? "$b.$l"
                                                                    : $b)
                                                                : $stk;
                                                    }
                                                }
                                            @endphp
                                            <tr data-size_mode="{{ $sizeMode }}"
                                                data-pieces_per_box="{{ $ppb }}"
                                                data-price_per_m2="{{ $prod->price_per_m2 ?? 0 }}"
                                                data-stock_display="{{ $selStockDisp }}">
                                                <!-- Product -->
                                                <td class="col-product">
                                                    <select class="form-select product" name="composite_id[]"
                                                        style="width:100%">
                                                        @if ($prod)
                                                            <option value="{{ $item->product_id }}|{{ $item->product_package_id }}" selected>
                                                                {{ $prod->item_name }} - {{ optional($item->package)->name ?? optional($item->package)->code ?? '' }}</option>
                                                        @endif
                                                    </select>
                                                    <input type="hidden" name="product_id[]" class="actual-product-id" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="product_package_id[]" class="product-package-id" value="{{ $item->product_package_id }}">
                                                </td>

                                                <!-- Size Info -->
                                                <td>
                                                    <div class="d-flex align-items-center gap-1 justify-content-center">
                                                        <input type="text"
                                                            class="form-control text-center px-0 size-h input-readonly"
                                                            readonly value="{{ $prod->height }}"
                                                            style="font-size: 0.8rem; width: 45px;" tabindex="-1">
                                                        <span class="text-muted small">x</span>
                                                        <input type="text"
                                                            class="form-control text-center px-0 size-w input-readonly"
                                                            readonly value="{{ $prod->width }}"
                                                            style="font-size: 0.8rem; width: 45px;" tabindex="-1">
                                                    </div>
                                                </td>

                                                <!-- Size Mode -->
                                                <td>
                                                    <input type="text"
                                                        class="form-control text-center px-1 size-mode-text input-readonly"
                                                        readonly value="{{ $sizeMode }}" tabindex="-1"
                                                        style="font-size: 0.8rem; background-color: #f8f9fa;">
                                                </td>

                                                <!-- Warehouse -->
                                                <td class="col-warehouse">
                                                    <select class="form-select warehouse" name="warehouse_id[]">
                                                        <option value="">Select Warehouse</option>
                                                        @foreach ($warehouse as $w)
                                                            @php
                                                                $wStk = 0;
                                                                $ws = $prod->warehouseStocks
                                                                    ->where('warehouse_id', $w->id)
                                                                    ->where('product_id', $prod->id)
                                                                    ->first();
                                                                if ($ws) {
                                                                    $wStk = (float) $ws->total_pieces;
                                                                    if ($wStk <= 0 && $ws->quantity > 0) {
                                                                        $wStk = $ws->quantity * $ppb;
                                                                    }
                                                                }
                                                                $wDisp = $wStk;
                                                                if (
                                                                    in_array($sizeMode, ['by_cartons', 'by_size']) &&
                                                                    $ppb > 0
                                                                ) {
                                                                    $wb = floor($wStk / $ppb);
                                                                    $wl = $wStk % $ppb;
                                                                    $wDisp = $wl > 0 ? "$wb.$wl" : $wb;
                                                                }
                                                            @endphp
                                                            <option value="{{ $w->id }}"
                                                                data-stock="{{ $wStk }}"
                                                                {{ $item->warehouse_id == $w->id ? 'selected' : '' }}>
                                                                {{ $w->warehouse_name }} (Stock: {{ $wDisp }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>

                                                <!-- Qty Input -->
                                                <td class="col-qty">
                                                    <div class="qty-wrapper input-group input-group-sm">
                                                        <input type="text" class="form-control sales-qty text-end"
                                                            name="qty[]" id="sales-qty" value="{{ $qtyDisplay }}"
                                                            placeholder="Qty">
                                                        <span class="input-group-text qty-unit-label">{{ optional($item->package)->symbol ?? 'pc' }}</span>
                                                    </div>
                                                </td>

                                                <!-- Pack Size -->
                                                <td class="col-qty">
                                                    <input type="text"
                                                        class="form-control pack-qty text-end input-readonly"
                                                        name="pack_qty[]" readonly value="{{ $ppb }}"
                                                        tabindex="-1">
                                                </td>


                                                <!-- Total -->
                                                <td class="col-pieces">
                                                    <input type="text"
                                                        class="form-control total-pieces text-end input-readonly"
                                                        name="total_pieces[]" readonly value="{{ $item->total_pieces }}"
                                                        tabindex="-1">
                                                </td>

                                                <!-- Price -->
                                                <td class="col-price-p">
                                                    <input type="text"
                                                        class="form-control visible-price text-end input-readonly"
                                                        name="visible_price[]" readonly
                                                        value="{{ $item->product->sale_price_per_box ?? 0 }}"
                                                        tabindex="-1">
                                                    <input type="hidden" class="price-per-piece"
                                                        name="price_per_piece[]"
                                                        value="{{ $item->product->sale_price_per_box ?? 0 }}">
                                                    <input type="hidden" class="retail-price"
                                                        value="{{ $item->product->retail_price ?? 0 }}">
                                                </td>

                                                <!-- Discount -->
                                                <td class="col-disc">
                                                    <div class="discount-wrapper">
                                                        <input type="number" class="form-control discount-value text-end"
                                                            name="item_disc[]" value="{{ $item->discount_percent }}">
                                                        <button type="button"
                                                            class="btn btn-outline-secondary discount-toggle"
                                                            data-type="percent" tabindex="-1">%</button>
                                                    </div>
                                                </td>

                                                <!-- Disc Amt -->
                                                <td class="col-disc-amt">
                                                    <input type="text" class="form-control discount-amount text-end">
                                                </td>

                                                <!-- Amount -->
                                                <td class="col-amount">
                                                    <input type="text"
                                                        class="form-control sales-amount text-end input-readonly"
                                                        name="total[]" value="{{ $item->total }}" readonly
                                                        tabindex="-1">
                                                </td>

                                                <!-- Action -->
                                                <td class="col-action">
                                                    <button type="button" class="btn btn-sm btn-outline-danger del-row"
                                                        tabindex="-1">&times;</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="10" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold"><span id="totalAmount">0.00</span></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Totals + Receipts --}}
                <div class="row g-3 mt-3">
                    <div class="col-lg-7">
                        <div class="section-title mb-2">Receipt Vouchers</div>
                        <div id="rvWrapper" class="border rounded-3 p-2">
                            <div class="d-flex gap-2 align-items-center mb-2 rv-row">
                                <select class="form-select rv-account" name="receipt_account_id[]"
                                    style="max-width: 320px">
                                    <option value="" selected disabled>Select account</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]"
                                    placeholder="0.00" style="max-width:160px">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddRV">Add
                                    more</button>
                            </div>
                            <div class="text-end">
                                <span class="me-2">Receipts Total:</span>
                                <span class="fw-bold" id="receiptsTotal">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="section-title mb-2">Totals</div>
                        <div class="totals-card p-3">
                            <div class="row py-1">
                                <div class="col-7 text-muted fw-medium">Total Qty <span id="tQtyUnit"></span></div>
                                <div class="col-5 text-end"><span id="tQty">0</span></div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7 text-muted">Invoice Gross (Σ Sales Price × Qty)</div>
                                <div class="col-5 text-end"><span id="tGross">0.00</span></div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7 text-muted">Line Discount (on Retail)</div>
                                <div class="col-5 text-end"><span id="tLineDisc">0.00</span></div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7 fw-semibold">Sub-Total</div>
                                <div class="col-5 text-end fw-semibold"><span id="tSub">0.00</span></div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7">Aditional Discount %</div>
                                <div class="col-5 text-end">
                                    <input type="text" class="form-control text-end" name="discountPercent"
                                        id="discountPercent"
                                        value="{{ isset($sale) && $sale->total_bill_amount > 0 ? number_format(($sale->total_extradiscount / $sale->total_bill_amount) * 100, 2) : 0 }}"
                                        style="max-width:120px; margin-left:auto">
                                </div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7 text-muted">Aditional Discount Rs</div>
                                <div class="col-5 text-end"><span id="tOrderDisc">0.00</span></div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7 fw-bold">Current Bill Total</div>
                                <div class="col-5 text-end fw-bold"><span id="tCurrentBill">0.00</span></div>
                            </div>
                            <div class="row py-1">
                                <div class="col-7 text-danger">Previous Balance</div>
                                <div class="col-5 text-end text-danger"><span
                                        id="tPrev">{{ number_format(optional($sale->customer_relation)->previous_balance ?? 0, 2) }}</span>
                                </div>
                            </div>
                            <div class="row py-2">
                                <div class="col-7 fw-bold text-primary">Payable / Total Balance</div>
                                <div class="col-5 text-end fw-bold text-primary"><span id="tPayable">0.00</span></div>
                            </div>

                            {{-- hidden mirrors for backend --}}
                            {{-- Maps to 'total_bill_amount' in DB (SubTotal AFTER line discounts) --}}
                            <input type="hidden" name="subTotal1" id="subTotal1" value="0">
                            <input type="hidden" name="total_subtotal" id="subTotal2" value="0">

                            {{-- Maps to 'total_extradiscount' in DB --}}
                            <input type="hidden" name="total_extra_cost" id="discountAmount" value="0">

                            {{-- Maps to 'total_net' in DB --}}
                            <input type="hidden" name="total_net" id="totalBalance" value="0">

                            {{-- Default values for nullable fields to satisfy controller --}}
                            <input type="hidden" name="cash" value="0">
                            <input type="hidden" name="card" value="0">
                            <input type="hidden" name="change" value="0">
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="d-flex flex-wrap gap-2 justify-content-center p-3 mt-3 border-top">
                    <button type="button" class="btn btn-sm btn-primary" id="btnSave"><i class="fas fa-bookmark me-1"></i>Booking</button>
                    <button type="button" class="btn btn-sm btn-success" id="btnPosted" disabled><i class="fas fa-check-circle me-1"></i>Sale</button>

                    <button type="button" class="btn btn-sm btn-secondary" id="btnPrint"><i class="fas fa-print me-1"></i>Print A4 Half</button>
                    <button type="button" class="btn btn-sm btn-outline-info" id="btnEstimate"><i class="fas fa-file-invoice me-1"></i>Estimate</button>
                    <button type="button" class="btn btn-sm btn-secondary" id="btnPrint2"><i class="fas fa-receipt me-1"></i>Print Thermal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnDcThermal"><i class="fas fa-truck me-1"></i>DC Thermal</button>
                </div>
            </form>
            @endsection

            @section('js')
                @include('admin_panel.sale.scripts.shared_logic')
                <script>
                    // --- Customer Logic (Ported from shared_logic/add_sale) ---
                    $(document).on('change', 'input[name="partyType"]', function() {
                        $('#customerSelect').val('').trigger('change');
                        loadCustomersByType(this.value);
                    });

                    function loadCustomersByType(type) {
                        $('#customerSelect').prop('disabled', true).html('<option selected disabled>Loading…</option>');
                        $.get('{{ route('salecustomers.index') }}', {
                            type: type
                        }, function(data) {
                            let html = '<option value="">-- Select --</option>';
                            if (data && data.length > 0) {
                                data.forEach(row => {
                                    html +=
                                        `<option value="${row.id}">${row.customer_id} -- ${row.customer_name}</option>`;
                                });
                                $('#customerCountHint').text(data.length + ' record(s) found');
                            } else {
                                html += '<option disabled>No record found</option>';
                                $('#customerCountHint').text('No record found');
                            }
                            $('#customerSelect').html(html).prop('disabled', false);
                        }).fail(function() {
                            $('#customerSelect').html('<option disabled>Error loading</option>');
                        });
                    }

                    $(document).on('change', '#customerSelect', function() {
                        const id = $(this).val();
                        if (!id) return;
                        // Don't clear fields in Edit mode if just initializing, but here it's a change event

                        $.get("{{ url('sale/customers') }}/" + id + "?t=" + new Date().getTime(), function(d) {
                            $('#address').val(d.address || '');
                            $('#tel').val(d.mobile || '');
                            $('#remarks').val(d.status || '');
                            const prev = parseFloat(d.previous_balance || 0);
                            const range = parseFloat(d.balance_range || 0);
                            $('#previousBalance').val(prev.toFixed(2));
                            $('#rangeBalance').val(range.toFixed(2));

                            if (typeof updateGrandTotals === 'function') updateGrandTotals();
                        });
                    });

                    $('#clearCustomerData').on('click', function() {
                        $('#customerSelect').val('').trigger('change');
                        $('#address, #tel, #remarks').val('');
                        $('#previousBalance, #rangeBalance').val('0');
                        updateGrandTotals();
                    });

                    // Edit Sale Specific Handlers
                    $('#btnPrint').on('click', function() {
                        ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/invoice', '_blank'));
                    });
                    $('#btnEstimate').on('click', function() {
                        ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/invoice?type=estimate', '_blank'));
                    });
                    $('#btnPrint2').on('click', function() {
                        ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/recepit', '_blank'));
                    });
                    $('#btnDcThermal').on('click', function() {
                        ensureSaved().then(id => window.open('{{ url('sales') }}/' + id + '/dc-thermal', '_blank'));
                    });

                </script>
                @if (isset($sale))
                    <script>
                        $(document).ready(function() {
                            // --- PRE-FILL EDIT MODE (Server Side Rendered) ---
                            console.log("Loading Edit Mode for Sale #{{ $sale->id }}");
                            $('#booking_id').val("{{ $sale->id }}");
                            $('#entryDateTime').text("Date: {{ $sale->created_at->format('Y-m-d H:i') }}");

                            // Initialize Select2 on server-rendered rows
                            $('.product').each(function() {
                                if (typeof initProductSelect2 === 'function') {
                                    initProductSelect2($(this));
                                }
                            });

                            // Recalculate totals based on rendered values
                            $('#salesTableBody tr').each(function() {
                                if (typeof computeRow === 'function') {
                                    computeRow($(this));
                                }
                            });
                            updateGrandTotals();

                            setTimeout(() => {
                                $('#pageLoader').addClass('d-none');
                            }, 300);
                        });
                    </script>
                @endif
            @endsection
