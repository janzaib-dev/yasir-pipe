@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Sale Return Details</h4>
                        <p class="text-muted mb-0 small">Return Invoice: {{ $return->return_invoice }}</p>
                    </div>
                    <div>
                        <a href="{{ route('sale.return.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">

                        {{-- Return Header Info --}}
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-3">Return Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td class="fw-bold">Return Invoice:</td>
                                        <td>{{ $return->return_invoice }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Return Date:</td>
                                        <td>{{ \Carbon\Carbon::parse($return->return_date)->format('d M, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Customer:</td>
                                        <td>{{ $return->customer->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Warehouse:</td>
                                        <td>{{ $return->warehouse->warehouse_name ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-3">Financial Summary</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td class="fw-bold">Bill Amount:</td>
                                        <td class="text-end">{{ number_format($return->bill_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Item Discount:</td>
                                        <td class="text-end text-danger">-{{ number_format($return->item_discount, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Extra Discount:</td>
                                        <td class="text-end text-danger">
                                            -{{ number_format($return->extra_discount ?? 0, 2) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold">Net Amount:</td>
                                        <td class="text-end fw-bold text-primary">
                                            {{ number_format($return->net_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Refund Paid:</td>
                                        <td class="text-end text-success">{{ number_format($return->paid, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Balance:</td>
                                        <td class="text-end {{ $return->balance > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($return->balance, 2) }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Return Items --}}
                        <h6 class="text-muted text-uppercase small mb-3">Returned Items</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th class="text-center">Boxes</th>
                                        <th class="text-center">Loose</th>
                                        <th class="text-center">Total Pieces</th>
                                        <th class="text-end">Price/Pc</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($return->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $item->product->product_name ?? 'N/A' }}</strong><br>
                                                <small class="text-muted">{{ $item->product->product_code ?? '' }}</small>
                                            </td>
                                            <td class="text-center">{{ number_format($item->boxes, 2) }}</td>
                                            <td class="text-center">{{ $item->loose_pieces }}</td>
                                            <td class="text-center fw-bold">{{ number_format($item->qty, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->price, 2) }}</td>
                                            <td class="text-end text-danger">{{ number_format($item->item_discount, 2) }}
                                            </td>
                                            <td class="text-end fw-bold">{{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($return->remarks)
                            <div class="mt-4">
                                <h6 class="text-muted text-uppercase small mb-2">Remarks</h6>
                                <p class="border-start border-4 border-primary ps-3 mb-0">{{ $return->remarks }}</p>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
