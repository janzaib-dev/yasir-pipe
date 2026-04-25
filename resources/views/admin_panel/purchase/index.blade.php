@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Purchase Management</h4>
                        <p class="text-muted mb-0 small">View and manage your purchase invoices</p>
                    </div>
                    <div>
                        {{-- Purchase Returns Button --}}
                        <a class="btn btn-outline-danger px-3 shadow-sm fw-medium me-2"
                            href="{{ route('purchase.return.index') }}">
                            <i class="fas fa-undo"></i> Purchase Returns
                        </a>

                        @can('purchases.create')
                            <a class="btn btn-primary px-4 shadow-sm fw-medium align-items-center gap-2"
                                href="{{ route('add_purchase') }}">
                                <i class="fas fa-plus"></i> Add Purchase
                            </a>
                        @endcan
                    </div>
                </div>

                {{-- Status Filters --}}
                <div class="mb-4 d-flex gap-2">
                    <a href="{{ route('Purchase.home', ['status' => 'all']) }}"
                        class="btn btn-sm {{ request('status') == 'all' || !request('status') ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        All
                    </a>
                    <a href="{{ route('Purchase.home', ['status' => 'approved']) }}"
                        class="btn btn-sm {{ request('status') == 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                        Approved
                    </a>
                    <a href="{{ route('Purchase.home', ['status' => 'draft']) }}"
                        class="btn btn-sm {{ request('status') == 'draft' ? 'btn-warning' : 'btn-outline-warning' }}">
                        Draft
                    </a>
                    <a href="{{ route('Purchase.home', ['status' => 'Returned']) }}"
                        class="btn btn-sm {{ request('status') == 'Returned' ? 'btn-danger' : 'btn-outline-danger' }}">
                        Returned
                    </a>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="purchase-table" class="table table-hover align-middle datanew" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small">
                                            ID</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Date</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Invoice No</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Status</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Vendor</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Warehouse</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Net Amount
                                        </th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Paid</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Due</th>
                                        <th
                                            class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($Purchase as $purchase)
                                        <tr class="border-bottom-0">
                                            <td class="ps-3 fw-bold text-muted">#{{ $purchase->id }}</td>
                                            <td class="text-nowrap">
                                                {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}
                                            </td>
                                            <td class="font-monospace text-dark">{{ $purchase->invoice_no }}</td>
                                            <td>
                                                @if ($purchase->status_purchase == 'draft')
                                                    <span
                                                        class="badge badge-warning text-dark border border-warning">Draft</span>
                                                @elseif ($purchase->status_purchase == 'Returned')
                                                    <span
                                                        class="badge bg-danger text-white border border-danger">Returned</span>
                                                @else
                                                    <span class="badge badge-success border border-success">Approved</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-info-subtle text-info me-2 fw-bold d-flex align-items-center justify-content-center rounded-circle"
                                                        style="width: 32px; height: 32px; font-size: 14px;">
                                                        {{ strtoupper(substr($purchase->vendor->name ?? 'V', 0, 1)) }}
                                                    </div>
                                                    <span
                                                        class="fw-medium text-dark">{{ $purchase->vendor->name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $purchase->warehouse->warehouse_name ?? 'N/A' }}
                                            </td>

                                            <td class="text-end fw-bold text-dark">
                                                @if ($purchase->total_returned > 0)
                                                    {{-- Show original amount struck through --}}
                                                    <div>
                                                        <small
                                                            class="text-muted text-decoration-line-through">{{ number_format($purchase->net_amount, 2) }}</small>
                                                    </div>
                                                    {{-- Show updated amount --}}
                                                    <div class="text-success">
                                                        {{ number_format($purchase->updated_net_amount, 2) }}
                                                    </div>
                                                    <small
                                                        class="text-danger">(-{{ number_format($purchase->total_returned, 2) }})</small>
                                                @else
                                                    {{ number_format($purchase->net_amount, 2) }}
                                                @endif
                                            </td>
                                            <td class="text-end text-success">
                                                {{ number_format($purchase->paid_amount, 2) }}
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $displayDue =
                                                        $purchase->total_returned > 0
                                                            ? $purchase->updated_due_amount
                                                            : $purchase->due_amount;
                                                @endphp
                                                @if ($displayDue > 0)
                                                    <span
                                                        class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">{{ number_format($displayDue, 2) }}</span>
                                                @else
                                                    <span
                                                        class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Paid</span>
                                                @endif

                                                @if ($purchase->has_partial_return)
                                                    <br><small class="badge bg-danger text-white mt-1"><i class="fas fa-undo-alt me-1"></i> Partial
                                                        Return</small>
                                                @elseif($purchase->is_fully_returned)
                                                    <br><small class="badge bg-danger mt-1">Fully Returned</small>
                                                @endif
                                            </td>

                                            <td class="pe-3 text-center">
                                                <div class="dropdown">
                                                    {{-- Replaced data-bs-toggle with data-toggle for Bootstrap 4 compatibility --}}
                                                    <button class="btn btn-sm btn-light border dropdown-toggle"
                                                        type="button" data-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v small"></i> Actions
                                                    </button>
                                                    {{-- Replaced dropdown-menu-end (BS5) with dropdown-menu-right (BS4) --}}
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-right border-0 shadow-lg rounded-3">

                                                        @can('purchases.edit')
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                                    href="{{ route('purchase.edit', $purchase->id) }}">
                                                                    <i class="fas fa-edit text-primary fa-fw"></i> Edit
                                                                </a>
                                                            </li>
                                                        @endcan

                                                        @if ($purchase->status_purchase == 'draft')
                                                            @can('purchases.create')
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-success confirm-purchase-btn"
                                                                        href="{{ route('purchase.confirm', $purchase->id) }}">
                                                                        <i class="fas fa-check-circle fa-fw"></i> Confirm
                                                                        Purchase
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                            @endcan
                                                        @endif

                                                        @if ($purchase->status_purchase != 'draft')
                                                            @can('purchases.view')
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                                        href="{{ route('purchase.invoice', $purchase->id) }}">
                                                                        <i class="fas fa-file-invoice text-info fa-fw"></i> View
                                                                        Invoice
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                                        href="{{ route('purchase.receipt', $purchase->id) }}">
                                                                        <i class="fas fa-receipt text-secondary fa-fw"></i> View
                                                                        Receipt
                                                                    </a>
                                                                </li>
                                                            @endcan
                                                            @can('purchases.create')
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                                        href="{{ route('purchase.return.show', $purchase->id) }}">
                                                                        <i class="fas fa-undo text-warning fa-fw"></i> Return
                                                                    </a>
                                                                </li>
                                                            @endcan
                                                        @endif

                                                        @if ($purchase->status_purchase == 'draft')
                                                            @can('purchases.delete')
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li>
                                                                    <form
                                                                        action="{{ route('purchase.destroy', $purchase->id) }}"
                                                                        method="POST" class="d-inline delete-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button"
                                                                            class="dropdown-item d-flex align-items-center gap-2 py-2 delete-btn text-danger">
                                                                            <i class="fas fa-trash-alt fa-fw"></i> Delete
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

            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable().destroy();
            }
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search purchases..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

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
                                if (response.invoice_url) {
                                    window.open(response.invoice_url, '_blank');
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg =
                                    xhr.responseJSON.message;
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
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
