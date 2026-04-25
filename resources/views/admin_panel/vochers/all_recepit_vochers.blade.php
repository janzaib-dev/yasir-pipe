@extends('admin_panel.layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <div class="main-content">
        <div class="container-fluid">
            <div class="card-header mt-2 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Receipts Vouchers</h4>
                @can('receipts.voucher.create')
                    <a class="btn btn-primary" href="{{ route('recepit_vochers') }}">Add Receipts Voucher</a>
                @endcan
            </div>
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive mt-4 mb-4">
                        <table id="example" class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Voucher No</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Party / Account</th>
                                    <th>Remarks</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($receipts as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $item->voucher_no }}</span>
                                        </td>
                                        <td>{{ $item->date ? $item->date->format('d-M-Y') : '-' }}</td>
                                        <td>
                                            <span
                                                class="badge bg-info text-dark">{{ ucfirst($item->payment_from ?? 'Receipt') }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $item->party_name }}</strong>
                                            <small class="d-block text-muted">{{ $item->type_label }}</small>
                                        </td>
                                        <td>{{ Str::limit($item->remarks, 50) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($item->total_amount, 2) }}</td>
                                        <td class="text-center">
                                            @if ($item->status == 'posted')
                                                <span class="badge bg-success">Posted</span>
                                            @elseif($item->status == 'draft')
                                                <span class="badge bg-secondary">Draft</span>
                                            @else
                                                <span class="badge bg-danger">{{ ucfirst($item->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('print', $item->id) }}" target="_blank"
                                                class="btn btn-sm btn-outline-danger" title="Print">
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
