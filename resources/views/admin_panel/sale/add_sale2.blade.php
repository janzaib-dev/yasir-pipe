@extends('admin_panel.layout.app')
@section('content')
    <style>
        .searchResults {
            position: absolute;
            z-index: 9999;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: #fff;
            /* border: 1px solid #ddd; */
            text-align: start
        }

        .search-result-item.active {
            background: #007bff;
            color: white;
        }
    </style>

    <style>
        .table-scroll tbody {
            display: block;
            max-height: calc(60px * 5);
            /* Assuming each row is ~40px tall */
            overflow-y: auto;
        }

        .table-scroll thead,
        .table-scroll tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        /* Optional: Hide scrollbar width impact */
        .table-scroll thead {
            width: calc(100% - 1em);
        }

        .table-scroll .icon-col {
            width: 51px;
            /* Ya jitni chhoti chahiye */
            min-width: 51px;
            max-width: 40px;
        }

        .table-scroll {
            max-height: none !important;
            overflow-y: visible !important;
        }


        .disabled-row input {
            background-color: #f8f9fa;
            pointer-events: none;
        }
    </style>
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-light text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">SALES</h5>
                <div>
                    <a href="" class="btn btn-primary"> DC</a>
                </div>
            </div>
            <form action="{{ route('sales.store') }}" method="POST">
                @csrf
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <input type="hidden" name="branch_id" value="1">
                <input type="hidden" name="warehouse_id" value="1">

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="card-body">
                    {{-- Top Form --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Customer:</label>
                            <select name="customer" class="form-control form-control-sm">
                                <option value="">Select Customer</option>
                                @foreach ($customer as $c)
                                    <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reference #</label>
                            <input type="text" name="reference" class="form-control form-control-sm">
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle text-center">
                            <thead>
                                <tr class="text-center">
                                    <th>Product</th>
                                    <th>Item Code</th>
                                    <th>Color</th>
                                    <th>Brand</th>
                                    <th>Unit</th>
                                    <th style="width: 80px;">Stock</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Qty (Pcs)</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseItems" style="max-height: 300px; overflow-y: auto;">
                                <tr>
                                    <td>
                                        <input type="hidden" name="product_id[]" class="product_id">
                                        <input type="text" class="form-control productSearch"
                                            placeholder="Enter product name..." autocomplete="off">
                                        <ul class="searchResults list-group mt-1"></ul>
                                    </td>

                                    <td class="item_code border">
                                        <input type="text" name="item_code[]" class="form-control" readonly>
                                    </td>
                                    <td class="color border"
                                        style="min-width: 180px; max-width: 200px; overflow-x: auto; white-space: nowrap;">
                                        <div style="overflow-x: auto;">
                                            <select class="form-control form-control-sm select2-color" name="color[][]"
                                                multiple></select>
                                        </div>
                                    </td>

                                    <td class="uom border">
                                        <input type="text" name="uom[]" class="form-control" readonly>
                                    </td>

                                    <td class="unit border">
                                        <input type="text" name="unit[]" class="form-control" readonly>
                                    </td>

                                    <td class="stock border">
                                        <input type="text" class="form-control stock-qty text-center" readonly
                                            tabindex="-1" style="background:#f0f0f0;">
                                    </td>

                                    <!-- Price -->
                                    <td>
                                        <input type="number" step="0.01" name="price[]" class="form-control price"
                                            value="">
                                    </td>

                                    <!-- Discount -->
                                    <td>
                                        <input type="number" step="0.01" name="item_disc[]"
                                            class="form-control item_disc" value="">
                                    </td>

                                    <td class="qty">
                                        <input type="number" name="qty[]" class="form-control quantity" value=""
                                            min="1">
                                        <small class="text-muted d-block boxes-info"
                                            style="font-size: 10px; line-height: 1.1; margin-top: 2px;"></small>
                                    </td>

                                    <!-- Row Total -->
                                    <td class="total border">
                                        <input type="text" name="total[]" class="form-control row-total" readonly>
                                    </td>

                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
                                    </td>
                                </tr>
                            </tbody>

                        </table>
                    </div>

                    {{-- Amount Summary --}}
                    <table class="table table-bordered table-sm mt-4 mb-0 text-center">
                        <tr>
                            <th>Amount In Words : </th>
                            <th>BILL AMOUNT</th>
                            <th>ITEM DISCOUNT</th>
                            <th>EXTRA DISCOUNT</th>
                            <th>NET AMOUNT</th>
                            <th>Cash</th>
                            <th>C/D Card</th>
                            <th>Change</th>
                        </tr>
                        <tr class="align-middle">
                            <td><input type="text" name="total_amount_Words" class="form-control form-control-sm"
                                    id="amountInWords" readonly></td>
                            <td><input type="text" name="total_subtotal"
                                    class="form-control form-control-sm text-center" id="billAmount" readonly></td>
                            <td><input type="text" name="total_discount"
                                    class="form-control form-control-sm text-center" id="itemDiscount" readonly></td>
                            <td><input type="number" name="total_extra_cost"
                                    class="form-control form-control-sm text-center" id="extraDiscount" value="0">
                            </td>
                            <td><input type="text" name="total_net" class="form-control form-control-sm text-center"
                                    id="netAmount" readonly></td>
                            <td><input type="number" name="cash" class="form-control form-control-sm text-center"
                                    id="cash" value="0"></td>
                            <td><input type="number" name="card" class="form-control form-control-sm text-center"
                                    id="card" value="0"></td>
                            <td><input type="text" name="change" class="form-control form-control-sm text-center"
                                    id="change" readonly></td>
                        </tr>

                    </table>


                    {{-- Footer Buttons --}}
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <strong>TOTAL PIECES : </strong> <span>0</span>
                        </div>
                        <div>
                            <!-- Using onclick to handle postFinal via AJAX for 'sale' action if needed, or submit form normally -->
                            <button type="button" onclick="submitSale('booking')" class="btn btn-warning">Book</button>
                            <button type="button" onclick="submitSale('sale')" class="btn btn-success">Sale</button>
                            <button type="button" class="btn btn-secondary">Close</button>
                        </div>
                    </div>
                </div>
        </div>
        <!-- </form> Removed form tag to use JS submission -->
    </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function submitSale(action) {
        // Collect Data and Post to postFinal
        // Check if there are items
        if ($('#purchaseItems tr').length === 0) {
            alert("Please add items first.");
            return;
        }

        const formData = {
            action: action,
            _token: '{{ csrf_token() }}',
            customer: $('select[name="customer"]').val(),
            reference: $('input[name="reference"]').val(),
            warehouse_id: $('input[name="warehouse_id"]').val(),

            product_id: [],
            item_code: [],
            uom: [],
            unit: [],
            price: [],
            item_disc: [],
            qty: [],
            total: [],
            color: [],

            total_amount_Words: $('#amountInWords').val(),
            total_subtotal: $('#billAmount').val(),
            total_discount: $('#itemDiscount').val(),
            total_extra_cost: $('#extraDiscount').val(),
            total_net: $('#netAmount').val(),
            cash: $('#cash').val(),
            card: $('#card').val(),
            change: $('#change').val()
        };

        // Loop rows
        $('#purchaseItems tr').each(function() {
            const pid = $(this).find('.product_id').val();
            if (pid) {
                formData.product_id.push(pid);
                formData.item_code.push($(this).find('.item_code input').val());
                formData.uom.push($(this).find('.uom input').val());
                formData.unit.push($(this).find('.unit input').val());
                formData.price.push($(this).find('.price').val());
                formData.item_disc.push($(this).find('.item_disc').val());
                formData.qty.push($(this).find('.quantity').val());
                formData.total.push($(this).find('.row-total').val());
                formData.color.push($(this).find('.select2-color').val());
            }
        });

        const url = action === 'sale' ? "{{ route('sales.post_final') }}" : "{{ route('sales.store') }}";

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            success: function(res) {
                if (res.ok) {
                    alert(res.msg);
                    if (res.invoice_url) {
                        window.location.href = res.invoice_url;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert("Error: " + res.msg);
                }
            },
            error: function(err) {
                alert("An error occurred: " + (err.responseJSON ? err.responseJSON.msg : err.statusText));
            }
        });
    }

    $(document).ready(function() {
        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function numberToWords(num) {
            // ... (Simple implementation or keep existing if defined) ...
            if (num === 0) return "Zero Rupees Only";
            return num + " Rupees Only"; // Keep it simple or use library
        }

        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());
            const price = num($row.find('.price').val());
            const disc = num($row.find('.item_disc').val());
            let total = (qty * price) - disc;
            if (total < 0) total = 0;
            $row.find('.row-total').val(total.toFixed(2));

            // Calc Boxes
            const pcsPerBox = num($row.data('ppb')) || 1;
            if (qty > 0 && pcsPerBox > 0) {
                const boxes = Math.floor(qty / pcsPerBox);
                const loose = qty % pcsPerBox;
                $row.find('.boxes-info').text(`${boxes} Box, ${loose} Loose`);
            } else {
                $row.find('.boxes-info').text('');
            }
        }

        function recalcSummary() {
            let billAmount = 0;
            let itemDiscount = 0;
            let totalQty = 0;

            $('#purchaseItems tr').each(function() {
                const rowTotal = num($(this).find('.row-total').val());
                const disc = num($(this).find('.item_disc').val());
                const qty = num($(this).find('.quantity').val());

                billAmount += rowTotal;
                itemDiscount += disc;
                totalQty += qty;
            });

            const extraDiscount = num($('#extraDiscount').val());
            const cash = num($('#cash').val());
            const card = num($('#card').val());

            const net = billAmount - itemDiscount - extraDiscount;
            const change = (cash + card) - net;

            $('#billAmount').val(billAmount.toFixed(2));
            $('#itemDiscount').val(itemDiscount.toFixed(2));
            $('#netAmount').val(net.toFixed(2));
            $('#change').val(change.toFixed(2));
            // $('#amountInWords').val(numberToWords(Math.round(net)));

            $('strong:contains("TOTAL PIECES")').next().text(totalQty);
        }

        // Events
        $(document).on('input', '.quantity, .price, .item_disc, #extraDiscount, #cash, #card', function() {
            const $row = $(this).closest('tr');
            if ($row.length) {
                recalcRow($row);
            }
            recalcSummary();
        });

        function appendBlankRow() {
            const newRow = `
<tr>
    <td>
        <input type="hidden" name="product_id[]" class="product_id">
        <input type="text" class="form-control productSearch" placeholder="Enter product name..." autocomplete="off">
        <ul class="searchResults list-group mt-1"></ul>
    </td>
    <td class="item_code border"><input type="text" name="item_code[]" class="form-control" readonly></td>
    <td class="color border">
        <select class="form-control form-control-sm select2-color" name="color[][]" multiple></select>
    </td>
    <td class="uom border"><input type="text" name="uom[]" class="form-control" readonly></td>
    <td class="unit border"><input type="text" name="unit[]" class="form-control" readonly></td>
    <td class="stock border"><input type="text" class="form-control stock-qty text-center" readonly tabindex="-1" style="background:#f0f0f0;"></td>
    <td><input type="number" step="0.01" name="price[]" class="form-control price" value="1" ></td>
    <td><input type="number" step="0.01" name="item_disc[]" class="form-control item_disc" value=""></td>
    <td class="qty">
        <input type="number" name="qty[]" class="form-control quantity" value="" min="1">
        <small class="text-muted d-block boxes-info" style="font-size: 10px; line-height: 1.1; margin-top: 2px;"></small>
    </td>
    <td class="total border"><input type="text" name="total[]" class="form-control row-total" readonly></td>
    <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
</tr>`;
            $('#purchaseItems').append(newRow);
            // Re-init select2 for new row key if needed (handled by focus event below)
        }

        // ---------- Product Search ----------
        $(document).on('keyup', '.productSearch', function(e) {
            // ... (Keep existing keyboard nav logic or copy it if truncated)
            const $input = $(this);
            const q = $input.val().trim();
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            if (q.length === 0) {
                $box.empty();
                return;
            }

            // Ajax
            $.ajax({
                url: "{{ route('search-product-name') }}",
                type: 'GET',
                data: {
                    q,
                    warehouse_id: $('input[name="warehouse_id"]').val()
                },
                success: function(data) {
                    let html = '';
                    (data || []).forEach(p => {
                        const brand = (p.brand && p.brand.name) ? p.brand.name : '';
                        const unit = (p.unit_id ?? '');
                        const price = (p.price ?? 0);
                        const code = (p.item_code ?? '');
                        const name = (p.item_name ?? '');
                        const id = (p.id ?? '');
                        const colors = p.color ? p.color : '[]';
                        // Use wh_stock (Pieces)
                        const stock = p.wh_stock || 0;
                        const ppb = p.pieces_per_box || p.pcs_in_carton || 1;

                        html += `
<li class="list-group-item search-result-item"
    tabindex="0"
    data-product-id="${id}"
    data-product-name="${name}"
    data-product-uom="${brand}"
    data-product-unit="${unit}"
    data-product-code="${code}"
    data-price="${price}"
    data-stock="${stock}"
    data-ppb="${ppb}"
    data-colors='${colors}'>
  ${name} - ${code} - Stock: ${stock}
</li>`;
                    });
                    $box.html(html);
                }
            });
        });

        // On Click Product Suggestion
        $(document).on('click', '.search-result-item', function() {
            const $li = $(this);
            const $row = $li.closest('tr');

            $row.find('.productSearch').val($li.data('product-name'));
            $row.find('.item_code input').val($li.data('product-code'));
            $row.find('.uom input').val($li.data('product-uom'));
            $row.find('.unit input').val($li.data('product-unit'));
            $row.find('.price').val($li.data('price'));
            $row.find('.product_id').val($li.data('product-id'));

            // Stock & Meta
            $row.find('.stock-qty').val($li.data('stock'));
            $row.data('ppb', $li.data('ppb'));

            $row.find('.quantity').val(1);
            $row.find('.item_disc').val(0);

            // Handle Color Select2
            const colors = JSON.parse($li.attr('data-colors') || '[]');
            const $colorSelect = $row.find('.select2-color');
            $colorSelect.empty();
            colors.forEach(color => {
                const option = new Option(color, color, false, false);
                $colorSelect.append(option);
            });
            $colorSelect.trigger('change.select2');

            recalcRow($row);
            recalcSummary();

            $row.find('.searchResults').empty();
            appendBlankRow();
            $('#purchaseItems tr:last .productSearch').focus();
        });

        // Select2 Color Init on focus
        // ... (existing logic)
        $('#purchaseItems').on('focus', '.select2-color', function() {
            if (!$(this).hasClass("select2-hidden-accessible")) {
                $(this).select2({
                    placeholder: "Select Color",
                    tags: true,
                    width: '100%'
                });
            }
        });
    });
</script>
