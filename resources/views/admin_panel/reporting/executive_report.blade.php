@extends('admin_panel.layout.app')

@section('content')
<style>
    .exec-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
    }
    .exec-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    }
    .stat-box {
        padding: 24px;
        position: relative;
    }
    .stat-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 4px;
    }
    .stat-subtext {
        font-size: 12px;
        color: #9ca3af;
    }
    .icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 16px;
    }
    .bg-soft-primary { background: #eef2ff; color: #4f46e5; }
    .bg-soft-success { background: #ecfdf5; color: #10b981; }
    .bg-soft-danger { background: #fef2f2; color: #ef4444; }
    .bg-soft-warning { background: #fffbeb; color: #f59e0b; }
    .bg-soft-info { background: #f0f9ff; color: #0ea5e9; }

    .account-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .account-row:last-child { border-bottom: none; }
    
    .trend-up { color: #10b981; font-size: 11px; font-weight: 600; }
    .trend-down { color: #ef4444; font-size: 11px; font-weight: 600; }

    .header-gradient {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 40px 0;
        margin-bottom: -60px;
        color: #fff;
    }

    .main-container {
        position: relative;
        z-index: 1;
        padding-top: 20px;
    }
</style>

<div class="main-content">
    <div class="header-gradient">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">Executive Overview</h3>
                    <p class="opacity-75 mb-0">Real-time business performance snapshot</p>
                </div>
                <div>
                    <button class="btn btn-light btn-sm fw-bold px-3 py-2" onclick="fetchData()">
                        <i class="fas fa-sync-alt me-2"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container container-fluid px-4">
        <!-- Loader -->
        <div id="loader" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted fw-500">Syncing latest figures...</p>
        </div>

        <div id="content-row">
            <!-- Row 1: Sales, Purchases, Expenses, Net Profit -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="exec-card shadow-sm stat-box">
                        <div class="icon-circle bg-soft-primary"><i class="fas fa-shopping-cart"></i></div>
                        <div class="stat-label">Total Sales (Today)</div>
                        <div class="stat-value" id="salesToday">0.00</div>
                        <div class="stat-subtext">This Month: <span class="fw-bold text-dark" id="salesMonth">0.00</span></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="exec-card shadow-sm stat-box">
                        <div class="icon-circle bg-soft-warning"><i class="fas fa-truck-loading"></i></div>
                        <div class="stat-label">Purchases (Today)</div>
                        <div class="stat-value" id="purchasesToday">0.00</div>
                        <div class="stat-subtext">This Month: <span class="fw-bold text-dark" id="purchasesMonth">0.00</span></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="exec-card shadow-sm stat-box">
                        <div class="icon-circle bg-soft-danger"><i class="fas fa-receipt"></i></div>
                        <div class="stat-label">Expenses (Today)</div>
                        <div class="stat-value" id="expensesToday">0.00</div>
                        <div class="stat-subtext">This Month: <span class="fw-bold text-dark" id="expensesMonth">0.00</span></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="exec-card shadow-sm stat-box">
                        <div class="icon-circle bg-soft-success"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-label">Net Profit (Month)</div>
                        <div class="stat-value" id="netProfitMonth">0.00</div>
                        <div class="stat-subtext" id="profitStatus">Consolidated figure</div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Receivables & Payables -->
                <div class="col-lg-4">
                    <div class="exec-card shadow-sm h-100">
                        <div class="p-4 border-bottom bg-light">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-balance-scale me-2 text-primary"></i>Business Vitals</h6>
                        </div>
                        <div class="p-4">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small fw-600">RECEIVABLES (CUSTOMERS)</span>
                                    <span class="text-danger fw-bold" id="totalReceivables">0.00</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" style="width: 70%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small fw-600">PAYABLES (VENDORS)</span>
                                    <span class="text-warning fw-bold" id="totalPayables">0.00</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: 40%"></div>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-soft-primary rounded-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold">Liquidity Index</span>
                                    <span id="liquidityRatio" class="badge bg-primary">Calculating...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash Accounts -->
                <div class="col-lg-4">
                    <div class="exec-card shadow-sm h-100">
                        <div class="p-4 border-bottom bg-light">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-wallet me-2 text-success"></i>Cash in Hand</h6>
                        </div>
                        <div class="p-4" id="cashList">
                            <div class="text-center py-3 text-muted small">No cash accounts found</div>
                        </div>
                    </div>
                </div>

                <!-- Bank Accounts -->
                <div class="col-lg-4">
                    <div class="exec-card shadow-sm h-100">
                        <div class="p-4 border-bottom bg-light">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-university me-2 text-info"></i>Bank Balances</h6>
                        </div>
                        <div class="p-4" id="bankList">
                            <div class="text-center py-3 text-muted small">No bank accounts found</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top 10 Customers -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="exec-card shadow-sm h-100">
                        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-crown me-2 text-warning"></i>Top 10 Customers by Profit</h6>
                            <span class="badge bg-soft-primary text-primary fw-bold">Life-time Analysis</span>
                        </div>
                        <div class="p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="topCustomersTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 py-3 small fw-bold text-muted" style="width: 50px;">#</th>
                                            <th class="py-3 small fw-bold text-muted">CUSTOMER</th>
                                            <th class="py-3 small fw-bold text-muted text-end">REVENUE</th>
                                            <th class="py-3 small fw-bold text-muted text-end">BALANCE</th>
                                            <th class="py-3 small fw-bold text-muted text-end pe-4">NET PROFIT</th>
                                        </tr>
                                    </thead>
                                    <tbody id="topCustomersList">
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="topCustomersEmpty" class="text-center py-5 text-muted small" style="display:none;">
                                No profitable customer data found
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Insights Chart -->
            <div class="row g-4">
                <div class="col-12">
                   <div class="exec-card shadow-sm p-4 text-center">
                        <p class="text-muted mb-0 small">This report is restricted to Super Admin or authorized executive personnel only.</p>
                   </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
function fmt(n) {
    let val = parseFloat(n || 0);
    let sign = val < 0 ? '-' : '';
    return sign + 'Rs ' + Math.abs(val).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function fetchData() {
    $('#loader').show();
    $('#content-row').css('opacity', '0.3');

    $.ajax({
        url: "{{ route('report.executive.fetch') }}",
        type: "GET",
        success: function(res) {
            $('#loader').hide();
            $('#content-row').css('opacity', '1');

            // Sales
            $('#salesToday').text(fmt(res.sales.today));
            $('#salesMonth').text(fmt(res.sales.month));

            // Purchases
            $('#purchasesToday').text(fmt(res.purchases.today));
            $('#purchasesMonth').text(fmt(res.purchases.month));

            // Expenses
            $('#expensesToday').text(fmt(res.expenses.today));
            $('#expensesMonth').text(fmt(res.expenses.month));

            // Net Profit
            let netProfit = res.sales.month - res.expenses.month; // Simplistic view
            $('#netProfitMonth').text(fmt(netProfit));
            if(netProfit >= 0) {
                $('#netProfitMonth').addClass('text-success').removeClass('text-danger');
                $('#profitStatus').text('Profitable performance this month');
            } else {
                $('#netProfitMonth').addClass('text-danger').removeClass('text-success');
                $('#profitStatus').text('Performance below expense margin');
            }

            // Vitals
            $('#totalReceivables').text(fmt(res.receivables));
            $('#totalPayables').text(fmt(res.payables));
            
            let totalEscrow = parseFloat(res.receivables) + parseFloat(res.payables);
            if(totalEscrow > 0) {
               let recPercent = (res.receivables / totalEscrow) * 100;
               let payPercent = (res.payables / totalEscrow) * 100;
               $('.progress-bar.bg-danger').css('width', recPercent + '%');
               $('.progress-bar.bg-warning').css('width', payPercent + '%');
            }

            // Accounts
            let cashHtml = '';
            if(res.accounts.cash.length > 0) {
                res.accounts.cash.forEach(acc => {
                    cashHtml += `
                    <div class="account-row">
                        <span class="fw-500 small text-dark">${acc.title}</span>
                        <span class="fw-bold small text-success">${fmt(acc.current_balance)}</span>
                    </div>`;
                });
            } else {
                cashHtml = '<div class="text-center py-3 text-muted small">No cash accounts found</div>';
            }
            $('#cashList').html(cashHtml);

            let bankHtml = '';
            if(res.accounts.bank.length > 0) {
                res.accounts.bank.forEach(acc => {
                    bankHtml += `
                    <div class="account-row">
                        <span class="fw-500 small text-dark">${acc.title}</span>
                        <span class="fw-bold small text-info">${fmt(acc.current_balance)}</span>
                    </div>`;
                });
            } else {
                bankHtml = '<div class="text-center py-3 text-muted small">No bank accounts found</div>';
            }
            $('#bankList').html(bankHtml);

            // Top Customers
            let topCustHtml = '';
            if(res.top_customers && res.top_customers.length > 0) {
                $('#topCustomersTable').show();
                $('#topCustomersEmpty').hide();
                res.top_customers.forEach((c, i) => {
                    topCustHtml += `
                    <tr>
                        <td class="ps-4 small text-muted">${i+1}</td>
                        <td>
                            <div class="fw-bold text-dark">${c.name}</div>
                            <div class="text-muted small" style="font-size:10px;">ID: CUST-${c.id.toString().padStart(4, '0')}</div>
                        </td>
                        <td class="text-end small">${fmt(c.revenue)}</td>
                        <td class="text-end small">${fmt(c.balance)}</td>
                        <td class="text-end pe-4">
                            <span class="badge bg-soft-success text-success fw-bold p-2">${fmt(c.profit)}</span>
                        </td>
                    </tr>`;
                });
                $('#topCustomersList').html(topCustHtml);
            } else {
                $('#topCustomersTable').hide();
                $('#topCustomersEmpty').show();
            }

            // Liquidity Index
            let totalAvailable = 0;
            res.accounts.cash.forEach(a => totalAvailable += parseFloat(a.current_balance));
            res.accounts.bank.forEach(a => totalAvailable += parseFloat(a.current_balance));
            
            let ratio = res.payables > 0 ? (totalAvailable / res.payables).toFixed(2) : '1.0+';
            $('#liquidityRatio').text('Ratio: ' + ratio);

        },
        error: function() {
            $('#loader').hide();
            $('#content-row').css('opacity', '1');
            Swal.fire('Error', 'Failed to fetch executive data', 'error');
        }
    });
}

$(document).ready(function() {
    fetchData();
});
</script>
@endsection
