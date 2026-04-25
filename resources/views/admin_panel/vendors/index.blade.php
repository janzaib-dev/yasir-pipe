@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Vendor Management</h4>
                        <p class="text-muted mb-0 small">Manage your suppliers and their details</p>
                    </div>
                    <div class="d-flex gap-2">
                        @can('vendors.create')
                            <button class="btn btn-primary px-4 shadow-sm fw-medium d-flex align-items-center gap-2"
                                id="btnAddVendor">
                                <i class="bi bi-plus-lg"></i> Add Vendor
                            </button>
                        @endcan
                        <a href="{{ url('vendors-ledger') }}"
                            class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-journal-text"></i> Ledger
                        </a>
                        <a href="{{ route('vendor.payments') }}"
                            class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack"></i> Payments
                        </a>
                        <a href="{{ url('vendor/bilties') }}"
                            class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-truck"></i> Bilty
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session()->has('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-hover align-middle datanew" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small"
                                            style="width: 5%">#</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Vendor Name</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Contact</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Opening Bal</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Address</th>
                                        <th class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center"
                                            style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($vendors as $key => $v)
                                        <tr class="border-bottom-0">
                                            <td class="ps-3 fw-bold text-muted">{{ $key + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle-sm bg-primary-subtle text-primary fw-bold d-flex justify-content-center align-items-center rounded-circle"
                                                        style="width:32px; height:32px;">
                                                        {{ strtoupper(substr($v->name, 0, 1)) }}
                                                    </div>
                                                    <span class="fw-medium text-dark">{{ $v->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($v->phone)
                                                    <span
                                                        class="badge bg-light text-dark border fw-normal">{{ $v->phone }}</span>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td class="fw-semibold text-dark">
                                                {{ number_format((float) $v->opening_balance, 2) }}</td>
                                            <td class="text-secondary small">{{ Str::limit($v->address, 30) ?: '-' }}</td>
                                            <td class="text-center pe-3">
                                                @include('admin_panel.partials.action_buttons', [
                                                    'editRoute' => 'javascript:void(0)',
                                                    'deleteRoute' => url('vendor/delete/' . $v->id),
                                                    'editIsLink' => false,
                                                    'permissions' => [
                                                        'edit' => 'vendors.edit',
                                                        'delete' => 'vendors.delete',
                                                    ],
                                                    'dataId' => $v->id,
                                                ])
                                            </td>
                                            <!-- Hidden data for JS -->
                                            <input type="hidden" class="v-name" value="{{ $v->name }}">
                                            <input type="hidden" class="v-phone" value="{{ $v->phone }}">
                                            <input type="hidden" class="v-balance" value="{{ $v->opening_balance }}">
                                            <input type="hidden" class="v-address" value="{{ $v->address }}">
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

    <!-- Modal for Add/Edit Vendor -->
    <div class="modal fade" id="vendorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ url('vendor/store') }}" method="POST" id="vendorForm">@csrf
                    <input type="hidden" id="vendor_id" name="id">

                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold ms-2" id="modalTitle">New Vendor</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4 pt-3">
                        <p class="text-muted small mb-4 ms-1">Fill in the details below to add or update a vendor.</p>

                        <div class="form-group mb-3">
                            <label for="vname" class="small text-secondary fw-bold mb-1">Vendor Name</label>
                            <input type="text" class="form-control" name="name" id="vname"
                                placeholder="Vendor Name" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="vphone" class="small text-secondary fw-bold mb-1">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" id="vphone"
                                        placeholder="Phone Number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="opening_balance" class="small text-secondary fw-bold mb-1">Opening
                                        Balance</label>
                                    <input type="number" step="any" class="form-control" name="opening_balance"
                                        id="opening_balance" placeholder="Opening Balance" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="vaddress" class="small text-secondary fw-bold mb-1">Full Address</label>
                            <textarea class="form-control" name="address" id="vaddress" placeholder="Full Address" style="height: 100px"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button type="button" class="btn btn-light fw-medium" data-dismiss="modal">Cancel</button>
                        @can('vendors.create')
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Save Vendor</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            // Check if DataTable is already initialized to avoid re-init error
            if ($.fn.DataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable().destroy();
            }
            $('.datanew').DataTable({
                "pageLength": 10,
                "order": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search vendors..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            // Open Modal for Create
            $('#btnAddVendor').click(function() {
                // Reset form
                $('#vendorForm')[0].reset();
                $('#vendor_id').val('');
                $('#modalTitle').text('New Vendor');
                $('#opening_balance').prop('readonly', false);
                // Bootstrap 4 Modal syntax
                $('#vendorModal').modal('show');
            });

            // Edit Vendor
            $(document).on('click', '.edit-btn', function() {
                const $row = $(this).closest('tr');
                const id = $(this).data('id');

                // Fetch data from hidden inputs for reliability
                const name = $row.find('.v-name').val();
                const phone = $row.find('.v-phone').val();
                const balance = $row.find('.v-balance').val();
                const address = $row.find('.v-address').val();

                $('#vendor_id').val(id);
                $('#vname').val(name);
                $('#vphone').val(phone);
                $('#opening_balance').val(balance).prop('readonly',
                    true); // Prevent editing balance in edit mode
                $('#vaddress').val(address);

                $('#modalTitle').text('Edit Vendor');
                // Bootstrap 4 Modal syntax
                $('#vendorModal').modal('show');
            });
        });
    </script>
@endsection
