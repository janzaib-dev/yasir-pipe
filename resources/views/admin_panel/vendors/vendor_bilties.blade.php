@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Vendor Bilties</h4>
                    <h6>Manage Transport & Delivery Records</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#biltyModal" onclick="clearBiltyForm()">Add Bilty</button>
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
                                <th>Vendor</th>
                                <th>Purchase</th>
                                <th>Bilty No</th>
                                <th>Vehicle No</th>
                                <th>Transporter</th>
                                <th>Delivery Date</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bilties as $key => $b)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $b->vendor->name ?? 'N/A' }}</td>
                                <td>{{ $b->purchase->invoice_no ?? 'N/A' }}</td>
                                <td>{{ $b->bilty_no }}</td>
                                <td>{{ $b->vehicle_no }}</td>
                                <td>{{ $b->transporter_name }}</td>
                                <td>{{ $b->delivery_date }}</td>
                                <td>{{ $b->note }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bilty Modal -->
<div class="modal fade" id="biltyModal">
    <div class="modal-dialog">
        <form action="{{ route('vendor.bilties.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Vendor Bilty</h5></div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Vendor</label>
                        <select name="vendor_id" class="form-control" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Related Purchase (optional)</label>
                        <select name="purchase_id" class="form-control">
                            <option value="">None</option>
                            @foreach($purchases as $purchase)
                                <option value="{{ $purchase->id }}">{{ $purchase->invoice_no }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2"><input class="form-control" name="bilty_no" placeholder="Bilty No"></div>
                    <div class="mb-2"><input class="form-control" name="vehicle_no" placeholder="Vehicle No"></div>
                    <div class="mb-2"><input class="form-control" name="transporter_name" placeholder="Transporter Name"></div>
                    <div class="mb-2"><input type="date" class="form-control" name="delivery_date"></div>
                    <div class="mb-2"><textarea class="form-control" name="note" placeholder="Note (optional)"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function clearBiltyForm() {
    $('#biltyModal select[name="vendor_id"]').val('');
    $('#biltyModal select[name="purchase_id"]').val('');
    $('#biltyModal input[name="bilty_no"]').val('');
    $('#biltyModal input[name="vehicle_no"]').val('');
    $('#biltyModal input[name="transporter_name"]').val('');
    $('#biltyModal input[name="delivery_date"]').val('');
    $('#biltyModal textarea[name="note"]').val('');
}

$('.datanew').DataTable();
</script>
@endpush
