@extends('admin_panel.layout.app')
@section('content')
<style>
    .recovery-table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; }
    .recovery-table th, .recovery-table td { padding: 12px; border: 1px solid #eee; text-align: right; }
    .recovery-table th { background-color: #f8f9fa; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 0.9rem; }
    .recovery-table .text-left { text-align: left; }
    .recovery-table .text-center { text-align: center; }
    .btn-print { background-color: #0d6efd; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; }
    
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .recovery-table th, .recovery-table td { border: 1px solid #ccc !important; }
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3 align-items-center no-print">
            <div class="col-md-3">
                <label>Start Date:</label>
                <input type="date" id="start_date" class="form-control" value="{{ date('Y-m-01') }}">
            </div>
            <div class="col-md-3">
                <label>End Date:</label>
                <input type="date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2 mt-4">
                <button class="btn btn-primary w-100" id="btnGenerate">Generate</button>
            </div>
        </div>

        <div class="card shadow-sm" style="background:#fff; min-height:600px;">
            <div class="card-body p-4" id="printArea">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold mb-1">RECOVERY REPORTS</h4>
                        <p class="text-muted mb-0" id="reportDateLabel">Report generated on : {{ date('d-m-Y') }}</p>
                    </div>
                    <button class="btn-print no-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
                </div>

                <div id="loader" style="display:none; text-align:center; padding: 50px;">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div id="reportContent">
                    <table class="recovery-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">SR</th>
                                <th style="width: 25%;" class="text-left">PARTY</th>
                                <th style="width: 15%;">OPENING</th>
                                <th style="width: 15%;">SALES</th>
                                <th style="width: 20%;">CASH RECEIVED</th>
                                <th style="width: 20%;">FINAL</th>
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
            if (val === 0) return ""; // empty cells for 0 like the image
            return val.toFixed(2);
        }

        function loadReport() {
            let start = $("#start_date").val();
            let end = $("#end_date").val();
            
            $("#loader").show();
            $("#reportContent").hide();

            $.get("{{ route('report.recovery.fetch') }}", { start_date: start, end_date: end }, function(res) {
                $("#loader").hide();
                
                // Update header date
                let today = new Date();
                let dd = String(today.getDate()).padStart(2, '0');
                let mm = String(today.getMonth() + 1).padStart(2, '0');
                let yyyy = today.getFullYear();
                $("#reportDateLabel").text("Report generated on : " + dd + "-" + mm + "-" + yyyy);

                let rowsHtml = "";
                res.rows.forEach(function(row) {
                    rowsHtml += `
                        <tr>
                            <td class="text-center">${row.sr}</td>
                            <td class="text-left">${row.party}</td>
                            <td>${formatMoney(row.opening)}</td>
                            <td>${formatMoney(row.sales)}</td>
                            <td>${formatMoney(row.received)}</td>
                            <td>${formatMoney(row.final)}</td>
                        </tr>
                    `;
                });

                if (res.rows.length === 0) {
                    rowsHtml = `<tr><td colspan="6" class="text-center text-muted py-4">No data found in this period.</td></tr>`;
                }

                $("#tbodyRows").html(rowsHtml);

                // Totals
                if (res.rows.length > 0) {
                    let t = res.totals;
                    let totalsHtml = `
                        <tr style="background:#f8f9fa; font-weight:bold;">
                            <td colspan="2" class="text-center">TOTAL</td>
                            <td>${formatMoney(t.opening)}</td>
                            <td>${formatMoney(t.sales)}</td>
                            <td>${formatMoney(t.received)}</td>
                            <td>${formatMoney(t.final)}</td>
                        </tr>
                    `;
                    $("#tfootTotals").html(totalsHtml);
                } else {
                    $("#tfootTotals").empty();
                }

                $("#reportContent").show();
            });
        }

        $("#btnGenerate").click(function() {
            loadReport();
        });

        // Load initially
        loadReport();
    });
</script>
@endsection
