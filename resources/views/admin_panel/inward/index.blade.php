@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Inward Gatepasses</h3>
                            @can('inward.gatepass.create')
                                <a class="btn btn-primary" href="{{ route('add_inwardgatepass') }}">Add Inward Gatepass</a>
                            @endcan
                        </div>

                        <div class="border mt-1 shadow rounded" style="background-color: white;">
                            <div class="col-lg-12 m-auto">
                                <div class="table-responsive mt-5 mb-5">
                                    <table id="gatepass-table" class="table table-bordered">
                                        <thead class="text-center" style="background:#add8e6;">
                                            <tr>
                                                <th style="text-align: center">ID</th>
                                                <th style="text-align: center">Branch</th>
                                                <th style="text-align: center">Warehouse</th>
                                                <th style="text-align: center">Vendor</th>
                                                <th style="text-align: center">Date</th>
                                                <th style="text-align: center">Note</th>
                                                <th style="text-align: center">Status</th> <!-- Added Status Column -->
                                                <th style="text-align: center">Action</th> <!-- Added Action Column -->
                                            </tr>
                                        </thead>
                                        <tbody class="text-center">
                                            @foreach ($gatepasses as $gp)
                                                <tr>
                                                    <td>{{ $gp->id }}</td>
                                                    <td>{{ $gp->branch->name ?? 'N/A' }}</td>
                                                    <td>{{ $gp->warehouse->warehouse_name ?? 'N/A' }}</td>
                                                    <td>{{ $gp->vendor->name ?? 'N/A' }}</td>
                                                    <td>{{ $gp->gatepass_date }}</td>
                                                    <td>{{ $gp->note ?? 'N/A' }}</td>
                                                    <td>
                                                        @if ($gp->status == 'pending')
                                                            <span class="badge bg-warning">Pending</span>
                                                        @elseif($gp->status == 'linked')
                                                            <span class="badge bg-success">Linked</span>
                                                        @elseif($gp->status == 'cancelled')
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('InwardGatepass.show', $gp->id) }}"
                                                            class="btn btn-sm btn-info mb-1">View</a>

                                                        @if ($gp->status == 'pending')
                                                            @can('inward.gatepass.create')
                                                                <a href="{{ route('add_bill', $gp->id) }}"
                                                                    class="btn btn-sm btn-info mb-1">Add Bill</a>
                                                            @endcan
                                                        @elseif($gp->status == 'linked')
                                                            <a href="{{ route('InwardGatepass.show', $gp->purchase_id) }}"
                                                                class="btn btn-sm btn-success mb-1">View Bill</a>
                                                        @endif

                                                        @can('inward.gatepass.edit')
                                                            <a href="{{ route('InwardGatepass.edit', $gp->id) }}"
                                                                class="btn btn-sm mb-1" style="background:#add8e6">Edit</a>
                                                        @endcan

                                                        <form action="{{ route('InwardGatepass.destroy', $gp->id) }}"
                                                            method="POST" class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            @can('inward.gatepass.delete')
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm delete-btn mb-1">Delete</button>
                                                            @endcan
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- DataTable --}}
                        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

                        <script>
                            $(document).ready(function() {
                                $('#gatepass-table').DataTable({
                                    "pageLength": 10,
                                    "lengthMenu": [5, 10, 25, 50, 100],
                                    "order": [
                                        [0, 'desc']
                                    ],
                                    "language": {
                                        "search": "Search Gatepass:",
                                        "lengthMenu": "Show _MENU_ entries"
                                    }
                                });
                            });
                        </script>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

{{-- SweetAlert --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Delete confirm
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this gatepass!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Success alert after delete
    @if (session('success'))
        Swal.fire({
            title: 'Deleted!',
            text: "{{ session('success') }}",
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    @endif
</script>
