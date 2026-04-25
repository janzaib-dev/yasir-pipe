@extends('admin_panel.layout.app')

@section('content')
<style>
.searchResults {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    max-height: 200px;
    background: #fff;
    border: 1px solid #ccc;
    z-index: 999999 !important;
}
.table-responsive,
.table,
.table-bordered,
#gatepassItems {
    overflow: visible !important;
    position: relative !important;
}
.remove-row {
    min-height: 30px;
    min-width: 30px;
    padding: 2px 6px;
    font-size: 14px;
}
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="page-title">Edit Inward Gatepass</h5>
                    <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger">Back</a>
                </div>

                <div class="col-lg-12 col-md-12 mb-30">
                    <div class="card">
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            <form action="{{ route('InwardGatepass.update',$gatepass->id) }}" method="POST" id="gatepassForm">
                                @csrf
                                @method('PUT')

                                <!-- Top fields -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label>Date</label>
                                        <input type="date" name="gatepass_date" class="form-control"
                                            value="{{ old('gatepass_date',$gatepass->gatepass_date) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Branch</label>
                                        <select name="branch_id" class="form-control select2">
                                            @foreach ($branches as $item)
                                                <option value="{{ $item->id }}" {{ $gatepass->branch_id==$item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Warehouse</label>
                                        <select name="warehouse_id" class="form-control select2">
                                            @foreach ($warehouses as $item)
                                                <option value="{{ $item->id }}" {{ $gatepass->warehouse_id==$item->id ? 'selected' : '' }}>
                                                    {{ $item->warehouse_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Vendor</label>
                                        <select name="vendor_id" class="form-control select2">
                                            @foreach ($vendors as $item)
                                                <option value="{{ $item->id }}" {{ $gatepass->vendor_id==$item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Transport Name</label>
                                        <input type="text" name="transport_name" class="form-control" value="{{ old('transport_name',$gatepass->transport_name) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Bilty No</label>
                                        <input type="text" name="bilty_no" class="form-control" value="{{ old('bilty_no',$gatepass->bilty_no) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Note</label>
                                        <input type="text" name="note" class="form-control" value="{{ old('note',$gatepass->note) }}">
                                    </div>
                                </div>

                                <!-- Product Table -->
                                <div style="max-height: 400px; position: relative; overflow-x: visible !important;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Product</th>
                                                <th>Item Code</th>
                                                <th>Brand</th>
                                                <th>Unit</th>
                                                <th>Qty</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gatepassItems">
                                            @foreach($gatepass->items as $item)
                                            <tr>
                                                <td style="position: relative;">
                                                    <input type="hidden" name="product_id[]" class="product_id" value="{{ $item->product_id }}">
                                                    <input type="text" class="form-control productSearch" value="{{ $item->product->item_name }}" autocomplete="off">
                                                    <ul class="searchResults list-group"></ul>
                                                </td>
                                                <td><input type="text" name="item_code[]" class="form-control" value="{{ $item->product->item_code }}" readonly></td>
                                                <td><input type="text" name="brand[]" class="form-control" value="{{ $item->product->brand->name ?? '' }}" readonly></td>
                                                <td><input type="text" name="unit[]" class="form-control" value="{{ $item->product->unit_id ?? '' }}" readonly></td>
                                                <td><input type="number" name="qty[]" class="form-control quantity" min="1" value="{{ $item->qty }}"></td>
                                                <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <button type="submit" class="btn btn-success w-100 mt-3">Update Gatepass</button>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

{{-- Scripts --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){

    $('.select2').select2({ width: '100%', placeholder: 'Select One', allowClear: true });

    function appendBlankRow(){
        const row = `
        <tr>
            <td style="position: relative;">
                <input type="hidden" name="product_id[]" class="product_id">
                <input type="text" class="form-control productSearch" placeholder="Enter product name..." autocomplete="off">
                <ul class="searchResults list-group"></ul>
            </td>
            <td><input type="text" name="item_code[]" class="form-control" readonly></td>
            <td><input type="text" name="brand[]" class="form-control" readonly></td>
            <td><input type="text" name="unit[]" class="form-control" readonly></td>
            <td><input type="number" name="qty[]" class="form-control quantity" min="1" value="1"></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
        $('#gatepassItems').append(row);
    }

    // Product search
    $(document).on('keyup', '.productSearch', function(){
        const $input = $(this);
        const q = $input.val().trim();
        const $row = $input.closest('tr');
        const $box = $row.find('.searchResults');

        if (q.length === 0) { $box.empty(); return; }

        $.ajax({
            url: "{{ route('search-products') }}",
            type: "GET",
            data: { q },
            success: function(data){
                let html = '';
                (data || []).forEach(p => {
                    const brand = p.brand && p.brand.name ? p.brand.name : '';
                    const unit  = p.unit_id ?? '';
                    const code  = p.item_code ?? '';
                    const name  = p.item_name ?? '';
                    const id    = p.id ?? '';
                    html += `
                    <li class="list-group-item search-result-item"
                        style="cursor:pointer;"
                        data-product-id="${id}"
                        data-product-name="${escapeHtml(name)}"
                        data-code="${escapeHtml(code)}"
                        data-brand="${escapeHtml(brand)}"
                        data-unit="${escapeHtml(unit)}">
                        ${escapeHtml(name)} (${escapeHtml(code)}) - ${escapeHtml(brand)}
                    </li>`;
                });
                $box.html(html).show();
            },
            error: function(){ $box.empty(); }
        });
    });

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"'`=\/]/g, function (s) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#47;','`':'&#96;','=':'&#61;'}[s];
        });
    }

    // Select product from search
    $(document).on('click', '.search-result-item', function(){
        const $li = $(this);
        const $row = $li.closest('tr');

        $row.find('.product_id').val($li.data('product-id'));
        $row.find('.productSearch').val($li.data('product-name'));
        $row.find('input[name="item_code[]"]').val($li.data('code'));
        $row.find('input[name="brand[]"]').val($li.data('brand'));
        $row.find('input[name="unit[]"]').val($li.data('unit'));
        $row.find('.searchResults').empty();

        if($('#gatepassItems tr:last .product_id').val() !== ''){
            appendBlankRow();
            $('#gatepassItems tr:last .productSearch').focus();
        }
    });

    $(document).on('click', '.remove-row', function(){
        if($('#gatepassItems tr').length > 1){
            $(this).closest('tr').remove();
        }
    });

    $('#gatepassForm').on('submit', function(e){
        $('#gatepassItems tr').each(function(){
            const pid = $(this).find('.product_id').val();
            if (!pid) $(this).remove();
        });
        if ($('input[name="product_id[]"]').filter(function(){ return $(this).val() != ''; }).length === 0) {
            e.preventDefault();
            Swal.fire('Error','Please add at least one product for the gatepass','error');
            return false;
        }
    });

    $('#gatepassForm').on('keypress', function(e) {
        if (e.key === 'Enter' && e.target.type !== 'textarea') {
            e.preventDefault();
            return false;
        }
    });

});
</script>
