@extends('admin_panel.layout.app')
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">💰 Product Discounts</h5>
            <small class="text-muted">Manage all product discounts here</small>
        </div>
        @if(auth()->user()->can('Create Discount') || auth()->user()->email === 'admin@admin.com')
            <a href="{{ route('product') }}" class="btn btn-success btn-sm">
                View Product
            </a>
        @endif
    </div>

    <div class="card-body">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show">
                ✅ {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="discountTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Descriptions</th>
                        {{-- <th>Image</th> --}}
                        {{-- <th>Category / Sub-Category</th> --}}
                        {{-- <th>Item Name</th> --}}
                        {{-- <th>Unit</th> --}}
                        {{-- <th>Brand</th> --}}
                        <th>Original Price</th>
                        <th>Discount</th>
                        {{-- <th>Flat Discount</th> --}}
                        <th>Date</th>
                        <th>Discount Price</th>
                        <th>Barcode</th>
                        <th>Status</th>
                    </tr>
                </thead>
               <tbody>
@foreach($discounts as $key => $discount)
    <tr class="{{ $discount->status ? '' : 'table-danger' }}">
        <td>{{ $key + 1 }}</td>

        {{-- Product Info Column --}}
        <td class="d-flex align-items-center">
            @if($discount->product->image)
                <img src="{{ asset('uploads/products/'.$discount->product->image) }}" 
                     width="45" height="45" class="rounded me-2">
            @else
                <span class="badge bg-secondary me-2">No Img</span>
            @endif
            <div>
                <strong>{{ $discount->product->item_name }}</strong><br>
                <small class="text-muted">Code: {{ $discount->product->item_code }}</small><br>
                <small class="text-muted">Brand: {{ $discount->product->brand->name ?? '-' }}</small>
            </div>
        </td>

        {{-- Prices --}}
        <td>{{ number_format($discount->actual_price,2) }}</td>
        
        {{-- Discount Column --}}
        <td>
            <span class="badge bg-info">{{ $discount->discount_percentage }}%</span> 
            <span class="badge bg-warning text-dark">{{ number_format($discount->discount_amount,2) }} PKR</span>
        </td>

        {{-- Date --}}
        <td>{{ \Carbon\Carbon::parse($discount->date)->format('d-M-Y') }}</td>

        {{-- Final Price --}}
        <td><strong class="text-success">{{ number_format($discount->final_price,2) }}</strong></td>

        {{-- Barcode --}}
        <td>
            <a href="{{ route('discount.barcode', $discount->id) }}" 
               class="btn btn-sm btn-outline-primary">
               🏷 Barcode
            </a>
        </td>

        {{-- Status --}}
        <td>
            <form action="{{ route('discount.toggleStatus', $discount->id) }}" method="POST">
                @csrf
                <button class="btn btn-sm {{ $discount->status ? 'btn-success' : 'btn-danger' }}">
                    {{ $discount->status ? '✔ Active' : '✖ Inactive' }}
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

{{-- DataTables JS --}}
<script>
$(document).ready(function() {
    $('#discountTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search discounts..."
        }
    });
});
</script>
@endsection
