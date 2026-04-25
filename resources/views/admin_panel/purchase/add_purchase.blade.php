{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->
@extends('admin_panel.layout.app')
<style>
    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        /* border: 1px solid #ddd; */
    }

    .search-result-item.active {
        background: #007bff;
        color: white;
    }
</style>
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
                        rel="stylesheet">

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

                    <body>
                        <!-- page-wrapper start -->

                        <form action="{{ route('store.Purchase') }}" method="POST">
                            @csrf

                            <style>
                                body {
                                    background: #f5f6fa;
                                }

                                .card {
                                    border-radius: 10px;
                                    border: 1px solid #e0e3ea;
                                    box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
                                }

                                .card-header {
                                    background: linear-gradient(90deg, #f8f9fc, #eef1f7);
                                    font-weight: 600;
                                    font-size: 17px;
                                    color: #2c3e50;
                                }

                                label {
                                    font-size: 14px;
                                    font-weight: 500;
                                    color: #495057;
                                }

                                .form-control,
                                .form-select {
                                    border-radius: 6px;
                                    font-size: 14px;
                                }

                                .table thead th {
                                    background: #f1f3f5;
                                    font-weight: 600;
                                    font-size: 14px;
                                    text-align: center;
                                    white-space: nowrap;
                                }

                                .table tbody td {
                                    vertical-align: middle;
                                    white-space: nowrap;
                                }

                                .btn-primary {
                                    padding: 8px 26px;
                                    border-radius: 6px;
                                }
                            </style>
                            <style>
                                .gp-actions-center {
                                    display: flex;
                                    justify-content: center;
                                    gap: 12px;
                                }

                                /* .gp-action-btn {
          display: flex;
          align-items: center;
          gap: 6px;
          padding: 6px 12px;
          border-radius: 6px;
          background: #f8f9fa;
          text-decoration: none;
          color: #333;
          font-size: 14px;
        } */

                                .gp-action-btn:hover {
                                    background: #e9ecef;
                                }

                                .gp-action-btn.danger {
                                    color: #dc3545;
                                }

                                .gp-action-btn {
                                    width: 60px;
                                    /* width kam */
                                    height: 60px;
                                    /* height zyada */
                                    padding: 10px;

                                    display: flex;
                                    flex-direction: column;
                                    /* icon upar, text neeche */
                                    align-items: center;
                                    justify-content: center;
                                    gap: 6px;

                                    background-color: #f1f3f5;
                                    border-radius: 10px;
                                    text-decoration: none;
                                    color: #333;
                                    font-size: 13px;
                                }

                                .gp-action-btn i {
                                    font-size: 20px;
                                }
                            </style>
                            <div class="gp-header row align-items-center mb-2">

                                <!-- Left : Title -->
                                <div class="col-md-3">
                                    <div class="gp-title">
                                        <h5 class="mb-0 fw-semibold" style="font-size:20px">Purchase Product</h5>
                                        {{-- <small class="text-muted">Create & manage inward stock entries</small> --}}
                                    </div>
                                </div>

                                {{-- <div class="row"> --}}
                                <div class="col-7">
                                    <div class="gp-actions-center text-center">

                                        <a href="javascript:void(0)" class="gp-action-btn" data-bs-toggle="modal"
                                            data-bs-target="#vendorModal">
                                            <i class="fa fa-user-plus"></i>
                                            <span>Vendor</span>
                                        </a>


                                        <a href="#" class="gp-action-btn">
                                            <i class="fa fa-box"></i>
                                            <span>Item</span>
                                        </a>

                                        <a href="#" class="gp-action-btn danger"
                                            onclick="return confirm('Delete this gatepass?')">
                                            <i class="fa fa-trash"></i>
                                            <span>Delete</span>
                                        </a>

                                    </div>
                                </div>
                                {{-- </div> --}}

                                <!-- Right : Back Button -->
                                <div class="col-md-2 text-end">
                                    <a href="{{ route('Purchase.home') }}" class="btn btn-outline-danger btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back
                                    </a>
                                </div>

                            </div>
                            <div class="container-fluid "style="background-color:white;padding:20px 20px;">


                                <!-- ================= TOP TWO COLUMNS ================= -->
                                <div class="row g-4 mt-3">

                                    <!-- LEFT : PURCHASE DETAILS -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header" style="font-size:20px">Purchase Details</div>
                                            <div class="card-body">
                                                <div class="row g-3">

                                                    <div class="col-md-6">
                                                        <label>Current Date</label>
                                                        <input type="date" name="purchase_date"
                                                            value="{{ date('Y-m-d') }}" class="form-control">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Company Invoice #</label>
                                                        <input type="text" name="purchase_order_no" class="form-control">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Warehouse</label>
                                                        <select name="warehouse_id" class="form-control">
                                                            <option disabled selected>Select Warehouse</option>
                                                            @foreach ($Warehouse as $w)
                                                                <option value="{{ $w->id }}">{{ $w->warehouse_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Transport Name</label>
                                                        <input type="text" name="transport_name" class="form-control">
                                                    </div>

                                                    <div class="col-md-12">
                                                        <label>Job / Description</label>
                                                        <input type="text" name="note" class="form-control">
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT : VENDOR DETAILS -->
                                    <div class="col-lg-6">
                                        <div class="card h-100">
                                            <div class="card-header" style="font-size:20px">Vendor Details</div>
                                            <div class="card-body">
                                                <div class="row g-3">

                                                    <div class="col-md-12">
                                                        <label>Vendor</label>
                                                        <select name="vendor_id" id="vendor_select" class="form-control">
                                                            <option disabled selected>Select Vendor</option>
                                                            @foreach ($Vendor as $v)
                                                                <option value="{{ $v->id }}"
                                                                    data-phone="{{ $v->phone }}"
                                                                    data-email="{{ $v->email }}"
                                                                    data-address="{{ $v->address }}">
                                                                    {{ $v->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Phone</label>
                                                        <input type="text" id="vendor_phone" class="form-control"
                                                            readonly>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Email</label>
                                                        <input type="text" id="vendor_email" class="form-control"
                                                            readonly>
                                                    </div>

                                                    <div class="col-md-12">
                                                        <label>Address</label>
                                                        <input type="text" id="vendor_address" class="form-control"
                                                            readonly>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- ================= PRODUCT TABLE ================= -->
                                <div class="card mt-4">
                                    <div class="card-header" style="font-size:20px">Product Details</div>
                                    <div class="card-body p-0">

                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0" style="table-layout:fixed;">
                                                <thead>
                                                    <tr>
                                                        <th style="width:220px">Product</th>
                                                        <th style="width:120px">Item Code</th>
                                                        <th style="width:120px">Brand</th>
                                                        <th style="width:90px">Unit</th>
                                                        <th style="width:110px">Price</th>
                                                        <th style="width:110px">Discount</th>
                                                        <th style="width:80px">Qty</th>
                                                        <th style="width:120px">Total</th>
                                                        <th style="width:70px">Action</th>
                                                    </tr>
                                                </thead>

                                                <tbody id="purchaseItems">
                                                    <tr>
                                                        <td>
                                                            <input type="hidden" name="product_id[]">
                                                            <input type="text" class="form-control"
                                                                placeholder="Search product">
                                                        </td>
                                                        <td><input type="text" name="item_code[]" class="form-control"
                                                                readonly></td>
                                                        <td><input type="text" name="uom[]" class="form-control"
                                                                readonly></td>
                                                        <td><input type="text" name="unit[]" class="form-control"
                                                                readonly></td>
                                                        <td><input type="number" name="price[]" class="form-control">
                                                        </td>
                                                        <td><input type="number" name="item_disc[]"
                                                                class="form-control"></td>
                                                        <td><input type="number" name="qty[]" class="form-control"
                                                                min="1"></td>
                                                        <td><input type="text" name="total[]" class="form-control"
                                                                readonly></td>
                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger">×</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>

                                <!-- ================= SUMMARY ================= -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-md-3">
                                                <label>Subtotal</label>
                                                <input type="text" class="form-control" readonly>
                                            </div>

                                            <div class="col-md-3">
                                                <label>Overall Discount</label>
                                                <input type="number" class="form-control">
                                            </div>

                                            <div class="col-md-3">
                                                <label>Extra Cost</label>
                                                <input type="number" class="form-control">
                                            </div>

                                            <div class="col-md-3">
                                                <label>Net Amount</label>
                                                <input type="text" class="form-control fw-bold text-success" readonly>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- ================= SUBMIT ================= -->
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        Save Purchase
                                    </button>
                                </div>

                            </div>
                        </form>

                        <!-- =========================== -->

                        <!-- ===== END SUMMARY ===== -->



                        {{--
                                                    <button type="submit" class="btn btn-primary w-100 mt-4">Submit
                                                        Purchase</button> --}}
                        <button type="button" id="submitBtn" class="btn btn-primary mt-5">Submit</button>

                        </form>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>
    </div>
    </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("form[action='{{ route('store.Purchase') }}']");
        const submitBtn = document.getElementById("submitBtn");

        // Enter key se form submit disable
        form.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
            }
        });

        // Sirf button click pe submit
        submitBtn.addEventListener("click", function() {
            form.submit();
        });
    });
</script>

{{-- Success & Error Messages --}}
@if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success')),
            confirmButtonColor: '#3085d6',
        });
    </script>
@endif


@if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: {
                !!json_encode(implode('<br>', $errors - > all())) !!
            },
            confirmButtonColor: '#d33',
        });
    </script>
@endif

{{-- Cancel Button Confirmation --}}
<script>
    // Prevent Enter key from submitting form in product search
    $(document).on('keydown', '.productSearch', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // stops form submission
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will cancel your changes!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, go back!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '';
                    }
                });
            });
        }
    });
</script>

{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->

<script>
    $(document).ready(function() {

        // ---------- Helpers ----------
        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());
            const price = num($row.find('.price').val());
            const disc = num($row.find('.item_disc').val()); // per-item discount
            let total = (qty * price) - (qty * disc); // ✅ correct formula
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

        // ---------- Product Search (AJAX) ----------
        $(document).on('keyup', '.productSearch', function(e) {
            const $input = $(this);
            const q = $input.val().trim();
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            // Keyboard navigation (Arrow Up/Down + Enter)
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

            // Normal fetch
            if (q.length === 0) {
                $box.empty();
                return;
            }

            $.ajax({
                url: "{{ route('search-products') }}",
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
                        html += `
                            <li class="list-group-item search-result-item"
                                tabindex="0"
                                data-product-id="${id}"
                                data-product-name="${name}"
                                data-product-uom="${brand}"
                                data-product-unit="${unit}"
                                data-product-code="${code}"
                                data-price="${price}">
                                ${name} - ${code} - Rs. ${price}
                            </li>`;
                    });
                    $box.html(html);

                    // first item active for quick Enter
                    $box.children('.search-result-item').first().addClass('active');
                },
                error: function() {
                    $box.empty();
                }
            });
        });

        // Click/Enter on suggestion
        $(document).on('click', '.search-result-item', function() {
            const $li = $(this);
            const $row = $li.closest('tr');

            $row.find('.productSearch').val($li.data('product-name'));
            $row.find('.item_code input').val($li.data('product-code'));
            $row.find('.uom input').val($li.data('product-uom'));
            $row.find('.unit input').val($li.data('product-unit'));
            $row.find('.price').val($li.data('price'));

            $row.find('.product_id').val($li.data('product-id'));

            // reset qty & discount for fresh calc
            $row.find('.quantity').val(1);
            $row.find('.item_disc').val(0);

            recalcRow($row);
            recalcSummary();

            // clear results
            $row.find('.searchResults').empty();

            // append new blank row and focus its search
            appendBlankRow();
            $('#purchaseItems tr:last .productSearch').focus();
        });

        // Also allow keyboard Enter selection when list focused
        $(document).on('keydown', '.searchResults .search-result-item', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('click');
            }
        });

        // Row calculations
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

        // Summary inputs
        $('#overallDiscount, #extraCost').on('input', function() {
            recalcSummary();
        });

        // init first row values
        recalcRow($('#purchaseItems tr:first'));
        recalcSummary();
    });
</script>
