@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Customer Payments & Recoveries</h4>
                    <h6>Manage Customer Receivables</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="clearPaymentForm()">Add Payment</button>
                </div>
            </div>

            @if (session()->has('success'))
            <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <table class="table datanew">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $key => $p)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $p->customer->customer_name ?? 'N/A' }}</td>
                                <td>{{ number_format($p->amount, 2) }}</td>
                                <td>{{ $p->payment_date }}</td>
                                <td>{{ $p->payment_method }}</td>
                                <td>{{ $p->note }}</td>
                                <td>
                                    <form action="{{ route('customer.payments.destroy', $p->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this payment?')">
                                            Delete
                                        </button>
                                    </form>
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

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal">
    <div class="modal-dialog">
        <form action="{{ route('customer.payments.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer Payment</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Customer</label>
                        <select name="customer_id" class="form-control" required onchange="fetchCustomerBalance(this.value)">
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Outstanding Balance</label>
                        <input type="text" id="customer_balance" class="form-control" readonly>
                    </div>

                    <!-- Adjustment Type -->
                    <div class="mb-2">
                        <label>Adjustment Type</label>
                        <select name="adjustment_type" class="form-control" required>
                            <option value="minus">- Minus (Payment Received)</option>
                            <option value="plus">+ Plus (Outstanding Increased)</option>
                        </select>
                    </div>
                    <div class="mb-2"><label>Payment Date</label><input type="date" name="payment_date" class="form-control" required></div>
                    <div class="mb-2"><label>Amount</label><input type="number" name="amount" step="0.01" class="form-control" required></div>
                    <div class="mb-2"><label>Payment Method</label><input type="text" name="payment_method" class="form-control" placeholder="e.g. Cash, Bank"></div>
                    <div class="mb-2"><label>Note</label><textarea name="note" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
    </div>
</div>
<script>
    function fetchCustomerBalance(customerId) {
        $.ajax({
            url: '/customer/ledger/' + customerId,
            method: 'GET',
            success: function(response) {
                if (response.closing_balance !== undefined) {
                    $('#customer_balance').val(parseFloat(response.closing_balance).toFixed(2));
                } else {
                    $('#customer_balance').val('0.00');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                $('#customer_balance').val('Error');
            }
        });
    }
</script>
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    function clearPaymentForm() {
        $('#paymentModal select[name="customer_id"]').val('');
        $('#paymentModal input[name="payment_date"]').val('');
        $('#paymentModal input[name="amount"]').val('');
        $('#paymentModal input[name="payment_method"]').val('');
        $('#paymentModal textarea[name="note"]').val('');
        $('#customer_balance').val('');
    }



    $(document).ready(function() {
        $('.datanew').DataTable();
    });
</script>
@endpush