@extends('admin_panel.layout.app')

@section('content')
<style>
    .bs-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 40px;
        margin-bottom: 30px;
    }
    .bs-header {
        text-align: center;
        margin-bottom: 40px;
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
    }
    .bs-header h3 {
        font-weight: 700;
        text-transform: uppercase;
        margin: 0;
        color: #000;
    }
    .bs-header p {
        margin: 5px 0 0;
        color: #666;
        font-weight: 500;
    }
    
    .bs-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .bs-table th, .bs-table td {
        padding: 10px 0;
        border-bottom: 1px solid #eeeeee;
        font-size: 0.95rem;
    }
    .section-head {
        font-weight: 800;
        text-transform: uppercase;
        font-size: 1.05rem;
        color: #000;
        background: #f8f9fa;
        padding: 10px !important;
        border-bottom: 2px solid #000 !important;
    }
    .subsection-head {
        font-weight: 700;
        padding-top: 15px !important;
        color: #333;
    }
    .row-title {
        padding-left: 15px !important;
    }
    .amount-cell {
        text-align: right;
        font-weight: 600;
        font-family: 'Courier New', Courier, monospace;
    }
    .total-row {
        font-weight: 700;
        border-top: 1px solid #333 !important;
        background: #fcfcfc;
    }
    .grand-total-row {
        font-weight: 800;
        font-size: 1.1rem;
        background: #333;
        color: #fff;
    }
    .grand-total-row td {
        padding: 15px 10px !important;
    }

    @media print {
        .no-print { display: none !important; }
        .bs-card { border: none; padding: 0; }
        .main-content { padding: 0; }
        .grand-total-row { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="main-content">
    <div class="container">
        <div class="card p-3 mb-4 no-print border-0 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label class="fw-bold small mb-1">Select As-of Date:</label>
                    <input type="date" id="bs_date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-2 mt-4">
                    <button class="btn btn-dark w-100 fw-bold" id="btnGenerate">Generate Report</button>
                </div>
                <div class="col mt-4 text-end">
                    <button class="btn btn-outline-secondary px-4" onclick="window.print()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <div id="loader" class="text-center py-5" style="display:none;">
            <div class="spinner-border text-dark" role="status"></div>
            <p class="mt-2 text-muted">Loading Statement...</p>
        </div>

        <div id="bsContent" class="bs-card">
            <div class="bs-header">
                <h3>Balance Sheet Statement</h3>
                <p id="reportDateBadge">As of {{ date('d M, Y') }}</p>
            </div>

            <div id="dynamicTable">
                <table class="bs-table">
                    <!-- ASSETS -->
                    <thead>
                        <tr>
                            <th colspan="2" class="section-head">Assets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="2" class="subsection-head">Current Assets</td>
                        </tr>
                        <tbody id="cashBankRows">
                            <!-- Items here -->
                        </tbody>
                        <tr>
                            <td class="row-title">Accounts Receivable</td>
                            <td class="amount-cell" id="lblReceivables">0.00</td>
                        </tr>
                        <tr>
                            <td class="row-title">Inventory on Hand</td>
                            <td class="amount-cell" id="lblInventory">0.00</td>
                        </tr>
                        <tr class="total-row">
                            <td class="row-title">Total Current Assets</td>
                            <td class="amount-cell" id="lblCurrentAssetsTotal">0.00</td>
                        </tr>
                        
                        <tr>
                            <td colspan="2" class="subsection-head">Fixed Assets</td>
                        </tr>
                        <tr>
                            <td class="row-title text-muted italic">No Fixed Assets Records Found</td>
                            <td class="amount-cell">0.00</td>
                        </tr>

                        <tr class="grand-total-row">
                            <td>TOTAL ASSETS</td>
                            <td class="amount-cell" id="lblAssetsGrandTotal">0.00</td>
                        </tr>

                        <!-- LIABILITIES -->
                        <tr>
                            <th colspan="2" class="section-head pt-4">Liabilities & Equity</th>
                        </tr>
                        <tr>
                            <td colspan="2" class="subsection-head">Current Liabilities</td>
                        </tr>
                        <tr>
                            <td class="row-title">Accounts Payable</td>
                            <td class="amount-cell" id="lblPayables">0.00</td>
                        </tr>
                        <tr class="total-row">
                            <td class="row-title">Total Current Liabilities</td>
                            <td class="amount-cell" id="lblLiabTotal">0.00</td>
                        </tr>

                        <tr>
                            <td colspan="2" class="subsection-head">Owner's Equity</td>
                        </tr>
                        <tr>
                            <td class="row-title">Owner's Capital</td>
                            <td class="amount-cell" id="lblEquity">0.00</td>
                        </tr>
                        <tr class="total-row">
                            <td class="row-title">Total Equity</td>
                            <td class="amount-cell" id="lblEquityTotal">0.00</td>
                        </tr>

                        <tr class="grand-total-row">
                            <td>TOTAL LIABILITIES & EQUITY</td>
                            <td class="amount-cell" id="lblLiabEquityGrandTotal">0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
$(document).ready(function() {
    function fmt(n) {
        let val = parseFloat(n) || 0;
        return val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function loadData() {
        let date = $("#bs_date").val();
        $("#loader").show();
        $("#bsContent").hide();

        $.get("{{ route('report.balance_sheet.fetch') }}", { date: date }, function(res) {
            $("#loader").hide();
            $("#bsContent").show();
            $("#reportDateBadge").text("As of " + res.date);

            // Cash Bank Rows
            let cbHtml = '';
            res.assets.cash_bank.forEach(a => {
                cbHtml += `<tr>
                    <td class="row-title">${a.name}</td>
                    <td class="amount-cell">${fmt(a.balance)}</td>
                </tr>`;
            });
            $("#cashBankRows").html(cbHtml);

            $("#lblReceivables").text(fmt(res.assets.receivables));
            $("#lblInventory").text(fmt(res.assets.inventory));
            $("#lblCurrentAssetsTotal").text(fmt(res.assets.current_total));
            $("#lblAssetsGrandTotal").text(fmt(res.assets.total));

            $("#lblPayables").text(fmt(res.liabilities.payables));
            $("#lblLiabTotal").text(fmt(res.liabilities.current_total));
            $("#lblEquity").text(fmt(res.liabilities.equity));
            $("#lblEquityTotal").text(fmt(res.liabilities.equity));
            $("#lblLiabEquityGrandTotal").text(fmt(res.liabilities.total));

        });
    }

    $("#btnGenerate").click(loadData);
    loadData();
});
</script>
@endsection

