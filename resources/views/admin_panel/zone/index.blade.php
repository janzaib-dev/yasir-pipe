@extends('admin_panel.layout.app')
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Zone</h3>
                            @can('zones.create')
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"
                                    id="reset">
                                    Create
                                </button>
                            @endcan
                        </div>

                        <div class="border mt-1 shadow rounded" style="background-color: white;">
                            <div class="col-lg-12 m-auto">
                                <div class="table-responsive mt-5 mb-5">
                                    <table id="default-datatable" class="table">
                                        <thead class="text-center">
                                            <tr>
                                                <th class="text-center">Id</th>
                                                <th class="text-center">Zone</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-center">
                                            @foreach ($zones as $zone)
                                                <tr id="row-{{ $zone->id }}">
                                                    <td class="id">{{ $zone->id }}</td>
                                                    <td class="zone">{{ $zone->zone }}</td>
                                                    <td>
                                                        @include('admin_panel.partials.action_buttons', [
                                                            'editRoute' => route('zone.edit', $zone->id),
                                                            'deleteRoute' => route('zone.delete', $zone->id),
                                                            'editIsLink' => false,
                                                            'permissions' => [
                                                                'edit' => 'zones.edit',
                                                                'delete' => 'zones.delete',
                                                            ],
                                                            'dataId' => $zone->id,
                                                            'deleteMsg' =>
                                                                'Are you sure you want to delete this zone?',
                                                        ])
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
        </div>
    </div>

    <!-- CREATE MODAL -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="myform" action="{{ route('zone.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Zone</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Zone</label>
                            <input type="text" name="zone" class="form-control" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        @can('zones.create')
                            <input type="submit" class="btn btn-primary save-btn" value="Save">
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="editform" action="{{ route('zone.store') }}" method="POST">
                @csrf
                <input type="hidden" name="edit_id" id="edit_id" />
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Zone</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Zone</label>
                            <input type="text" name="zone" class="form-control" id="edit_zone" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        @can('zones.edit')
                            <input type="submit" class="btn btn-primary save-btn" value="Update">
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#default-datatable').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [
                    [0, 'desc']
                ]
            });

            // CREATE FORM
            $('.myform').submit(function(e) {
                e.preventDefault();
                var form = this;
                var formData = new FormData(form);
                var url = $(form).attr('action');
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        $('#createModal').modal('hide');
                        Swal.fire('Success!', 'Zone created successfully.', 'success').then(
                        () => location.reload());
                    }
                });
            });

            // EDIT MODAL DATA
            $('.edit-btn').click(function() {
                var id = $(this).data('id');
                $.get("{{ url('zones/edit') }}/" + id, function(res) {
                    $('#edit_id').val(res.id);
                    $('#edit_zone').val(res.zone);
                    $('#editModal').modal('show');
                });
            });

            // EDIT FORM
            $('.editform').submit(function(e) {
                e.preventDefault();
                var form = this;
                var formData = new FormData(form);
                var url = $(form).attr('action');
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        $('#editModal').modal('hide');
                        Swal.fire('Updated!', 'Zone updated successfully.', 'success').then(
                        () => location.reload());
                    }
                });
            });

            // DELETE FUNCTION
            $('.delete-btn').click(function() {
                var id = $(this).data('id');
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'GET',
                            url: url,
                            success: function(res) {
                                $('#row-' + id).remove();
                                Swal.fire('Deleted!', 'Zone has been deleted.',
                                    'success');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
