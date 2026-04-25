@extends('admin_panel.layout.app')
@section('content')
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">Inventory On-Hand</h5>
    <a href="{{ route('product') }}" class="btn btn-sm btn-outline-secondary">Back to Products</a>
  </div>

  <div class="card">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Name</th>
              <th>Brand</th>
              <th>UOM</th>
              <th class="text-end">On-Hand</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $i => $r)
              <tr>
                <td>{{ $i+1 }}</td>
                <td class="text-muted">{{ $r->item_code }}</td>
                <td>{{ $r->item_name }}</td>
                <td>{{ $r->brand_name }}</td>
                <td>{{ $r->unit_name }}</td>
                <td class="text-end">{{ rtrim(rtrim(number_format($r->onhand_qty, 3, '.', ''), '0'), '.') }}</td>
                <td>
                  @if($r->is_part)
                    <span class="badge bg-info">Part</span>
                  @elseif($r->is_assembled)
                    <span class="badge bg-primary">Assembled</span>
                  @else
                    <span class="badge bg-secondary">Simple</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted">No data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection
