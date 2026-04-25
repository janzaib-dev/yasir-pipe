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
                <h5 class="mb-0">Booking System</h5>
                <div>
                    <a href="" class="btn btn-primary"> DC</a>
                </div>
            </div>
     <form action="{{ route('bookings.store') }}" method="POST">

                @csrf
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

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
                                @foreach ($Customer as $c)
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
                                    <th>product</th>
                                    <th>Item Code</th>
                                    <th>Color</th>

                                    <th>Brand</th>
                                    <th>Unit</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
<style>
/* Select2: make selection stay in one line and scroll horizontally */
.select2-container--default .select2-selection--multiple {
    display: flex !important;
    flex-wrap: nowrap !important;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    min-height: 38px !important;
    max-height: 38px !important;
    white-space: nowrap !important;
    scrollbar-width: thin;
}

/* Each tag styling */
.select2-selection__choice {
    white-space: nowrap !important;
    margin-right: 3px !important;
    font-size: 11px;
    padding: 2px 5px !important;
}

/* Remove unwanted spacing */
.select2-search--inline {
    flex: none !important;
}
</style>



                            </style>
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
 <td class="color border" style="min-width: 180px; max-width: 200px; overflow-x: auto; white-space: nowrap;">
    <div style="overflow-x: auto;">
        <select class="form-control form-control-sm select2-color" name="color[][]" multiple></select>
    </div>
</td>





                                    <td class="uom border">
                                        <input type="text" name="uom[]" class="form-control" readonly>
                                    </td>

                                    <td class="unit border">
                                        <input type="text" name="unit[]" class="form-control" readonly>
                                    </td>

                                    <!-- Price = wholesale_price (readonly) -->
                                    <td>
                                        <input type="number" step="0.01" name="price[]" class="form-control price"
                                            value="">
                                    </td>

                                    <!-- Per-item Discount (PKR, editable) -->
                                    <td>
                                        <input type="number" step="0.01" name="item_disc[]"
                                            class="form-control item_disc" value="">
                                    </td>

                                    <td class="qty">
                                        <input type="number" name="qty[]" class="form-control quantity" value=""
                                            min="1">
                                    </td>

                                    <!-- Row Total (readonly) -->
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
                            <td><input type="text" name="total_subtotal" class="form-control form-control-sm text-center"
                                    id="billAmount" readonly></td>
                            <td><input type="text" name="total_discount" class="form-control form-control-sm text-center"
                                    id="itemDiscount" readonly></td>
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
                            <button class="btn btn-primary">Save</button>
                            <button class="btn btn-secondary">Close</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    function num(n) {
        return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
    }

    function numberToWords(num) {
        const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
            "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
            "Eighteen", "Nineteen"];
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
        const qty = num($row.find('.quantity').val());
        const price = num($row.find('.price').val());
        const disc = num($row.find('.item_disc').val());
        let total = (qty * price) - disc;
        if (total < 0) total = 0;
        $row.find('.row-total').val(total.toFixed(2));
    }

    function recalcSummary() {
        let billAmount = 0;
        let itemDiscount = 0;
        let totalQty = 0;

        $('#purchaseItems tr').each(function () {
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
        $('#amountInWords').val(numberToWords(Math.round(net)));

        $('strong:contains("TOTAL PIECES")').next().text(totalQty);
    }

    // Events
    $(document).on('input', '.quantity, .price, .item_disc, #extraDiscount, #cash, #card', function () {
        const $row = $(this).closest('tr');
        if ($row.length) {
            recalcRow($row);
        }
        recalcSummary();
    });

    // Initialize
    $('#purchaseItems tr').each(function () {
        recalcRow($(this));
    });
    recalcSummary();
});
</script>

<script>
    $(document).ready(function() {

        // ---------- Helper Functions ----------
        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());
            const price = num($row.find('.price').val());
            const disc = num($row.find('.item_disc').val());
            let total = (qty * price) - disc;
            if (total < 0) total = 0;
            $row.find('.row-total').val(total.toFixed(2));
        }

        function recalcSummary() {
            let sub = 0;
            $('#purchaseItems .row-total').each(function() {
                sub += num($(this).val());
            });
            $('#subtotal').val(sub.toFixed(2));

            const oDisc = num($('#overallDiscount').val());
            const xCost = num($('#extraCost').val());
            const net = (sub - oDisc + xCost);
            $('#netAmount').val(net.toFixed(2));
        }

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
    <td><input type="number" step="0.01" name="price[]" class="form-control price" value="1" ></td>
    <td><input type="number" step="0.01" name="item_disc[]" class="form-control item_disc" value=""></td>
    <td class="qty"><input type="number" name="qty[]" class="form-control quantity" value="" min="1"></td>
    <td class="total border"><input type="text" name="total[]" class="form-control row-total" readonly></td>
    <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
</tr>`;
            $('#purchaseItems').append(newRow);
        }

        // ---------- Product Search ----------
        $(document).on('keyup', '.productSearch', function(e) {
            const $input = $(this);
            const q = $input.val().trim();
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            if (q.length === 0) {
                $box.empty();
                return;
            }

            // Keyboard Navigation
            const isNavKey = ['ArrowDown', 'ArrowUp', 'Enter'].includes(e.key);
            if (isNavKey && $box.children('.search-result-item').length) {
                const $items = $box.children('.search-result-item');
                let idx = $items.index($items.filter('.active'));
                if (e.key === 'ArrowDown') {
                    idx = (idx + 1) % $items.length;
                    $items.removeClass('active');
                    $items.eq(idx).addClass('active');
                    e.preventDefault();
                    return;
                }
                if (e.key === 'ArrowUp') {
                    idx = (idx <= 0 ? $items.length - 1 : idx - 1);
                    $items.removeClass('active');
                    $items.eq(idx).addClass('active');
                    e.preventDefault();
                    return;
                }
                if (e.key === 'Enter') {
                    if (idx >= 0) {
                        $items.eq(idx).trigger('click');
                    } else if ($items.length === 1) {
                        $items.eq(0).trigger('click');
                    }
                    e.preventDefault();
                    return;
                }
            }

            // AJAX call to search
            $.ajax({
                url: "{{ route('search-product-name') }}",
                type: 'GET',
                data: {
                    q
                },
                success: function(data) {
                    let html = '';
                    (data || []).forEach(p => {
                        const brand = (p.brand && p.brand.name) ? p.brand.name : '';
                        const unit = (p.unit_id ?? '');
                        const price = (p.wholesale_price ?? 0);
                        const code = (p.item_code ?? '');
                        const name = (p.item_name ?? '');
                        const id = (p.id ?? '');
                        const colors = p.color ? p.color : '[]';

                        html += `
<li class="list-group-item search-result-item"
    tabindex="0"
    data-product-id="${id}"
    data-product-name="${name}"
    data-product-uom="${brand}"
    data-product-unit="${unit}"
    data-product-code="${code}"
    data-price="${price}"
    data-colors='${colors}'>
  ${name} - ${code} - Rs. ${price}
</li>`;
                    });
                    $box.html(html);
                    $box.children('.search-result-item').first().addClass('active');
                },
                error: function() {
                    $box.empty();
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

        // Keyboard Enter on suggestion
        $(document).on('keydown', '.searchResults .search-result-item', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('click');
            }
        });

        // Quantity/Price/Disc Update
        $('#purchaseItems').on('input', '.quantity, .price, .item_disc', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        // Remove row
        $('#purchaseItems').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            recalcSummary();
        });

        // Discount/Extra Cost Update
        $('#overallDiscount, #extraCost').on('input', function() {
            recalcSummary();
        });

        // Initialize first row
        recalcRow($('#purchaseItems tr:first'));
        recalcSummary();

        // Select2 Color Init on focus
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
