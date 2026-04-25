@extends('admin_panel.layout.app')
@section('content')
<style>
    .balance-table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; }
    .balance-table th, .balance-table td { padding: 10px; border: 1px solid #eee; text-align: center; }
    .balance-table th { background-color: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; }
    .balance-table .text-left { text-align: left; }
    .balance-table .text-right { text-align: right; }
    .btn-print, .btn-excel { color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
    .btn-print { background-color: #0d6efd; margin-left: 5px; }
    .btn-excel { background-color: #36b9cc; }
    
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .balance-table th, .balance-table td { border: 1px solid #ccc !important; }
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="card shadow-sm mb-3 no-print">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-muted">Report:</label>
                        <select id="report_type" class="form-control">
                            <option value="BOTH">BOTH</option>
                            <option value="RECEIVABLE">RECEIVABLE</option>
                            <option value="PAYABLE">PAYABLE</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="form-check form-check-inline mt-4">
                            <input class="form-check-input" type="checkbox" id="show_zero">
                            <label class="form-check-label text-muted" for="show_zero">Show Parties have Zero Balance</label>
                        </div>
                    </div>
                </div>
                <div class="row g-3 align-items-end mt-2">
                    <div class="col-md-3">
                        <label class="form-label text-muted">Customer / Vendor:</label>
                        <input type="text" id="party_name" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Mobile:</label>
                        <input type="text" id="mobile" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="btnSearch">Search</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm" style="background:#fff; min-height:500px;">
            <div class="card-body p-4" id="printArea">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <small class="text-muted" id="reportDateLabel">Report generated on : {{ date('d-m-Y') }}</small>
                    </div>
                    <h5 class="fw-bold mb-0 text-center" style="position:absolute; left:50%; transform:translateX(-50%);" id="reportTitle">PARTIES BALANCES</h5>
                    <div class="no-print">
                        <button class="btn-excel" id="btnExcel"><i class="fa fa-file-excel"></i> Excel Download</button>
                        <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
                    </div>
                </div>

                <div id="loader" style="display:none; text-align:center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div id="reportContent">
                    <table class="balance-table" id="reportTable">
                        <thead>
                            <tr>
                                <th style="width:5%;">SR</th>
                                <th style="width:15%;">CODE</th>
                                <th style="width:30%;" class="text-left">TITLE</th>
                                <th style="width:15%;">MOBILE</th>
                                <th style="width:12%;" class="col-receivable text-right">RECEIVABLE</th>
                                <th style="width:12%;" class="col-payable text-right">PAYABLE</th>
                                <th style="width:11%;">NOTES</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRows">
                            <!-- Rows will go here -->
                        </tbody>
                        <tfoot id="tfootTotals">
                            <!-- Totals will go here -->
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        function formatMoney(amount) {
            let val = parseFloat(amount || 0);
            if (val === 0) return ""; 
            return val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function loadReport() {
            let reportType = $("#report_type").val();
            let showZero = $("#show_zero").is(":checked");
            let partyName = $("#party_name").val();
            let mobile = $("#mobile").val();
            
            $("#loader").show();
            $("#reportContent").hide();

            // Adjust Title
            if(reportType === 'RECEIVABLE') $("#reportTitle").text("PARTIES RECEIVABLE");
            else if(reportType === 'PAYABLE') $("#reportTitle").text("PARTIES PAYABLE");
            else $("#reportTitle").text("PARTIES BALANCES");

            $.get("{{ route('report.parties_balance.fetch') }}", {
                report_type: reportType,
                show_zero: showZero,
                party_name: partyName,
                mobile: mobile
            }, function(res) {
                $("#loader").hide();
                
                // Hide columns based on type
                if (reportType === 'RECEIVABLE') {
                    $(".col-payable").hide();
                    $(".col-receivable").show();
                } else if (reportType === 'PAYABLE') {
                    $(".col-receivable").hide();
                    $(".col-payable").show();
                } else {
                    $(".col-receivable").show();
                    $(".col-payable").show();
                }

                let rowsHtml = "";
                res.rows.forEach(function(row) {
                    rowsHtml += `<tr>
                        <td>${row.sr}</td>
                        <td>${row.code}</td>
                        <td class="text-left">${row.title}</td>
                        <td>${row.mobile}</td>
                        ${reportType !== 'PAYABLE' ? `<td class="text-right">${formatMoney(row.receivable)}</td>` : ''}
                        ${reportType !== 'RECEIVABLE' ? `<td class="text-right">${formatMoney(row.payable)}</td>` : ''}
                        <td>${row.notes}</td>
                    </tr>`;
                });

                if (res.rows.length === 0) {
                    let colCount = reportType === 'BOTH' ? 7 : 6;
                    rowsHtml = `<tr><td colspan="${colCount}" class="text-muted py-4">No data matching filters.</td></tr>`;
                }

                $("#tbodyRows").html(rowsHtml);

                // Totals
                if (res.rows.length > 0) {
                    let t = res.totals;
                    let totalsHtml = `
                        <tr style="background:#f8f9fa; font-weight:bold;">
                            <td colspan="4">TOTAL</td>
                            ${reportType !== 'PAYABLE' ? `<td class="text-right">${formatMoney(t.receivable)}</td>` : ''}
                            ${reportType !== 'RECEIVABLE' ? `<td class="text-right">${formatMoney(t.payable)}</td>` : ''}
                            <td></td>
                        </tr>
                    `;
                    $("#tfootTotals").html(totalsHtml);
                } else {
                    $("#tfootTotals").empty();
                }

                $("#reportContent").show();
            });
        }

        function exportToExcel() {
            let table = document.getElementById("reportTable");
            let rows = Array.from(table.rows);
            let csvContent = "data:text/csv;charset=utf-8,";

            rows.forEach(function(row) {
                let rowData = [];
                Array.from(row.cells).forEach(function(cell) {
                    // Check if cell is visible
                    if (window.getComputedStyle(cell).display !== 'none') {
                        let text = cell.innerText.replace(/,/g, ''); // Remove commas from numbers
                        rowData.push('"' + text + '"');
                    }
                });
                csvContent += rowData.join(",") + "\r\n";
            });

            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            let filename = $("#reportTitle").text().toLowerCase().replace(/ /g, '_') + "_" + new Date().toLocaleDateString().replace(/\//g, '-') + ".csv";
            
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        $("#btnSearch").click(function() {
            loadReport();
        });
        
        $("#btnExcel").click(function() {
            exportToExcel();
        });

        // Load initially
        loadReport();
    });
</script>
@endsection
