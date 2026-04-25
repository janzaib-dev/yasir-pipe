@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .payroll-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--hr-border);
            padding-bottom: 0;
        }

        .payroll-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: var(--hr-text-light);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            bottom: -2px;
        }

        .payroll-tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .payroll-tab:hover {
            color: var(--hr-text);
        }

        .payroll-card {
            background: var(--hr-card);
            border: 1px solid var(--hr-border);
            border-radius: 14px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .payroll-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
        }

        .payroll-card.monthly::before {
            background: linear-gradient(180deg, #3b82f6, #2563eb);
        }

        .payroll-card.daily::before {
            background: linear-gradient(180deg, #22c55e, #16a34a);
        }

        .payroll-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }

        .payroll-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payroll-type-badge.monthly {
            background: #dbeafe;
            color: #1e40af;
        }

        .payroll-type-badge.daily {
            background: #d1fae5;
            color: #065f46;
        }

        .salary-display {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-top: 16px;
        }

        .salary-display.monthly {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .salary-display.daily {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        .salary-display .amount {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .salary-display .label {
            font-size: 0.8rem;
            opacity: 0.95;
        }

        .breakdown-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.875rem;
            border-bottom: 1px dashed var(--hr-border);
        }

        .breakdown-row:last-child {
            border-bottom: none;
        }

        .breakdown-row .label {
            color: var(--hr-text-light);
        }

        .breakdown-row .value {
            font-weight: 600;
            color: var(--hr-text);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.generated {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.reviewed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.paid {
            background: #d1fae5;
            color: #065f46;
        }

        .month-badge {
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--hr-text);
            border: 1px solid var(--hr-border);
        }

        .payroll-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .payroll-actions .btn {
            flex: 1;
            padding: 8px;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--hr-text-light);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--hr-border);
            margin-bottom: 16px;
        }

        .modal-detail-section {
            margin-bottom: 24px;
        }

        .modal-detail-section h6 {
            font-weight: 700;
            color: var(--hr-text);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--hr-border);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed var(--hr-border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            color: var(--hr-text-light);
            font-weight: 500;
        }

        .detail-row .value {
            font-weight: 600;
            color: var(--hr-text);
        }

        .detail-row.total {
            font-size: 1.1rem;
            padding-top: 12px;
            margin-top: 8px;
            border-top: 2px solid var(--hr-border);
        }

        /* Modern Payroll UI Overhaul */
        :root {
            --modern-primary: #6366f1;
            --modern-success: #10b981;
            --modern-danger: #ef4444;
            --modern-warning: #f59e0b;
            --modern-text: #1e293b;
            --modern-text-light: #64748b;
            --modern-bg: #f8fafc;
            --modern-card-bg: #ffffff;
            --modern-border: #e2e8f0;
        }


        /* Modern Payroll UI Overhaul */
        :root {
            --modern-primary: #6366f1;
            --modern-success: #10b981;
            --modern-danger: #ef4444;
            --modern-warning: #f59e0b;
            --modern-text: #1e293b;
            --modern-text-light: #64748b;
            --modern-bg: #f8fafc;
            --modern-card-bg: #ffffff;
            --modern-border: #e2e8f0;
        }

        .net-payable {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            margin-top: 24px;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
            position: relative;
            overflow: hidden;
        }

        .net-payable::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 40%, rgba(255, 255, 255, 0.1) 45%, transparent 50%);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .net-payable .label {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .net-payable .amount {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Modern Expandable Sections */
        .expandable-section {
            border: 1px solid var(--modern-border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .expandable-section:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }

        .expandable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: white;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }

        .expandable-header:hover {
            background: #f8fafc;
        }

        .expandable-header.active {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }

        .expandable-header.active .expand-icon {
            transform: rotate(180deg);
            color: white;
        }

        .expandable-header.active .expandable-value {
            color: white;
        }

        .expandable-header.active .detail-item-label {
            color: rgba(255, 255, 255, 0.9);
        }

        .expandable-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 1rem;
        }

        .expandable-title i {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .expandable-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--modern-text);
        }

        .expand-icon {
            font-size: 0.9rem;
            transition: transform 0.3s ease, color 0.2s ease;
            color: var(--modern-text-light);
            background: rgba(0, 0, 0, 0.05);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .expandable-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), padding 0.3s ease;
            background: #f8fafc;
            padding: 0 20px;
        }

        .expandable-content.active {
            max-height: 1000px;
            padding: 20px;
            border-top: 1px solid var(--modern-border);
        }

        /* Scrollable Attendance Details */
        .attendance-details-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .attendance-details-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .attendance-details-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .attendance-details-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .attendance-details-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .section-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--modern-border);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            color: var(--modern-text);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .section-header i {
            color: var(--modern-primary);
            font-size: 1.1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .detail-item:hover {
            border-color: var(--modern-border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item-label {
            color: var(--modern-text-light);
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-item-value {
            font-weight: 600;
            color: var(--modern-text);
            font-size: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed var(--modern-border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row.total {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            border: 1px solid #bbf7d0;
        }

        .detail-row.total .label {
            font-weight: 700;
            color: #166534;
        }

        .detail-row.total .value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #15803d;
        }

        .detail-row.total-deduction {
            background: #fef2f2;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            border: 1px solid #fecaca;
        }

        .detail-row.total-deduction .label {
            font-weight: 700;
            color: #991b1b;
        }

        .detail-row.total-deduction .value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #b91c1c;
        }

        .no-data-message {
            text-align: center;
            padding: 30px;
            color: var(--modern-text-light);
            font-style: italic;
            font-size: 0.9rem;
            background: white;
            border-radius: 8px;
        }

        .period-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 24px;
            box-shadow: 0 8px 20px -6px rgba(99, 102, 241, 0.4);
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-box {
            background: white;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--modern-border);
        }

        .stat-box .count {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
            display: block;
        }

        .stat-box .text {
            font-size: 0.75rem;
            color: var(--modern-text-light);
            text-transform: uppercase;
            font-weight: 600;
        }

        .period-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .attendance-stat {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 3px solid #6366f1;
        }

        .attendance-stat-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .stat-highlight {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            color: #92400e;
        }

        .stat-highlight.danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .stat-highlight.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-money-bill-wave"></i> Payroll Management</h1>
                        <p class="page-subtitle">Manage monthly and daily employee payroll</p>
                    </div>
                    <div class="d-flex gap-2">
                        @can('hr.payroll.create')
                            <div class="dropdown">
                                <button class="btn btn-create dropdown-toggle" type="button" id="generateDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-plus-circle"></i> Generate Payroll
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="generateDropdown">
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)" id="generateMonthlyBtn">
                                            <i class="fa fa-calendar-alt me-2 text-primary"></i> Generate Monthly Payroll
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)" id="generateDailyBtn">
                                            <i class="fa fa-calendar-day me-2 text-success"></i> Generate Daily Payroll
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)" id="generateBtn">
                                            <i class="fa fa-hand-holding-usd me-2 text-warning"></i> Manual / Single Entry
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @endcan
                    </div>
                </div>

                <!-- Stats Row -->
                @php
                    $generatedCount = \App\Models\Hr\Payroll::where('status', 'generated')->count();
                    $reviewedCount = \App\Models\Hr\Payroll::where('status', 'reviewed')->count();
                    $paidCount = \App\Models\Hr\Payroll::where('status', 'paid')->count();
                    $totalNet = \App\Models\Hr\Payroll::sum('net_salary');
                    $monthlyCount = \App\Models\Hr\Payroll::monthly()->count();
                    $dailyCount = \App\Models\Hr\Payroll::daily()->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                        <div class="stat-value">{{ $payrolls->total() }}</div>
                        <div class="stat-label">Total Payrolls</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-clock"></i></div>
                        <div class="stat-value">{{ $generatedCount }}</div>
                        <div class="stat-label">Generated</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-eye"></i></div>
                        <div class="stat-value">{{ $reviewedCount }}</div>
                        <div class="stat-label">Reviewed</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-value">{{ $paidCount }}</div>
                        <div class="stat-label">Paid</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-coins"></i></div>
                        <div class="stat-value">{{ number_format($totalNet, 0) }}</div>
                        <div class="stat-label">Total Amount</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="payroll-tabs">
                    <a href="{{ route('hr.payroll.index') }}"
                        class="payroll-tab {{ ($activeTab ?? 'all') === 'all' ? 'active' : '' }}">
                        <i class="fa fa-list"></i> All Payrolls ({{ $monthlyCount + $dailyCount }})
                    </a>
                    <a href="{{ route('hr.payroll.monthly') }}"
                        class="payroll-tab {{ ($activeTab ?? '') === 'monthly' ? 'active' : '' }}">
                        <i class="fa fa-calendar-alt"></i> Monthly ({{ $monthlyCount }})
                    </a>
                    <a href="{{ route('hr.payroll.daily') }}"
                        class="payroll-tab {{ ($activeTab ?? '') === 'daily' ? 'active' : '' }}">
                        <i class="fa fa-calendar-day"></i> Daily ({{ $dailyCount }})
                    </a>
                </div>

                <!-- Payrolls Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="payrollSearch" placeholder="Search by employee name...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm active" data-status="all">All</button>
                                <button class="btn btn-outline-warning btn-sm" data-status="generated">Generated</button>
                                <button class="btn btn-outline-info btn-sm" data-status="reviewed">Reviewed</button>
                                <button class="btn btn-outline-success btn-sm" data-status="paid">Paid</button>
                            </div>
                        </div>
                        <span class="text-muted small" id="payrollCount">{{ $payrolls->total() }} payrolls</span>
                    </div>

                    <div class="hr-grid" id="payrollGrid">
                        @forelse($payrolls as $payroll)
                            <div class="payroll-card {{ $payroll->payroll_type }}" data-id="{{ $payroll->id }}"
                                data-name="{{ strtolower($payroll->employee->full_name ?? '') }}"
                                data-status="{{ $payroll->status }}" data-type="{{ $payroll->payroll_type }}">

                                <div class="hr-item-header">
                                    <div class="d-flex align-items-center">
                                        <div class="hr-avatar"
                                            style="background: {{ $payroll->payroll_type === 'monthly' ? 'linear-gradient(135deg, #3b82f6, #2563eb)' : 'linear-gradient(135deg, #22c55e, #16a34a)' }};">
                                            {{ strtoupper(substr($payroll->employee->first_name ?? 'U', 0, 1) . substr($payroll->employee->last_name ?? 'N', 0, 1)) }}
                                        </div>
                                        <div class="hr-item-info">
                                            <h4 class="hr-item-name">{{ $payroll->employee->full_name ?? 'Unknown' }}</h4>
                                            <div class="hr-item-subtitle">
                                                {{ $payroll->employee->designation->name ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <span class="payroll-type-badge {{ $payroll->payroll_type }}">
                                            {{ ucfirst($payroll->payroll_type) }}
                                        </span>
                                        <span class="month-badge">{{ $payroll->month }}</span>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="breakdown-row">
                                        <span class="label">Gross Salary</span>
                                        <span class="value">Rs. {{ number_format($payroll->gross_salary, 2) }}</span>
                                    </div>
                                    <div class="breakdown-row">
                                        <span class="label">Total Deductions</span>
                                        <span class="value text-danger">- Rs.
                                            {{ number_format($payroll->total_deductions, 2) }}</span>
                                    </div>
                                    @if ($payroll->carried_forward_to_next > 0)
                                        <div class="breakdown-row">
                                            <span class="label text-warning">Carried Fwd (Next)</span>
                                            <span class="value text-warning">Rs.
                                                {{ number_format($payroll->carried_forward_to_next, 2) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="salary-display {{ $payroll->payroll_type }}">
                                    <div class="label">Net Payable</div>
                                    <div class="amount">Rs. {{ number_format($payroll->net_salary, 2) }}</div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="status-badge {{ $payroll->status }}">
                                        @if ($payroll->status === 'generated')
                                            <i class="fa fa-clock"></i>
                                        @elseif($payroll->status === 'reviewed')
                                            <i class="fa fa-eye"></i>
                                        @else
                                            <i class="fa fa-check"></i>
                                        @endif
                                        {{ ucfirst($payroll->status) }}
                                    </span>
                                    @if ($payroll->auto_generated)
                                        <small class="text-muted"><i class="fa fa-robot"></i> Auto-generated</small>
                                    @endif
                                </div>

                                <div class="payroll-actions">
                                    @can('hr.payroll.view')
                                        <button class="btn btn-view view-details-btn" title="View Details"
                                            data-id="{{ $payroll->id }}">
                                            <i class="fa fa-eye"></i> Details
                                        </button>
                                    @endcan

                                    @if ($payroll->canEdit())
                                        @can('hr.payroll.edit')
                                            <button class="btn btn-edit edit-payroll-btn" title="Edit"
                                                data-id="{{ $payroll->id }}">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        @endcan
                                    @endif

                                    @if ($payroll->canMarkReviewed())
                                        @can('hr.payroll.edit')
                                            <button class="btn btn-info mark-reviewed-btn" title="Mark Reviewed"
                                                data-id="{{ $payroll->id }}">
                                                <i class="fa fa-eye"></i> Review
                                            </button>
                                        @endcan
                                    @endif

                                    @if ($payroll->canMarkPaid())
                                        @can('hr.payroll.edit')
                                            <button class="btn btn-success mark-paid-btn" title="Mark Paid"
                                                data-id="{{ $payroll->id }}">
                                                <i class="fa fa-check"></i> Pay
                                            </button>
                                        @endcan
                                    @endif

                                    @can('hr.payroll.delete')
                                        @if ($payroll->status !== 'paid')
                                            <button class="btn btn-delete delete-btn" title="Delete"
                                                data-id="{{ $payroll->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-money-bill-wave"></i>
                                <p>No payrolls generated yet.</p>
                                <p class="text-muted small">Click "Generate Payroll" to create payroll entries.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-top">
                        {{ $payrolls->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Payroll Modal -->
    <div class="modal fade" id="generatePayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-plus"></i>
                        <span>Generate Payroll</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="generatePayrollForm" action="{{ route('hr.payroll.generate') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-bookmark"></i> Payroll Type</label>
                            <select name="payroll_type" class="form-select" required id="payrollTypeSelect">
                                <option value="">Select Type</option>
                                <option value="monthly">Monthly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-user"></i> Employee</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group-modern" id="monthField">
                            <label class="form-label"><i class="fa fa-calendar"></i> Month</label>
                            <input type="month" name="month" class="form-control" required>
                        </div>
                        <div class="form-group-modern" id="dateField" style="display: none;">
                            <label class="form-label"><i class="fa fa-calendar-day"></i> Date</label>
                            <input type="date" name="date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                            <i class="fa fa-check"></i>
                            <span>Generate</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generate Daily Payrolls Modal -->
    <div class="modal fade" id="generateDailyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #10b981, #059669) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-calendar-day"></i>
                        <span>Generate Daily Payrolls</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="generateDailyForm" action="{{ route('hr.payroll.generate-daily') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            This will generate payroll for all active daily-wage employees for the selected date.
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar-day"></i> Date</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fa fa-check"></i>
                            <span>Generate All</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generate Monthly Payrolls Modal -->
    <div class="modal fade" id="generateMonthlyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #3b82f6, #2563eb) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-calendar-alt"></i>
                        <span>Generate Monthly Payrolls</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="generateMonthlyForm" action="{{ route('hr.payroll.generate-monthly') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            This will generate monthly payroll for all active salaried employees for the selected month.
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar"></i> Month</label>
                            <input type="month" name="month" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="fa fa-check"></i>
                            <span>Generate All</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #6366f1, #4f46e5) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-file-invoice"></i>
                        <span>Payroll Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payroll Modal -->
    <div class="modal fade" id="editPayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #f59e0b, #d97706) !important;">
                    <h5 class="modal-title">
                        <i class="fa fa-edit"></i>
                        <span>Edit Payroll</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPayrollForm" method="POST" data-ajax-validate="true">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-plus-circle"></i> Manual Allowances</label>
                            <input type="number" name="manual_allowances" class="form-control" step="0.01"
                                min="0" value="0">
                            <small class="text-muted">Additional allowances not in salary structure</small>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-minus-circle"></i> Manual Deductions</label>
                            <input type="number" name="manual_deductions" class="form-control" step="0.01"
                                min="0" value="0">
                            <small class="text-muted">Additional deductions not in salary structure</small>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-sticky-note"></i> Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add notes or comments..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fa fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Tab switching
            $('.payroll-tab').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                var tab = $(this).data('tab');

                $('.payroll-card').each(function() {
                    if (tab === 'all') {
                        $(this).show();
                    } else {
                        $(this).toggle($(this).data('type') === tab);
                    }
                });
                updateCount();
            });

            // Status filter
            $('[data-status]').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                var status = $(this).data('status');

                $('.payroll-card').each(function() {
                    if (status === 'all') {
                        $(this).show();
                    } else {
                        $(this).toggle($(this).data('status') === status);
                    }
                });
                updateCount();
            });

            // Search
            $('#payrollSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.payroll-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                updateCount();
            });

            function updateCount() {
                $('#payrollCount').text($('.payroll-card:visible').length + ' payrolls');
            }

            // Generate payroll modal
            $('#generateBtn').click(function() {
                $('#generatePayrollForm')[0].reset();
                $('#generatePayrollModal').modal('show');
            });

            // Generate monthly modal
            $('#generateMonthlyBtn').click(function() {
                $('#generateMonthlyForm')[0].reset();
                $('#generateMonthlyModal').modal('show');
            });

            // Generate daily modal
            $('#generateDailyBtn').click(function() {
                $('#generateDailyForm')[0].reset();
                $('#generateDailyModal').modal('show');
            });

            // Payroll type change
            $('#payrollTypeSelect').change(function() {
                if ($(this).val() === 'daily') {
                    $('#monthField').hide().find('input').prop('required', false);
                    $('#dateField').show().find('input').prop('required', true);
                } else {
                    $('#monthField').show().find('input').prop('required', true);
                    $('#dateField').hide().find('input').prop('required', false);
                }
            });

            // View details
            $(document).on('click', '.view-details-btn', function() {
                var id = $(this).data('id');
                $('#detailsModal').modal('show');

                $.ajax({
                    url: '/hr/payroll/' + id + '/details',
                    type: 'GET',
                    success: function(response) {
                        renderDetails(response);
                    },
                    error: function() {
                        $('#detailsContent').html(
                            '<div class="alert alert-danger">Failed to load details.</div>');
                    }
                });
            });

            function renderDetails(data) {
                // Compact Header with Period & Employee
                var headerHtml = `
                    <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center gap-3">
                            <div style="background: #e0e7ff; color: #4338ca; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                ${data.payroll.employee.first_name.charAt(0)}${data.payroll.employee.last_name.charAt(0)}
                            </div>
                            <div>
                                <h6 style="margin:0; font-weight:700;">${data.payroll.employee.first_name} ${data.payroll.employee.last_name}</h6>
                                <div class="text-muted small">${data.payroll.employee.designation ? data.payroll.employee.designation.name : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="text-end d-flex gap-2">
                             <div class="badge bg-white text-dark border px-3 py-2 d-flex align-items-center" style="font-weight: 600;">
                                <i class="fa fa-tag me-2 text-muted"></i>
                                ${data.payroll.payroll_type.charAt(0).toUpperCase() + data.payroll.payroll_type.slice(1)} Payroll
                            </div>
                             <div class="period-badge mb-0 py-1 px-3" style="font-size: 0.85rem;">
                                <i class="fa fa-calendar-alt me-1"></i> ${data.payroll_period.formatted}
                            </div>
                        </div>
                    </div>
                `;

                var html = `
                    ${headerHtml}
                    
                    <div class="row g-3">
                        <!-- Left Column: Earnings -->
                        <div class="col-md-6">
                            <div class="section-card h-100 mb-0">
                                <div class="section-header mb-3 py-2 text-primary border-primary border-opacity-25" style="border-bottom-width: 2px;">
                                    <i class="fa fa-wallet"></i> Earnings
                                </div>
                                
                                <div class="detail-row py-2">
                                    <span class="label">Basic Salary</span>
                                    <span class="value fw-bold">Rs. ${parseFloat(data.breakdown.earnings.basic_salary).toFixed(2)}</span>
                                </div>
                                
                                <div class="expandable-section allowances-section my-2 shadow-sm border-0 bg-light">
                                    <div class="expandable-header py-2 px-3" onclick="toggleExpandable(this)" style="background: transparent;">
                                        <div class="expandable-title small">Allowances</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="expandable-value small">Rs. ${parseFloat(data.breakdown.earnings.allowances).toFixed(2)}</span>
                                            <i class="fa fa-chevron-down expand-icon" style="font-size: 0.7rem;"></i>
                                        </div>
                                    </div>
                                    <div class="expandable-content">
                                        ${data.allowance_details.length > 0 ? 
                                            data.allowance_details.map(allowance => `
                                                                                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                                                                    <small class="text-muted">${allowance.name}</small>
                                                                                    <small class="fw-bold">Rs. ${parseFloat(allowance.amount).toFixed(2)}</small>
                                                                                </div>
                                                                            `).join('') 
                                            : '<div class="text-center small text-muted py-1">- None -</div>'
                                        }
                                    </div>
                                </div>
                                
                                <div class="detail-row py-2">
                                    <span class="label">Manual Allowances</span>
                                    <span class="value">Rs. ${parseFloat(data.breakdown.earnings.manual_allowances).toFixed(2)}</span>
                                </div>
                                <div class="detail-row total mt-auto bg-green-50 border-green-200">
                                    <span class="label text-success">Total Earnings</span>
                                    <span class="value text-success">Rs. ${parseFloat(data.breakdown.earnings.total).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Deductions -->
                        <div class="col-md-6">
                            <div class="section-card h-100 mb-0">
                                <div class="section-header mb-3 py-2 text-danger border-danger border-opacity-25" style="border-bottom-width: 2px;">
                                    <i class="fa fa-file-invoice-dollar"></i> Deductions
                                </div>
                                
                                <div class="detail-row py-2">
                                    <span class="label">Fixed Deductions</span>
                                    <span class="value fw-bold">Rs. ${parseFloat(data.breakdown.deductions.fixed_deductions).toFixed(2)}</span>
                                </div>
                                
                                <div class="expandable-section attendance-section my-2 shadow-sm border-0 bg-light">
                                    <div class="expandable-header py-2 px-3" onclick="toggleExpandable(this)" style="background: transparent;">
                                        <div class="expandable-title small">Attendance Deductions</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="expandable-value small">Rs. ${parseFloat(data.breakdown.deductions.attendance_deductions).toFixed(2)}</span>
                                            <i class="fa fa-chevron-down expand-icon" style="font-size: 0.7rem;"></i>
                                        </div>
                                    </div>
                                    <div class="expandable-content">
                                        ${data.payroll.payroll_type === 'daily' ? `
                                                                <!-- Daily Payroll View -->
                                                                <div class="py-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                                        <span class="text-muted small fw-bold text-uppercase">Total Deduction</span>
                                                                        <span class="text-danger fw-bold fs-6">Rs. ${parseFloat(data.attendance_breakdown.total_deduction || 0).toFixed(2)}</span>
                                                                    </div>
                                                                    
                                                                    ${data.attendance_breakdown.has_data ? `
                                                    <div class="d-flex justify-content-between gap-3">
                                                        <div class="text-center p-2 rounded bg-white border ${data.attendance_breakdown.is_late ? 'border-warning apple-glow-warning' : 'border-light'} flex-fill">
                                                            <div class="small text-muted mb-1">Check In</div>
                                                            ${data.attendance_breakdown.late_deduction_amount > 0 ? `
                                                                                <div class="text-danger fw-bold small mb-1">-Rs. ${parseFloat(data.attendance_breakdown.late_deduction_amount).toFixed(2)}</div>
                                                                            ` : ''}
                                                            <div class="fw-bold ${data.attendance_breakdown.is_late ? 'text-warning' : 'text-dark'}">
                                                                ${data.attendance_breakdown.check_in || '--:--'}
                                                            </div>
                                                            ${data.attendance_breakdown.is_late ? `
                                                                                <div class="badge bg-warning text-dark mt-1" style="font-size: 0.7rem;">Late (${data.attendance_breakdown.late_minutes}m)</div>
                                                                            ` : ''}
                                                        </div>
                                                        
                                                        <div class="text-center p-2 rounded bg-white border ${data.attendance_breakdown.is_early_out ? 'border-info apple-glow-info' : 'border-light'} flex-fill">
                                                            <div class="small text-muted mb-1">Check Out</div>
                                                            ${data.attendance_breakdown.early_deduction_amount > 0 ? `
                                                                                <div class="text-danger fw-bold small mb-1">-Rs. ${parseFloat(data.attendance_breakdown.early_deduction_amount).toFixed(2)}</div>
                                                                            ` : ''}
                                                            <div class="fw-bold ${data.attendance_breakdown.is_early_out ? 'text-info' : 'text-dark'}">
                                                                ${data.attendance_breakdown.check_out || '--:--'}
                                                            </div>
                                                            ${data.attendance_breakdown.is_early_out ? `
                                                                                <div class="badge bg-info text-white mt-1" style="font-size: 0.7rem;">Early (${data.attendance_breakdown.early_checkout_minutes}m)</div>
                                                                            ` : ''}
                                                        </div>
                                                    </div>
                                                ` : `
                                                    <div class="text-center text-muted small py-2">
                                                        <i class="fa fa-exclamation-circle"></i> No attendance record
                                                    </div>
                                                `}
                                                                </div>
                                                            ` : `
                                                                <!-- Monthly Payroll View -->
                                                                <!-- Summary Badges -->
                                                                <div class="d-flex flex-wrap gap-2 justify-content-center py-2 border-bottom mb-2">
                                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                                        Present: <b class="text-success">${data.attendance_breakdown.days_present || 0}</b>
                                                                     </span>
                                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                                        Absent: <b class="text-danger">${data.attendance_breakdown.days_absent || 0}</b>
                                                                     </span>
                                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                                        Late: <b class="text-warning">${data.attendance_breakdown.late_check_ins || 0}</b>
                                                                     </span>
                                                                     <span class="badge bg-white text-muted border border-light shadow-sm">
                                                                        Early Out: <b class="text-info">${data.attendance_breakdown.early_check_outs || 0}</b>
                                                                     </span>
                                                                </div>
                                                                
                                                                ${!data.attendance_breakdown.has_data ? `
                                                <div class="alert alert-warning py-2 mb-0 small text-center">
                                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                                    ${data.attendance_breakdown.data_message || 'Attendance data incomplete for this period'}
                                                </div>
                                            ` : `
                                                <!-- Detailed Records with Scroll -->
                                                <div class="attendance-details-scroll" style="max-height: 200px; overflow-y: auto;">
                                                    
                                                    ${(data.attendance_breakdown.absent_records && data.attendance_breakdown.absent_records.length > 0) ? `
                                                                            <div class="mb-3">
                                                                                <div class="small fw-bold text-danger mb-2 px-2">
                                                                                    <i class="fa fa-times-circle me-1"></i> Absent Days (${data.attendance_breakdown.absent_records.length})
                                                                                </div>
                                                                                ${data.attendance_breakdown.absent_records.map(record => `
                                                                <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                    <div>
                                                                        <span class="text-muted">${record.date}</span>
                                                                        <span class="badge bg-light text-muted ms-1">${record.day}</span>
                                                                    </div>
                                                                    <span class="text-danger fw-bold">-Rs. ${parseFloat(record.deduction).toFixed(2)}</span>
                                                                </div>
                                                            `).join('')}
                                                                            </div>
                                                                        ` : ''}
                                                    
                                                    ${(data.attendance_breakdown.late_records && data.attendance_breakdown.late_records.length > 0) ? `
                                                                            <div class="mb-3">
                                                                                <div class="small fw-bold text-warning mb-2 px-2">
                                                                                    <i class="fa fa-clock me-1"></i> Late Check-ins (${data.attendance_breakdown.late_records.length})
                                                                                </div>
                                                                                ${data.attendance_breakdown.late_records.map(record => `
                                                                <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                    <div>
                                                                        <span class="text-muted">${record.date}</span>
                                                                        <span class="badge bg-warning text-dark ms-1">${record.check_in}</span>
                                                                        <span class="text-muted small ms-1">(${record.late_minutes} min late)</span>
                                                                    </div>
                                                                    <span class="text-danger fw-bold">-Rs. ${parseFloat(record.deduction).toFixed(2)}</span>
                                                                </div>
                                                            `).join('')}
                                                                            </div>
                                                                        ` : ''}
                                                    
                                                    ${(data.attendance_breakdown.early_records && data.attendance_breakdown.early_records.length > 0) ? `
                                                                            <div class="mb-2">
                                                                                <div class="small fw-bold text-info mb-2 px-2">
                                                                                    <i class="fa fa-sign-out-alt me-1"></i> Early Check-outs (${data.attendance_breakdown.early_records.length})
                                                                                </div>
                                                                                ${data.attendance_breakdown.early_records.map(record => `
                                                                <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom" style="font-size: 0.8rem;">
                                                                    <div>
                                                                        <span class="text-muted">${record.date}</span>
                                                                        <span class="badge bg-info text-white ms-1">${record.check_out}</span>
                                                                        <span class="text-muted small ms-1">(${record.early_minutes} min early)</span>
                                                                    </div>
                                                                    <span class="text-danger fw-bold">-Rs. ${parseFloat(record.deduction).toFixed(2)}</span>
                                                                </div>
                                                            `).join('')}
                                                                            </div>
                                                                        ` : ''}
                                                    
                                                    ${(!data.attendance_breakdown.absent_records?.length && !data.attendance_breakdown.late_records?.length && !data.attendance_breakdown.early_records?.length) ? `
                                                                            <div class="text-center text-muted py-2 small">
                                                                                <i class="fa fa-check-circle text-success me-1"></i> No attendance issues this period
                                                                            </div>
                                                                        ` : ''}
                                                </div>
                                            `}
                                                            `}
                                    </div>
                                </div>
                                
                                ${data.breakdown.deductions.carried_forward > 0 ? `
                                                                    <div class="detail-row py-2" style="background: #fff1f2; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; border: 1px dashed #fecaca;">
                                                                        <div class="d-flex justify-content-between w-100">
                                                                            <span class="label text-danger small fw-bold">Carried Forward (From Prev)</span>
                                                                            <span class="value text-danger small fw-bold">Rs. ${parseFloat(data.breakdown.deductions.carried_forward).toFixed(2)}</span>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}

                                <div class="detail-row py-2">
                                    <div class="d-flex justify-content-between w-100">
                                        <span class="label small text-muted">Carry Fwd (To Next)</span>
                                        <span class="value text-warning small">Rs. ${parseFloat(data.breakdown.deductions.carried_forward_to_next || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                                 <div class="detail-row py-2">
                                    <span class="label">Manual Deductions</span>
                                    <span class="value">Rs. ${parseFloat(data.breakdown.deductions.manual_deductions).toFixed(2)}</span>
                                </div>
                                <div class="detail-row total-deduction mt-auto">
                                    <span class="label">Total Deductions</span>
                                    <span class="value">Rs. ${parseFloat(data.breakdown.deductions.total).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Payable & Footer Notes -->
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                             <div class="net-payable py-3 px-4 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 12px;">
                                <div class="text-start">
                                    <div class="label text-white-50 mb-0 small">Net Payable Amount</div>
                                    <div class="small text-white-50" style="font-size: 0.8rem;">${data.payroll.status.toUpperCase()}</div>
                                </div>
                                <div class="amount mb-0" style="font-size: 2rem;">Rs. ${parseFloat(data.breakdown.net_payable).toFixed(2)}</div>
                            </div>
                        </div>
                        ${data.payroll.notes ? `
                                                                <div class="col-12">
                                                                    <div class="alert alert-warning mb-0 py-2 fs-7 small d-flex align-items-center">
                                                                        <i class="fa fa-sticky-note me-2 text-warning"></i> 
                                                                        <span class="fst-italic text-truncate">${data.payroll.notes}</span>
                                                                    </div>
                                                                </div>
                                                            ` : ''}
                    </div>
                `;

                $('#detailsContent').html(html);
            }

            // Toggle expandable sections
            window.toggleExpandable = function(header) {
                const content = $(header).next('.expandable-content');
                const isActive = $(header).hasClass('active');

                if (isActive) {
                    $(header).removeClass('active');
                    content.removeClass('active');
                } else {
                    $(header).addClass('active');
                    content.addClass('active');
                }
            };

            // Edit payroll
            $(document).on('click', '.edit-payroll-btn', function() {
                var id = $(this).data('id');
                $('#editPayrollForm').attr('action', '/hr/payroll/' + id);
                $('#editPayrollModal').modal('show');
            });

            // Mark reviewed
            $(document).on('click', '.mark-reviewed-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Mark as Reviewed?',
                    text: 'This will update the payroll status to reviewed.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'Yes, Mark Reviewed'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/payroll/' + id + '/mark-reviewed',
                            type: 'PATCH',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Success', response.success, 'success')
                                        .then(() => location.reload());
                                }
                            }
                        });
                    }
                });
            });

            // Mark paid
            $(document).on('click', '.mark-paid-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Mark as Paid?',
                    text: 'This will mark the payroll as paid and cannot be undone.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    confirmButtonText: 'Yes, Mark Paid'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/payroll/' + id + '/mark-paid',
                            type: 'PATCH',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Success', response.success, 'success')
                                        .then(() => location.reload());
                                }
                            }
                        });
                    }
                });
            });

            // Delete payroll
            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Payroll?',
                    text: 'This cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/payroll/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', response.success, 'success')
                                        .then(() => location.reload());
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
