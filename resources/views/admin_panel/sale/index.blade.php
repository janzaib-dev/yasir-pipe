@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Modern Registry UI Styles */
        .registry-container {
            background-color: #f8f9fc;
            padding: 20px;
            border-radius: 12px;
        }

        .registry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .registry-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .registry-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: none;
        }

        .registry-table thead th {
            background: #fdfdfd;
            border-bottom: 1px solid #edf2f7;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 16px;
            vertical-align: middle;
        }

        .registry-table tbody td {
            padding: 20px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
            font-size: 0.875rem;
        }

        /* ID Style */
        .id-cell {
            font-weight: 600;
            color: #4a5568;
        }

        /* Reference Badge */
        .ref-badge {
            background-color: #f0fff4;
            color: #38a169;
            border: 1px solid #c6f6d5;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-block;
        }

        /* Billing Detail Layout */
        .billing-summary {
            font-size: 0.7rem;
            color: #a0aec0;
            line-height: 1.4;
            text-align: right;
            margin-bottom: 8px;
        }

        .billing-total {
            font-weight: 700;
            color: #2d3748;
            font-size: 1rem;
            text-align: right;
        }

        .status-pill {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-posted { color: #38a169; }
        .status-posted .status-dot { background-color: #38a169; }
        .status-draft { color: #d69e2e; }
        .status-draft .status-dot { background-color: #d69e2e; }
        .status-returned { color: #e53e3e; }
        .status-returned .status-dot { background-color: #e53e3e; }

        /* Action Button */
        .btn-manage {
            background-color: #ebf4ff;
            color: #3182ce;
            border: 1px solid #bee3f8;
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-manage:hover {
            background-color: #bee3f8;
            color: #2c5282;
        }

        /* Top Actions */
        .top-action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.2s;
        }

        .top-action-btn:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .btn-primary-custom {
            background-color: #4c51bf;
            color: white;
            border: none;
        }

        .btn-primary-custom:hover {
            background-color: #434190;
            color: white;
        }
    </style>

    <div class="container-fluid registry-container">
        <div class="registry-header">
            <div class="registry-title">
                <i class="fas fa-bars text-primary"></i>
                Sales Registry
            </div>
            <div class="d-flex gap-2">
                @can('sales.create')
                    <a href="{{ route('sale.add') }}" class="top-action-btn btn-primary-custom">
                        <i class="fas fa-plus"></i> Add Sale
                    </a>
                @endcan
                <a href="#" class="top-action-btn">
                    <i class="fas fa-file-excel text-success"></i> Excel
                </a>
                <a href="#" class="top-action-btn">
                    <i class="fas fa-print"></i> Print Registry
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-none bg-transparent">
            <div class="table-responsive">
                <table class="table registry-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#ID</th>
                            <th>Verification Date</th>
                            <th>Sale Ref #</th>
                            <th>Customer / Institution</th>
                            <th>Product Details</th>
                            <th class="text-end">Billing Detail</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            @php
                                // Product Details Formatting
                                $itemDetails = [];
                                if ($sale->items && $sale->items->count() > 0) {
                                    foreach ($sale->items as $item) {
                                        $pName = optional($item->product)->item_name ?? 'Unknown';
                                        $q = $item->qty;
                                        $unit = optional($item->package)->symbol ?? optional(optional($item->product)->unit)->name ?? 'pc';
                                        $qFormatted = (float) $q == (int) $q ? (int) $q : number_format($q, 2);
                                        $itemDetails[] = [
                                            'name' => $pName,
                                            'qty' => $qFormatted . ' ' . $unit,
                                        ];
                                    }
                                }

                                // Status Logic
                                $statusClass = 'status-draft';
                                $statusLabel = 'Draft';
                                if ($sale->sale_status === 'posted' || $sale->sale_status === null) {
                                    $statusClass = 'status-posted';
                                    $statusLabel = 'Posted';
                                } elseif ($sale->sale_status === 'returned' || $sale->sale_status == 1) {
                                    $statusClass = 'status-returned';
                                    $statusLabel = 'Returned';
                                }
                                
                                $hasPartialReturn = ($sale->returns && $sale->returns->count() > 0);
                            @endphp
                            <tr>
                                <td class="id-cell">#{{ $sale->id }}</td>
                                <td>
                                    <i class="far fa-calendar-check text-muted me-2"></i>
                                    {{ $sale->created_at->format('d-m-Y') }}
                                </td>
                                <td>
                                    <span class="ref-badge">{{ $sale->reference ?? 'REF-' . $sale->id }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ optional($sale->customer_relation)->customer_name ?? 'N/A' }}</div>
                                    <div class="text-muted small"><i class="fas fa-user-tag me-1"></i> Individual</div>
                                </td>
                                <td>
                                    @foreach ($itemDetails as $detail)
                                        <div class="mb-1">
                                            {{ $detail['name'] }} <span class="text-muted small">({{ $detail['qty'] }})</span>
                                        </div>
                                    @endforeach
                                    @if (empty($itemDetails))
                                        <div class="text-muted small italic">No products listed</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="billing-summary">
                                        GROSS: {{ number_format($sale->total_bill_amount > 0 ? $sale->total_bill_amount : (float) $sale->per_total, 2) }}<br>
                                        GST: 0.00<br>
                                        DISC: {{ number_format($sale->total_extradiscount, 2) }}
                                    </div>
                                    <div class="billing-total">
                                        {{ number_format($sale->total_net, 2) }}
                                    </div>
                                    <div class="status-pill {{ $statusClass }}">
                                        <div class="status-dot"></div> {{ $statusLabel }}
                                    </div>
                                    @if($hasPartialReturn)
                                        <div class="status-pill status-returned mt-1" style="font-size: 0.65rem;">
                                            <i class="fas fa-undo-alt me-1"></i> Partial Return
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="btn-manage" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-h"></i> Manage
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            @if ($sale->sale_status === 'draft' || $sale->sale_status === 'booked')
                                                <li><a class="dropdown-item py-2" href="{{ route('sales.edit', $sale->id) }}"><i class="fas fa-check-circle text-success me-2"></i> Confirm Sale</a></li>
                                            @endif
                                            <li><a class="dropdown-item py-2" href="{{ route('sales.invoice', $sale->id) }}" target="_blank"><i class="fas fa-file-invoice text-info me-2"></i> View Invoice</a></li>
                                            <li><a class="dropdown-item py-2" href="{{ route('sales.invoice', ['id' => $sale->id, 'type' => 'estimate']) }}" target="_blank"><i class="fas fa-file-alt text-secondary me-2"></i> View Estimate</a></li>
                                            <li><a class="dropdown-item py-2" href="{{ route('sales.dc', $sale->id) }}" target="_blank"><i class="fas fa-truck text-warning me-2"></i> View DC</a></li>
                                            <li><a class="dropdown-item py-2" href="{{ route('sales.dc_thermal', $sale->id) }}" target="_blank"><i class="fas fa-receipt text-muted me-2"></i> DC Thermal</a></li>
                                            <li><a class="dropdown-item py-2" href="{{ route('sales.receipt', $sale->id) }}" target="_blank"><i class="fas fa-receipt text-success me-2"></i> View Receipt</a></li>
                                            @if ($sale->sale_status !== 'returned')
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item py-2 text-danger" href="{{ route('sale.return.show', $sale->id) }}"><i class="fas fa-undo me-2"></i> Return Sale</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
