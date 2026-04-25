@extends('admin_panel.layout.app')
@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">SALES</h5>
                <div>
                    @can('sales.create')
                        <span class="fw-bold text-dark"><a href="{{ route('sale.add') }}" class="btn btn-primary">Add
                                sale</a></span>
                    @endcan
                    <span class="fw-bold text-dark"><a href="{{ url('bookings') }}" class="btn btn-primary">All
                            Booking</a></span>
                    <span class="fw-bold text-dark ms-1"><a href="{{ route('sale.return.index') }}"
                            class="btn btn-secondary text-white">All Returns</a></span>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Reference</th>
                            <th>Products</th>
                            <th>Qty</th>
                            <th>Gross</th>
                            <th>Disc</th>
                            <th>Net Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            @php
                                // Product Names
                                $pNames = 'N/A';
                                if ($sale->items && $sale->items->count() > 0) {
                                    $pNames = $sale->items
                                        ->map(fn($item) => optional($item->product)->item_name ?? '?')
                                        ->implode(', ');
                                } elseif ($sale->product) {
                                    $pNames = $sale->product;
                                }

                                // Status
                                $status = '<span class="badge bg-secondary">Draft</span>';
                                if ($sale->sale_status === 'posted') {
                                    $status = '<span class="badge bg-primary">Posted</span>';
                                } elseif ($sale->sale_status === 'returned') {
                                    $status = '<span class="badge bg-danger">Returned</span>';
                                } elseif ($sale->sale_status == 1) {
                                    $status = '<span class="badge bg-danger">Return</span>';
                                } elseif ($sale->sale_status === null) {
                                    $status = '<span class="badge bg-success">Sale</span>';
                                }

                                // Check for returns
                                if ($sale->returns && $sale->returns->count() > 0) {
                                    $status .= '<br><span class="badge bg-danger mt-1"><i class="fas fa-undo-alt me-1"></i> Partial Return</span>';
                                }
                            @endphp
                            <tr>
                                <td>{{ $sale->id }}</td>
                                <td>{{ optional($sale->customer_relation)->customer_name ?? 'N/A' }}</td>
                                <td>{{ $sale->reference }}</td>
                                <td title="{{ $pNames }}">{{ \Illuminate\Support\Str::limit($pNames, 40) }}</td>
                                <td>{{ $sale->total_items > 0 ? $sale->total_items : $sale->qty }}</td>
                                <td>{{ number_format($sale->total_bill_amount > 0 ? $sale->total_bill_amount : (float) $sale->per_total, 2) }}
                                </td>
                                <td>{{ number_format($sale->total_extradiscount, 2) }}</td>
                                <td>{{ number_format($sale->total_net, 2) }}</td>
                                <td>{{ $sale->created_at->format('d-m-Y') }}</td>
                                <td>{!! $status !!}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if ($sale->sale_status === 'draft' || $sale->sale_status === 'booked')
                                            {{-- Draft: Edit/Confirm + Invoice --}}
                                            <a href="{{ route('sales.edit', $sale->id) }}"
                                                class="btn btn-sm btn-warning">Confirm</a>
                                            <a href="{{ route('sales.invoice', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-info text-white">Invoice</a>
                                            <a href="{{ route('sales.invoice', ['id' => $sale->id, 'type' => 'estimate']) }}" target="_blank"
                                                class="btn btn-sm btn-outline-info">Estimate</a>
                                        @else
                                            {{-- Posted: All Actions --}}
                                            <a href="{{ route('sales.invoice', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-info text-white">Invoice</a>
                                            <a href="{{ route('sales.invoice', ['id' => $sale->id, 'type' => 'estimate']) }}" target="_blank"
                                                class="btn btn-sm btn-outline-info">Estimate</a>
                                            <a href="{{ route('sales.dc', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-warning text-white">DC</a>
                                            <a href="{{ route('sales.dc_thermal', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-secondary text-white"><i class="fas fa-truck me-1"></i>DC Thermal</a>
                                            <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-success text-white">Receipt</a>

                                            @if ($sale->sale_status !== 'returned')
                                                <a href="{{ route('sale.return.show', $sale->id) }}"
                                                    class="btn btn-sm btn-danger text-white">Return</a>
                                            @else
                                                <button class="btn btn-sm btn-secondary text-white"
                                                    disabled>Returned</button>
                                            @endif
                                        @endif
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
