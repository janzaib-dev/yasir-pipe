@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4 class="fw-bold">Purchase Detailed Report</h4>
                    <p class="text-muted">Detailed view of inventory procurement and vendor transactions.</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <form id="purchaseFilterForm" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-t') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select select2">
                                <option value="all">All Vendors</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Product</label>
                            <select name="product_id" id="product_id" class="form-select select2">
                                <option value="all">All Products</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->item_name }} ({{ $p->item_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnExportCsv" class="btn btn-outline-success w-100 py-2">
                                <i class="fas fa-file-csv me-1"></i> Export
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
                        <p class="mt-2 text-muted">Compiling report data...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="purchaseReportTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Invoice No</th>
                                    <th>Vendor</th>
                                    <th>Product Details</th>
                                    <th class="text-center">Warehouse</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-end">Line Total</th>
                                    <th class="text-center">Returns</th>
                                    <th class="pe-3 text-end">Net Amount</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody">
                                <tr>
                                    <td colspan="10" class="text-center p-5 text-muted">
                                        Adjust filters and click search to view purchase data.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light d-none" id="reportFooter">
                                <tr class="fw-bold border-top-2">
                                    <td colspan="5" class="text-end ps-3">Grand Totals:</td>
                                    <td class="text-center" id="footerTotalQty">0</td>
                                    <td></td>
                                    <td class="text-end" id="footerTotalLine">0.00</td>
                                    <td class="text-center text-danger" id="footerTotalReturns">0.00</td>
                                    <td class="pe-3 text-end" id="footerTotalNet">0.00</td>
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
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
    }
    .table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        white-space: nowrap;
    }
    .table td {
        font-size: 0.85rem;
    }
    .border-top-2 {
        border-top: 2px solid #dee2e6 !important;
    }
</style>
@endsection

@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        $('#btnSearch').on('click', function() {
            fetchReport();
        });

        function fetchReport() {
            const start_date = $('#start_date').val();
            const end_date = $('#end_date').val();
            const vendor_id = $('#vendor_id').val();
            const product_id = $('#product_id').val();

            $('#loader').removeClass('d-none');
            $('#reportBody').html('');
            $('#reportFooter').addClass('d-none');

            $.ajax({
                url: "{{ route('report.purchase.fetch') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    start_date: start_date,
                    end_date: end_date,
                    vendor_id: vendor_id,
                    product_id: product_id
                },
                success: function(response) {
                    $('#loader').addClass('d-none');

                    if (response.success === false) {
                        alert('Error: ' + response.message);
                        return;
                    }

                    const rows = response.data;
                    
                    if (!rows || rows.length === 0) {
                        $('#reportBody').html('<tr><td colspan="10" class="text-center p-5">No purchase records found.</td></tr>');
                        return;
                    }

                    let html = "";
                    let totalQty = 0;
                    let totalLine = 0;
                    let totalNet = 0;
                    let totalReturns = 0;
                    let countedPurchases = new Set();

                    rows.forEach(function(r) {
                        totalQty += parseFloat(r.qty) || 0;
                        totalLine += parseFloat(r.line_total) || 0;
                        
                        if (!countedPurchases.has(r.purchase_id)) {
                            totalNet += parseFloat(r.purchase_net_amount) || 0;
                            countedPurchases.add(r.purchase_id);
                        }

                        let returnHtml = '-';
                        if (r.returns && r.returns.length > 0) {
                            let rQty = 0;
                            let rAmt = 0;
                            r.returns.forEach(ret => {
                                rQty += parseFloat(ret.qty);
                                rAmt += parseFloat(ret.line_total);
                            });
                            returnHtml = `<span class="text-danger fw-bold">${rQty}</span><br><small class="text-muted">Rs. ${rAmt.toFixed(2)}</small>`;
                            totalReturns += rAmt;
                        }

                        html += `
                        <tr>
                            <td class="ps-3">${r.purchase_date}</td>
                            <td>
                                <span class="fw-bold text-primary">${r.invoice_no}</span>
                                ${r.status_purchase ? `<br><small class="badge bg-light text-dark border-0 p-0">${r.status_purchase}</small>` : ''}
                            </td>
                            <td><span class="fw-medium">${r.vendor_name}</span></td>
                            <td>
                                <div class="fw-medium">${r.package_name || r.item_name}</div>
                                <div class="text-muted small">${r.item_code}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border font-weight-normal">${r.warehouse_name || '-'}</span>
                            </td>
                            <td class="text-center fw-bold">
                                ${parseFloat(r.qty).toFixed(2)} <small class="text-muted">${r.unit}</small>
                            </td>
                            <td class="text-end">${parseFloat(r.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-end fw-bold">${parseFloat(r.line_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-center">${returnHtml}</td>
                            <td class="pe-3 text-end fw-bold">
                                ${parseFloat(r.purchase_net_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}
                                <div class="small text-muted fw-normal">Paid: ${parseFloat(r.purchase_paid_amount).toFixed(0)}</div>
                            </td>
                        </tr>`;
                    });

                    $('#reportBody').html(html);
                    $('#footerTotalQty').text(totalQty.toFixed(0) + ' Pcs');
                    $('#footerTotalLine').text(totalLine.toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#footerTotalReturns').text(totalReturns.toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#footerTotalNet').text(totalNet.toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#reportFooter').removeClass('d-none');
                },
                error: function() {
                    $('#loader').addClass('d-none');
                    alert('Failed to load purchase report.');
                }
            });
        }

        $('#btnExportCsv').on('click', function() {
            let csv = [];
            $("#purchaseReportTable tr").each(function() {
                let row = [];
                $(this).find('th,td').each(function() {
                    row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
                });
                csv.push(row.join(","));
            });
            const blob = new Blob([csv.join("\n")], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `Purchase_Report_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
        });
    });
</script>
@endsection