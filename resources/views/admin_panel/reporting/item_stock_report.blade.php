@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4 class="fw-bold text-dark">Item Stock Report</h4>
                    <p class="text-muted">Real-time inventory levels, valuation, and transaction summary.</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <form id="stockFilterForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" id="category_id" class="form-select select2">
                                <option value="all">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Product</label>
                            <select name="product_id" id="product_id" class="form-select select2">
                                <option value="all">All Products</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->item_code }} - {{ $prod->item_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" id="btnExportCsv" class="btn btn-outline-success py-2">
                                <i class="fas fa-file-csv me-1"></i> Export CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Table Section -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div id="loader" class="text-center p-5 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Calculating stock data...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="stockTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Item Details</th>
                                    <th class="text-center">Initial</th>
                                    <th class="text-center">Purchased</th>
                                    <th class="text-center">Sold</th>
                                    <th class="text-center text-danger">Returned</th>
                                    <th class="text-center bg-light-success">Balance (Pcs)</th>
                                    <th class="text-center">Cartons / Loose</th>
                                    <th class="text-end">Avg Cost</th>
                                    <th class="pe-3 text-end">Stock Value</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody">
                                <!-- Filled by AJAX -->
                            </tbody>
                            <tfoot class="bg-light">
                                <tr class="fw-bold">
                                    <td colspan="8" class="ps-3 text-end">Grand Total Stock Value:</td>
                                    <td class="pe-3 text-end text-primary" id="grandStockValue">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-success {
        background-color: #f0fff4 !important;
    }
    .table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        white-space: nowrap;
    }
    .table td {
        font-size: 0.88rem;
    }
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
    }
</style>
@endsection

@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    function fetchReport() {
        var productId = $('#product_id').val();
        var categoryId = $('#category_id').val();
        $('#loader').removeClass('d-none');
        $('#reportBody').html('');

        $.ajax({
            url: "{{ route('report.item_stock.fetch') }}",
            type: "POST",
            data: { 
                _token: "{{ csrf_token() }}", 
                product_id: productId,
                category_id: categoryId
            },
            success: function(response) {
                $('#loader').addClass('d-none');
                if (response.data && response.data.length) {
                    let html = "";
                    response.data.forEach(function(r) {
                        html += `
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark">${r.item_name}</div>
                                <div class="text-muted small">${r.item_code}</div>
                            </td>
                            <td class="text-center">${parseFloat(r.initial_stock).toFixed(0)}</td>
                            <td class="text-center">
                                ${parseFloat(r.purchased).toFixed(0)}<br>
                                <small class="text-muted">Rs. ${parseFloat(r.purchase_amount).toLocaleString()}</small>
                            </td>
                            <td class="text-center">
                                ${parseFloat(r.sold).toFixed(0)}<br>
                                <small class="text-muted">Rs. ${parseFloat(r.sale_amount).toLocaleString()}</small>
                            </td>
                            <td class="text-center text-danger">${parseFloat(r.returned_qty).toFixed(0)}</td>
                            <td class="text-center fw-bold bg-light-success">${parseFloat(r.balance).toFixed(0)}</td>
                            <td class="text-center text-muted">
                                ${r.cartons !== '-' ? `Ctn: ${r.cartons} | L: ${r.loose}` : `${r.loose}`}
                            </td>
                            <td class="text-end">${parseFloat(r.average_price).toFixed(2)}</td>
                            <td class="pe-3 text-end fw-bold text-dark">${parseFloat(r.stock_value).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        </tr>`;
                    });
                    $('#reportBody').html(html);
                    $('#grandStockValue').text(parseFloat(response.grand_total).toLocaleString(undefined, {minimumFractionDigits: 2}));
                } else {
                    $('#reportBody').html('<tr><td colspan="9" class="text-center p-5">No products found.</td></tr>');
                    $('#grandStockValue').text('0.00');
                }
            },
            error: function() { $('#loader').addClass('d-none'); alert('Error fetching report.'); }
        });
    }

    $('#btnSearch').on('click', fetchReport);
    
    $('#btnExportCsv').on('click', function() {
        var productId = $('#product_id').val();
        var categoryId = $('#category_id').val();
        $.ajax({
            url: "{{ route('report.item_stock.fetch') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", product_id: productId, category_id: categoryId },
            success: function(response) {
                if (!response.data.length) { alert('No data to export'); return; }
                var csv = 'Item Code,Item Name,Initial,Purchased,Sold,Returned,Balance,Avg Cost,Value\n';
                response.data.forEach(function(r){
                    csv += `"${r.item_code}","${r.item_name}",${r.initial_stock},${r.purchased},${r.sold},${r.returned_qty},${r.balance},${r.average_price},${r.stock_value}\n`;
                });
                var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = `Stock_Report_${new Date().toISOString().split('T')[0]}.csv`;
                link.click();
            }
        });
    });

    fetchReport();
});
</script>
@endsection
