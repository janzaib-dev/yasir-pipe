@extends('admin_panel.layout.app')
@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">BOOKINGS</h5>
                <span class="fw-bold text-dark">
                    @can('bookings.create')
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary">Add Booking</a>
                    @endcan
                </span>
            </div>

            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Reference</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Booking Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ $booking->customer_relation->customer_name ?? 'N/A' }}</td>
                                <td>{{ $booking->reference }}</td>
                                <td>
                                    @foreach ($booking->items as $item)
                                        {{ $item->product->item_name ?? 'N/A' }} <br>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach ($booking->items as $item)
                                        {{ $item->qty }} <br>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach ($booking->items as $item)
                                        {{ $item->price }} <br>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach ($booking->items as $item)
                                        {{ $item->discount_percent }}% <br>
                                    @endforeach
                                </td>
                                <td>{{ number_format($booking->total_net, 2) }}</td>
                                <td>{{ $booking->created_at->format('d-m-Y') }}</td>
                                <td>
                                    @can('bookings.view')
                                        <a href="{{ route('sales.dc', $booking->id) }}" target="_blank"
                                            class="btn btn-sm btn-outline-secondary">DC Receipt</a>
                                    @endcan

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection
