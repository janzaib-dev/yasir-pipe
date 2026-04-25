@extends('admin_panel.layout.app')
@section('content')

    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>🔄 Stock Transfer List</h5>
            @can('stock.transfer.create')
                <a href="{{ route('stock_transfers.create') }}" class="btn btn-primary btn-sm">New Transfer</a>
            @endcan
        </div>
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-body">
            <table class="table table-bordered table-striped" id="transferTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>From Warehouse</th>
                        <th>To Warehouse / Shop</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfers as $transfer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $transfer->fromWarehouse->warehouse_name }}</td>
                            <td>
                                @if ($transfer->to_shop)
                                    Shop
                                @else
                                    {{ $transfer->toWarehouse ? $transfer->toWarehouse->warehouse_name : '-' }}
                                @endif
                            </td>
                            <td>{{ $transfer->product->item_name }}</td>
                            <td>{{ $transfer->quantity }}</td>
                            <td>{{ $transfer->remarks }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#transferTable').DataTable();
        });
    </script>
@endsection
