@extends('admin_panel.layout.app')

@section('content')
    <style>
        .ledger-card {
            border-top: 3px solid #0d6efd;
        }

        .table-ledger th {
            background-color: #212529;
            color: #fff;
        }

        .balance-positive {
            color: #198754;
            font-weight: 700;
        }

        .balance-neutral {
            color: #6c757d;
            font-weight: 700;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid mt-4">

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1 text-primary"><i class="bi bi-people"></i> Customer Ledger (Statement)</h4>
                        <p class="text-muted mb-0">Track all customer transactions, invoices, and receipts.</p>
                    </div>
                    <div>
                        <a href="{{ route('view_all') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i>
                            Back to Accounts</a>
                    </div>
                </div>

                <div class="card shadow-sm ledger-card">
                    <div class="card-body">

                        <!-- Filters -->
                        <form method="GET" action="{{ route('customers.ledger') }}"
                            class="row g-3 mb-4 p-3 bg-light rounded border">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Select Customer</label>
                                <select name="customer_id" class="form-control select2">
                                    <option value="">-- All Customers --</option>
                                    @foreach ($customers as $cust)
                                        <option value="{{ $cust->id }}"
                                            {{ request('customer_id') == $cust->id ? 'selected' : '' }}>
                                            {{ $cust->customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">From Date</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}"
                                    class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">To Date</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="d-flex w-100 gap-2">
                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i>
                                        Filter</button>
                                    <a href="{{ route('customers.ledger') }}" class="btn btn-outline-secondary"><i
                                            class="bi bi-arrow-clockwise"></i></a>
                                </div>
                            </div>
                        </form>

                        <!-- Ledger Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover table-ledger" id="ledger-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="12%">Date</th>
                                        <th width="18%">Customer</th>
                                        <th width="30%">Description / Particulars</th>
                                        <th width="10%" class="text-end">Debit (Dr)</th>
                                        <th width="10%" class="text-end">Credit (Cr)</th>
                                        <th width="15%" class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($CustomerLedgers as $key => $ledger)
                                        @php
                                            // Ledger object now has explicit debit/credit from Controller/BalanceService
                                            $debit = $ledger->debit ?? 0;
                                            $credit = $ledger->credit ?? 0;
                                            $balance = $ledger->closing_balance;
                                            $suffix = $balance >= 0 ? 'Dr' : 'Cr';
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $ledger->created_at->format('d-M-Y') }}</td>
                                            <td class="fw-bold">{{ $ledger->customer->customer_name ?? 'N/A' }}</td>
                                            <td>
                                                {{ $ledger->description }}
                                            </td>
                                            <td class="text-end text-success">
                                                {{ $debit > 0 ? number_format($debit, 2) : '-' }}
                                            </td>
                                            <td class="text-end text-danger">
                                                {{ $credit > 0 ? number_format($credit, 2) : '-' }}
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format(abs($balance), 2) }}
                                                <small class="text-muted">{{ $suffix }}</small>
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
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Init Select2 if available
            if ($('.select2').length > 0) {
                $('.select2').select2();
            }
        });
    </script>
@endpush
