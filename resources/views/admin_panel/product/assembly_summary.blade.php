{{-- resources/views/admin_panel/product/assembly_summary.blade.php --}}
@extends('admin_panel.layout.app')
@section('content')
<div class="card">
  <div class="card-header">Assembly Summary</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>Product</th>
            <th>Ready Stock</th>
            <th>Assemble Possible</th>
            <th>Total Sellable</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>{{ $r['product_name'] }}</td>
              <td>{{ $r['ready_stock'] }}</td>
              <td>{{ $r['assemble_possible'] }}</td>
              <td>{{ $r['total_sellable'] }}</td>
              <td>
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('products.assembly-report', $r['product_id']) }}">
                   View Parts
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted">No assembled products found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
