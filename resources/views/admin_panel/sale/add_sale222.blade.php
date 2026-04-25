@extends('admin_panel.layout.app')

@section('content')
    <!-- Loader Overlay -->
    <div id="pageLoader"
        class="{{ isset($sale) ? '' : 'd-none' }} position-fixed top-0 start-0 w-100 h-100 d-flex flex-column gap-3 justify-content-center align-items-center"
        style="background: rgba(255,255,255,0.9); z-index: 1055;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="fw-bold text-primary fs-5">Loading Sale Data...</div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            min-width: 700px;
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
                min-width: 700px;
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
                min-width: 650px;
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
            min-width: 300px;
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
            min-width: 900px;
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

        .col-stock {
            width: 90px;
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

            <form id="saleForm" autocomplete="off">
                @csrf
                <input type="hidden" id="booking_id" name="booking_id" value="">
                <input type="hidden" id="action" name="action" value="sale">

                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div>
                        <small class="text-secondary" id="entryDateTime">Entry Date_Time: --</small> <br>
                        <a href="{{ route('sale.index') }}" target="_blank" rel="noopener"
                            class="btn btn-sm btn-outline-secondary" title="Sales List (opens new tab)">
                            Sales List
                        </a>
                    </div>


                    <h2 class="header-text text-secondary fw-bold mb-0">Sales</h2>


                    <div class="d-flex align-items-center gap-2">
                        <small class="text-secondary me-2" id="entryDate">Date: --</small>
                        <button type="button" class="btn btn-sm btn-success" id="btnHeaderPosted"
                            disabled>Sale</button>
                    </div>
                </div>

                <div class="d-flex gap-3 align-items-start border-bottom py-3">
                    {{-- LEFT: Invoice & Customer --}}
                    <div class="p-3 border rounded-3 minw-350">
                        <div class="section-title mb-3">Invoice & Customer</div>

                        <div class="mb-2 d-flex align-items-center gap-2">
                            <label class="form-label fw-bold mb-0">Invoice No.</label>
                            <input type="text" class="form-control input-readonly" name="Invoice_no" style="width:150px"
                                value="{{ $nextInvoiceNumber }}" readonly>
                            <!-- <label class="form-label fw-bold mb-0">M. Inv#</label>
                            <input type="text" class="form-control" name="Invoice_main" placeholder="Manual invoice"
                                value="{{ $sale->reference ?? '' }}"> -->
                        </div>

                        {{-- Credit Days (Optional) --}}
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <label class="form-label fw-bold mb-0" style="min-width: 90px;">Credit Days</label>
                            <input type="number" class="form-control" name="credit_days" placeholder="Optional"
                                style="width:100px" min="0" value="{{ $sale->credit_days ?? '' }}">
                            <!-- <small class="text-muted">(Leave empty for no notification)</small> -->
                        </div>

                        {{-- Type toggle --}}
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-1 d-block">Type</label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group" role="group" id="partyTypeGroup">
                                    <input type="radio" class="btn-check" name="partyType" id="typeCustomers"
                                        value="Main Customer" checked>
                                    <label class="btn btn-outline-primary btn-sm" for="typeCustomers">Customers</label>

                                    <input type="radio" class="btn-check" name="partyType" id="typeWalkin"
                                        value="Walking Customer">
                                    <label class="btn btn-outline-primary btn-sm" for="typeWalkin">Walk-in</label>
                                </div>
                                <button type="button" class="mb-2 btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add New Customer">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>

                        <!-- CUSTOMER SELECT (Select2 Search) -->
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-1">Select Customer</label>
                            <select class="form-select" id="customerSelect" name="customer" style="width:100%">
                                <option value=""></option>
                            </select>
                            <small class="text-muted" id="customerCountHint"></small>
                        </div>

                        <!-- CUSTOMER INFO CARD -->
                        <div id="customerInfoCard" class="mb-2 p-2 border rounded-2 bg-light d-none">
                            <table class="table table-sm table-borderless mb-0" style="font-size:0.82rem">
                                <tr>
                                    <td class="fw-bold text-muted py-0" style="width:90px">Code</td>
                                    <td class="py-0" id="ci_code">—</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted py-0">Name</td>
                                    <td class="py-0" id="ci_name">—</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted py-0">Mobile</td>
                                    <td class="py-0" id="ci_mobile">—</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted py-0">Address</td>
                                    <td class="py-0" id="ci_address">—</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-danger py-0">Prev. Bal</td>
                                    <td class="py-0 text-danger fw-bold" id="ci_prev_bal">0.00</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted py-0">Credit Limit</td>
                                    <td class="py-0" id="ci_range_bal">0.00</td>
                                </tr>
                            </table>
                        </div>

                        <!-- SALES OFFICER -->
                        <!-- <div class="mb-2">
                            <label class="form-label fw-bold mb-1">Sales Officer</label>
                            <select class="form-select" id="salesOfficerSelect" name="sales_officer_id">
                                <option value="">-- Select Officer --</option>
                                @foreach(\App\Models\SalesOfficer::orderBy('name')->get() as $officer)
                                    <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                                @endforeach
                            </select>
                        </div> -->

                        <div class="mb-2">
                            <label class="form-label fw-bold">Remarks</label>
                            <textarea class="form-control" name="reference" id="remarks"></textarea>
                        </div>

                        {{-- Hidden fields for backend --}}
                        <input type="hidden" id="address" name="address">
                        <input type="hidden" id="tel" name="tel">
                        <input type="hidden" id="previousBalance" value="0">
                        <input type="hidden" id="rangeBalance" value="0">

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
                                        <th class="col-stock">Conv Factor</th>
                                        <th class="col-qty qty-header">Total Qty</th>
                                        <th class="col-qty pack-size-col">Pices Box</th>
                                        <th class="col-pieces boxes-col">total pieces</th>
                                        <th class="col-price-p price-pc-header">Retail Price</th>
                                        <th class="col-disc">Disc %</th>
                                        <th class="col-disc-amt">Disc Amt</th>
                                        <th class="col-amount">Amount</th>
                                        <th class="col-action">—</th>
                                    </tr>
                                </thead>
                                <tbody id="salesTableBody">

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="8" class="text-end fw-bold">Total:</td>
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
                                <div class="col-7 text-muted">Total Qty <span id="tQtyUnit"></span></div>
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
                                        id="discountPercent" value="0" style="max-width:120px; margin-left:auto">
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
                                <div class="col-5 text-end text-danger"><span id="tPrev">0.00</span></div>
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
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">
                        <i class="fas fa-user-plus text-primary me-2"></i>New Customer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ajaxAddCustomerForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Customer Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="customer_type" required>
                                    <option value="Main Customer">Main Customer</option>
                                    <option value="Walking Customer">Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_name" required placeholder="Customer Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Mobile</label>
                                <input type="text" class="form-control" name="mobile" placeholder="0300-1234567">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Opening Balance</label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Address</label>
                                <input type="text" class="form-control" name="address" placeholder="Address">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnSaveAjaxCustomer">Save Customer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- sjadlfksal --}}

    <script>
        /* ========== DISCOUNT TOGGLE (% ↔ PKR) ========== */

        // $(document).on('click', '.discount-toggle', function () {

        //     const $btn = $(this);
        //     const currentType = $btn.data('type');

        //     if (currentType === 'percent') {
        //         // switch to PKR
        //         $btn.data('type', 'pkr');
        //         $btn.text('PKR');
        //     } else {
        //         // switch to %
        //         $btn.data('type', 'percent');
        //         $btn.text('%');
        //     }

        //     // focus back to input
        //     $btn.closest('.discount-wrapper')
        //         .find('.discount-value')
        //         .focus();
        // });
    </script>














    {{-- hajshdsadsdsksa --}}

    {{-- Shared Logic for Sales (Add/Edit) --}}
@endsection

@section('js')
    @include('admin_panel.sale.scripts.shared_logic')

    <script>
        $(document).ready(function() {
            // --- Initial Setup ---
            if ($('#salesTableBody tr').length === 0) {
                addNewRow();
            }
            updateGrandTotals();
            refreshPostedState();

            // ============================================================
            // CUSTOMER SELECT2 AJAX SEARCH (Name or Code)
            // ============================================================
            function getPartyType() {
                return $('input[name="partyType"]:checked').val() || 'Main Customer';
            }

            $('#customerSelect').select2({
                placeholder: 'Search by Name or Code...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('salecustomers.index') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            type: getPartyType(),
                            search: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(c) {
                                return {
                                    id: c.id,
                                    text: (c.customer_id || '') + ' — ' + c.customer_name,
                                    customer: c
                                };
                            })
                        };
                    },
                    cache: false
                },
                templateResult: function(item) {
                    if (item.loading) return item.text;
                    if (!item.customer) return item.text;
                    const c = item.customer;
                    return $(`<div>
                        <strong>${c.customer_name}</strong>
                        <small class="text-muted ms-2">${c.customer_id || ''}</small>
                        ${c.mobile ? '<br><small class="text-muted">' + c.mobile + '</small>' : ''}
                    </div>`);
                },
                templateSelection: function(item) {
                    if (!item.customer) return item.text;
                    return item.customer.customer_id + ' — ' + item.customer.customer_name;
                }
            });

            // Party type change → reset customer
            $(document).on('change', 'input[name="partyType"]', function() {
                $('#customerSelect').val(null).trigger('change');
                clearCustomerInfo();
            });

            // Customer selected → load details
            $('#customerSelect').on('select2:select', function(e) {
                const id = e.params.data.id;
                if (!id) return;

                $.get("{{ url('sale/customers') }}/" + id + "?t=" + new Date().getTime(), function(d) {
                    // Fill hidden fields
                    $('#address').val(d.address || '');
                    $('#tel').val(d.mobile || '');
                    const prev = parseFloat(d.previous_balance || 0);
                    const range = parseFloat(d.balance_range || 0);
                    $('#previousBalance').val(prev.toFixed(2));
                    $('#rangeBalance').val(range.toFixed(2));

                    // Fill info card
                    $('#ci_code').text(d.customer_id || '—');
                    $('#ci_name').text(d.customer_name || '—');
                    $('#ci_mobile').text(d.mobile || '—');
                    $('#ci_address').text(d.address || '—');
                    $('#ci_prev_bal').text(prev.toFixed(2));
                    $('#ci_range_bal').text(range.toFixed(2));
                    $('#customerInfoCard').removeClass('d-none');

                    // Auto-fill Sales Officer if customer has one
                    if (d.sales_officer_id) {
                        $('#salesOfficerSelect').val(d.sales_officer_id);
                    }

                    if (typeof updateGrandTotals === 'function') updateGrandTotals();
                }).fail(function() {
                    showAlert('error', 'Failed to load customer details');
                });
            });

            // Customer cleared
            $('#customerSelect').on('select2:clear', function() {
                clearCustomerInfo();
                if (typeof updateGrandTotals === 'function') updateGrandTotals();
            });

            function clearCustomerInfo() {
                $('#address, #tel').val('');
                $('#previousBalance, #rangeBalance').val('0');
                $('#ci_code, #ci_name, #ci_mobile, #ci_address').text('—');
                $('#ci_prev_bal, #ci_range_bal').text('0.00');
                $('#customerInfoCard').addClass('d-none');
                $('#salesOfficerSelect').val('');
            }

            $('#clearCustomerData').on('click', function() {
                $('#customerSelect').val(null).trigger('change');
                clearCustomerInfo();
                if (typeof updateGrandTotals === 'function') updateGrandTotals();
            });

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

            // AJAX Customer Submit
            $('#btnSaveAjaxCustomer').on('click', function() {
                let form = $('#ajaxAddCustomerForm');
                if (!form[0].checkValidity()) {
                    form[0].reportValidity();
                    return;
                }
                
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    url: '{{ route('customers.store') }}',
                    type: 'POST',
                    data: form.serialize(),
                    success: function(res) {
                        btn.prop('disabled', false).text('Save Customer');
                        if (res.success) {
                            $('#addCustomerModal').modal('hide');
                            form[0].reset();
                            
                            // Make sure UI toggles map to the new customer's type
                            if (res.customer.customer_type === 'Walking Customer') {
                                $('#typeWalkin').prop('checked', true).trigger('change');
                            } else {
                                $('#typeCustomers').prop('checked', true).trigger('change');
                            }
                            
                            // Auto select new customer
                            let newOption = new Option(res.customer.customer_id + ' — ' + res.customer.customer_name, res.customer.id, true, true);
                            $('#customerSelect').append(newOption).trigger('change');
                            
                            // trigger select2 API selection to load customer details like Prev Bal
                            $('#customerSelect').trigger({
                                type: 'select2:select',
                                params: {
                                    data: {
                                        id: res.customer.id,
                                        text: res.customer.customer_id + ' — ' + res.customer.customer_name
                                    }
                                }
                            });
                            
                            showAlert('success', 'Customer added successfully!');
                        }
                    },
                    error: function(err) {
                        btn.prop('disabled', false).text('Save Customer');
                        showAlert('error', 'Error adding customer. Check inputs.');
                    }
                });
            });
        });
    </script>
@endsection
