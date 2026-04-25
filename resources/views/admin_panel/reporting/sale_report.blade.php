@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="page-header row mb-3">
                <div class="page-title col-lg-6">
                    <h4>Sale Report</h4>
                    <h6>View Sales by date range with details</h6>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="SaleFilterForm" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btnSearch" class="btn btn-primary w-100">Search</button>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" id="btnExportCsv" class="btn btn-danger">Export CSV</button>
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
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered" id="saleReport">
                                <thead class="bg-gray">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Reference</th>
                                        <th>Products</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                        <th>Net</th>
                                        <th>Returns</th>
                                    </tr>
                                </thead>
                                <tbody id="saleBody"></tbody>
                            </table>
                        </div>

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
    $(document).on('click', '#btnSearch', function() {
        let start = $('#start_date').val();
        let end = $('#end_date').val();

        $("#loader").show();
        $.ajax({
            url: "{{ route('report.sale.fetch') }}",
            type: "GET",
            data: {
                start_date: start,
                end_date: end
            },
            success: function(res) {
                $("#loader").hide();
                let html = "";
                let grandQty = 0,
                    grandTotal = 0,
                    grandNet = 0,
                    grandReturn = 0;

                res.forEach((s, i) => {
                    let products = s.product.split(',').join('<br>');
                    let qtyArr = s.qty.split(',');
                    let price = s.per_price.split(',').join('<br>');
                    let total = s.per_total.split(',').join('<br>');

                    // qty total per row
                    let rowQty = qtyArr.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    grandQty += rowQty;

                    // calculate totals
                    let rowTotal = s.per_total.split(',').reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    grandTotal += parseFloat(rowTotal);
                    grandNet += parseFloat(s.total_net);

                    // returns
                    let returnHtml = "";
                    let returnTotal = 0;
                    if (s.returns && s.returns.length > 0) {
                        s.returns.forEach(r => {
                            returnHtml += `${r.product} (${r.qty}) - ${r.per_total}<br>`;
                            returnTotal += parseFloat(r.per_total);
                        });
                    }
                    grandReturn += returnTotal;

                    html += `<tr>
                    <td>${i+1}</td>
                    <td>${s.created_at.split(" ")[0]}</td>
                    <td>INVSLE-${s.id}</td>
                    <td>${s.customer_name ?? '-'}</td>
                    <td>${s.reference}</td>
                    <td>${products}</td>
                    <td>${qtyArr.join('<br>')}</td>
                    <td>${price}</td>
                    <td>${total}</td>
                    <td>${parseFloat(s.total_net).toFixed(2)}</td>
                    <td>${returnHtml || '-'}</td>
                </tr>`;
                });

                // Grand total row
                html += `<tr class="fw-bold">
                <td colspan="6" class="text-end">Grand Total:</td>
                <td>${grandQty.toFixed(2)}</td>
                <td>-</td>
                <td>${grandTotal.toFixed(2)}</td>
                <td>${grandNet.toFixed(2)}</td>
                <td>${grandReturn.toFixed(2)}</td>
            </tr>`;

                $('#saleBody').html(html);
            }
        });
    });


    // Ensure DOM is loaded
    $(document).ready(function() {
        // CSV export
        $(document).on('click', '#btnExportCsv', function() {
            alert('ok'); // test ho jana chahiye

            let csv = [];
            $("#saleReport tr").each(function() {
                let row = [];
                $(this).find('th,td').each(function() {
                    let cellHtml = $(this).html();

                    // <br> ko "|" ya comma se replace kardo
                    let cellText = cellHtml
                        .replace(/<br\s*\/?>/gi, " | ")
                        .replace(/&nbsp;/gi, " ")
                        .replace(/<[^>]*>/g, "")
                        .trim();

                    row.push('"' + cellText.replace(/"/g, '""') + '"');
                });
                csv.push(row.join(","));
            });

            let csvString = csv.join("\n");
            let blob = new Blob([csvString], {
                type: 'text/csv;charset=utf-8;'
            });

            let link = document.createElement("a");
            if (link.download !== undefined) {
                let url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "sale_report.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    });
</script>