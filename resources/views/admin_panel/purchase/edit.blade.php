@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            width: 90px;
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

        .select2-container .select2-selection--single {
            height: 36px !important;
            padding: 3px 12px;
            border-color: #ced4da;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 5px !important;
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

            <form id="purchaseForm" action="{{ route('purchase.update', $purchase->id) }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')

                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div>
                        <a href="{{ route('Purchase.home') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                    <h2 class="header-text text-secondary fw-bold mb-0">Edit Purchase #{{ $purchase->invoice_no }}</h2>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-secondary" id="entryDate">Date: {{ date('d-M-Y') }}</small>
                    </div>
                </div>

                <div class="row g-3 border-bottom pb-4 mb-3 mt-2">
                    {{-- LEFT: Invoice & Vendor --}}
                    <div class="col-lg-3 col-md-4">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Invoice & Vendor</div>

                            <div class="mb-2 d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0 text-muted small" style="min-width: 80px;">Invoice
                                    No</label>
                                <input type="text" class="form-control input-readonly" name="invoice_no"
                                    value="{{ $purchase->invoice_no }}" readonly>
                            </div>

                            <!-- VENDOR SELECT -->
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1 text-muted small">Select Vendor</label>
                                <select class="form-select select2" id="vendorSelect" name="vendor_id">
                                    <option value="" disabled>Select Vendor</option>
                                    @foreach ($Vendor as $v)
                                        <option value="{{ $v->id }}"
                                            {{ $v->id == $purchase->vendor_id ? 'selected' : '' }}>
                                            {{ $v->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1 text-muted small">Date</label>
                                <input type="date" name="purchase_date" class="form-control"
                                    value="{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d') }}">
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Remarks</label>
                                <textarea class="form-control" name="note" rows="2">{{ $purchase->note }}</textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Warehouse</label>
                                <select name="warehouse_id" class="form-control select2">
                                    @foreach ($Warehouse as $w)
                                        <option value="{{ $w->id }}"
                                            {{ $w->id == $purchase->warehouse_id ? 'selected' : '' }}>
                                            {{ $w->warehouse_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Items --}}
                    <div class="col-lg-9 col-md-8">
                        <div class="card-panel shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="section-title mb-0">Purchase Items</div>
                                <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm"
                                    onclick="addBlankRow()">
                                    <i class="bi bi-plus-lg"></i> Add Row
                                </button>
                            </div>

                            <div class="table-responsive border rounded-3 bg-white">
                                <table class="table table-bordered sales-table mb-0" id="purchaseTable">
                                    <thead>
                                        <tr>
                                            <th class="col-product">Product</th>
                                            <th class="col-qty">Boxes</th>
                                            <th class="col-stock">Conv Factor</th>
                                            <th class="col-pieces">Pieces</th>
                                            <th class="col-price">Price</th>
                                            <th class="col-disc">Disc %</th>
                                            <th class="col-disc-amt">Disc Amt</th>
                                            <th class="col-amount">Amount</th>
                                            <th class="col-action">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseTableBody">
                                        @foreach ($purchase->items as $item)
                                            @php
                                                $sizeMode = $item->size_mode ?? 'by_pieces';
                                                $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : 1);
                                                $boxes = (float) ($item->boxes_qty ?? 0);
                                                $loose = (float) ($item->loose_qty ?? 0);

                                                // Create 5.3 format for display
                                                $displayBoxes = $boxes > 0 ? $boxes : '0';
                                                if ($loose > 0) {
                                                    $displayBoxes .= '.' . $loose;
                                                } elseif ($boxes == 0) {
                                                    $displayBoxes = ''; // Empty if 0
                                                }

                                                // Unit Label
                                                $unitLabel = '';
                                                if ($sizeMode == 'by_size') {
                                                    $unitLabel = '(m²)';
                                                } elseif ($sizeMode == 'by_cartons') {
                                                    $unitLabel = '(carton)';
                                                } else {
                                                    $unitLabel = '(piece)';
                                                }
                                            @endphp
                                            @php
                                                // Calculate stock for popover
                                                $totalStk = $item->product->warehouseStocks->sum('total_pieces');
                                                if ($totalStk <= 0 && $item->product->warehouseStocks->sum('quantity') > 0) {
                                                    $totalStk = $item->product->warehouseStocks->sum('quantity') * $ppb;
                                                }
                                                $stk_b = floor($totalStk / $ppb);
                                                $stk_l = $totalStk % $ppb;
                                                $stkDisp = $ppb > 1 ? ($stk_l > 0 ? "$stk_b.$stk_l" : $stk_b) : $totalStk;
                                            @endphp
                                            <tr data-sizemode="{{ $sizeMode }}"
                                                data-pieces_per_m2="{{ $item->pieces_per_m2 }}"
                                                data-stock_display="{{ $stkDisp }}">
                                                <td>
                                                    <select class="form-select product-select2" name="composite_id[]">
                                                        <option value="{{ $item->product_id }}|{{ $item->product_package_id }}" selected>
                                                            {{ $item->product->item_name }} - {{ optional($item->package)->name ?? optional($item->package)->code ?? '' }}
                                                            ({{ $item->product->item_code }})
                                                        </option>
                                                    </select>
                                                    {{-- Snapshots --}}
                                                    <input type="hidden" name="product_id[]" class="actual-product-id" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="product_package_id[]" class="product-package-id" value="{{ $item->product_package_id }}">
                                                    <input type="hidden" name="size_mode[]" class="hidden-size-mode"
                                                        value="{{ $sizeMode }}">
                                                    <input type="hidden" name="pieces_per_box[]"
                                                        class="hidden-pieces-per-box" value="{{ $ppb }}">
                                                    <input type="hidden" name="pieces_per_m2[]"
                                                        class="hidden-pieces-per-m2" value="{{ $item->pieces_per_m2 }}">
                                                    <input type="hidden" name="length[]" class="hidden-length"
                                                        value="{{ $item->length }}">
                                                    <input type="hidden" name="width[]" class="hidden-width"
                                                        value="{{ $item->width }}">
                                                    {{-- Hidden Box/Loose Calc fields --}}
                                                    <input type="hidden" name="boxes_qty[]" class="hidden-boxes-qty"
                                                        value="{{ $boxes }}">
                                                    <input type="hidden" name="loose_qty[]" class="hidden-loose-qty"
                                                        value="{{ $loose }}">
                                                </td>
                                                <td>
                                                    <div class="qty-wrapper input-group input-group-sm">
                                                        <input type="text" class="form-control box-qty"
                                                            value="{{ $displayBoxes }}" placeholder="Qty">
                                                        <span class="input-group-text qty-unit-label">{{ optional($item->package)->symbol ?? 'pc' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control input-readonly pack-size"
                                                        value="{{ $ppb }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="qty[]"
                                                        class="form-control input-readonly qty-pcs"
                                                        value="{{ (float) $item->qty }}" readonly>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="price[]" class="form-control price"
                                                            step="0.01" value="{{ (float) $item->price }}">
                                                    </div>
                                                    <small class="text-muted price-unit-label"
                                                        style="font-size:0.7rem;">{{ $unitLabel }}</small>
                                                </td>
                                                <td>
                                                    {{-- Calc Disc % from Amt --}}
                                                    @php
                                                        $gross = $item->line_total + $item->item_discount;
                                                        $dPct = $gross > 0 ? ($item->item_discount / $gross) * 100 : 0;
                                                    @endphp
                                                    <input type="number" class="form-control item-disc-percent"
                                                        value="{{ round($dPct, 2) }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="item_discount[]"
                                                        class="form-control item-disc-amt"
                                                        value="{{ (float) $item->item_discount }}">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control input-readonly row-total"
                                                        value="{{ (float) $item->line_total }}" readonly>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger remove-row border-0"><i
                                                            class="bi bi-x-lg"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
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

                {{-- SUMMARY --}}
                <div class="row g-3 mt-1">
                    {{-- LEFT: Payment / Receipt Voucher --}}
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
                            </div>
                            <div class="text-end">
                                <span class="me-2 fw-bold text-muted">Total Paid:</span>
                                <span class="fw-bold fs-6 text-success" id="totalPaid">0.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Summary --}}
                    <div class="col-lg-5">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Summary</div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Total Qty <span id="tQtyUnit"></span></div>
                                <div class="col-5 text-end"><span id="tQty" class="fw-bold">0</span></div>
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Sub-Total</div>
                                <div class="col-5 text-end fw-bold"><span id="tSub">0.00</span></div>
                                <input type="hidden" name="subtotal" id="subtotalInput">
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Bill Discount</div>
                                <div class="col-5 text-end d-flex gap-1">
                                    @php
                                        $bSub = (float) $purchase->subtotal;
                                        $bDisc = (float) $purchase->discount;
                                        $bPct = $bSub > 0 ? ($bDisc / $bSub) * 100 : 0;
                                    @endphp
                                    <input type="number" class="form-control text-end form-control-sm"
                                        id="billDiscountPct" value="{{ round($bPct, 2) }}" placeholder="%" style="width: 70px;" step="0.01">
                                    <input type="number" class="form-control text-end form-control-sm" name="discount"
                                        id="billDiscount" value="{{ (float) $purchase->discount }}" step="0.01">
                                </div>
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Extra Cost</div>
                                <div class="col-5 text-end">
                                    <input type="number" class="form-control text-end form-control-sm" name="extra_cost"
                                        id="extraCost" value="{{ (float) $purchase->extra_cost }}">
                                </div>
                            </div>
                            <hr class="my-2 border-secondary">
                            <div class="row py-2">
                                <div class="col-6 fw-bold fs-5 text-primary">Net Payable</div>
                                <div class="col-6 text-end fw-bold fs-5 text-primary"><span id="tPayable">0.00</span>
                                </div>
                                <input type="hidden" name="net_amount" id="netAmountInput">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i> Update Purchase
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Init Global Select2
            $('.select2').select2({
                width: '100%'
            });

            // Initialize existing product selects
            $('.product-select2').each(function() {
                initProductSelect2($(this));
            });

            // Recalc existing rows
            recalcAll();

            // Add Row
            window.addBlankRow = function() {
                const html = `
                <tr>
                    <td>
                        <select class="form-select product-select2" name="composite_id[]"></select>
                        <input type="hidden" name="product_id[]" class="actual-product-id">
                        <input type="hidden" name="product_package_id[]" class="product-package-id">
                        <input type="hidden" name="size_mode[]" class="hidden-size-mode">
                        <input type="hidden" name="pieces_per_box[]" class="hidden-pieces-per-box" value="1">
                        <input type="hidden" name="pieces_per_m2[]" class="hidden-pieces-per-m2" value="0">
                        <input type="hidden" name="length[]" class="hidden-length">
                        <input type="hidden" name="width[]" class="hidden-width">
                        <input type="hidden" name="boxes_qty[]" class="hidden-boxes-qty" value="0">
                        <input type="hidden" name="loose_qty[]" class="hidden-loose-qty" value="0">
                    </td>
                    <td>
                        <div class="qty-wrapper input-group input-group-sm">
                            <input type="text" class="form-control box-qty" name="boxes_qty[]" value="" placeholder="Qty">
                            <span class="input-group-text qty-unit-label">...</span>
                        </div>
                    </td>
                    <td><input type="number" class="form-control input-readonly pack-size" value="1" readonly></td>
                    <td><input type="number" name="qty[]" class="form-control input-readonly qty-pcs" value="0" readonly></td>
                    <td><div class="input-group input-group-sm"><input type="number" name="price[]" class="form-control price" step="0.01" value="0"></div></td>
                    <td><input type="number" class="form-control item-disc-percent" value="0"></td>
                    <td><input type="number" name="item_discount[]" class="form-control item-disc-amt" value="0"></td>
                    <td><input type="number" class="form-control input-readonly row-total" value="0" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row border-0"><i class="bi bi-x-lg"></i></button></td>
                </tr>`;
                const $row = $(html);
                $('#purchaseTableBody').append($row);
                initProductSelect2($row.find('.product-select2'));
            };

            // Remove Row
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                recalcAll();
            });

            // Inputs -> Calc
            $('#purchaseTableBody').on('input', '.box-qty, .price, .item-disc-percent, .item-disc-amt', function() {
                if ($(this).hasClass('box-qty')) {
                    normalizeQtyInput($(this), $(this).closest('tr'));
                }
                recalcRow($(this).closest('tr'));
                recalcAll();
            });

            $('#billDiscount, #billDiscountPct, #extraCost').on('input', function() {
                recalcAll();
            });

            // --- Payment Section Logic ---
            $('#btnAddPayment').on('click', function() {
                const row = `
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
                $('#paymentWrapper').append(row);
            });

            $(document).on('click', '.remove-payment', function() {
                $(this).closest('.payment-row').remove();
                recalcPayments();
            });

            $(document).on('input', '.payment-amount', function() {
                recalcPayments();
            });

            function recalcPayments() {
                let total = 0;
                $('.payment-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalPaid').text(total.toFixed(2));
            }

            function normalizeQtyInput($input, $row) {
                // Same logic as add_purchase_v2
                const val = $input.val();
                const ppb = parseFloat($row.find('.pack-size').val()) || 1;
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();

                if (sizeMode === 'by_pieces') {
                    if (val.includes('.')) {
                        $input.val(val.split('.')[0]);
                    }
                    return;
                }

                if (ppb > 1 && val.includes('.')) {
                    const parts = val.split('.');
                    const boxes = parseInt(parts[0]) || 0;
                    const loose = parts[1] ? parseInt(parts[1]) : 0;

                    if (loose >= ppb) {
                        const extraBoxes = Math.floor(loose / ppb);
                        const newLoose = loose % ppb;
                        const newBoxes = boxes + extraBoxes;
                        let newVal = newBoxes.toString();
                        if (newLoose > 0) newVal += '.' + newLoose;
                        $input.val(newVal);
                    }
                }
            }

            function recalcRow($row) {
                let boxesStr = $row.find('.box-qty').val();
                if (!boxesStr) boxesStr = "0";
                boxesStr = boxesStr.toString();

                const ppb = parseFloat($row.find('.pack-size').val()) || 1;
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();
                const pieces_per_m2 = parseFloat($row.data('pieces_per_m2')) || parseFloat($row.find(
                    '.hidden-pieces-per-m2').val()) || 0;

                let boxes = 0;
                let loose = 0;
                let totalPieces = 0;

                if (ppb > 1 && boxesStr.includes('.')) {
                    const parts = boxesStr.split('.');
                    boxes = parseInt(parts[0]) || 0;
                    loose = parts[1] ? parseInt(parts[1]) : 0;
                    totalPieces = (boxes * ppb) + loose;
                } else {
                    boxes = parseFloat(boxesStr) || 0;
                    totalPieces = boxes * ppb;
                }

                // Update hidden separate fields
                $row.find('.hidden-boxes-qty').val(boxes);
                $row.find('.hidden-loose-qty').val(loose);

                $row.find('.qty-pcs').val(totalPieces);

                const price = parseFloat($row.find('.price').val()) || 0;

                // --- TOTAL CALCULATION ---
                let grossTotal = 0;

                if (sizeMode == 'by_size') {
                    // Price is per M2. Total M2 = totalPieces * pieces_per_m2 (m2/piece)
                    grossTotal = (totalPieces * pieces_per_m2) * price;
                } else if (sizeMode == 'by_cartons') {
                    // Price is per Carton.
                    // If ppb > 0
                    if (ppb > 0) {
                        grossTotal = (totalPieces / ppb) * price;
                    } else {
                        grossTotal = totalPieces * price; // fallback
                    }
                } else {
                    // Price is per Piece
                    grossTotal = totalPieces * price;
                }

                // Discount
                let discAmt = parseFloat($row.find('.item-disc-amt').val()) || 0;
                // If focus on %, calc amt
                if ($(document.activeElement).hasClass('item-disc-percent')) {
                    const pct = parseFloat($row.find('.item-disc-percent').val()) || 0;
                    discAmt = grossTotal > 0 ? grossTotal * (pct / 100) : 0;
                    $row.find('.item-disc-amt').val(discAmt.toFixed(2));
                } else {
                    // Else calc % from amt (default or if amt edited)
                    const pct = grossTotal > 0 ? (discAmt / grossTotal) * 100 : 0;
                    $row.find('.item-disc-percent').val(pct.toFixed(2));
                }

                const net = grossTotal - discAmt;
                $row.find('.row-total').val(net.toFixed(2));
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
                    const qty = parseFloat($(this).find('.qty-pcs').val()) || 0;
                    const total = parseFloat($(this).find('.row-total').val()) || 0;
                    totalQty += qty;
                    subtotal += total;
                });

                $('#tQty').text(totalQty % 1 === 0 ? totalQty : totalQty.toFixed(2));
                $('#tQtyUnit').text(firstUnit ? '(' + firstUnit + ')' : '');
                $('#tSub').text(subtotal.toFixed(2));
                $('#subtotalInput').val(subtotal.toFixed(2));
                $('#totalAmount').text(subtotal.toFixed(2));

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
            }

            function initProductSelect2($el) {
                $el.select2({
                    placeholder: 'Search Product...',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: '{{ route('products.ajax.search') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            // Map if needed or just return results
                            return {
                                results: data.results || data
                            };
                        },
                        cache: true
                    },
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

                    // Populate Snapshots
                    $row.find('.actual-product-id').val(productId);
                    $row.find('.product-package-id').val(packageId);
                    $row.find('.hidden-size-mode').val(data.size_mode || '');
                    $row.find('.hidden-pieces-per-box').val(data.pieces_per_box || 1);
                    $row.find('.hidden-pieces-per-m2').val(data.pieces_per_m2 || 0);
                    $row.find('.hidden-length').val(data.length || '');
                    // Also set visible pack size
                    $row.find('.pack-size').val(data.pieces_per_box || 1);
                    $row.find('.qty-unit-label').text(data.symbol || 'pc');

                    // Set default discount
                    $row.find('.item-disc-percent').val(data.purchase_discount_percent || 0);

                    // Set Price & Label
                    const sizeMode = data.size_mode || 'std';
                    const pM2 = parseFloat(data.purchase_price_per_m2) || 0;
                    const pPiece = parseFloat(data.purchase_price_per_piece) || 0;
                    const ppb = parseFloat(data.pieces_per_box) || 1;

                    let price = 0;
                    let unitLabel = '';

                    if (sizeMode === 'by_size') {
                        price = pM2;
                        unitLabel = '(m²)';
                    } else if (sizeMode === 'by_cartons') {
                        price = pPiece * ppb; // Carton Price
                        unitLabel = '(carton)';
                    } else {
                        price = pPiece;
                        unitLabel = '(piece)';
                    }

                    $row.find('.price').val(price);
                    // Add/Update label (remove old if any)
                    $row.find('.price-unit-label').remove();
                    $row.find('.price').after(
                        '<small class="text-muted price-unit-label" style="font-size:0.7rem;">' +
                        unitLabel + '</small>');

                    // Data Attributes
                    $row.data('sizemode', sizeMode);
                    $row.data('pieces_per_m2', Number(data.pieces_per_m2) || 0);

                    // Recalc
                    $row.find('.box-qty').focus();
                    recalcRow($row);
                    recalcAll();
                });
            }

            function formatProduct(repo) {
                if (repo.loading) return repo.text;
                let stock = repo.stock !== undefined ? repo.stock : 0;
                let sku = repo.sku || 'N/A';
                return $(`
                <div class="clearfix">
                    <div class="float-start">
                        <div class="fw-bold">${repo.name || repo.text}</div>
                        <small class="text-muted">SKU: ${sku}</small>
                    </div>
                    <div class="float-end">
                        <span class="badge bg-secondary rounded-pill">Stock: ${stock}</span>
                    </div>
                </div>`);
            }

            function formatSelection(repo) {
                return repo.name || repo.text;
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
