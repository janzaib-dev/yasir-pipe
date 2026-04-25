@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Professional ERP Styling */
        :root {
            --erp-primary: #4a69bd;
            /* Professional Blue */
            --erp-bg: #f5f6fa;
            --erp-border: #dcdde1;
            --erp-text: #2f3640;
            --erp-muted: #7f8fa6;
        }

        body {
            background-color: var(--erp-bg);
            color: var(--erp-text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .erp-card {
            background: white;
            border: 1px solid var(--erp-border);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .erp-header {
            background: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--erp-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .erp-header h5 {
            margin: 0;
            font-weight: 600;
            color: var(--erp-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--erp-muted);
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .form-control,
        .form-select {
            border-radius: 4px;
            border: 1px solid var(--erp-border);
            padding: 0.4rem 0.75rem;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: var(--erp-primary);
            box-shadow: 0 0 0 2px rgba(74, 105, 189, 0.2);
        }

        .form-control[readonly] {
            background-color: #f9fafb;
            /* Minimalist light gray */
            color: var(--erp-muted);
            font-weight: 500;
            cursor: not-allowed;
            pointer-events: none;
            border-color: var(--erp-border);
            /* Standard border, no pattern */
            opacity: 1;
            /* Full opacity for clarity */
        }

        .form-control[readonly]:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }

        /* Table Styles */
        .erp-table-container {
            border: 1px solid var(--erp-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .table-erp {
            width: 100%;
            margin-bottom: 0;
        }

        .table-erp thead th {
            background-color: #f1f2f6;
            color: var(--erp-text);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 0.75rem;
            border-bottom: 2px solid var(--erp-border);
            white-space: nowrap;
        }

        .table-erp tbody td {
            vertical-align: middle;
            padding: 0.5rem;
            border-bottom: 1px solid #f1f2f6;
        }

        .table-erp input.form-control {
            border: 1px solid transparent;
            background: transparent;
            padding: 0.25rem 0.5rem;
            height: auto;
        }

        .table-erp input.form-control:focus,
        .table-erp input.form-control:hover {
            border-color: var(--erp-border);
            background: white;
        }

        /* Read-only inputs in table */
        .table-erp input.form-control[readonly] {
            cursor: not-allowed;
            pointer-events: none;
            background-color: transparent;
            /* Seamless integration */
            color: #adb5bd;
            /* Muted text */
            font-weight: 400;
            border-color: transparent;
        }

        .table-erp input.form-control[readonly]:hover,
        .table-erp input.form-control[readonly]:focus {
            background-color: #f8f9fa;
            border-color: transparent;
            box-shadow: none;
            cursor: not-allowed;
        }

        .summary-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1.5rem;
            border: 1px solid var(--erp-border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .summary-row.total {
            border-top: 1px solid var(--erp-border);
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--erp-primary);
        }

        .btn-erp-primary {
            background-color: var(--erp-primary);
            color: white;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border: none;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .btn-erp-primary:hover {
            background-color: #3c5aa6;
            color: white;
            transform: translateY(-1px);
        }

        /* Select2 Tweaks */
        .select2-container .select2-selection--single {
            height: 36px;
            border-color: var(--erp-border);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default .select2-selection--multiple {
            border-color: var(--erp-border);
        }

        /* Editable Field Highlighting */
        /* Editable Field Highlighting - Minimal */
        .quantity-box:not([readonly]),
        #extraDiscount {
            background-color: #fff;
            font-weight: 600;
            color: var(--erp-text);
            transition: all 0.2s;
        }

        /* Only show focus ring when active */
        .quantity-box:not([readonly]):focus,
        #extraDiscount:focus {
            border-color: var(--erp-primary) !important;
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.1) !important;
        }

        .quantity-box:not([readonly]):hover {
            border-color: #b0bdd1;
        }

        /* Visual indicator for editable vs read-only */
        .form-label .fa-lock {
            color: #dee2e6;
            /* Very subtle lock icon */
        }
    </style>


    <!-- Structure Wrapper -->
    <div class="container-fluid py-4">
        <div class="erp-card">
            <div class="erp-header">
                <h5><i class="fas fa-undo-alt me-2"></i> Sale Return</h5>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-dark border"><i class="fas fa-file-invoice me-1"></i> Original Invoice #
                        {{ $sale->invoice_no }}</span>
                    <a href="{{ route('sale.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('sale.return.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                    {{-- Assuming Single Branch/Warehouse for now or derived from Purchase --}}
                    <input type="hidden" name="warehouse_id" value="{{ $sale->warehouse_id ?? 1 }}">

                    <!-- Alert Section -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Top Section: customer & Reference -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-lock text-muted me-1"
                                    style="font-size: 0.7rem;"></i>customer</label>
                            {{-- Read-Only customer Name --}}
                            <input type="text" class="form-control form-control-sm"
                                value="{{ optional($sale->customer)->name ?? 'Unknown customer' }}" readonly
                                style="background-color: #e9ecef; border-color: #dee2e6;">
                            {{-- Hidden ID for Form Submission --}}
                            <input type="hidden" name="customer_id" value="{{ $sale->customer_id }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-lock text-muted me-1"
                                    style="font-size: 0.7rem;"></i>Reference / PO #</label>
                            <input type="text" name="reference" class="form-control form-control-sm"
                                value="{{ $sale->invoice_no ?? '' }}" readonly>
                        </div>
                        <div class="col-md-5 text-end align-self-end">
                            <div class="p-2 bg-light rounded d-inline-block border">
                                <small class="text-muted d-block text-start" style="font-size: 0.7rem;">ORIGINAL PURCHASE
                                    DATE</small>
                                <strong class="text-dark"><i class="far fa-calendar-alt me-1"></i>
                                    {{ $sale->created_at->format('d M, Y h:i A') }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Return Info -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Return Date <span class="text-danger">*</span></label>
                            <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Return Reason / Notes</label>
                            <input type="text" name="return_reason" class="form-control"
                                placeholder="e.g., Damaged goods, Wrong item sent">
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnReturnAll">
                            <i class="fas fa-check-double me-1"></i> Return All
                        </button>
                    </div>
                    <div class="table-responsive erp-table-container mb-4">
                        <table class="table table-erp table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 25%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Product</th>
                                    <th style="width: 10%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Item Code</th>
                                    <th style="width: 15%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>PC per box</th>
                                    <th style="width: 10%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Purchased Price</th>
                                    <th style="width: 10%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Purchased Qty PC</th>
                                    <th style="width: 10%;">Return Qty (Box.Piece)</th>
                                    <th style="width: 12%;">Total Return Pieces</th>
                                    <th style="width: 12%;">Total Amount</th>
                                    <th style="width: 5%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="returnItems">
                                @foreach ($sale->items as $index => $item)
                                    <tr>
                                        <input type="hidden" name="product_id[]" value="{{ $item['product_id'] }}">
                                        {{-- Hidden Discount to preserve refund math if needed --}}
                                        <input type="hidden" name="item_disc[]" class="item_disc"
                                            value="{{ $item['discount'] ?? 0 }}">
                                        <input type="hidden" name="unit[]" value="{{ $item['unit'] ?? 'pc' }}">
                                        <input type="hidden" name="size_mode[]" class="size-mode"
                                            value="{{ $item['size_mode'] ?? 'by_pieces' }}">
                                        <input type="hidden" name="pieces_per_m2[]" class="pieces-per-m2"
                                            value="{{ $item['pieces_per_m2'] ?? 0 }}">

                                        <td>
                                            <input type="text" class="form-control fw-bold"
                                                value="{{ $item['item_name'] }}" readonly>
                                            <small class="text-muted d-block ms-2"
                                                style="font-size: 0.75rem;">{{ $item['brand'] ?? '' }}</small>
                                        </td>

                                        <td><input type="text" class="form-control text-center"
                                                value="{{ $item['item_code'] }}" readonly></td>

                                        {{-- PC per box --}}
                                        <td>
                                            <input type="number" class="form-control text-center pieces-per-box"
                                                value="{{ $item['pieces_per_box'] ?? 1 }}" readonly>
                                        </td>


                                        {{-- Purchased Price Per Pc --}}
                                        <td><input type="number" name="price[]" step="0.01"
                                                class="form-control text-end price" value="{{ (float) $item['price'] }}"
                                                readonly>
                                            <small class="text-muted d-block text-end" style="font-size: 0.65rem;">
                                                @if (($item['size_mode'] ?? '') == 'by_size')
                                                    Per M²
                                                @elseif(($item['size_mode'] ?? '') == 'by_cartons')
                                                    Per Box
                                                @else
                                                    Per Pc
                                                @endif
                                            </small>
                                        </td>

                                        {{-- Purchased Qty (Read Only) --}}
                                        <td>
                                            @php
                                                $original = $item['original_qty'] ?? $item['qty'];
                                                $returned = $item['returned_qty'] ?? 0;
                                                $netRemaining = $item['max_returnable'] ?? ($original - $returned);
                                                $ppb = $item['pieces_per_box'] ?? 1;
                                                
                                                // Format remaining pieces to Box.Piece
                                                if ($ppb > 1) {
                                                    $remBoxes = floor($netRemaining / $ppb);
                                                    $remPcs = $netRemaining % $ppb;
                                                    $remDisplay = $remBoxes . ($remPcs > 0 ? '.'.$remPcs : '');
                                                } else {
                                                    $remDisplay = $netRemaining;
                                                }
                                            @endphp
                                            <div class="text-center">
                                                <span class="fw-bold text-dark fs-6">{{ $remDisplay }}</span>
                                                <small class="text-muted d-block" style="font-size: 0.65rem;">Remaining Pieces: {{ $netRemaining }}</small>
                                            </div>
                                            @if ($returned > 0)
                                                <div class="mt-1 pt-1 border-top" style="font-size: 0.65rem;">
                                                    <span class="text-danger">Returned: {{ $returned }}</span>
                                                    <span class="text-muted ms-1">/ {{ $original }}</span>
                                                </div>
                                            @endif
                                        </td>


                                        {{-- Return Qty (Box.Piece) --}}
                                        <td>
                                            <input type="text" name="qty_box[]"
                                                class="form-control text-center quantity-box" value="0"
                                                placeholder="0.0" {{ $netRemaining <= 0 ? 'readonly' : '' }}>
                                            <small class="text-muted" style="font-size: 0.7rem;">Format: Box.Piece</small>
                                        </td>

                                        {{-- Total Return Pieces (Read Only, Calculated) --}}
                                        <td>
                                            <input type="number" name="qty[]"
                                                class="form-control text-center fw-bold text-primary quantity"
                                                value="0" readonly min="0" max="{{ $netRemaining }}"
                                                data-max="{{ $netRemaining }}" data-original="{{ $original }}"
                                                data-returned="{{ $returned }}">
                                        </td>

                                        <td><input type="text" name="total[]"
                                                class="form-control text-end fw-bold row-total" value="0.00" readonly>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger border-0 remove-row rounded-circle"
                                                title="Remove Item"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Summary -->
                    <div class="row mt-4">
                        <div class="col-md-7">
                            <div class="p-4 bg-light rounded border h-100">
                                <label class="form-label text-muted small">AMOUNT IN WORDS</label>
                                <input type="text" name="total_amount_Words"
                                    class="form-control border-0 bg-transparent fw-bold text-primary fs-5 fst-italic"
                                    id="amountInWords" readonly placeholder="...">

                                <div class="mt-4 pt-4 border-top">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-cubes text-muted"></i>
                                        <span class="text-muted small">Total Pieces Returned:</span>
                                        <strong id="totalPieces" class="text-dark fs-5">0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Partial Return Status Indicator -->
                        <div class="col-md-12 mb-3">
                            <div class="partial-return-indicator shadow-sm border-0 p-3 rounded"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-white">
                                        <i class="fas fa-chart-pie me-2"></i>Return Status
                                    </h6>
                                    <span id="returnTypeBadge" class="badge bg-light text-dark">
                                        <i class="fas fa-spinner fa-spin me-1"></i>Calculating...
                                    </span>
                                </div>
                                <div class="progress" style="height: 25px; background: rgba(255,255,255,0.2);">
                                    <div id="returnProgressBar"
                                        class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                        style="width: 0%; background: #10ac84;" aria-valuenow="0" aria-valuemin="0"
                                        aria-valuemax="100">
                                        <strong id="returnPercentage">0%</strong>
                                    </div>
                                </div>
                                <div class="mt-2 text-white small" id="returnStatusText">
                                    <i class="fas fa-info-circle me-1"></i>Select items to return
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="summary-card shadow-sm border-0">
                                <h6 class="mb-3 text-uppercase fw-bold text-muted"
                                    style="font-size: 0.8rem; letter-spacing: 1px;">Refund Summary</h6>
                                <div class="summary-row">
                                    <span class="text-muted">Subtotal</span>
                                    <input type="text" name="total_subtotal" id="billAmount"
                                        class="form-control form-control-sm w-50 text-end border-0 bg-transparent p-0"
                                        readonly value="0.00">
                                </div>
                                <div class="summary-row">
                                    <span class="text-muted">Less: Item Discount</span>
                                    <input type="text" name="total_discount" id="itemDiscount"
                                        class="form-control form-control-sm w-50 text-end border-0 bg-transparent p-0 text-danger"
                                        readonly value="0.00">
                                </div>
                                <div class="summary-row align-items-center mt-2">
                                    <span class="text-dark fw-medium">Less: Extra Deductions</span>
                                    <input type="number" name="extra_discount" id="extraDiscount"
                                        class="form-control form-control-sm w-50 text-end bg-white" value="0">
                                </div>
                                <hr class="my-3">
                                <div class="summary-row total">
                                    <span>NET REFUND AMOUNT</span>
                                    <input type="text" name="net_amount" id="netAmount"
                                        class="form-control form-control-lg w-50 text-end border-0 bg-transparent p-0 fw-bold text-primary"
                                        readonly value="0.00">
                                </div>

                                <!-- Payment Voucher Section -->
                                <div class="mt-4 pt-4 border-top">
                                    <h6 class="mb-3 text-uppercase fw-bold text-muted"
                                        style="font-size: 0.8rem; letter-spacing: 1px;">
                                        <i class="fas fa-money-bill-wave me-2"></i>Refund Received (Optional)
                                    </h6>

                                    <div class="alert alert-light border small text-muted">
                                        <i class="fas fa-info-circle me-1"></i> If you received cash/bank refund, enter
                                        details. Otherwise, amount will be credited to customer Ledger.
                                    </div>

                                    <div class="payment-voucher-rows">
                                        <div class="payment-row mb-2">
                                            <div class="row g-2">
                                                <div class="col-7">
                                                    <select name="payment_account_id[]"
                                                        class="form-select form-select-sm payment-account">
                                                        <option value="">Select Account (Cash/Bank)</option>
                                                        @foreach ($accounts as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->title }}
                                                                ({{ $acc->account_code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-5">
                                                    <input type="number" name="payment_amount[]" step="0.01"
                                                        class="form-control form-control-sm text-end payment-amount"
                                                        placeholder="Amount">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="mt-4 d-grid gap-2">
                                    <button type="submit" class="btn btn-erp-primary btn-lg shadow-sm">
                                        <i class="fas fa-check-circle me-2"></i> Process Sale Return
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {

            // Initialize Select2
            // Initialize Select2
            if ($.fn.select2) {
                $('.payment-account').select2();
            } else {
                console.warn('Select2 not loaded');
            }

            function num(n) {
                return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
            }

            function numberToWords(num) {
                const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
                    "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
                    "Eighteen", "Nineteen"
                ];
                const b = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
                if ((num = num.toString()).length > 9) return "Overflow";
                const n = ("000000000" + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{3})$/);
                if (!n) return;
                let str = "";
                str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + " " + a[n[1][1]]) + " Crore " : "";
                str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + " " + a[n[2][1]]) + " Lakh " : "";
                str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + " " + a[n[3][1]]) + " Thousand " : "";
                str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + " " + a[n[4][1]]) + " " : "";
                return str.trim() + " Rupees Only";
            }

            function recalcRow($row) {
                const qty = num($row.find('.quantity').val()); // Pieces (Total)
                const price = num($row.find('.price').val()); // Price (Unit Price)
                const sizeMode = $row.find('.size-mode').val();
                const ppm2 = num($row.find('.pieces-per-m2').val());
                const ppb = num($row.find('.pieces-per-box').val()) || 1;

                let total = 0;

                if (sizeMode === 'by_size') {
                    // Price is per M2. Total M2 = Qty * PPM2 (Wait, check logic)
                    // usually pieces_per_m2 is M2 per Piece?
                    // Let's check logic: Gross = TotalPieces * m2_per_piece * price_per_m2
                    // Controller says: $lineTotal = round($ppm2 * $qty * $price, 2);
                    // note: in controller $ppm2 variable name is $ppm2 but it comes from pieces_per_m2 column.
                    // Usually if it is "Pieces Per M2", then M2 = pieces / PPM2.
                    // BUT if the math in controller is `PPM2 * Qty`, then that variable is actually "M2 Per Piece".
                    // Let's assume controller logic is Source of Truth: $ppm2 * $qty * $price.
                    total = qty * ppm2 * price;
                } else if (sizeMode === 'by_cartons' || sizeMode === 'by_carton') {
                    // Price is Per Box.
                    // Total = (Qty / PPB) * Price
                    total = (qty / ppb) * price;
                } else {
                    // Price is Per Piece
                    total = qty * price;
                }

                $row.find('.row-total').val(total.toFixed(2));
            }

            function recalcSummary() {
                let billAmount = 0;
                let totalQty = 0;

                $('#returnItems tr').each(function() {
                    const qty = num($(this).find('.quantity').val());
                    const rowTotal = num($(this).find('.row-total').val());

                    billAmount += rowTotal;
                    totalQty += qty;
                });

                const extraDiscount = num($('#extraDiscount').val()); // Deduction

                const net = billAmount - extraDiscount;

                $('#billAmount').val(billAmount.toFixed(2));
                $('#netAmount').val(net.toFixed(2));

                if (net > 0) {
                    $('#amountInWords').val(numberToWords(Math.round(net)));
                } else {
                    $('#amountInWords').val('Zero Rupees');
                }

                $('#totalPieces').text(totalQty);

                // Update visual indicators
                updatePartialReturnIndicator();
            }

            // Return All Button Logic
            $('#btnReturnAll').click(function() {
                $('#returnItems tr').each(function() {
                    const $row = $(this);
                    const maxQty = parseFloat($row.find('.quantity').attr('data-max')) || 0;
                    const ppb = parseFloat($row.find('.pieces-per-box').val()) || 1;

                    // Set Total Pieces
                    $row.find('.quantity').val(maxQty);

                    // Calculate Box.Piece representation
                    let boxDisplay = '';
                    if (ppb > 1) {
                        const boxes = Math.floor(maxQty / ppb);
                        const pieces = maxQty % ppb;
                        if (pieces > 0) {
                            boxDisplay = boxes + '.' + pieces;
                        } else {
                            boxDisplay = boxes;
                        }
                    } else {
                        boxDisplay = maxQty; // If no box size, just show total pieces
                    }

                    // Update Box Input
                    $row.find('.quantity-box').val(boxDisplay);

                    // Trigger recalc
                    recalcRow($row);
                });
                recalcSummary();
            });

            // Auto-fill Max on Click/Focus if 0
            $(document).on('click focus', '.quantity-box', function() {
                const val = $(this).val();
                // Check if 0, 0.0, or empty
                if (parseFloat(val) === 0 || val === '') {
                    const $row = $(this).closest('tr');
                    const maxQty = parseFloat($row.find('.quantity').attr('data-max')) || 0;
                    const ppb = parseFloat($row.find('.pieces-per-box').val()) || 1;

                    // Calculate Box.Piece representation
                    let boxDisplay = '';
                    if (ppb > 1) {
                        const boxes = Math.floor(maxQty / ppb);
                        const pieces = maxQty % ppb;
                        if (pieces > 0) {
                            boxDisplay = boxes + '.' + pieces;
                        } else {
                            boxDisplay = boxes;
                        }
                    } else {
                        boxDisplay = maxQty;
                    }

                    $(this).val(boxDisplay);
                    $(this).trigger('input'); // Update totals
                    $(this).select(); // Select text for easy overwrite
                }
            });

            // Box.Piece Input Logic
            $(document).on('input', '.quantity-box', function() {
                const $row = $(this).closest('tr');
                const val = $(this).val();
                const ppb = num($row.find('.pieces-per-box').val());

                // Parse Box.Piece format
                // Example: 1.2 => 1 Box, 2 Pieces (NOT 1.2 Boxes)
                // Logic: Integer part = Box. Decimal part = Pieces.

                let boxes = 0;
                let pieces = 0;

                if (val.includes('.')) {
                    const parts = val.split('.');
                    boxes = num(parts[0]);

                    // STRICT LOGIC: "10.1" -> 1 piece. "10.01" -> 1 piece. "10.10" -> 10 pieces.
                    // We treat the part after dot as an integer "Piece Count" directly.
                    const decimalPart = parts[1];
                    if (decimalPart) {
                        // User explicitly said "10.1 means 10 box and 1 piece".
                        // Standard float would treat 10.1 as 10.10 (10 pieces if ppb=100) or similar.
                        // Here we take the string value literally as integer pieces.
                        pieces = parseInt(decimalPart);
                    }
                } else {
                    boxes = num(val);
                }

                // Validation: If pieces >= ppb, just allow it but it might look weird.
                // Or auto-convert? User said "apply it everything... accucsry".
                // If I have 12 pieces/box and write 0.12, that is 12 pieces => 1 box.
                // Let's just sum it up.

                let totalPieces = 0;
                if (ppb > 0) {
                    totalPieces = (boxes * ppb) + pieces;
                } else {
                    totalPieces = boxes; // If no box size, inputs are pieces
                }

                // Update Read-Only Total Pieces
                $row.find('.quantity').val(totalPieces);

                // Trigger validations and recalc
                $row.find('.quantity').trigger('input');
            });


            // Events
            $(document).on('input', '.quantity, .price, #extraDiscount', function() {
                const $row = $(this).closest('tr');

                // Validate max returnable quantity
                if ($(this).hasClass('quantity')) {
                    const qtyPc = num($(this).val());
                    const maxReturnable = num($(this).attr('data-max'));

                    if (qtyPc > maxReturnable) {
                        $(this).val(maxReturnable);
                        $(this).addClass('border-danger');

                        // Show warning
                        if (!$(this).next('.text-danger').length) {
                            $(this).after('<small class="text-danger d-block">Max: ' + maxReturnable +
                                ' pieces (Purchased Qty)</small>');
                        }

                        setTimeout(() => {
                            $(this).removeClass('border-danger');
                            $(this).next('.text-danger').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 2000);
                    }
                }

                if ($row.length) {
                    recalcRow($row);
                }
                recalcSummary();
            });

            // Initialize
            $('#returnItems tr').each(function() {
                recalcRow($(this));
            });
            recalcSummary();

            // Remove row
            $(document).on('click', '.remove-row', function() {
                if (confirm('Are you sure you want to remove this item from return?')) {
                    $(this).closest('tr').remove();
                    recalcSummary();
                }
            });

            // Update Partial Return Visual Indicator
            function updatePartialReturnIndicator() {
                let totalOriginalPieces = 0;
                let totalReturningPieces = 0;

                $('#returnItems tr').each(function() {
                    const $qtyInput = $(this).find('.quantity');
                    const soldQty = num($qtyInput.attr('data-max')); // Max is original
                    const returningQty = num($qtyInput.val());

                    totalOriginalPieces += soldQty;
                    totalReturningPieces += returningQty;
                });

                const returnPercentage = totalOriginalPieces > 0 ? (totalReturningPieces / totalOriginalPieces *
                        100) :
                    0;

                // Update progress bar
                $('#returnProgressBar').css('width', returnPercentage + '%');
                $('#returnProgressBar').attr('aria-valuenow', returnPercentage);
                $('#returnPercentage').text(returnPercentage.toFixed(1) + '%');

                // Update badge and status text
                if (totalReturningPieces === 0) {
                    $('#returnTypeBadge').html('<i class="fas fa-info-circle me-1"></i>No Items Selected');
                    $('#returnTypeBadge').removeClass().addClass('badge bg-secondary');
                    $('#returnStatusText').html('<i class="fas fa-info-circle me-1"></i>Select items to return');
                    $('#returnProgressBar').css('background', '#6c757d');
                } else if (returnPercentage >= 100) {
                    $('#returnTypeBadge').html('<i class="fas fa-check-circle me-1"></i>Full Return');
                    $('#returnTypeBadge').removeClass().addClass('badge bg-success');
                    $('#returnStatusText').html('<i class="fas fa-check-circle me-1"></i>Returning all ' +
                        totalOriginalPieces + ' pieces (100% of purchase)');
                    $('#returnProgressBar').css('background', '#10ac84');
                } else {
                    $('#returnTypeBadge').html('<i class="fas fa-chart-pie me-1"></i>Partial Return');
                    $('#returnTypeBadge').removeClass().addClass('badge bg-warning text-dark');
                    $('#returnStatusText').html('<i class="fas fa-chart-pie me-1"></i>Returning ' +
                        totalReturningPieces + ' of ' + totalOriginalPieces + ' pieces (' + returnPercentage
                        .toFixed(1) + '%)');
                    $('#returnProgressBar').css('background', '#f79f1f');
                }
            }


        });
    </script>
@endsection
