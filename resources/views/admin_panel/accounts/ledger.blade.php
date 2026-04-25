@extends('admin_panel.layout.app')

@section('content')
    <style>
        .ledger-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px 8px 0 0;
        }

        .balance-positive {
            color: #198754;
            font-weight: bold;
        }

        .balance-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .table-ledger th {
            background-color: #212529 !important;
            color: #fff;
            text-align: center;
        }

        .table-ledger td {
            vertical-align: middle;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid mt-4">

                <div class="card shadow-sm">
                    <!-- Ledger Header -->
                    <div class="ledger-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 text-primary"><i class="bi bi-book"></i> General Ledger</h4>
                            <h5 class="text-dark">{{ $account->title }} <span
                                    class="text-muted fs-6">({{ $account->account_code }})</span></h5>
                            <p class="mb-0 text-muted">Head: {{ $account->head->name ?? 'N/A' }} | Type: {{ $account->type }}
                            </p>
                        </div>
                        <div class="text-end">
                            <h3 class="{{ $account->current_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                {{ number_format(abs($account->current_balance), 2) }}
                                <small class="fs-6 text-muted">{{ $account->current_balance >= 0 ? 'Dr' : 'Cr' }}</small>
                            </h3>
                            <span class="badge bg-secondary">Current Balance</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}"
                                    class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i>
                                    Filter</button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('accounts.ledger', $account->id) }}"
                                    class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('view_all') }}" class="btn btn-secondary w-100"><i
                                        class="bi bi-arrow-left"></i> Back</a>
                            </div>
                        </form>

                        <!-- Ledger Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover table-ledger">
                                <thead>
                                    <tr>
                                        <th width="10%">Date</th>
                                        <th width="12%">Voucher No</th>
                                        <th width="25%" class="text-start">Description</th>
                                        <th width="18%">Party</th>
                                        <th width="10%">Debit</th>
                                        <th width="10%">Credit</th>
                                        <th width="15%">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $runningBalance = $account->opening_balance ?? 0;
                                    @endphp

                                    {{-- Opening Balance Row --}}
                                    <tr class="bg-light">
                                        <td colspan="6" class="text-end fw-bold">Opening Balance</td>
                                        <td class="text-end fw-bold">{{ number_format($runningBalance, 2) }}</td>
                                    </tr>

                                    @foreach ($entries as $entry)
                                        @php
                                            $debit = $entry->debit ?? 0;
                                            $credit = $entry->credit ?? 0;
                                            $runningBalance = $runningBalance + $debit - $credit;

                                            $voucherNo = '-';
                                            if ($entry->source && $entry->source->voucher_no) {
                                                $voucherNo = $entry->source->voucher_no;
                                            } elseif ($entry->source && $entry->source->invoice_no) {
                                                $voucherNo = $entry->source->invoice_no;
                                            }

                                            // Resolve party name and type
                                            $partyName = '';
                                            $partyType = '';
                                            $partyBadgeClass = 'bg-secondary';
                                            if ($entry->party) {
                                                if ($entry->party_type === 'App\\Models\\Customer') {
                                                    $partyName = $entry->party->customer_name ?? 'Unknown';
                                                    $partyType = 'Customer';
                                                    $partyBadgeClass = 'bg-primary';
                                                } elseif ($entry->party_type === 'App\\Models\\Vendor') {
                                                    $partyName = $entry->party->name ?? 'Unknown';
                                                    $partyType = 'Vendor';
                                                    $partyBadgeClass = 'bg-warning text-dark';
                                                } else {
                                                    $partyName = $entry->party->title ?? $entry->party->name ?? 'Account';
                                                    $partyType = 'Account';
                                                    $partyBadgeClass = 'bg-info text-dark';
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $entry->entry_date->format('d-M-Y') }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">{{ $voucherNo }}</span>
                                            </td>
                                            <td>{{ $entry->description }}</td>
                                            <td>
                                                @if ($partyName)
                                                    <span class="badge {{ $partyBadgeClass }}" style="font-size: 0.7rem;">{{ $partyType }}</span>
                                                    <span class="fw-bold d-block" style="font-size: 0.85rem;">{{ $partyName }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-success">
                                                {{ $debit > 0 ? number_format($debit, 2) : '-' }}</td>
                                            <td class="text-end text-danger">
                                                {{ $credit > 0 ? number_format($credit, 2) : '-' }}</td>
                                            <td class="text-end fw-bold">
                                                {{ number_format(abs($runningBalance), 2) }}
                                                <small class="text-muted">{{ $runningBalance >= 0 ? 'Dr' : 'Cr' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="4" class="text-end">Total Period</td>
                                        <td class="text-end">{{ number_format($entries->sum('debit'), 2) }}</td>
                                        <td class="text-end">{{ number_format($entries->sum('credit'), 2) }}</td>
                                        <td class="text-end">{{ number_format(abs($runningBalance), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-muted text-center">
                        End of Report
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
