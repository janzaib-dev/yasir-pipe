@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --dash-primary: #6366f1;
            --dash-success: #22c55e;
            --dash-warning: #f59e0b;
            --dash-danger: #ef4444;
            --dash-info: #0ea5e9;
            --dash-purple: #8b5cf6;
            --dash-bg: #f8fafc;
            --dash-card: #ffffff;
            --dash-border: #e2e8f0;
            --dash-text: #1e293b;
            --dash-muted: #64748b;
        }

        .dashboard-container {
            padding: 0;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            border-radius: 20px;
            padding: 32px 40px;
            color: white;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            right: 10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .welcome-date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--dash-card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--dash-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.primary::before {
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
        }

        .stat-card.success::before {
            background: linear-gradient(180deg, #22c55e, #16a34a);
        }

        .stat-card.warning::before {
            background: linear-gradient(180deg, #f59e0b, #d97706);
        }

        .stat-card.danger::before {
            background: linear-gradient(180deg, #ef4444, #dc2626);
        }

        .stat-card.info::before {
            background: linear-gradient(180deg, #0ea5e9, #0284c7);
        }

        .stat-card.purple::before {
            background: linear-gradient(180deg, #8b5cf6, #7c3aed);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-card.primary .stat-icon {
            background: #eef2ff;
            color: #6366f1;
        }

        .stat-card.success .stat-icon {
            background: #dcfce7;
            color: #22c55e;
        }

        .stat-card.warning .stat-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .stat-card.danger .stat-icon {
            background: #fee2e2;
            color: #ef4444;
        }

        .stat-card.info .stat-icon {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .stat-card.purple .stat-icon {
            background: #f3e8ff;
            color: #8b5cf6;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .stat-trend.up {
            background: #dcfce7;
            color: #16a34a;
        }

        .stat-trend.down {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--dash-text);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--dash-muted);
            margin-top: 4px;
        }

        /* Chart Cards */
        .chart-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .chart-card {
            background: var(--dash-card);
            border-radius: 16px;
            border: 1px solid var(--dash-border);
            overflow: hidden;
        }

        .chart-card.full-width {
            grid-column: span 2;
        }

        .chart-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--dash-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--dash-primary);
        }

        .chart-filter {
            display: flex;
            gap: 8px;
        }

        .filter-btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid var(--dash-border);
            background: white;
            color: var(--dash-muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--dash-primary);
            color: white;
            border-color: var(--dash-primary);
        }

        .chart-body {
            padding: 24px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .action-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
            border-color: var(--dash-primary);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.2rem;
        }

        .action-card.sales .action-icon {
            background: #dcfce7;
            color: #22c55e;
        }

        .action-card.purchase .action-icon {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .action-card.products .action-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .action-card.hr .action-icon {
            background: #f3e8ff;
            color: #8b5cf6;
        }

        .action-title {
            font-weight: 600;
            color: var(--dash-text);
            font-size: 0.95rem;
        }

        .action-desc {
            font-size: 0.8rem;
            color: var(--dash-muted);
            margin-top: 4px;
        }

        /* Summary Cards Row */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .summary-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 20px;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .summary-title {
            font-size: 0.85rem;
            color: var(--dash-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dash-text);
        }

        .summary-change {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            margin-top: 6px;
        }

        .summary-change.positive {
            color: #22c55e;
        }

        .summary-change.negative {
            color: #ef4444;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .chart-section {
                grid-template-columns: 1fr;
            }

            .chart-card.full-width {
                grid-column: span 1;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .summary-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .summary-row {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 24px;
            }
        }

        /* Top 10 Lists Widget */
        .top-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--dash-border);
        }
        .top-list-item:last-child {
            border-bottom: none;
        }
        .top-list-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .top-list-rank {
            font-weight: bold;
            color: var(--dash-muted);
            width: 25px;
            text-align: center;
        }
        .top-list-name {
            font-weight: 600;
            color: var(--dash-text);
            margin-bottom: 2px;
        }
        .top-list-sub {
            font-size: 0.8rem;
            color: var(--dash-muted);
        }
        .top-list-val {
            font-weight: bold;
            color: var(--dash-primary);
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container dashboard-container">

                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <h1 class="welcome-title">Welcome back, {{ auth()->user()->name ?? 'Admin' }}! 👋</h1>
                        <p class="welcome-subtitle">Here's what's happening with your business today.</p>
                        <div class="welcome-date">
                            <i class="fa fa-calendar-alt"></i>
                            {{ now()->format('l, F d, Y') }}
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    @can('sales.create')
                        <a href="{{ route('sale.index') }}" class="action-card sales">
                            <div class="action-icon"><i class="fa fa-shopping-cart"></i></div>
                            <div class="action-title">New Sale</div>
                            <div class="action-desc">Create invoice</div>
                        </a>
                    @endcan

                    @can('purchases.create')
                        <a href="{{ route('Purchase.home') }}" class="action-card purchase">
                            <div class="action-icon"><i class="fa fa-truck"></i></div>
                            <div class="action-title">New Purchase</div>
                            <div class="action-desc">Add stock</div>
                        </a>
                    @endcan

                    @can('products.view')
                        <a href="{{ route('product') }}" class="action-card products">
                            <div class="action-icon"><i class="fa fa-box"></i></div>
                            <div class="action-title">Products</div>
                            <div class="action-desc">Manage inventory</div>
                        </a>
                    @endcan

                    @can('hr.employees.view')
                        <a href="{{ route('hr.employees.index') }}" class="action-card hr">
                            <div class="action-icon"><i class="fa fa-users"></i></div>
                            <div class="action-title">HR Module</div>
                            <div class="action-desc">Manage employees</div>
                        </a>
                    @endcan
                </div>

                <!-- Financial Health (Accounting Based) -->
                @if (isset($financialSummary) && !empty($financialSummary))
                    <h5 class="mb-3 text-muted"
                        style="font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Financial Health (This Month)
                    </h5>
                    <div class="stats-grid mb-4">
                        <!-- Sales Revenue -->
                        <div class="stat-card success">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-hand-holding-usd"></i></div>
                                <div class="stat-trend up">Accounting</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($financialSummary['sales'] ?? 0, 0) }}</div>
                            <div class="stat-label">Sales Revenue</div>
                        </div>

                        <!-- Purchase Expense -->
                        <div class="stat-card danger">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
                                <div class="stat-trend down">Accounting</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($financialSummary['purchases'] ?? 0, 0) }}</div>
                            <div class="stat-label">Purchase Expenses</div>
                        </div>

                        <!-- Payables (Money going out) -->
                        <div class="stat-card warning">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-file-invoice"></i></div>
                                <div class="stat-trend">Liabilities</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($financialSummary['payables'] ?? 0, 0) }}</div>
                            <div class="stat-label">Total Payables (Owe)</div>
                        </div>
                    </div>

                    <h5 class="mb-3 text-muted"
                        style="font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Cash Flow Summary (Payments)
                    </h5>
                    <div class="stats-grid mb-4">
                        <!-- Payment In -->
                        <a href="{{ route('all_recepit_vochers') }}" class="stat-card info text-decoration-none" style="cursor: pointer;">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-arrow-down"></i></div>
                                <div class="stat-trend up">This Month</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($paymentInMonth, 0) }}</div>
                            <div class="stat-label">Payment In (Receipts)</div>
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted">Overall: <strong>Rs {{ number_format($paymentInOverall, 0) }}</strong></small>
                            </div>
                        </a>

                        <!-- Payment Out -->
                        <a href="{{ route('all_Payment_vochers') }}" class="stat-card danger text-decoration-none" style="cursor: pointer;">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-arrow-up"></i></div>
                                <div class="stat-trend down">This Month</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($paymentOutMonth, 0) }}</div>
                            <div class="stat-label">Payment Out (Vendor/Exp)</div>
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted">Overall: <strong>Rs {{ number_format($paymentOutOverall, 0) }}</strong></small>
                            </div>
                        </a>
                    </div>
                @endif

                <!-- Main Stats (Legacy/Ops) -->
                <div class="stats-grid">
                    @can('sales.view')
                        <div class="stat-card success">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-shopping-cart"></i></div>
                                <div class="stat-trend up"><i class="fa fa-arrow-up"></i> Sales</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalSales, 0) }}</div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                    @endcan

                    @can('purchases.view')
                        <div class="stat-card primary">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                                <div class="stat-trend up"><i class="fa fa-arrow-up"></i> Purchases</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalPurchases, 0) }}</div>
                            <div class="stat-label">Total Purchases</div>
                        </div>
                    @endcan

                    @can('sales.returns.view')
                        <div class="stat-card danger">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-undo-alt"></i></div>
                                <div class="stat-trend down"><i class="fa fa-arrow-down"></i> Returns</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalSalesReturns, 0) }}</div>
                            <div class="stat-label">Sales Returns</div>
                        </div>
                    @endcan

                    @can('purchase.returns.view')
                        <div class="stat-card warning">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-undo"></i></div>
                                <div class="stat-trend down"><i class="fa fa-arrow-down"></i> Returns</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalPurchaseReturns, 0) }}</div>
                            <div class="stat-label">Purchase Returns</div>
                        </div>
                    @endcan
                </div>

                <!-- Inventory Summary -->
                <div class="summary-row">
                    @can('categories.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Categories</span>
                                <div class="summary-icon" style="background: #eef2ff; color: #6366f1;"><i
                                        class="fa fa-layer-group"></i></div>
                            </div>
                            <div class="summary-value">{{ $categoryCount }}</div>
                            <div class="summary-change positive"><i class="fa fa-folder"></i> Product groups</div>
                        </div>
                    @endcan

                    @can('subcategories.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Subcategories</span>
                                <div class="summary-icon" style="background: #dcfce7; color: #22c55e;"><i
                                        class="fa fa-sitemap"></i></div>
                            </div>
                            <div class="summary-value">{{ $subcategoryCount }}</div>
                            <div class="summary-change positive"><i class="fa fa-tags"></i> Sub-groups</div>
                        </div>
                    @endcan

                    @can('products.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Products</span>
                                <div class="summary-icon" style="background: #fef3c7; color: #f59e0b;"><i
                                        class="fa fa-box-open"></i></div>
                            </div>
                            <div class="summary-value">{{ $productCount }}</div>
                            <div class="summary-change positive"><i class="fa fa-cubes"></i> In inventory</div>
                        </div>
                    @endcan

                    @can('customers.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Customers</span>
                                <div class="summary-icon" style="background: #e0f2fe; color: #0ea5e9;"><i
                                        class="fa fa-users"></i></div>
                            </div>
                            <div class="summary-value">{{ $customerscount }}</div>
                            <div class="summary-change positive"><i class="fa fa-user-plus"></i> Registered</div>
                        </div>
                    @endcan
                </div>

                <!-- Charts Section -->
                <div class="chart-section">
                    @can('sales.view')
                        <div class="chart-card full-width">
                            <div class="chart-header">
                                <div class="chart-title">
                                    <i class="fa fa-chart-line"></i> Sales Analytics
                                </div>
                                <div class="chart-filter" id="salesFilterBtns">
                                    <button class="filter-btn active" data-filter="daily">Daily</button>
                                    <button class="filter-btn" data-filter="weekly">Weekly</button>
                                    <button class="filter-btn" data-filter="monthly">Monthly</button>
                                </div>
                            </div>
                            <div class="chart-body">
                                <div id="salesReportChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    @endcan

                    @can('purchases.view')
                        <div class="chart-card full-width">
                            <div class="chart-header">
                                <div class="chart-title">
                                    <i class="fa fa-chart-area"></i> Purchase Analytics
                                </div>
                                <div class="chart-filter" id="purchaseFilterBtns">
                                    <button class="filter-btn active" data-filter="daily">Daily</button>
                                    <button class="filter-btn" data-filter="weekly">Weekly</button>
                                    <button class="filter-btn" data-filter="monthly">Monthly</button>
                                </div>
                            </div>
                            <div class="chart-body">
                                <div id="purchaseReportChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    @endcan
                </div>

                <!-- Top 10 Sections -->
                @can('sales.view')
                <div class="chart-section" style="margin-top: 28px;">
                    <!-- Top Products -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fa fa-fire text-danger" style="color: var(--dash-danger) !important;"></i> Top Products (Qty Sold)
                            </div>
                        </div>
                        <div class="chart-body" style="padding: 24px;">
                            @if(isset($topProducts) && count($topProducts) > 0)
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0 d-flex justify-content-center">
                                        <div id="topProductsDonut"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="max-height: 280px; overflow-y: auto; padding-right: 10px;">
                                            @php $rank = 1; @endphp
                                            @foreach($topProducts as $tp)
                                            <div class="top-list-item">
                                                <div class="top-list-info">
                                                    <div class="top-list-rank">#{{ $rank++ }}</div>
                                                    <div>
                                                        <div class="top-list-name">{{ $tp->product_name ?: 'Unknown Product' }}</div>
                                                        <div class="top-list-sub">Revenue: Rs {{ number_format($tp->total_revenue ?? 0) }}</div>
                                                    </div>
                                                </div>
                                                <div class="top-list-val">
                                                    {{ number_format($tp->total_qty) }} units
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">No product data available yet.</div>
                            @endif
                        </div>
                    </div>

                    <!-- Top Customers -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fa fa-crown text-warning" style="color: var(--dash-warning) !important;"></i> Top Customers (Sales Vol)
                            </div>
                        </div>
                        <div class="chart-body" style="padding: 24px;">
                            @if(isset($topCustomers) && count($topCustomers) > 0)
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0 d-flex justify-content-center">
                                        <div id="topCustomersDonut"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="max-height: 280px; overflow-y: auto; padding-right: 10px;">
                                            @php $rank = 1; @endphp
                                            @foreach($topCustomers as $tc)
                                            <div class="top-list-item">
                                                <div class="top-list-info">
                                                    <div class="top-list-rank">#{{ $rank++ }}</div>
                                                    <div>
                                                        <div class="top-list-name">{{ $tc->customer_name ?: 'Walk-in Customer' }}</div>
                                                        <div class="top-list-sub">{{ $tc->total_orders }} orders completed</div>
                                                    </div>
                                                </div>
                                                <div class="top-list-val" style="color: var(--dash-success);">
                                                    Rs {{ number_format($tc->total_sales) }}
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">No customer data available yet.</div>
                            @endif
                        </div>
                    </div>
                </div>
                @endcan

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const salesStats = @json($salesChartStats);
            const purchaseStats = @json($purchaseChartStats);
            
            const topProductsData = @json($topProducts ?? []);
            const topCustomersData = @json($topCustomers ?? []);

            // Sales Chart
            const salesOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    dropShadow: {
                        enabled: true,
                        top: 3,
                        left: 2,
                        blur: 4,
                        opacity: 0.1
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#22c55e'],
                series: salesStats.daily.series,
                xaxis: {
                    categories: salesStats.daily.categories,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        },
                        formatter: val => 'Rs ' + val.toLocaleString()
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 5,
                    colors: ['#fff'],
                    strokeColors: '#22c55e',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: val => "Rs " + val.toLocaleString()
                    }
                }
            };

            const salesChart = new ApexCharts(document.querySelector("#salesReportChart"), salesOptions);
            salesChart.render();

            // Sales Filter
            document.querySelectorAll('#salesFilterBtns .filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#salesFilterBtns .filter-btn').forEach(b => b
                        .classList.remove('active'));
                    this.classList.add('active');
                    const selected = this.dataset.filter;
                    salesChart.updateOptions({
                        series: salesStats[selected].series,
                        xaxis: {
                            categories: salesStats[selected].categories
                        }
                    });
                });
            });

            // Purchase Chart
            const purchaseOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    dropShadow: {
                        enabled: true,
                        top: 3,
                        left: 2,
                        blur: 4,
                        opacity: 0.1
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#6366f1'],
                series: purchaseStats.daily.series,
                xaxis: {
                    categories: purchaseStats.daily.categories,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        },
                        formatter: val => 'Rs ' + val.toLocaleString()
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 5,
                    colors: ['#fff'],
                    strokeColors: '#6366f1',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: val => "Rs " + val.toLocaleString()
                    }
                }
            };

            const purchaseChart = new ApexCharts(document.querySelector("#purchaseReportChart"), purchaseOptions);
            purchaseChart.render();

            // Purchase Filter
            document.querySelectorAll('#purchaseFilterBtns .filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#purchaseFilterBtns .filter-btn').forEach(b => b
                        .classList.remove('active'));
                    this.classList.add('active');
                    const selected = this.dataset.filter;
                    purchaseChart.updateOptions({
                        series: purchaseStats[selected].series,
                        xaxis: {
                            categories: purchaseStats[selected].categories
                        }
                    });
                });
            });

            // ===== Top 10 Products Donut Chart =====
            if (topProductsData.length > 0) {
                const productNames = topProductsData.map(p => p.product_name || 'Unknown');
                const productQtys = topProductsData.map(p => parseFloat(p.total_qty) || 0);

                const prodOptions = {
                    chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                    series: productQtys,
                    labels: productNames,
                    colors: ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#84cc16', '#22c55e', '#06b6d4', '#3b82f6'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    name: { show: true, fontSize: '12px' },
                                    value: { show: true, fontSize: '16px', fontWeight: 'bold' },
                                    total: { show: true, showAlways: true, label: 'Total Sold', fontSize: '14px' }
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    stroke: { width: 0 },
                    legend: { show: false },
                    tooltip: {
                        theme: 'light',
                        y: { formatter: val => val + " units" }
                    }
                };
                new ApexCharts(document.querySelector("#topProductsDonut"), prodOptions).render();
            }

            // ===== Top 10 Customers Donut Chart =====
            if (topCustomersData.length > 0) {
                const customerNames = topCustomersData.map(c => c.customer_name || 'Walk-in');
                const customerSales = topCustomersData.map(c => parseFloat(c.total_sales) || 0);

                const custOptions = {
                    chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                    series: customerSales,
                    labels: customerNames,
                    colors: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#ef4444', '#14b8a6', '#6366f1', '#eab308', '#22c55e'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    name: { show: true, fontSize: '12px' },
                                    value: { show: true, fontSize: '16px', fontWeight: 'bold', formatter: val => 'Rs ' + val.toLocaleString() },
                                    total: { show: true, showAlways: true, label: 'Total Rev', fontSize: '14px', formatter: w => {
                                        return 'Rs ' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                                    }}
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    stroke: { width: 0 },
                    legend: { show: false },
                    tooltip: {
                        theme: 'light',
                        y: { formatter: val => "Rs " + val.toLocaleString() }
                    }
                };
                new ApexCharts(document.querySelector("#topCustomersDonut"), custOptions).render();
            }
        });
    </script>
@endsection
