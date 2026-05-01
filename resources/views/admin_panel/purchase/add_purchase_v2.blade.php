@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* ================= RESPONSIVE PURCHASE UI (Modernized) ================= */
        body {
            background-color: #f4f6f9;
            /* Light gray background for contrast */
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .sales-table {
            min-width: 1000px;
            /* Base width */
            border-collapse: separate;
            border-spacing: 0;
        }

        .sales-table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 12px 8px;
            border-bottom: 2px solid #e9ecef !important;
        }

        .sales-table tbody td {
            vertical-align: middle;
            padding: 8px;
            border-color: #f1f3f5;
        }

        .sales-table tfoot td {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }

        /* Premium Table Look */
        .table-bordered>:not(caption)>*>* {
            border-width: 1px;
            border-color: #e9ecef;
        }

        /* Column widths */
        .col-product {
            width: 300px;
            min-width: 250px;
        }

        .col-warehouse {
            width: 140px;
        }

        .col-stock {
            width: 130px;
        }

        .col-qty {
            width: 100px;
        }

        .col-pieces {
            width: 100px;
        }

        .col-price {
            width: 120px;
        }

        .col-disc {
            width: 80px;
        }

        .col-disc-amt {
            width: 95px;
        }

        .col-price-p {
            width: 100px;
        }

        .col-amount {
            width: 120px;
            text-align: right;
        }

        .col-action {
            width: 50px;
            text-align: center;
        }

        .input-readonly {
            background: #f8f9fa;
            color: #495057;
            font-weight: 500;
            border: 1px solid #dee2e6;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.4rem 0.6rem;
            font-size: 0.85rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .main-container {
            font-size: .85rem;
            max-width: 99%;
            border-radius: 12px !important;
            border: none !important;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08) !important;
        }

        .btn {
            font-size: .82rem;
            padding: .35rem .8rem;
            border-radius: 5px;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }

        .section-title {
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
            border-left: 3px solid #0d6efd;
            padding-left: 8px;
        }

        /* Product Search Dropdown */
        .search-results {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            width: 100%;
            list-style: none;
            padding: 0;
            margin: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
        }

        .search-result-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.1s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover,
        .search-result-item.active {
            background-color: #e7f1ff;
            color: #0b5ed7;
        }

        /* Layout Helpers */
        .card-panel {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
        }

        .summary-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
    </style>
    <style>
        /* Custom Stock Popover */
        .qty-popover {
            position: absolute;
            background: white;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 8px 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
            z-index: 10001;
            pointer-events: none;
            display: none;
            white-space: nowrap;
            transition: opacity 0.2s;
        }

        .qty-popover h6 {
            margin: 0;
            font-weight: 700;
            color: #212529;
            font-size: 0.9rem;
        }

        .qty-popover::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid #28a745;
        }
    </style>
    <div id="globalQtyPopover" class="qty-popover">
        <h6 id="popoverStockText">Quantity In Hand: 0</h6>
    </div>

    <div class="container-fluid py-2">
        <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3">

            <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

            <form id="purchaseForm" action="{{ route('store.Purchase') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="action" name="action" value="purchase">

                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div>
                        <a href="{{ route('Purchase.home') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>

                    <h2 class="header-text text-secondary fw-bold mb-0">Purchase Entry</h2>

                    <div class="d-flex align-items-center gap-2">
                        <small class="text-secondary" id="entryDate">Date: {{ date('d-M-Y') }}</small>
                    </div>
                </div>

                <div class="row g-3 border-bottom pb-4 mb-3">
                    {{-- LEFT: Invoice & Vendor --}}
                    <div class="col-lg-3 col-md-4">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Invoice & Vendor</div>

                            <div class="mb-2 d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0 text-muted small" style="min-width: 80px;">System
                                    No.</label>
                                <input type="text" class="form-control input-readonly" name="invoice_no"
                                    value="{{ $nextInvoice ?? 'NEW' }}" readonly>
                            </div>

                            <div class="mb-2 d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0 text-muted small" style="min-width: 80px;">Vendor
                                    Inv#</label>
                                <input type="text" class="form-control" name="purchase_order_no"
                                    placeholder="Manual Ref">
                            </div>

                            <!-- VENDOR SELECT -->
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1 text-muted small">Select Vendor</label>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="flex-grow-1">
                                        <select class="form-select select2" id="vendorSelect" name="vendor_id">
                                            <option value="" selected disabled>Select Vendor</option>
                                            @foreach ($Vendor as $v)
                                                <option value="{{ $v->id }}" data-phone="{{ $v->phone }}"
                                                    data-address="{{ $v->address }}">{{ $v->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addVendorModal" style="padding: 0.38rem 0.75rem;" title="Add New Vendor">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1 text-muted small">Date</label>
                                <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Remarks</label>
                                <textarea class="form-control" name="note" id="remarks" rows="2" placeholder="Optional notes..."></textarea>
                            </div>

                            <!-- VENDOR INFO CARD -->
                            <div id="vendorInfoCard" class="mt-3 p-2 border rounded-2 bg-light d-none">
                                <div class="fw-bold text-muted small mb-2 border-bottom pb-1">Vendor Details</div>
                                <table class="table table-sm table-borderless mb-0" style="font-size:0.82rem">
                                    <tr>
                                        <td class="fw-bold text-muted py-0" style="width:90px">Mobile</td>
                                        <td class="py-0" id="vi_mobile">—</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted py-0">Address</td>
                                        <td class="py-0" id="vi_address">—</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-danger py-0">Prev. Bal</td>
                                        <td class="py-0 text-danger fw-bold" id="vi_prev_bal">0.00</td>
                                    </tr>
                                </table>
                            </div>

                            <input type="hidden" name="warehouse_id" value="{{ $Warehouse->first()->id ?? 1 }}">

                        </div>
                    </div>

                    {{-- RIGHT: Items --}}
                    <div class="col-lg-9 col-md-8">
                        <div class="card-panel shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="section-title mb-0">Purchase Items</div>
                                <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" id="btnAdd">
                                    <i class="bi bi-plus-lg"></i> Add Row
                                </button>
                            </div>

                            <div class="table-responsive border rounded-3 bg-white">
                                <table class="table table-bordered sales-table mb-0" id="purchaseTable">
                                    <thead>
                                        <tr>
                                            <th class="col-product">Product</th>
                                            <th class="col-qty">Qty</th> <!-- Was Total Pcs -->
                                            <th class="col-stock">Conv Factor</th>
                                            <!-- Loose Column Removed -->
                                            <th class="col-pieces">Pieces</th> <!-- Was Boxes -->
                                            <th class="col-price">Purchase Price</th>
                                            <th class="col-disc">Disc %</th>
                                            <th class="col-disc-amt">Disc Amt</th>
                                            <th class="col-amount">Amount</th>
                                            <th class="col-action">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseTableBody">
                                        <!-- Rows added via JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7" class="text-end fw-bold text-muted">Total Amount:</td>
                                            <td class="text-end fw-bold fs-6 text-dark"><span id="totalAmount">0.00</span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Totals + Summary --}}
                <div class="row g-3 mt-1">
                    <div class="col-lg-7">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Payment / Receipt Voucher</div>
                            <div id="paymentWrapper" class="border rounded p-3 bg-light mb-3">
                                <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                                    <select class="form-select rv-account" name="payment_account_id[]"
                                        style="max-width: 300px; flex-grow: 1;">
                                        <option value="" selected disabled>Select Account</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" class="form-control text-end payment-amount"
                                        name="payment_amount[]" placeholder="Amount" style="width:140px">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPayment">
                                        <i class="bi bi-plus"></i> Add
                                    </button>
                                </div>
                                <!-- Additional rows will be appended here -->
                            </div>
                            <div class="text-end">
                                <span class="me-2 fw-bold text-muted">Total Paid:</span>
                                <span class="fw-bold fs-6 text-success" id="totalPaid">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="bg-white shadow-sm rounded-3 p-3 h-100 border">
                            <div class="section-title mb-3">Summary</div>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-muted fw-medium">Total Qty <span id="tQtyUnit"></span></div>
                                    <div class="col-5 text-end"><span id="tQty" class="fw-bold">0</span></div>
                                </div>
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-muted fw-medium">Sub-Total</div>
                                    <div class="col-5 text-end fw-bold"><span id="tSub">0.00</span></div>
                                </div>
                                <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Bill Discount</div>
                                <div class="col-5 text-end d-flex gap-1">
                                    <input type="number" class="form-control text-end form-control-sm"
                                        id="billDiscountPct" placeholder="%" style="width: 70px;" step="0.01">
                                    <input type="number" class="form-control text-end form-control-sm"
                                        name="discount" id="billDiscount" value="0" step="0.01">
                                </div>
                            </div>
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-muted fw-medium">Extra Cost</div>
                                    <div class="col-5 text-end">
                                        <input type="number" class="form-control text-end form-control-sm"
                                            name="extra_cost" id="extraCost" value="0">
                                    </div>
                                </div>
                                <div class="row py-1 align-items-center">
                                    <div class="col-7 text-danger fw-medium">Previous Balance</div>
                                    <div class="col-5 text-end text-danger fw-bold"><span id="tPrev">0.00</span></div>
                                </div>
                                <hr class="my-2 border-secondary">
                                <div class="row py-2">
                                    <div class="col-6 fw-bold fs-5 text-primary">Current Bill</div>
                                    <div class="col-6 text-end fw-bold fs-5 text-primary"><span id="tPayable">0.00</span></div>
                                </div>
                                <div class="row py-2 bg-warning-subtle rounded-2">
                                    <div class="col-6 fw-bold fs-5 text-dark">Total Payable</div>
                                    <div class="col-6 text-end fw-bold fs-5 text-dark"><span id="tTotalPayable">0.00</span></div>
                                </div>
                                <input type="hidden" name="net_amount" id="netAmountInput" value="0">
                                <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Buttons --}}
                <div class="d-flex flex-wrap gap-3 justify-content-end p-3 mt-3 border-top bg-light rounded-bottom">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold"
                        onclick="window.location.reload()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                    {{-- New Save Only Button --}}
                    <button type="button" class="btn btn-info px-4 fw-bold shadow-sm text-white" id="btnSaveOnly">
                        <i class="bi bi-save"></i> Save Purchase
                    </button>
                    {{-- Existing Submit (Confirm) --}}
                    <button type="button" class="btn btn-success px-5 fw-bold shadow-sm" id="btnConfirm">
                        <i class="bi bi-check-circle"></i> Confirm Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0 pb-2">
                    <h5 class="modal-title fw-bold" id="addVendorModalLabel">Add New Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddVendorForm">
                    @csrf
                    <div class="modal-body pt-2">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Vendor Name</label>
                            <input type="text" class="form-control" name="name" required placeholder="Enter vendor name">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Phone Number</label>
                                <input type="text" class="form-control" name="phone" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Opening Balance</label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" value="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-muted">Address</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnQuickSaveVendor">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Init Select2
            $('.select2').select2({
                width: '100%'
            });

            // Vendor Select Logic
            $('#vendorSelect').on('change', function() {
                const vendorId = $(this).val();
                if (!vendorId) {
                    $('#vendorInfoCard').addClass('d-none');
                    return;
                }

                // Fetch Vendor Info & Ledger
                $.get(`/vendor/${vendorId}/ledger-json`, function(data) {
                    // Update Info Card
                    $('#vi_mobile').text(data.vendor.phone || '—');
                    $('#vi_address').text(data.vendor.address || '—');
                    $('#vi_prev_bal').text(parseFloat(data.current_balance).toFixed(2));
                    $('#vendorInfoCard').removeClass('d-none');

                    // Update Summary
                    $('#tPrev').text(parseFloat(data.current_balance).toFixed(2));
                    recalcAll();
                });
            });

            // Add First Row
            addBlankRow();

            // Add Row Button
            $('#btnAdd').click(function() {
                addBlankRow();
            });

            // Remove Row
            $(document).on('click', '.remove-row', function() {
                if ($('#purchaseTableBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    recalcAll();
                }
            });

            // Inputs -> Calc
            $('#purchaseTableBody').on('input', '.box-qty, .price, .item-disc-percent, .item-disc-amt', function() {
                if ($(this).hasClass('box-qty')) {
                    normalizeQtyInput($(this), $(this).closest('tr'));
                }
                recalcRow($(this).closest('tr'));
                recalcAll();
            });

            // Summary Inputs
            $('#billDiscount, #billDiscountPct, #extraCost').on('input', function() {
                recalcAll();
            });

            // Payment Row Add
            $('#btnAddPayment').click(function() {
                const html = `
                    <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                        <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 300px; flex-grow: 1;">
                            <option value="" selected disabled>Select Account</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                            @endforeach
                        </select>
                        <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="Amount" style="width:140px">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-payment">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>`;
                $('#paymentWrapper').append(html);
            });

            $(document).on('click', '.remove-payment', function() {
                $(this).closest('.payment-row').remove();
                calcTotalPaid();
            });

            $(document).on('input', '.payment-amount', function() {
                calcTotalPaid();
            });

            function calcTotalPaid() {
                let total = 0;
                $('.payment-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalPaid').text(total.toFixed(2));
                recalcAll(); // Trigger summary update
            }


            // --- SAVE ONLY AJAX ---
            // --- Submit Logic (AJAX for both Save & Confirm) ---

            // 1. Save (Draft)
            $('#btnSaveOnly').click(function(e) {
                e.preventDefault();
                let $btn = $(this);
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

                $('#action').val('save_only'); // Set action

                $.ajax({
                    url: "{{ route('store.Purchase') }}",
                    method: "POST",
                    data: $('#purchaseForm').serialize(),
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: 'Purchase saved as draft successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "{{ route('Purchase.home') }}";
                        });
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-save"></i> Save Purchase');
                        let msg = 'Something went wrong.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON
                            .message;
                        // Validation errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = Object.values(xhr.responseJSON.errors).flat().join(
                                '\n');
                            msg += '\n' + errors;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // 2. Confirm (Approved)
            $('#btnConfirm').click(function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Confirm Purchase?',
                    text: "This will update stock and accounts. You cannot revert this directly.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Confirm it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let $btn = $('#btnConfirm');
                        $btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
                        );

                        $('#action').val('approved'); // Set action

                        $.ajax({
                            url: "{{ route('store.Purchase') }}",
                            method: "POST",
                            data: $('#purchaseForm').serialize(),
                            success: function(response) {
                                // Open Invoice in New Tab
                                if (response.invoice_url) {
                                    window.open(response.invoice_url, '_blank');
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: 'Purchase confirmed and processed successfully.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = response
                                        .redirect_url ||
                                        "{{ route('Purchase.home') }}";
                                });
                            },
                            error: function(xhr) {
                                $btn.prop('disabled', false).html(
                                    '<i class="bi bi-check-circle"></i> Confirm Purchase'
                                );
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg =
                                    xhr.responseJSON.message;
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    let errors = Object.values(xhr.responseJSON.errors)
                                        .flat().join('\n');
                                    msg += '\n' + errors;
                                }
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });

            // --- QUICK ADD VENDOR AJAX ---
            $('#quickAddVendorForm').on('submit', function(e) {
                e.preventDefault();
                let $btn = $('#btnQuickSaveVendor');
                let originalText = $btn.text();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: "{{ route('vendors.store.ajax') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $btn.prop('disabled', false).text(originalText);
                        
                        // We assume the backend might not return the full vendor object directly in a predictable format
                        // So reload the page is safest, OR if we can parse the name, we just do that.
                        // Actually VendorController->store returns back()->with('success', ...) 
                        // So it sends HTML of the previous page! Let's handle this carefully.
                        // Wait, if it's returning a redirect, ajax will silently follow it and return HTML.
                        // The safest logic here for a standard Laravel controller returning back() is to reload the window.
                        // Or we can manually reload. 
                        
                        // For a seamless experience, we just reload window because appending HTML block is messy
                        Swal.fire({
                            icon: 'success',
                            title: 'Vendor Added',
                            text: 'The vendor has been created successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text(originalText);
                        let msg = 'Error adding vendor.';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });


            function normalizeQtyInput($input, $row) {
                const val = $input.val();
                const ppb = parseFloat($row.find('.pack-size').val()) || 1;
                const sizeMode = $row.data('sizemode');

                // If size_mode is strictly 'by_pieces', force integer
                if (sizeMode === 'by_pieces' || sizeMode === 'by_piece') {
                    if (val.includes('.')) {
                        // Remove everything after dot
                        $input.val(val.split('.')[0]);
                        return;
                    }
                }

                // Since this is Purchase, we assume box input logic applies whenever PPB > 1
                // logic similar to shared_logic.blade.php

                if (ppb > 1 && val.includes('.')) {
                    const parts = val.split('.');
                    const boxes = parseInt(parts[0]) || 0;
                    const looseStr = parts[1];

                    if (looseStr && looseStr !== '') {
                        const loose = parseInt(looseStr);
                        // If loose pieces >= pack size, convert to boxes
                        if (loose >= ppb) {
                            const extraBoxes = Math.floor(loose / ppb);
                            const newLoose = loose % ppb;
                            const newBoxes = boxes + extraBoxes;

                            let newVal = newBoxes.toString();
                            if (newLoose > 0) {
                                newVal += '.' + newLoose;
                            }
                            // Update input value
                            $input.val(newVal);
                        }
                    }
                }
            }

            function addBlankRow() {
                const rowCount = $('#purchaseTableBody tr').length;
                const html = `
                <tr>
                    <td style="min-width: 250px;">
                        <select class="form-select product-select2" name="composite_id[]"></select>
                        <!-- Hidden fields for product data snapshot -->
                        <input type="hidden" name="product_id[]" class="actual-product-id">
                        <input type="hidden" name="product_package_id[]" class="product-package-id">
                        <input type="hidden" name="size_mode[]" class="hidden-size-mode" value="">
                        <input type="hidden" name="pieces_per_box[]" class="hidden-pieces-per-box" value="">
                        <input type="hidden" name="pieces_per_m2[]" class="hidden-pieces-per-m2" value="">
                        <input type="hidden" name="length[]" class="hidden-length" value="">
                        <input type="hidden" name="width[]" class="hidden-width" value="">
                    </td>
                    <td>
                        <div class="qty-wrapper input-group input-group-sm">
                            <input type="text" class="form-control box-qty" name="boxes_qty[]" value="" placeholder="Qty">
                            <span class="input-group-text qty-unit-label">...</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control input-readonly pack-size text-end" name="pieces_per_box_display[]" value="1" readonly tabindex="-1">
                            <span class="input-group-text base-unit-label">...</span>
                        </div>
                    </td>
                    <!-- Loose Column Removed -->
                    <td><input type="number" name="qty[]" class="form-control input-readonly qty-pcs" value="0" readonly></td>
                    <td><input type="number" name="price[]" class="form-control price" value="0"></td>
                    <td><input type="number" name="item_discount[]" class="form-control item-disc-percent" value="0"></td>
                    <td><input type="number" class="form-control item-disc-amt" value="0" readonly></td>
                    <td><input type="number" class="form-control input-readonly row-total" value="0" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">x</button></td>
                </tr>
            `;
                const $row = $(html);
                $('#purchaseTableBody').append($row);
                initProductSelect2($row.find('.product-select2'));
            }

            function initProductSelect2($el) {
                $el.select2({
                    placeholder: 'Search Product (Name / SKU / Barcode)',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: '{{ route('products.ajax.search') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            // Transform result to match Select2 format
                            const results = data.results || [];
                            return {
                                results: results,
                                pagination: {
                                    more: (data.pagination && data.pagination.more) ? true : false
                                }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 0,
                    templateResult: formatProduct,
                    templateSelection: formatSelection
                });

                $el.on('select2:select', function(e) {
                    const data = e.params.data;
                    const $row = $(this).closest('tr');

                    // Extract IDs
                    const parts = data.id.split('|');
                    const productId = parts[0];
                    const packageId = parts[1];

                    // 1. Snapshot Data Population
                    $row.find('.actual-product-id').val(productId);
                    $row.find('.product-package-id').val(packageId);
                    $row.find('.hidden-size-mode').val(data.size_mode || '');
                    $row.find('.hidden-pieces-per-box').val(data.pieces_per_box || 1);
                    $row.find('.hidden-pieces-per-m2').val(data.pieces_per_m2 || 0);
                    $row.find('.hidden-length').val(data.length || '');
                    $row.find('.hidden-width').val(data.width || '');

                    // Also set visible pack size
                    const cf = parseFloat(data.pieces_per_box || 1);
                    $row.find('.pack-size').val(cf.toString());
                    $row.find('.base-unit-label').text(data.base_unit || 'pc');
                    $row.find('.qty-unit-label').text(data.symbol || 'pc');

                    // Attach data to row for dynamic calc
                    $row.data('sizemode', data.size_mode);
                    $row.data('pieces_per_m2', Number(data.pieces_per_m2) || 0);
                    $row.data('p_price_piece', Number(data.purchase_price_per_piece) || 0);

                    // Set default discount
                    $row.find('.item-disc-percent').val(data.purchase_discount_percent || 0);

                    // Store stock for popover
                    $row.data('stock_display', data.stock || '0');

                    // Logic for Cost Price (Purchase Price) based on Size Mode
                    const sizeMode = data.size_mode || 'std';
                    const pM2 = parseFloat(data.purchase_price_per_m2) || 0;
                    const pPiece = parseFloat(data.purchase_price_per_piece) || 0;
                    let finalPrice = pPiece; // Default to package price

                    $row.find('.price-unit-label').remove();
                    let unitLabel = '(pieces)';


                    if (sizeMode === 'by_size') {
                        $row.find('.price').val(pM2);
                        unitLabel = '(m2)';
                    } else {
                        $row.find('.price').val(pPiece);
                    }

                    if (unitLabel) {
                        $row.find('.price').after(
                            '<span class="price-unit-label text-muted small ms-1" style="font-size:0.75rem">' +
                            unitLabel + '</span>');
                    }
                    $row.find('.pack-size').val(data.pieces_per_box || 1);
                    $row.data('sizemode', sizeMode);
                    $row.data('pieces_per_m2', data.pieces_per_m2);
                    $row.data('p_price_piece', pPiece);
                    // Trigger recalc
                    $row.find('.box-qty').focus();
                    recalcRow($row);
                    recalcAll();
                });
            }

            function formatProduct(repo) {
                if (repo.loading) return repo.text;
                let stock = repo.stock !== undefined ? repo.stock : 0;
                let sku = repo.sku || 'N/A';
                let badgeClass = 'bg-info'; // Neutral for Purchase

                return $(`
            <div class="clearfix">
                <div class="float-start">
                    <div class="fw-bold">${repo.name || repo.text}</div>
                    <small class="text-muted">SKU: ${sku}</small>
                </div>
                <div class="float-end">
                    <span class="badge ${badgeClass} rounded-pill">Stock: ${stock}</span>
                </div>
            </div>
            `);
            }

            function formatSelection(repo) {
                return repo.name || repo.text;
            }

            function recalcRow($row) {
                // Read BOX input
                let boxesStr = $row.find('.box-qty').val();
                if (boxesStr === null || boxesStr === undefined) boxesStr = "0";
                boxesStr = boxesStr.toString();
                const ppb = parseFloat($row.find('.pack-size').val()) || 1;
                const pieces_per_m2 = $row.data('pieces_per_m2');
                const sizeMode = $row.data().sizemode;
                const p_price_piece = $row.data().p_price_piece;
                let totalPieces = 0;
                let boxes = 0;
                let loose = 0;

                // Box.Loose Logic (Similar to shared_logic)
                if (ppb > 1 && boxesStr.includes('.')) {
                    const parts = boxesStr.split('.');
                    boxes = parseInt(parts[0]) || 0;
                    loose = parts[1] ? parseInt(parts[1]) : 0;

                    totalPieces = (boxes * ppb) + loose;
                } else {
                    // Whole boxes
                    boxes = parseFloat(boxesStr) || 0;
                    totalPieces = boxes * ppb;
                }

                // Update the hidden/readonly Piece Input (which is sent as qty[])
                $row.find('.qty-pcs').val(totalPieces);

                const price = parseFloat($row.find('.price').val()) || 0;
                // Discount
                const discPct = parseFloat($row.find('.item-disc-percent').val()) || 0;
                let total = 0;

                // Total Amount calculation
                if (sizeMode == 'by_size') {
                    // price is per m2
                    total = pieces_per_m2 * totalPieces * price;
                } else if (sizeMode == 'by_cartons' || sizeMode == 'by_carton') {
                    // price is per carton
                    if (ppb > 0) {
                        total = (totalPieces / ppb) * price;
                    } else {
                        total = totalPieces * price;
                    }
                } else {
                    // price is per piece
                    total = totalPieces * price;
                }

                // Discount Amount
                const discAmt = total * (discPct / 100);
                $row.find('.item-disc-amt').val(discAmt.toFixed(2));

                total = total - discAmt;

                $row.data('total-pieces', totalPieces);

                $row.find('.row-total').val(total.toFixed(2));
                // Update hidden cost factor if needed (optional, depends on backend requirements)
            }

            function recalcAll() {
                let totalQty = 0;
                let subtotal = 0;
                let firstUnit = '';

                $('#purchaseTableBody tr').each(function(index) {
                    const $r = $(this);
                    if (index === 0) {
                        firstUnit = $r.find('.qty-unit-label').text();
                    }
                    let qty = $(this).data('total-pieces');
                    // Fallback if data attribute not set
                    if (qty === undefined) {
                        qty = parseFloat($(this).find('.qty-pcs').val()) || 0;
                    }
                    const total = parseFloat($(this).find('.row-total').val()) || 0;

                    totalQty += qty;
                    subtotal += total;
                });

                $('#tQty').text(totalQty % 1 === 0 ? totalQty : totalQty.toFixed(2));
                $('#tQtyUnit').text(firstUnit ? '(' + firstUnit + ')' : '');
                $('#tSub').text(subtotal.toFixed(2));
                $('#subtotalInput').val(subtotal.toFixed(2));

                const billDiscVal = parseFloat($('#billDiscount').val()) || 0;
                
                // If focus is on %, recalc the amount before using it
                if ($(document.activeElement).is('#billDiscountPct')) {
                    const pct = parseFloat($('#billDiscountPct').val()) || 0;
                    const calculatedAmt = subtotal * (pct / 100);
                    $('#billDiscount').val(calculatedAmt.toFixed(2));
                } else {
                    // Calc % from amount
                    const pct = subtotal > 0 ? (billDiscVal / subtotal) * 100 : 0;
                    $('#billDiscountPct').val(pct.toFixed(2));
                }

                const finalBillDisc = parseFloat($('#billDiscount').val()) || 0;
                const extraCost = parseFloat($('#extraCost').val()) || 0;

                const net = subtotal - finalBillDisc + extraCost;

                $('#tPayable').text(net.toFixed(2));
                $('#netAmountInput').val(net.toFixed(2));
                $('#totalAmount').text(subtotal.toFixed(2));

                // NEW: Handle Previous Balance & Total Payable
                const prevBal = parseFloat($('#tPrev').text()) || 0;
                const totalPaid = parseFloat($('#totalPaid').text()) || 0;
                const totalPayable = (prevBal + net) - totalPaid;
                
                $('#tTotalPayable').text(totalPayable.toFixed(2));
            }
            // --- Qty Popover Logic ---
            $(document).on('focusin', '.box-qty', function() {
                const $row = $(this).closest('tr');
                const stock = $row.data('stock_display');
                if (stock) {
                    const $popover = $('#globalQtyPopover');
                    $('#popoverStockText').text('Quantity In Hand: ' + stock);
                    
                    // Position popover
                    const offset = $(this).offset();
                    const width = $(this).outerWidth();
                    
                    $popover.css({
                        top: offset.top - 45, // Adjust based on popover height
                        left: offset.left + (width / 2) - ($popover.outerWidth() / 2),
                        display: 'block',
                        opacity: 1
                    });
                }
            });

            $(document).on('focusout', '.box-qty', function() {
                $('#globalQtyPopover').css({ display: 'none', opacity: 0 });
            });

            $(window).on('scroll', function() {
                $('#globalQtyPopover').css({ display: 'none', opacity: 0 });
            });
        });
    </script>
@endsection
