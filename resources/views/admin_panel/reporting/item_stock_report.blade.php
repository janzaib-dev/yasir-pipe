@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Item Stock Report</h4>
                    <h6>Track initial, purchased, sold and balance per product</h6>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="stockFilterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="category_id" class="form-control">
                                <option value="all">-- All Categories --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <select name="product_id" id="product_id" class="form-control">
                                <option value="all">-- All Products --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->item_code }} - {{ $prod->item_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-danger w-100">Search</button>
                        </div>

                        <div class="col-md-4 text-end">
                            <button type="button" id="btnExportCsv" class="btn btn-outline-secondary">Export CSV</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="loader" style="display:none;text-align:center;margin-bottom:10px;">
                        <div class="spinner-border" role="status"></div>
                    </div>

                    <div class="table-responsive">
                        <table id="stockTable" class="table table-striped table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Initial Stock</th>
                                    <th>Purchased Qty</th>
                                    <th>Purchased Amount</th>
                                    <th>Sold Qty</th>
                                    <th>Returned Qty</th>
                                    <th>Sold Amount</th>
                                    <th>Cartons</th>
                                    <th>Loose Pcs</th>
                                    <th>Current Stock (Pcs)</th>
                                    <th>Avg Price</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody">
                                <!-- Filled by AJAX -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="12" class="text-end">Grand Stock Value:</th>
                                    <th id="grandStockValue">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    var stockTable = $('#stockTable').DataTable({
        paging: true,
        searching: true,
        info: true,
        ordering: true,
        columns: [
            { data: 'index' },
            { data: 'item_code' },
            { data: 'item_name' },
            { data: 'initial_stock' },
            { data: 'purchased' },
            { data: 'purchase_amount' },
            { data: 'sold' },
            { data: 'returned_qty' },
            { data: 'sale_amount' },
            { data: 'cartons' },
            { data: 'loose' },
            { data: 'balance' },
            { data: 'average_price' },
            { data: 'stock_value' }
        ]
    });

    function renderRows(rows, grandTotal) {
        if ($.fn.DataTable.isDataTable('#stockTable')) {
            stockTable.clear().draw();
        }

        rows.forEach(function(r, idx) {
            stockTable.row.add({
                index: idx + 1,
                item_code: r.item_code,
                item_name: r.item_name,
                initial_stock: parseFloat(r.initial_stock).toFixed(2),
                purchased: parseFloat(r.purchased).toFixed(2),
                purchase_amount: parseFloat(r.purchase_amount).toFixed(2),
                sold: parseFloat(r.sold).toFixed(2),
                returned_qty: parseFloat(r.returned_qty).toFixed(2),
                sale_amount: parseFloat(r.sale_amount).toFixed(2),
                cartons: r.cartons,
                loose: parseFloat(r.loose).toFixed(2),
                balance: parseFloat(r.balance).toFixed(2),
                average_price: parseFloat(r.average_price).toFixed(2),
                stock_value: parseFloat(r.stock_value).toFixed(2)
            }).draw(false);
        });

        $('#grandStockValue').text(parseFloat(grandTotal).toFixed(2));
    }

    $('#btnSearch').on('click', function() { fetchReport(); });
    $('#product_id').on('keypress', function(e){ if(e.key==='Enter'){ e.preventDefault(); fetchReport(); } });

    function fetchReport() {
        var productId = $('#product_id').val();
        var categoryId = $('#category_id').val();
        $('#loader').show();
        $.ajax({
            url: "{{ route('report.item_stock.fetch') }}",
            type: "POST",
            data: { 
                _token: "{{ csrf_token() }}", 
                product_id: productId,
                category_id: categoryId
            },
            success: function(response) {
                $('#loader').hide();
                if (response.data && response.data.length) {
                    renderRows(response.data, response.grand_total);
                } else {
                    renderRows([], 0);
                }
            },
            error: function(xhr, status, err) {
                $('#loader').hide();
                alert('Error fetching report. See console.');
                console.error(xhr.responseText || err);
            }
        });
    }

    $('#btnExportCsv').on('click', function() {
        var productId = $('#product_id').val();
        var categoryId = $('#category_id').val();
        $('#loader').show();
        $.ajax({
            url: "{{ route('report.item_stock.fetch') }}",
            type: "POST",
            data: { 
                _token: "{{ csrf_token() }}", 
                product_id: productId,
                category_id: categoryId
            },
            success: function(response) {
                $('#loader').hide();
                if (!response.data || !response.data.length) { alert('No data to export'); return; }

                var csv = 'Item Code,Item Name,Initial Stock,Purchased Qty,Purchased Amount,Sold Qty,Sold Amount,Balance (Pcs),Cartons,Loose Pcs,Avg Price,Stock Value\n';
                response.data.forEach(function(r){
                    csv += `"${r.item_code}","${r.item_name}",${r.initial_stock},${r.purchased},${r.purchase_amount},${r.sold},${r.sale_amount},${r.balance},${r.cartons},${r.loose},${r.average_price},${r.stock_value}\n`;
                });

                var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'item_stock_report.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            },
            error: function() { $('#loader').hide(); alert('Export failed'); }
        });
    });

    // Initial load
    fetchReport();
});
</script>
