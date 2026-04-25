@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <!-- Header -->
            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Vendor Payments</h4>
                    <h6>Manage Vendor Payment Records</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="clearPaymentForm()">Add Payment</button>
                </div>
            </div>

            <!-- Alert -->
            @if (session()->has('success'))
            <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
            @endif

            <!-- Table -->
            <div class="card">
                <div class="card-body">
                    <table class="table datanew">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $key => $pay)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $pay->vendor->name ?? 'N/A' }}</td>
                                <td>{{ number_format($pay->amount, 2) }}</td>
                                <td>{{ $pay->payment_date }}</td>
                                <td>{{ $pay->payment_method }}</td>
                                <td>{{ $pay->note }}</td>
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
        <form action="{{ route('vendor.payments.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Vendor Payment</h5></div>
                <div class="modal-body">
               <div class="mb-2">
    <label>Vendor</label>
    <select name="vendor_id" id="vendor_id" class="form-control" required>
        <option value="">Select Vendor</option>
        @foreach($vendors as $vendor)
            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-2">
    <label>Stock (Closing Balance)</label>
    <input type="text" id="vendor_stock" class="form-control" readonly>
</div>

                    <div class="mb-2">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>
                  <div class="mb-2">
    <label>Type</label>
    <select name="adjustment_type" class="form-control" required>
        <option value="minus">Minus (Payment)</option>
        <option value="plus">Plus (Return / Advance)</option>
    </select>
</div>

<div class="mb-2">
    <label>Amount</label>
    <input type="number" step="0.01" name="amount" class="form-control" required>
</div>

                    <div class="mb-2">
                        <label>Payment Method</label>
                        <input type="text" name="payment_method" class="form-control" placeholder="e.g. Cash, Bank">
                    </div>
                    <div class="mb-2">
                        <label>Note</label>
                        <textarea name="note" class="form-control" placeholder="Optional note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {
    $('#vendor_id').on('change', function () {
        var vendorId = $(this).val();
        if (vendorId) {
            $.ajax({
                url: '/get-vendor-balance/' + vendorId,
                type: 'GET',
                success: function (data) {
                    $('#vendor_stock').val(data.closing_balance);
                },
                error: function () {
                    $('#vendor_stock').val('0');
                }
            });
        } else {
            $('#vendor_stock').val('');
        }
    });
});
</script>
