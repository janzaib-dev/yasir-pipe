@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4 class="fw-bold">Sale Detailed Report</h4>
                    <p class="text-muted">Analyze item-wise sales performance and transaction details.</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <form id="SaleFilterForm" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-t') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select select2">
                                <option value="all">All Customers</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
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
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="booked">Booked</option>
                                <option value="posted">Posted</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex gap-2">
                                <button type="button" id="btnSearch" class="btn btn-primary w-100 py-2">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                                <button type="button" id="btnExportCsv" class="btn btn-outline-success">
                                    <i class="fas fa-file-csv"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Table Section -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div id="loader" class="text-center p-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Generating your report...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="saleReportTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Invoice / Ref</th>
                                    <th>Customer / Officer</th>
                                    <th>Product Details</th>
                                    <th class="text-center">Warehouse</th>
                                    <th class="text-center">Qty (UOM)</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Line Total</th>
                                    <th class="text-center">Returns</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-3 text-end">Invoice Net</th>
                                </tr>
                            </thead>
                            <tbody id="saleBody">
                                <tr>
                                    <td colspan="11" class="text-center p-5 text-muted">
                                        Use the filters above to generate a report.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light d-none" id="reportFooter">
                                <tr class="fw-bold">
                                    <td colspan="5" class="text-end ps-3">Totals:</td>
                                    <td class="text-center" id="footerTotalQty">0</td>
                                    <td></td>
                                    <td class="text-end" id="footerTotalLine">0.00</td>
                                    <td class="text-center text-danger" id="footerTotalReturns">0.00</td>
                                    <td></td>
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
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .badge-status {
        font-weight: 500;
        padding: 0.4em 0.8em;
    }
    .table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        border-bottom: 2px solid #f8f9fa;
    }
    .table td {
        font-size: 0.9rem;
    }
    .product-code {
        font-size: 0.75rem;
        color: #adb5bd;
    }
</style>
@endsection

@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'default',
            width: '100%'
        });

        $('#btnSearch').on('click', function() {
            fetchReport();
        });

        function fetchReport() {
            const formData = {
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                customer_id: $('#customer_id').val(),
                product_id: $('#product_id').val(),
                status: $('#status').val()
            };

            $('#loader').removeClass('d-none');
            $('#saleBody').html('');
            $('#reportFooter').addClass('d-none');

            $.ajax({
                url: "{{ route('report.sale.fetch') }}",
                type: "GET",
                data: formData,
                success: function(res) {
                    $('#loader').addClass('d-none');
                    
                    if (res.success === false) {
                        alert('Error: ' + res.message);
                        return;
                    }

                    let data = res.data;
                    if (!data || data.length === 0) {
                        $('#saleBody').html('<tr><td colspan="11" class="text-center p-5">No records found for the selected period.</td></tr>');
                        return;
                    }

                    let html = "";
                    let totalQty = 0;
                    let totalLine = 0;
                    let totalNet = 0;
                    let totalReturns = 0;
                    
                    // Keep track of shown invoice nets to avoid double counting in footer
                    let countedInvoices = new Set();

                    data.forEach((row) => {
                        totalQty += parseFloat(row.total_pieces) || 0;
                        totalLine += parseFloat(row.line_total) || 0;
                        
                        if (!countedInvoices.has(row.sale_id)) {
                            totalNet += parseFloat(row.invoice_net) || 0;
                            countedInvoices.add(row.sale_id);
                        }

                        let statusBadge = '';
                        switch(row.sale_status) {
                            case 'posted': statusBadge = '<span class="badge bg-success badge-status">Posted</span>'; break;
                            case 'draft': statusBadge = '<span class="badge bg-secondary badge-status">Draft</span>'; break;
                            case 'booked': statusBadge = '<span class="badge bg-info badge-status">Booked</span>'; break;
                            case 'returned': statusBadge = '<span class="badge bg-danger badge-status">Returned</span>'; break;
                            default: statusBadge = `<span class="badge bg-dark badge-status">${row.sale_status}</span>`;
                        }

                        let returnHtml = '-';
                        if (row.returns && row.returns.length > 0) {
                            let rQty = 0;
                            let rAmt = 0;
                            row.returns.forEach(ret => {
                                rQty += parseFloat(ret.qty);
                                rAmt += parseFloat(ret.line_total);
                            });
                            returnHtml = `<span class="text-danger fw-bold" title="Total Pieces Returned">${rQty}</span><br><small class="text-muted">Rs. ${rAmt.toFixed(2)}</small>`;
                            totalReturns += rAmt;
                        }

                        html += `
                        <tr>
                            <td class="ps-3">
                                <span class="fw-medium">${row.formatted_date.split(' ')[0]}</span><br>
                                <small class="text-muted">${row.formatted_date.split(' ')[1]}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-primary">#${row.invoice_no}</span><br>
                                <small class="text-muted">${row.reference || '-'}</small>
                            </td>
                            <td>
                                <span class="fw-medium">${row.customer_name || 'Walk-in'}</span><br>
                                <small class="text-secondary"><i class="fas fa-user-tie me-1"></i>${row.officer_name || '-'}</small>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">${row.package_name || row.item_name}</div>
                                <div class="product-code">${row.item_code}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">${row.warehouse_name || '-'}</span>
                            </td>
                            <td class="text-center fw-bold">
                                ${parseFloat(row.qty).toFixed(2)} <small class="text-muted">${row.package_symbol || 'pc'}</small>
                                <div class="small text-muted fw-normal">(${row.total_pieces} Pcs)</div>
                            </td>
                            <td class="text-end">
                                ${parseFloat(row.price).toLocaleString(undefined, {minimumFractionDigits: 2})}
                                ${row.discount_percent > 0 ? `<br><small class="text-success">-${row.discount_percent}%</small>` : ''}
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ${parseFloat(row.line_total).toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </td>
                            <td class="text-center">
                                ${returnHtml}
                            </td>
                            <td class="text-center">
                                ${statusBadge}
                            </td>
                            <td class="pe-3 text-end fw-bold">
                                ${parseFloat(row.invoice_net).toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </td>
                        </tr>`;
                    });

                    $('#saleBody').html(html);
                    $('#footerTotalQty').text(totalQty.toFixed(0) + ' Pcs');
                    $('#footerTotalLine').text(totalLine.toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#footerTotalReturns').text(totalReturns.toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#footerTotalNet').text(totalNet.toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#reportFooter').removeClass('d-none');
                },
                error: function(xhr) {
                    $('#loader').addClass('d-none');
                    let msg = 'Error generating report.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += "\n" + xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        }

        $('#btnExportCsv').on('click', function() {
            let csv = [];
            $("#saleReportTable tr").each(function() {
                let row = [];
                $(this).find('th,td').each(function() {
                    let cellText = $(this).text().replace(/\s+/g, ' ').trim();
                    row.push('"' + cellText.replace(/"/g, '""') + '"');
                });
                csv.push(row.join(","));
            });

            const csvString = csv.join("\n");
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.setAttribute("href", URL.createObjectURL(blob));
            link.setAttribute("download", `Sale_Report_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
@endsection