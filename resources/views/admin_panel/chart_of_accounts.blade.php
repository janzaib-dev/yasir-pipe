@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Chart Of Accounts</h4>
                        <p class="text-muted mb-0 small">Manage your financial accounts and categories</p>
                    </div>
                    @can('chart.of.accounts.create')
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary px-4 shadow-sm fw-medium d-flex align-items-center gap-2"
                                data-toggle="modal" data-target="#addAccountModal">
                                <i class="fas fa-plus"></i> Add New Account
                            </button>
                            <button class="btn btn-outline-secondary d-flex align-items-center gap-2" data-toggle="modal"
                                data-target="#addHeadModal">
                                <i class="fas fa-folder-plus"></i> Add Category
                            </button>
                        </div>
                    @endcan
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3 mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-hover align-middle datanew" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small"
                                            style="width: 5%">#</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 10%">
                                            Code</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 15%">
                                            Head / Group</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 20%">
                                            Account Title</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 8%">
                                            Type</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 12%">
                                            Balance</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 8%">
                                            Status</th>
                                        <th class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center"
                                            style="width: 15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accounts as $acc)
                                        <tr class="border-bottom-0">
                                            <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-light text-dark border font-monospace">{{ $acc->account_code ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark">{{ $acc->head->name ?? '-' }}</span>
                                                @if ($acc->head && $acc->head->parent_id)
                                                    <small class="text-muted d-block"
                                                        style="font-size: 0.8em;">({{ $acc->head->parent->name ?? '' }})</small>
                                                @endif
                                            </td>
                                            <td class="fw-medium text-dark">{{ $acc->title }}</td>
                                            <td>
                                                @if ($acc->type == 'Debit')
                                                    <span
                                                        class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Debit</span>
                                                @else
                                                    <span
                                                        class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Credit</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div
                                                    class="{{ $acc->current_balance < 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                                    {{ number_format(abs($acc->current_balance), 2) }}
                                                    <small
                                                        class="text-secondary fw-normal ms-1">{{ $acc->current_balance >= 0 ? 'Dr' : 'Cr' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($acc->status)
                                                    <span
                                                        class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Active</span>
                                                @else
                                                    <span
                                                        class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('accounts.ledger', $acc->id) }}"
                                                        class="btn btn-sm btn-outline-info d-flex align-items-center gap-1"
                                                        title="View Ledger">
                                                        <i class="fas fa-book"></i> Ledger
                                                    </a>
                                                    <form action="{{ route('accounts.toggleStatus', $acc->id) }}"
                                                        method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="button" onclick="this.closest('form').submit()"
                                                            class="btn btn-sm {{ $acc->status ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                            title="{{ $acc->status ? 'Deactivate' : 'Activate' }}">
                                                            <i
                                                                class="fas {{ $acc->status ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add New Account Modal -->
                <div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog"
                    aria-labelledby="addAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content border-0 shadow-lg rounded-4" action="{{ route('accounts.store') }}"
                            method="POST">
                            @csrf
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold ms-2" id="addAccountModalLabel">Add New Account</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-4 pt-3">
                                <p class="text-muted small mb-4 ms-1">Create a new financial account.</p>

                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">Select Head (Category)</label>
                                    <select class="form-control" name="head_id" required style="height: 45px;">
                                        <option value="">Select Head</option>
                                        @foreach ($heads as $head)
                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">Account Title</label>
                                    <input type="text" name="title" class="form-control"
                                        placeholder="e.g., UBL Current" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="small text-secondary fw-bold mb-1">Type</label>
                                            <select class="form-control" name="type" style="height: 45px;">
                                                <option value="Debit">Debit</option>
                                                <option value="Credit">Credit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="small text-secondary fw-bold mb-1">Opening Balance</label>
                                            <input type="number" step="0.01" name="opening_balance"
                                                class="form-control" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="statusCheck"
                                            name="status" checked>
                                        <label class="custom-control-label small text-secondary" for="statusCheck">Active
                                            Account</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4">
                                <button type="button" class="btn btn-light fw-medium"
                                    data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Save
                                    Account</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Add Head Modal -->
                <div class="modal fade" id="addHeadModal" tabindex="-1" role="dialog" aria-labelledby="addHeadLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content border-0 shadow-lg rounded-4"
                            action="{{ route('account-heads.store') }}" method="POST">
                            @csrf
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold ms-2" id="addHeadLabel">Add New Category</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-4 pt-3">
                                <p class="text-muted small mb-4 ms-1">Create a new account category/head.</p>
                                <div class="form-group mb-0">
                                    <label class="small text-secondary fw-bold mb-1">Head Name</label>
                                    <input type="text" name="name" class="form-control"
                                        placeholder="e.g., Current Assets" required>
                                </div>
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4">
                                <button type="button" class="btn btn-light fw-medium"
                                    data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Save
                                    Category</button>
                            </div>
                        </form>
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
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search accounts..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });
        });
    </script>
@endsection
