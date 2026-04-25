@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* ====== Look & Feel ====== */
        .gp-shell {
            max-width: 1200px;
            margin-inline: auto
        }

        .gp-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 4px 16px rgba(16, 24, 40, .06)
        }

        .gp-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-bottom: 1px solid #eef2f7
        }

        .gp-head h6 {
            margin: 0;
            font-weight: 700;
            letter-spacing: .2px
        }

        .gp-body {
            padding: 12px
        }

        .gp-row {
            display: grid;
            gap: 10px
        }

        .gp-2 {
            grid-template-columns: repeat(2, 1fr)
        }

        .gp-3 {
            grid-template-columns: repeat(3, 1fr)
        }

        .gp-4 {
            grid-template-columns: repeat(4, 1fr)
        }

        @media (max-width: 1100px) {

            .gp-2,
            .gp-3,
            .gp-4 {
                grid-template-columns: 1fr
            }
        }

        label {
            font-size: .82rem;
            color: #6b7280;
            margin-bottom: 4px
        }

        .form-control,
        .form-select {
            height: 34px;
            padding: .28rem .55rem;
            font-size: .9rem;
            border-radius: 9px
        }

        .select2-container--default .select2-selection--single {
            height: 34px;
            border-radius: 9px;
            border-color: #dfe3ea
        }

        .select2-selection__rendered {
            line-height: 32px !important
        }

        .select2-selection__arrow {
            height: 34px
        }

        /* ====== Items table ====== */
        .table-sm th,
        .table-sm td {
            padding: .45rem .55rem;
            vertical-align: middle
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            border-bottom: 1px solid #eaeef4
        }

        .table tbody tr:hover {
            background: #fafafa
        }

        .compact {
            --bs-table-bg: #fff;
            border: 1px solid #edf0f5;
            border-radius: 10px;
            overflow: hidden
        }

        .table-responsive {
            overflow: visible
        }

        /* dropdowns should escape */

        /* ====== Search dropdown ====== */
        .searchWrap {
            position: relative
        }

        .searchResults {
            position: absolute;
            inset: calc(100% + 2px) 0 auto 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 12px 22px rgba(16, 24, 40, .12);
            max-height: 220px;
            overflow: auto;
            z-index: 9999;
            display: none
        }

        .searchResults .result {
            padding: .5rem .65rem;
            font-size: .92rem;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer
        }

        .searchResults .result:hover {
            background: #f1f5f9
        }

        .result small {
            color: #6b7280
        }

        /* ====== Footer actions ====== */
        .gp-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 10px 14px;
            border-top: 1px solid #eef2f7;
            background: #fcfcfd;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px
        }

        .btn-slim {
            --bs-btn-padding-y: .35rem;
            --bs-btn-padding-x: .7rem;
            --bs-btn-font-size: .86rem;
            border-radius: 10px
        }

        .remove-row {
            min-height: 30px;
            min-width: 30px;
            padding: 2px 6px;
            font-size: 14px
        }

        /* ///////////////////////////// */
        /* ///////////////////////////////// */
        /* //////////////////////////////// */

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

    <div class="container-fluid mb-3" style="padding:10px 10px 0px 10px;">

        <!-- ROW 1 : Title + Back -->
        <div class="gp-header row align-items-center mb-2">

            <!-- Left : Title -->
            <div class="col-md-3">
                <div class="gp-title">
                    <h5 class="mb-0 fw-semibold">Add Inward Gatepass</h5>
                    <small class="text-muted">Create & manage inward stock entries</small>
                </div>
            </div>

            {{-- <div class="row"> --}}
            <div class="col-7">
                <div class="gp-actions-center text-center">

                    <a href="javascript:void(0)" class="gp-action-btn" data-bs-toggle="modal" data-bs-target="#vendorModal">
                        <i class="fa fa-user-plus"></i>
                        <span>Vendor</span>
                    </a>


                    <a href="#" class="gp-action-btn">
                        <i class="fa fa-box"></i>
                        <span>Item</span>
                    </a>

                    <a href="#" class="gp-action-btn danger" onclick="return confirm('Delete this gatepass?')">
                        <i class="fa fa-trash"></i>
                        <span>Delete</span>
                    </a>

                </div>
            </div>
            {{-- </div> --}}

            <!-- Right : Back Button -->
            <div class="col-md-2 text-end">
                <a href="{{ route('InwardGatepass.home') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>

        </div>

        <!-- ROW 2 : Actions -->


    </div>


    {{-- {{ route('InwardGatepass.home') }} --}}
    <div class="gp-card">
        <div class="gp-head">
            <h6>Inwards Gate Pass</h6>
            <div class="d-flex gap-2">
                <button form="gatepassForm" class="btn btn-primary btn-slim">Save</button>
            </div>
        </div>

        <div class="gp-body">
            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-2">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success py-2 px-3 mb-2">{{ session('success') }}</div>
            @endif

            <form action="{{ route('store.InwardGatepass') }}" method="POST" id="gatepassForm">
                @csrf

                {{-- Top fields --}}
                {{-- Top fields in 2 columns --}}
                <div class="row mb-2">

                    <!-- LEFT : Bill / Gatepass Info -->
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <h6 class="mb-2 text-muted">Bill / Gatepass Info</h6>

                            <div class="gp-row gp-2 mb-2">
                                <div>
                                    <label>Date</label>
                                    <input type="date" name="gatepass_date" class="form-control"
                                        value="{{ old('gatepass_date', date('Y-m-d')) }}">
                                </div>

                                <div>
                                    <label>Branch</label>
                                    <select name="branch_id" class="form-select select2">
                                        <option value="">Select One</option>
                                        @foreach ($branches as $item)
                                            <option value="{{ $item->id }}"
                                                {{ old('branch_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label>Warehouse</label>
                                    <select name="warehouse_id" class="form-select select2">
                                        <option value="">Select One</option>
                                        @foreach ($warehouses as $item)
                                            <option value="{{ $item->id }}"
                                                {{ old('warehouse_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->warehouse_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label>Bilty No</label>
                                    <input type="text" name="bilty_no" class="form-control"
                                        value="{{ old('bilty_no') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT : Vendor Info -->
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <h6 class="mb-2 text-muted">Vendor / Transport Info</h6>

                            <div class="gp-row gp-2 mb-2">
                                <div>
                                    <label>Vendor</label>
                                    
                                    <select name="vendor_id" id="vendor_id" class="form-select select2">
                                        <option value="">Select One</option>
                                        @foreach ($vendors as $item)
                                            <option value="{{ $item->id }}"
                                                {{ old('vendor_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div style="grid-column:span 2">
                                    <label>Transport Name</label>
                                    <input type="text" name="transport_name" class="form-control"
                                        value="{{ old('transport_name') }}">
                                </div>

                                <div style="grid-column:span 2">
                                    <label>Note</label>
                                    <input type="text" name="note" class="form-control" value="{{ old('note') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                {{-- Items table --}}
                <div class="table-responsive compact mb-2">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr class="text-center">
                                <th style="min-width:280px;">Product</th>
                                <th style="min-width:120px;">Item Code</th>
                                <th style="min-width:120px;">Brand</th>
                                <th style="min-width:100px;">Unit</th>
                                <th style="min-width:90px;">Qty</th>
                                <th style="width:80px">Action</th>
                            </tr>
                        </thead>
                        <tbody id="gatepassItems">
                            <tr>
                                <td class="searchWrap">
                                    <input type="hidden" name="product_id[]" class="product_id">
                                    <input type="text" class="form-control productSearch"
                                        placeholder="Search product by name/code" autocomplete="off">
                                    <div class="searchResults"></div>
                                </td>
                                <td><input type="text" name="item_code[]" class="form-control" readonly></td>
                                <td><input type="text" name="brand[]" class="form-control" readonly></td>
                                <td><input type="text" name="unit[]" class="form-control" readonly></td>
                                <td><input type="number" name="qty[]" class="form-control quantity text-end"
                                        min="1" value="1"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-slim remove-row">X</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="gp-foot">
                    <button type="button" id="addRowBtn" class="btn btn-outline-primary btn-slim">Add Row</button>
                    <button type="submit" class="btn btn-primary btn-slim">Submit Gatepass</button>
                </div>
            </form>
            <!-- Modal for Add/Edit Vendor -->
<div class="modal fade" id="vendorModal">
    <div class="modal-dialog">
        <form action="{{ url('vendor/store') }}" method="POST">@csrf
            <input type="hidden" id="vendor_id" name="id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Vendor</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <input class="form-control" name="name" id="vname" placeholder="Name" required>
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="opening_balance" id="opening_balance" placeholder="Opening Balance" required>
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="phone" id="vphone" placeholder="Phone">
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control" name="address" id="vaddress" placeholder="Address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

        </div>
    </div>
    </div>
    
@endsection

{{-- libs --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    // Initialize DataTable
    $('.datanew').DataTable();

    // Clear modal fields function
    window.clearVendor = function () {
        $('#vendor_id').val('');
        $('#vname').val('');
        $('#opening_balance').val('').prop('readonly', false);  // Allow editing
        $('#vphone').val('');
        $('#vaddress').val('');
    };

    // Edit Vendor functionality
    $('.btn-edit-vendor').click(function () {
        var row = $(this).closest('tr');
        var id = $(this).data('id');
        var name = row.find('td:eq(1)').text().trim();
        var phone = row.find('td:eq(2)').text().trim();
        var balance = row.find('td:eq(3)').text().trim();
        var address = row.find('td:eq(4)').text().trim();

        // Populate modal with vendor data
        $('#vendor_id').val(id);
        $('#vname').val(name);
        $('#vphone').val(phone);
        $('#opening_balance').val(balance).prop('readonly', true);  // Prevent editing opening balance
        $('#vaddress').val(address);

        var modal = new bootstrap.Modal(document.getElementById('vendorModal'));
        modal.show();  // Show the modal
    });
});
</script>



<script>
    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select One',
            allowClear: true
        });

        function escapeHtml(t) {
            return String(t || '').replace(/[&<>"'`=\/]/g, s => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#47;',
                '`': '&#96;',
                '=': '&#61;'
            } [s]))
        }

        function appendBlankRow() {
            $('#gatepassItems').append(`
      <tr>
        <td class="searchWrap">
          <input type="hidden" name="product_id[]" class="product_id">
          <input type="text" class="form-control productSearch" placeholder="Search product by name/code" autocomplete="off">
          <div class="searchResults"></div>
        </td>
        <td><input type="text" name="item_code[]" class="form-control" readonly></td>
        <td><input type="text" name="brand[]" class="form-control" readonly></td>
        <td><input type="text" name="unit[]" class="form-control" readonly></td>
        <td><input type="number" name="qty[]" class="form-control quantity text-end" min="1" value="1"></td>
        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-slim remove-row">X</button></td>
      </tr>`);
        }

        $('#addRowBtn').on('click', appendBlankRow);

        // live search
        $(document).on('keyup', '.productSearch', function() {
            const $inp = $(this),
                q = $inp.val().trim(),
                $wrap = $inp.closest('.searchWrap'),
                $box = $wrap.find('.searchResults');
            if (!q) {
                $box.hide().empty();
                return;
            }
            $.get("{{ route('search-products') }}", {
                q
            }, function(data) {
                let html = '';
                (data || []).forEach(p => {
                    const brand = p.brand && p.brand.name ? p.brand.name : '';
                    html += `
          <div class="result"
               data-id="${p.id||''}"
               data-name="${escapeHtml(p.item_name||'')}"
               data-code="${escapeHtml(p.item_code||'')}"
               data-brand="${escapeHtml(brand)}"
               data-unit="${escapeHtml(p.unit_id||'')}">
            <span>${escapeHtml(p.item_name||'')} <small>(${escapeHtml(p.item_code||'')})</small></span>
            <small>${escapeHtml(brand)}</small>
          </div>`;
                });
                $box.html(html).show();
            });
        });

        // select from dropdown
        $(document).on('click', '.searchResults .result', function() {
            const $r = $(this),
                $tr = $r.closest('tr');
            $tr.find('.product_id').val($r.data('id'));
            $tr.find('.productSearch').val($r.data('name'));
            $tr.find('input[name="item_code[]"]').val($r.data('code'));
            $tr.find('input[name="brand[]"]').val($r.data('brand'));
            $tr.find('input[name="unit[]"]').val($r.data('unit'));
            $r.parent().hide().empty();

            if ($('#gatepassItems tr:last .product_id').val()) {
                appendBlankRow();
                $('#gatepassItems tr:last .productSearch').focus();
            }
        });

        // click outside hides dropdown
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.searchWrap').length) {
                $('.searchResults').hide().empty();
            }
        });

        // remove row
        $(document).on('click', '.remove-row', function() {
            if ($('#gatepassItems tr').length > 1) $(this).closest('tr').remove();
        });

        // submit guard
        $('#gatepassForm').on('submit', function(e) {
            $('#gatepassItems tr').each(function() {
                if (!$(this).find('.product_id').val()) $(this).remove();
            });
            if (!$('input.product_id').filter(function() {
                    return $(this).val();
                }).length) {
                e.preventDefault();
                Swal.fire('Error', 'Please add at least one product for the gatepass', 'error');
            }
        });

        // prevent accidental submit on Enter in inputs
        $('#gatepassForm').on('keypress', function(e) {
            if (e.key === 'Enter' && e.target.type !== 'textarea') {
                e.preventDefault();
            }
        });
    });
</script>
