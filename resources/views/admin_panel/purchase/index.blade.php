@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Modern Purchase Registry UI Styles */
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

        /* GRN Badge */
        .grn-badge {
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
            font-size: 0.95rem;
            text-align: right;
        }

        .billing-due {
            font-size: 0.75rem;
            color: #e53e3e;
            text-align: right;
            font-weight: 500;
        }

        /* Status Badges */
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

        .status-approved { color: #38a169; }
        .status-approved .status-dot { background-color: #38a169; }
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

        /* Batch Info Boxes */
        .batch-box {
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 2px;
            display: inline-block;
            width: 100%;
        }
        .batch-lot { background: #edf2f7; color: #4a5568; }
        .batch-mfg { background: #f0fff4; color: #2f855a; }
        .batch-exp { background: #fff5f5; color: #c53030; }

        .qty-label {
            font-weight: 700;
            font-size: 1rem;
            color: #2d3748;
        }
        .qty-subtext {
            font-size: 0.7rem;
            color: #a0aec0;
        }
    </style>

    <div class="container-fluid registry-container">
        <div class="registry-header">
            <div class="registry-title">
                <i class="fas fa-bars text-primary"></i>
                Purchase Registry
            </div>
            <div class="d-flex gap-2">
                @can('purchases.create')
                    <a href="{{ route('add_purchase') }}" class="top-action-btn btn-primary-custom">
                        <i class="fas fa-plus"></i> Add Purchase
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
                <table class="table registry-table" id="purchase-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#ID</th>
                            <th>Verification Date</th>
                            <th>GRN Number</th>
                            <th>Vendor / Company Entity</th>
                            <th>Product Details</th>
                            <th>Total Qty</th>
                            <th class="text-end">Billing Detail</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($Purchase as $purchase)
                            @php
                                // Product Details Formatting
                                $itemDetails = [];
                                if ($purchase->items && $purchase->items->count() > 0) {
                                    foreach ($purchase->items as $item) {
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

                                // Total Qty Calculation
                                $totalQty = $purchase->items->sum('qty');
                                $totalQtyFormatted = (float) $totalQty == (int) $totalQty ? (int) $totalQty : number_format($totalQty, 2);

                                // Status Logic
                                $statusClass = 'status-draft';
                                $statusLabel = 'Draft';
                                if ($purchase->status_purchase === 'approved' || $purchase->status_purchase === 'posted') {
                                    $statusClass = 'status-approved';
                                    $statusLabel = 'Approved';
                                } elseif ($purchase->status_purchase === 'Returned' || $purchase->status_purchase === 'returned') {
                                    $statusClass = 'status-returned';
                                    $statusLabel = 'Returned';
                                }

                                $displayDue = $purchase->total_returned > 0 ? $purchase->updated_due_amount : $purchase->due_amount;
                            @endphp
                            <tr>
                                <td class="id-cell">#{{ $purchase->id }}</td>
                                <td>
                                    <i class="far fa-calendar-check text-muted me-2"></i>
                                    {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') }}
                                </td>
                                <td>
                                    <span class="grn-badge">{{ $purchase->invoice_no }}</span>
                                    <div class="text-muted small mt-1">PO: {{ $purchase->reference ?? '000000' }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $purchase->vendor->name ?? 'N/A' }}</div>
                                    <div class="text-muted small"><i class="fas fa-building me-1"></i> Health Institution</div>
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
                                <td class="text-center">
                                    <div class="qty-label">{{ $totalQtyFormatted }}</div>
                                    <div class="qty-subtext">Total Pcs Received</div>
                                </td>
                                <td>
                                    <div class="billing-summary">
                                        GROSS: {{ number_format($purchase->net_amount + ($purchase->discount ?? 0) - ($purchase->extra_cost ?? 0), 2) }}<br>
                                        DISC: -{{ number_format($purchase->discount, 2) }}<br>
                                        TAX: +0.00
                                    </div>
                                    <div class="billing-total">
                                        {{ number_format($purchase->total_returned > 0 ? $purchase->updated_net_amount : $purchase->net_amount, 2) }}
                                    </div>
                                    @if($displayDue > 0)
                                        <div class="billing-due">Due: {{ number_format($displayDue, 2) }}</div>
                                    @endif
                                    <div class="status-pill {{ $statusClass }}">
                                        <div class="status-dot"></div> {{ $statusLabel }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="btn-manage" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-h"></i> Manage
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            @can('purchases.edit')
                                                <li><a class="dropdown-item py-2" href="{{ route('purchase.edit', $purchase->id) }}"><i class="fas fa-edit text-primary me-2"></i> Edit Purchase</a></li>
                                            @endcan
                                            
                                            @if ($purchase->status_purchase == 'draft')
                                                <li><a class="dropdown-item py-2 text-success confirm-purchase-btn" href="{{ route('purchase.confirm', $purchase->id) }}"><i class="fas fa-check-circle me-2"></i> Confirm Purchase</a></li>
                                            @endif

                                            @if ($purchase->status_purchase != 'draft')
                                                <li><a class="dropdown-item py-2" href="{{ route('purchase.invoice', $purchase->id) }}" target="_blank"><i class="fas fa-file-invoice text-info me-2"></i> View Invoice</a></li>
                                                <li><a class="dropdown-item py-2" href="{{ route('purchase.receipt', $purchase->id) }}" target="_blank"><i class="fas fa-receipt text-secondary me-2"></i> View Receipt</a></li>
                                                <li><a class="dropdown-item py-2 text-warning" href="{{ route('purchase.return.show', $purchase->id) }}"><i class="fas fa-undo me-2"></i> Return</a></li>
                                            @endif

                                            @if ($purchase->status_purchase == 'draft')
                                                @can('purchases.delete')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="dropdown-item py-2 text-danger delete-btn">
                                                                <i class="fas fa-trash-alt me-2"></i> Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endcan
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

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Confirm Purchase Action
            $(document).on('click', '.confirm-purchase-btn', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                Swal.fire({
                    title: "Confirm Purchase?",
                    text: "This will finalize the purchase, update stocks, and post ledgers.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#28a745",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, Confirm it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            method: "GET",
                            success: function(response) {
                                if (response.invoice_url) { window.open(response.invoice_url, '_blank'); }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => { window.location.reload(); });
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });

            // Delete Confirmation
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                let form = $(this).closest("form");
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) { form.submit(); }
                });
            });
        });
    </script>
@endsection
