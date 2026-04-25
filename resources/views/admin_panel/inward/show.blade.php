@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Print Settings */
    @media print {
        /* Hide navbar and action buttons */
        .navbar,
        .btn,
        .btn-group,
        .border-bottom.pb-3,
        .footer {
            display: none !important;
        }

        /* Remove background colors and shadows for clean print */
        .card,
        .border,
        .shadow-sm,
        .table,
        .table-bordered,
        .table-striped {
            box-shadow: none !important;
            border-color: #000 !important;
        }

        /* Ensure text color is black */
        body, .text-muted, .fw-bold, .badge {
            color: #000 !important;
            background: transparent !important;
        }

        /* Page margins for print */
        @page {
            margin: 20mm;
        }

        /* Ensure table looks neat */
        table th, table td {
            border: 1px solid #000 !important;
            padding: 6px !important;
        }
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    {{-- Header Section --}}
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 no-print">
                        <div>
                            <h2 class="fw-bold mb-1">Inward Gatepass</h2>
                            <small class="text-muted">Gatepass #{{ $gatepass->id }}</small>
                        </div>
                        <div>
                            <a href="{{ route('InwardGatepass.home') }}" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button onclick="window.print()" class="btn btn-sm btn-outline-success me-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <a href="{{ route('InwardGatepass.pdf', $gatepass->id) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                        </div>
                    </div>

                    {{-- Gatepass Details --}}
                    <div class="border rounded p-3 mb-4 shadow-sm">
                        <h6 class="fw-bold text-primary mb-3">General Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Branch:</strong> {{ $gatepass->branch->name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Warehouse:</strong> {{ $gatepass->warehouse->warehouse_name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Vendor:</strong> {{ $gatepass->vendor->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($gatepass->gatepass_date)->format('d M, Y') }}</p>
                                <p class="mb-1"><strong>Note:</strong> {{ $gatepass->note ?? '-' }}</p>
                                <p class="mb-1"><strong>Status:</strong> 
                                    <span class="badge bg-{{ $gatepass->status == 'linked' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($gatepass->status ?? 'Pending') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Product Table --}}
                    <div class="border rounded shadow-sm">
                        <div class="p-3 bg-light border-bottom">
                            <h6 class="fw-bold mb-0 text-success">Product Details</h6>
                        </div>
                        <div class="p-0">
                            <table class="table table-bordered table-striped mb-0 text-center align-middle">
                                <thead class="table-success">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th style="width:65%">Product</th>
                                        <th style="width:30%">Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($gatepass->items as $i => $item)
                                        <tr>
                                            <td>{{ $i+1 }}</td>
                                            <td class="text-start">{{ $item->product->item_name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-primary fs-6 px-3 py-2">
                                                    {{ $item->qty }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-muted py-3">No products found for this gatepass.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Footer Note --}}
                    <div class="text-center text-muted small mt-4">
                        <em>This is a system-generated gatepass. No signature is required.</em>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
