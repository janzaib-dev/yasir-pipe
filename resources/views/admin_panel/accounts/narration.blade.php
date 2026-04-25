@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container " >

        <div class="d-flex justify-content-between align-items-center mb-2 ">
            <h2 class="fw-bold mt-2">Narrations</h2>
            <button class="btn btn-primary mt-2" id="addBtn">
                <i class="bi bi-plus-lg"></i> Add Narration
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="narrationsTable" class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Expense Head</th>
                                <th>Narration</th>
                                <th>Date</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($narrations as $key => $row)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $row->expense_head }}</td>
                                <td>{{ $row->narration }}</td>
                                <td>{{ $row->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm editBtn" 
                                        data-id="{{ $row->id }}"
                                        data-expense="{{ $row->expense_head }}"
                                        data-narration="{{ $row->narration }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('narrations.destroy', $row->id) }}" method="POST" style="display:inline-block;">
                                        @csrf @method('DELETE')
                                        <button onclick="return confirm('Delete this?')" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i>
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

        <!-- Add/Edit Modal -->
        <div class="modal fade" id="narrationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content rounded-3 shadow">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="modalTitle">Add Narration</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form action="{{ route('narrations.store') }}" method="POST" id="narrationForm">
                            @csrf
                            <input type="hidden" name="id" id="narration_id">

                            <div class="mb-3">
                                <label class="form-label">Select Voucher Head</label>
                                <select name="expense_head" id="expense_head" class="form-select form-control" required>
                                    <option value="" disabled>Choose...</option>
                                    <option>Bank Charges</option>
                                    <option>Fuel Expense</option>
                                    <option>Lost Stock</option>
                                    <option>journal voucher</option>
                                    <option>receipt voucher</option>
                                    <option>payment voucher</option>
                                    <option>income voucher</option>
                                    <option>expense voucher</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Narration</label>
                                <textarea name="narration" id="narration" class="form-control" rows="3" required></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-success" id="saveBtn">Save Narration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    $('#narrationsTable').DataTable();

    // Add button click
    $('#addBtn').click(function(){
        $('#modalTitle').text('Add Narration');
        $('#narrationForm')[0].reset();
        $('#narration_id').val('');
        $('#narrationModal').modal('show');
    });

    // Edit button click
    $(document).on('click', '.editBtn', function(){
        $('#modalTitle').text('Edit Narration');
        $('#narration_id').val($(this).data('id'));
        $('#expense_head').val($(this).data('expense'));
        $('#narration').val($(this).data('narration'));
        $('#narrationModal').modal('show');
    });
});
</script>

@endsection
