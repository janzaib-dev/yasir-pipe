@extends('admin_panel.layout.app')

@section('content')
    <style>
        .ledger-header-summary {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px 8px 0 0;
        }

        .balance-positive {
            color: #198754;
            font-weight: bold;
        }

        .balance-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .table-ledger th {
            background-color: #212529 !important;
            color: #fff;
            text-align: center;
            vertical-align: middle;
        }

        .table-ledger td {
            vertical-align: middle;
            font-size: 14px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid mt-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1 text-primary"><i class="bi bi-file-earmark-spreadsheet"></i> Customer Ledger Report
                        </h4>
                        <p class="text-muted mb-0">Detailed financial statement by date range.</p>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form id="ledgerForm" class="row g-3 p-3 bg-light rounded border mb-4">
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Zone</label>
                                <select name="zone_id" id="zone_id" class="form-control select2">
                                    <option value="">-- All Zones --</option>
                                    @foreach ($zones as $z)
                                        <option value="{{ $z->id }}">{{ $z->zone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-control select2">
                                    <option value="all" data-zone="">-- All Customers --</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" data-zone="{{ $c->zone }}">{{ $c->customer_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Quick Filter</label>
                                <select id="quick_filter" class="form-control">
                                    <option value="custom">Custom Range</option>
                                    <option value="daily">Daily (Today)</option>
                                    <option value="weekly">Weekly (This Week)</option>
                                    <option value="monthly">Monthly (This Month)</option>
                                    <option value="yearly">Yearly (This Year)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control"
                                    value="2000-01-01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" id="btnSearch" class="btn btn-primary w-100"><i
                                        class="bi bi-search"></i> Generate</button>
                            </div>
                        </form>

                        <div id="loader" style="display:none;text-align:center;margin-bottom:20px;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Generating Report...</p>
                        </div>

                        <div id="ledgerBox" style="display:none;">
                            <!-- Report Header -->
                            <div id="ledgerHeader" class="ledger-header-summary row align-items-center"></div>

                            <!-- Ledger Chart -->
                            <div class="row mb-4 justify-content-center" id="ledgerChartWrapper" style="display:none;">
                                <div class="col-md-5 text-center">
                                    <div id="ledgerVolumeChart" style="min-height: 250px;"></div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover table-ledger">
                                    <thead>
                                        <tr>
                                            <th width="10%">Date</th>
                                            <th width="12%">Ref / Invoice</th>
                                            <th width="15%">Customer</th>
                                            <th width="28%">Description</th>
                                            <th width="10%">Debit (Dr)</th>
                                            <th width="10%">Credit (Cr)</th>
                                            <th width="15%">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ledgerBody"></tbody>
                                </table>
                            </div>

                            <div class="text-center mt-3 text-muted">
                                <small>Report generated on {{ date('d-M-Y H:i A') }}</small>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        $(document).ready(function() {
            if ($('.select2').length > 0) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            let allCustomers = $('#customer_id option').clone();

            // Zone Filter Logic
            $(document).on('change', '#zone_id', function() {
                let selectedZone = $(this).val();
                let customerSelect = $('#customer_id');

                customerSelect.empty();

                allCustomers.each(function() {
                    let optionZone = $(this).attr('data-zone');
                    
                    if ($(this).val() === 'all' || selectedZone === "" || optionZone == selectedZone) {
                        customerSelect.append($(this).clone());
                    }
                });

                customerSelect.val('all').trigger('change.select2');
            });

            // Quick Filter Logic
            $(document).on('change', '#quick_filter', function() {
                let val = $(this).val();
                let today = new Date();
                let start = new Date();
                let end = new Date();

                if (val === 'daily') {
                    // Start and end are both today
                } else if (val === 'weekly') {
                    // Start is first day of current week (let's use Monday)
                    let day = today.getDay(); // 0 is Sunday, 1 is Monday
                    let diff = today.getDate() - day + (day === 0 ? -6 : 1);
                    start.setDate(diff);
                } else if (val === 'monthly') {
                    // Start is 1st of current month
                    start.setDate(1);
                } else if (val === 'yearly') {
                    // Start is Jan 1st of current year
                    start.setMonth(0, 1);
                } else if (val === 'custom') {
                    return; // Don't change dates for custom
                }

                $("#start_date").val(formatDateForInput(start));
                $("#end_date").val(formatDateForInput(end));
                loadLedger();
            });

            function formatDateForInput(date) {
                let d = new Date(date),
                    month = '' + (d.getMonth() + 1),
                    day = '' + d.getDate(),
                    year = d.getFullYear();

                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;

                return [year, month, day].join('-');
            }

            // Auto-load ledger on page load (all customers, all dates)
            loadLedger();

            $(document).on('click', '#btnSearch', function() {
                loadLedger();
            });

            function loadLedger() {
                let zid = $("#zone_id").val();
                let cid = $("#customer_id").val();
                let start = $("#start_date").val();
                let end = $("#end_date").val();

                // Default dates if empty
                if (!start) start = '2000-01-01';
                if (!end) end = '{{ date("Y-m-d") }}';

                $("#loader").show();
                $("#ledgerBox").hide();

                $.get("{{ route('report.customer.ledger.fetch') }}", {
                    zone_id: zid,
                    customer_id: cid || 'all',
                    start_date: start,
                    end_date: end
                }, function(res) {
                    $("#loader").hide();
                    $("#ledgerBox").show();

                    let displayStart = formatDisplayDate(start);
                    let displayEnd = formatDisplayDate(end);

                    // Build Header
                    $("#ledgerHeader").html(`
                    <div class="col-md-6">
                        <h5 class="text-dark mb-1">${res.customer.customer_name}</h5>
                        <p class="mb-0 text-muted">Reporting Period: <strong>${displayStart}</strong> to <strong>${displayEnd}</strong></p>
                    </div>
                    <div class="col-md-6 text-end">
                         <span class="badge bg-secondary p-2 shadow-sm">Statement of Account</span>
                    </div>
                `);

                    let totalDebit = 0;
                    let totalCredit = 0;
                    let lastBalance = parseFloat(res.opening_balance);

                    // Opening Balance Row
                    let html = `
                    <tr class="bg-light fw-bold">
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td class="text-start">Opening Balance (B/F)</td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end text-dark">
                            ${lastBalance.toFixed(2)} 
                        </td>
                    </tr>
                `;

                    res.transactions.forEach((t) => {
                        let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                        let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                        totalDebit += debit;
                        totalCredit += credit;
                        lastBalance = parseFloat(t.balance);

                        // Determine Dr/Cr Label
                        let balLabel = lastBalance >= 0 ? 'Dr' : 'Cr';
                        let balClass = lastBalance >= 0 ? 'balance-positive' :
                            'balance-negative';

                        // Customer name column (useful for "All Customers")
                        let custName = t.customer_name || '-';

                        html += `
                        <tr>
                            <td class="text-center">${t.date}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${t.invoice ?? '-'}</span></td>
                            <td class="fw-bold">${custName}</td>
                            <td class="text-start">${t.description}</td>
                            <td class="text-end text-success">${debit > 0 ? debit.toFixed(2) : '-'}</td>
                            <td class="text-end text-danger">${credit > 0 ? credit.toFixed(2) : '-'}</td>
                            <td class="text-end fw-bold ${balClass}">
                                ${Math.abs(lastBalance).toFixed(2)} 
                                <small class="text-muted" style="font-size:0.75em">${balLabel}</small>
                            </td>
                        </tr>
                    `;
                    });

                    $("#ledgerBody").html(html);

                    // Totals Row
                    $("#ledgerBody").append(`
                    <tr class="fw-bold bg-light">
                        <td colspan="4" class="text-end text-dark">Totals:</td>
                        <td class="text-end text-dark">${totalDebit.toFixed(2)}</td>
                        <td class="text-end text-dark">${totalCredit.toFixed(2)}</td>
                        <td class="text-end ${lastBalance >= 0 ? 'balance-positive' : 'balance-negative'}">${Math.abs(lastBalance).toFixed(2)} ${lastBalance >= 0 ? 'Dr' : 'Cr'}</td>
                    </tr>
                `);

                    // Render Chart
                    renderLedgerChart(totalDebit, totalCredit);
                }).fail(function() {
                    $("#loader").hide();
                    alert("Error loading report data.");
                });
            }

            let ledgerChartInstance = null;
            function renderLedgerChart(dr, cr) {
                if (dr === 0 && cr === 0) {
                    $("#ledgerChartWrapper").hide();
                    return;
                }
                $("#ledgerChartWrapper").show();

                if (ledgerChartInstance) {
                    ledgerChartInstance.destroy();
                }

                var options = {
                    series: [dr, cr],
                    chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
                    labels: ['Total Debit (Invoices)', 'Total Credit (Receipts)'],
                    colors: ['#ef4444', '#10b981'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '75%',
                                labels: {
                                    show: true,
                                    name: { show: true, fontSize: '12px' },
                                    value: { show: true, fontSize: '14px', fontWeight: 'bold', formatter: function (val) { return 'Rs ' + val.toLocaleString() } },
                                    total: { show: true, showAlways: true, label: 'Total Volume', fontSize: '12px', formatter: function (w) {
                                      return 'Rs ' + w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0).toLocaleString();
                                    }}
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    stroke: { width: 0 },
                    legend: { show: false },
                    tooltip: { theme: 'light', y: { formatter: function (val) { return 'Rs ' + val.toLocaleString() } } }
                };

                ledgerChartInstance = new ApexCharts(document.querySelector("#ledgerVolumeChart"), options);
                ledgerChartInstance.render();
            }

            function formatDisplayDate(dateStr) {
                if (!dateStr) return '-';
                let d = new Date(dateStr);
                let months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return d.getDate().toString().padStart(2, '0') + '-' + months[d.getMonth()] + '-' + d.getFullYear();
            }
        });
    </script>
@endsection
