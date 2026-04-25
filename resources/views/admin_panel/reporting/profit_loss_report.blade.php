@extends('admin_panel.layout.app')

@section('content')
<style>
    .pl-card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .pl-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    .pl-summary-card {
        padding: 22px 24px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .pl-summary-card::after {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
    }
    .pl-summary-card .label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        opacity: 0.85;
        margin-bottom: 6px;
        font-weight: 600;
    }
    .pl-summary-card .value {
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    .pl-summary-card .icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(255,255,255,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .filter-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
    }
    .top-customer-row {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.15s;
    }
    .top-customer-row:hover {
        background: #f9fafb;
    }
    .top-customer-row:last-child {
        border-bottom: none;
    }
    .rank-badge {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        color: #fff;
        flex-shrink: 0;
    }
    .rank-1 { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .rank-2 { background: linear-gradient(135deg, #9ca3af, #6b7280); }
    .rank-3 { background: linear-gradient(135deg, #b45309, #92400e); }
    .rank-default { background: #e5e7eb; color: #6b7280; }
    .profit-bar {
        height: 6px;
        border-radius: 3px;
        background: #e5e7eb;
        overflow: hidden;
        flex: 1;
    }
    .profit-bar-fill {
        height: 100%;
        border-radius: 3px;
        background: linear-gradient(90deg, #34d399, #10b981);
        transition: width 0.6s ease;
    }
    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color: #111827;">Profit & Loss Analysis</h4>
                    <p class="text-muted mb-0 small">Earnings breakdown using weighted average cost</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card p-3 mb-4 shadow-sm">
                <form id="profitLossForm" class="row g-2 align-items-end">
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">From</label>
                        <input type="date" id="start_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">To</label>
                        <input type="date" id="end_date" class="form-control form-control-sm" value="{{ date('Y-m-t') }}">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Category</label>
                        <select id="category_id" class="form-select form-select-sm select2">
                            <option value="all">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Customer</label>
                        <select id="customer_id" class="form-select form-select-sm select2">
                            <option value="all">All Customers</option>
                            @foreach($customers as $cus)
                                <option value="{{ $cus->id }}">{{ $cus->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Product</label>
                        <select id="product_id" class="form-select form-select-sm select2">
                            <option value="all">All Products</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->item_code }} - {{ $prod->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <button type="button" id="btnSearch" class="btn btn-sm w-100 fw-bold text-white" style="background: #4f46e5; border-radius: 8px; padding: 7px;">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                    </div>
                </form>
            </div>

            <div id="loader" style="display:none;text-align:center;margin-bottom:20px;">
                <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                <span class="ms-2 text-muted small">Calculating...</span>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="pl-card shadow-sm">
                        <div class="pl-summary-card" style="background: linear-gradient(135deg, #059669, #047857);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="label">Gross Profit</div>
                                    <div class="value" id="cardGrossProfit">0.00</div>
                                </div>
                                <div class="icon-wrap"><i class="fas fa-arrow-up"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pl-card shadow-sm">
                        <div class="pl-summary-card" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="label">Total Expenses</div>
                                    <div class="value" id="cardExpenses">0.00</div>
                                </div>
                                <div class="icon-wrap"><i class="fas fa-arrow-down"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pl-card shadow-sm" id="netProfitCard">
                        <div class="pl-summary-card" id="netProfitBg" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="label">Net Profit / Loss</div>
                                    <div class="value" id="cardNetProfit">0.00</div>
                                </div>
                                <div class="icon-wrap"><i class="fas fa-balance-scale"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row g-4 mb-4">
                <!-- Profit Table -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 14px 14px 0 0; border-bottom: 1px solid #f3f4f6;">
                            <span class="section-title"><i class="fas fa-table me-2 text-primary"></i>Item Profitability</span>
                            <i class="fas fa-info-circle text-muted" style="cursor:help;" data-bs-toggle="tooltip" data-bs-placement="left" title="Profit = Revenue - (Total Sold Pieces * Weighted Average Purchase Price). Calculations account for all approved purchases and returns."></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="profitTable" class="table table-hover align-middle mb-0" style="width:100%;">
                                    <thead style="background: #f8fafc;">
                                        <tr>
                                            <th class="ps-4" style="font-size:12px; color:#6b7280;">Code</th>
                                            <th style="font-size:12px; color:#6b7280;">Product</th>
                                            <th class="text-center" style="font-size:12px; color:#6b7280;">Qty</th>
                                            <th class="text-center" style="font-size:12px; color:#6b7280;">Returns</th>
                                            <th class="text-end" style="font-size:12px; color:#6b7280;">Revenue</th>
                                            <th class="text-end" style="font-size:12px; color:#6b7280;">COGS</th>
                                            <th class="text-end pe-4" style="font-size:12px; color:#6b7280;">Profit</th>
                                        </tr>
                                    </thead>
                                    <tbody id="profitBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profit Chart -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                        <div class="card-header bg-white py-3 px-4" style="border-radius: 14px 14px 0 0; border-bottom: 1px solid #f3f4f6;">
                            <span class="section-title"><i class="fas fa-chart-pie me-2" style="color: #8b5cf6;"></i>Top 5 Profit Share</span>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <div id="noChartData" class="text-muted small py-5">Fetch data to see chart</div>
                            <canvas id="profitChart" style="max-height: 280px; width:100%; display:none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 10 Customers -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 14px 14px 0 0; border-bottom: 1px solid #f3f4f6;">
                    <span class="section-title"><i class="fas fa-trophy me-2" style="color: #f59e0b;"></i>Top 10 Customers by Profit</span>
                    <span class="badge text-muted bg-light small" id="customerCount">0 customers</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="topCustomersTableContent">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" style="font-size:12px; color:#6b7280; width:50px;">#</th>
                                    <th style="font-size:12px; color:#6b7280;">Customer Name</th>
                                    <th class="text-end" style="font-size:12px; color:#6b7280;">Revenue</th>
                                    <th class="text-end" style="font-size:12px; color:#6b7280;">Ledger Balance</th>
                                    <th class="text-end pe-4" style="font-size:12px; color:#6b7280;">Net Profit</th>
                                </tr>
                            </thead>
                            <tbody id="topCustomersBody">
                                <tr><td colspan="5" class="text-center text-muted py-5 small">Fetch data to see top customers</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    var profitTable = $('#profitTable').DataTable({
        paging: true,
        pageLength: 15,
        searching: true,
        ordering: true,
        order: [[5, 'desc']],
        language: { search: '', searchPlaceholder: 'Search items...' },
        columns: [
            { data: 'item_code' },
            { data: 'item_name' },
            { data: 'sold_qty', className: 'text-center' },
            { data: 'returned_qty', className: 'text-center' },
            { data: 'revenue', className: 'text-end' },
            { data: 'cogs', className: 'text-end' },
            { data: 'profit', className: 'text-end' }
        ]
    });

    var myChart = null;

    function fmt(n) {
        return parseFloat(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function fetchReport() {
        $('#loader').show();

        $.ajax({
            url: "{{ route('report.profit_loss.fetch') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                product_id: $('#product_id').val(),
                category_id: $('#category_id').val(),
                customer_id: $('#customer_id').val()
            },
            success: function(response) {
                $('#loader').hide();
                renderReport(response);
            },
            error: function() {
                $('#loader').hide();
                Swal.fire('Error', 'Could not fetch report data', 'error');
            }
        });
    }

    function renderReport(data) {
        // Table
        profitTable.clear();
        let chartLabels = [];
        let chartData = [];

        data.products.forEach(function(r) {
            let profitColor = parseFloat(r.profit) >= 0 ? '#059669' : '#dc2626';
            profitTable.row.add({
                item_code: '<span class="fw-semibold text-muted small">' + r.item_code + '</span>',
                item_name: r.item_name,
                sold_qty: r.sold_qty,
                returned_qty: '<span class="text-danger fw-bold">' + (r.returned_qty || 0) + '</span>',
                revenue: fmt(r.revenue),
                cogs: fmt(r.cogs),
                profit: '<span style="color:' + profitColor + '; font-weight:700;">' + fmt(r.profit) + '</span>'
            });
            if (chartLabels.length < 5 && parseFloat(r.profit) > 0) {
                chartLabels.push(r.item_name.length > 18 ? r.item_name.substring(0, 18) + '…' : r.item_name);
                chartData.push(r.profit);
            }
        });
        profitTable.draw();

        // Cards
        $('#cardGrossProfit').text(fmt(data.total_gross_profit));
        $('#cardExpenses').text(fmt(data.total_expenses));
        $('#cardNetProfit').text(fmt(data.net_profit));

        if (data.net_profit >= 0) {
            $('#netProfitBg').css('background', 'linear-gradient(135deg, #2563eb, #1d4ed8)');
        } else {
            $('#netProfitBg').css('background', 'linear-gradient(135deg, #dc2626, #b91c1c)');
        }

        // Chart
        updateChart(chartLabels, chartData);

        // Top 10 Customers
        renderTopCustomers(data.top_customers || []);
    }

    function renderTopCustomers(customers) {
        var html = '';
        $('#customerCount').text(customers.length + ' customers');

        if (customers.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted py-5 small">No customer data for this period</td></tr>';
            $('#topCustomersBody').html(html);
            return;
        }

        customers.forEach(function(c, idx) {
            var profitColor = c.profit >= 0 ? '#059669' : '#dc2626';
            var profitSign = c.profit < 0 ? '-' : '';

            html += '<tr>' +
                '<td class="ps-4 fw-bold text-muted" style="font-size:12px;">' + (idx + 1) + '</td>' +
                '<td>' +
                    '<div class="fw-bold text-dark" style="font-size:14px;">' + c.name + '</div>' +
                    '<div class="text-muted small" style="font-size:10px;">ID: CUST-' + c.id.toString().padStart(4, '0') + '</div>' +
                '</td>' +
                '<td class="text-end fw-semibold" style="font-size:13px;">Rs ' + fmt(c.revenue) + '</td>' +
                '<td class="text-end text-danger fw-semibold" style="font-size:13px;">Rs ' + fmt(c.balance) + '</td>' +
                '<td class="text-end pe-4">' +
                    '<span class="badge p-2" style="background: ' + (c.profit >= 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)') + '; color: ' + profitColor + '; font-size:13px; font-weight:700;">' + 
                        profitSign + 'Rs ' + fmt(Math.abs(c.profit)) + 
                    '</span>' +
                '</td>' +
            '</tr>';
        });

        $('#topCustomersBody').html(html);
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    }

    function updateChart(labels, data) {
        if (myChart) myChart.destroy();

        if (labels.length === 0) {
            $('#profitChart').hide();
            $('#noChartData').show().text('No profit data available');
            return;
        }

        $('#noChartData').hide();
        $('#profitChart').show();

        var ctx = document.getElementById('profitChart');
        myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 12 }
                    }
                }
            }
        });
    }

    $('#btnSearch').click(fetchReport);
    fetchReport();
});
</script>
@endsection
