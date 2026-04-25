@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Purchase Returns</h3>
                            <a class="btn btn-primary" href="{{ route('Purchase.home') }}">Back to Purchases</a>
                        </div>

                        <div class="border mt-1 shadow rounded bg-white">
                            <div class="table-responsive mt-4 mb-5 p-3">
                                <table id="return-table" class="table table-bordered text-center">
                                    <thead class="bg-info text-white">
                                        <tr>
                                            <th>ID</th>
                                            <th>Invoice #</th>
                                            <th>Vendor</th>
                                            <th>Warehouse</th>
                                            <th>Return Date</th>
                                            <th>Return Amount</th>
                                            <th>Original Purchase</th>
                                            <th>Total Returned</th>
                                            <th>New Net Amount</th>
                                            <th>New Due</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($returns as $return)
                                            @php
                                                $isPartialReturn =
                                                    $return->purchase &&
                                                    $return->total_returned < $return->original_net_amount;
                                                $isFullReturn =
                                                    $return->purchase &&
                                                    $return->total_returned >= $return->original_net_amount;
                                            @endphp
                                            <tr>
                                                <td>{{ $return->id }}</td>
                                                <td>
                                                    <strong>{{ $return->return_invoice }}</strong>
                                                    @if ($return->purchase)
                                                        <br><small class="text-muted">Orig:
                                                            {{ $return->purchase->invoice_no }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $return->vendor->name ?? 'N/A' }}</td>
                                                <td>{{ $return->warehouse->warehouse_name ?? 'N/A' }}</td>
                                                <td>{{ \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') }}</td>

                                                {{-- Return Amount --}}
                                                <td class="text-danger">
                                                    <strong>-{{ number_format($return->net_amount, 2) }}</strong>
                                                </td>

                                                {{-- Original Purchase Amount --}}
                                                <td>
                                                    @if ($return->purchase)
                                                        {{ number_format($return->original_net_amount, 2) }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Total Returned --}}
                                                <td class="text-danger">
                                                    @if ($return->purchase)
                                                        <strong>{{ number_format($return->total_returned, 2) }}</strong>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- New Net Amount --}}
                                                <td class="text-success">
                                                    @if ($return->purchase)
                                                        <strong>{{ number_format($return->new_net_amount, 2) }}</strong>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- New Due Amount --}}
                                                <td class="text-warning">
                                                    @if ($return->purchase)
                                                        <strong>{{ number_format($return->new_due_amount, 2) }}</strong>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Status Badge --}}
                                                <td>
                                                    @if ($isFullReturn)
                                                        <span class="badge bg-danger">Full Return</span>
                                                    @elseif($isPartialReturn)
                                                        <span class="badge bg-warning">Partial Return</span>
                                                    @else
                                                        <span class="badge bg-secondary">Standalone</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <a href="{{ route('purchase.return.view', $return->id) }}"
                                                        class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Scripts --}}
                        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                        <script>
                            $(document).ready(function() {
                                $('#return-table').DataTable({
                                    "pageLength": 10,
                                    "lengthMenu": [5, 10, 25, 50, 100],
                                    "order": [
                                        [0, 'desc']
                                    ],
                                    "language": {
                                        "search": "Search Return:",
                                        "lengthMenu": "Show _MENU_ entries"
                                    }
                                });
                            });
                        </script>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
