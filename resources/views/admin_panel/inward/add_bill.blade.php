@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Add Bill For Good received note #{{ $gatepass->id }}</h3>
                    <a href="{{ route('InwardGatepass.home') }}" class="btn btn-secondary">Back</a>
                </div>

                <div class="col-lg-12 col-md-12 mb-30">
                    <div class="card">
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('store.bill', $gatepass->id) }}" method="POST">
                                @csrf

                                <!-- Gatepass Info -->
                                <div class="row g-3 mb-4">
                                    <!-- Vendor -->
                                    <div class="col-md-3">
                                        <label>Vendor</label>
                                        <input type="text" class="form-control" value="{{ $gatepass->vendor->name ?? '-' }}" readonly>
                                        <input type="hidden" name="vendor_id" value="{{ $gatepass->vendor_id }}">
                                    </div>

                                    <!-- Warehouse -->
                                    <div class="col-md-3">
                                        <label>Warehouse</label>
                                        <input type="text" class="form-control" value="{{ $gatepass->warehouse->warehouse_name ?? '-' }}" readonly>
                                        <input type="hidden" name="warehouse_id" value="{{ $gatepass->warehouse_id }}">
                                    </div>

                                    <!-- Purchase Date -->
                                    <div class="col-md-3">
                                        <label>Purchase Date</label>
                                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->toDateString()) }}">
                                    </div>
                                </div>

                                <!-- Product Table -->
                                <div style="max-height: 400px; position: relative; overflow-x: visible !important;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Product</th>
                                                <th>Item Code</th>
                                                <th>Qty</th>
                                                <th>Price</th>
                                                <th>Discount</th>
                                                <th>Unit</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="gatepassItems">
                                            @foreach($gatepass->items as $item)
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" value="{{ $item->product->item_name ?? '-' }}" readonly>
                                                    <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="item_code[]" class="form-control" value="{{ $item->product->item_code ?? '-' }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="qty[]" class="form-control qty" min="1" value="{{ $item->qty }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="price[{{ $item->id }}]" class="form-control price" value="{{ old('price.' . $item->id, 0) }}" min="0" step="0.01">
                                                </td>
                                                <td>
                                                    <input type="number" name="item_discount[{{ $item->id }}]" class="form-control item_discount" value="{{ old('item_discount.' . $item->id, 0) }}" min="0" step="0.01">
                                                </td>
                                                <td>
                                                    <input type="text" name="unit[{{ $item->id }}]" class="form-control unit" value="{{ $item->product->unit ?? '-' }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" name="total[{{ $item->id }}]" class="form-control row-total" readonly>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row g-3 mt-3">
                                    <div class="col-md-3">
                                        <label>Subtotal</label>
                                        <input type="text" id="subtotal" name="subtotal" class="form-control" value="0" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Discount</label>
                                        <input type="number" step="0.01" id="discount" name="discount" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Extra Cost</label>
                                        <input type="number" step="0.01" id="extra_cost" name="extra_cost" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Net Amount</label>
                                        <input type="text" id="net_amount" name="net_amount" class="form-control" value="0" readonly>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mt-4">Submit Bill</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Calculate row total
        function recalcRow($row) {
            const qty = parseFloat($row.find('.qty').val()) || 0;
            const price = parseFloat($row.find('.price').val()) || 0;
            const discount = parseFloat($row.find('.item_discount').val()) || 0;
            const total = (qty * price) - (qty * discount);
            $row.find('.row-total').val(total.toFixed(2));
        }

        // Calculate subtotal, discount, extra cost, and net amount
        function recalcSummary() {
            let subtotal = 0;
            $('input[name^="total"]').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            $('#subtotal').val(subtotal.toFixed(2));

            const discount = parseFloat($('#discount').val()) || 0;
            const extraCost = parseFloat($('#extra_cost').val()) || 0;
            const netAmount = subtotal - discount + extraCost;
            $('#net_amount').val(netAmount.toFixed(2));
        }

        // Recalculate on price or quantity change
        $('input[name^="price"], input[name^="qty"], input[name^="item_discount"]').on('input', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        // Initial calculations
        $('tr').each(function() {
            recalcRow($(this));
        });
        recalcSummary();
    });
</script>
