@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container-fluid">
        <div class="card-header mt-2 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Payment Vouchers</h4>
            @can('payment.voucher.create')
            <a class="btn btn-primary" href="{{ route('Payment-vochers') }}">Add Payment Voucher</a>
            @endcan
        </div>
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive mt-4 mb-4">
                    <table id="example" class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Voucher No</th>
                                <th>Receipt Date</th>
                                <th>Entry Date</th>
                                <th>Type</th>
                                <th>Party</th>
                                <th>Reference No</th>
                                <th>Remarks</th>
                                <th>Amount</th>
                                <th>Total Amount</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipts as $item)
                            @php
                            // JSON decode for fields that are stored as arrays
                            $amounts = json_decode($item->amount, true);
                            $amount = is_array($amounts) ? (float)($amounts[0] ?? 0) : (float)$item->amount;

                            $refs = json_decode($item->reference_no, true);
                            $reference = is_array($refs) ? implode(', ', $refs) : $item->reference_no;

                            $narrations = json_decode($item->narration_id, true);
                            $narration = is_array($narrations) ? implode(', ', $narrations) : $item->narration_id;
                            @endphp
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->pvid }}</td>
                                <td>{{ $item->receipt_date }}</td>
                                <td>{{ $item->entry_date }}</td>
                                <td>{{ $item->type_label }}</td>
                                <td>{{ $item->party_name }}</td>

                                <td>{{ $reference }}</td>
                                <td>{{ $item->remarks }}</td>
                                <td>{{ number_format($amount, 2) }}</td>
                                <td>{{ number_format((float)$item->total_amount, 2) }}</td>
                                <td>{{ $item->created_at }}</td>
                                <td>
                                    <a href="{{ route('PaymentVoucher.print', $item->id) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-danger">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')


@endsection
