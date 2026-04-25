@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Vendor Ledger</h4>
                        <p class="text-muted mb-0 small">View and manage vendor balances and history</p>
                    </div>
                    <div>
                        <a href="{{ url('vendor') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to Vendors
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session()->has('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-hover align-middle datanew" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small">
                                            ID</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Vendor Name</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Address</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Opening
                                            Bal</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Current
                                            Balance</th>
                                        <th
                                            class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($VendorLedgers->isEmpty())
                                        <script>
                                            document.addEventListener("DOMContentLoaded", function() {
                                                if (document.getElementById("global-loader")) {
                                                    document.getElementById("global-loader").style.display = "none";
                                                }
                                            });
                                        </script>
                                    @endif
                                    @forelse($VendorLedgers as $ledger)
                                        <tr class="border-bottom-0">
                                            <td class="ps-3 fw-bold text-muted">#{{ $ledger->vendor_id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-primary-subtle text-primary me-2 fw-bold d-flex align-items-center justify-content-center rounded-circle"
                                                        style="width: 32px; height: 32px; font-size: 14px;">
                                                        {{ strtoupper(substr($ledger->vendor->name ?? 'V', 0, 1)) }}
                                                    </div>
                                                    <span class="fw-medium text-dark">{{ $ledger->vendor->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted small">{{ Str::limit($ledger->vendor->address, 30) }}</td>

                                            <td class="text-end font-monospace">
                                                {{ number_format($ledger->opening_balance, 2) }}</td>

                                            <td class="text-end">
                                                @php
                                                    $balance =
                                                        $ledger->formatted_closing_balance ?? $ledger->closing_balance;
                                                    // Positive means Payable (Cr), Negative means Advance (Dr)
                                                    // But usually in business, Payable is shown as simple number.
                                                    // Let's stick to standard accounting notation:
                                                    $isPayable = $balance >= 0;
                                                @endphp
                                                <div class="fw-bold {{ $isPayable ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format(abs($balance), 2) }}
                                                    <small
                                                        class="text-secondary fw-normal ms-1">{{ $isPayable ? 'Cr' : 'Dr' }}</small>
                                                </div>
                                            </td>

                                            <td class="pe-3 text-center">
                                                <a href="{{ route('vendor.ledger', $ledger->vendor_id) }}"
                                                    class="btn btn-sm btn-outline-info shadow-sm d-inline-flex align-items-center gap-1"
                                                    title="View Detailed Ledger">
                                                    <i class="fas fa-file-invoice-dollar"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable().destroy();
            }
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [], // Disable initial sort
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search vendors..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });
        });
    </script>
@endsection
