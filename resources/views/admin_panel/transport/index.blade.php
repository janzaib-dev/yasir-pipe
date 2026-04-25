@extends('admin_panel.layout.app')
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <h3>Transport List</h3>
                @can('transport.create')
                    <a href="{{ route('transport.create') }}" class="btn btn-success mb-3">Add New Transport</a>
                @endcan

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <table id="transportTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Action</th> {{-- Only one "Action" column --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transports as $key => $item)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->company_name }}</td>
                                <td>{{ $item->mobile }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ $item->address }}</td>
                                <td>
                                    <div style="white-space: nowrap;">
                                        @include('admin_panel.partials.action_buttons', [
                                            'editRoute' => route('transport.edit', $item->id),
                                            'deleteRoute' => route('transport.delete', $item->id),
                                            'editIsLink' => true,
                                            'permissions' => [
                                                'edit' => 'transport.edit',
                                                'delete' => 'transport.delete',
                                            ],
                                        ])
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- DataTables Initialization Script --}}
    <script>
        $(document).ready(function() {
            $('#transportTable').DataTable({
                responsive: false,
                pageLength: 10
            });
        });
    </script>
@endsection
