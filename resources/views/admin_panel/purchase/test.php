@extends('admin_panel.layout.app')

@section('content')

<style>
    html, body {
        overflow: hidden;
        height: 100vh;
        margin: 0;
        padding: 0;
    }

    .purchase-form-wrapper {
        padding: 20px;
        max-width: 100%;
        height: 100vh;
        overflow: hidden;
        background-color: #f8f9fa;
    }

    .purchase-form-card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        height: calc(100vh - 100px); /* adjust height to fit within screen */
        overflow-y: auto;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .purchase-form-card::-webkit-scrollbar {
        display: none; /* hide scrollbar */
    }

    .form-control, .form-select {
        font-size: 14px;
        padding: 5px 10px;
    }

    h4 {
        font-size: 18px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid purchase-form-wrapper">
    <div class="purchase-form-card">
        <h4>Edit Purchase</h4>

        <form action="{{ route('purchase.update', $purchase->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label>Invoice No</label>
                    <input type="text" name="invoice_no" class="form-control" value="{{ $purchase->invoice_no }}">
                </div>
                <div class="col-md-4">
                    <label>Supplier</label>
                    <input type="text" name="supplier" class="form-control" value="{{ $purchase->supplier }}">
                </div>
                <div class="col-md-4">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="{{ $purchase->purchase_date }}">
                </div>

                <div class="col-md-6">
                    <label>Warehouse</label>
                    <input type="text" name="warehouse_id" class="form-control" value="{{ $purchase->warehouse_id }}">
                </div>
                <div class="col-md-6">
                    <label>Item Category</label>
                    <input type="text" name="item_category" class="form-control" value="{{ $purchase->item_category }}">
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (json_decode($purchase->item_name, true) as $index => $item)
                        <tr>
                            <td><input type="text" name="item_name[]" class="form-control form-control-sm" value="{{ $item }}"></td>
                            <td><input type="number" name="quantity[]" class="form-control form-control-sm" value="{{ json_decode($purchase->quantity)[$index] }}"></td>
                            <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm" value="{{ json_decode($purchase->price)[$index] }}"></td>
                            <td><input type="text" name="unit[]" class="form-control form-control-sm" value="{{ json_decode($purchase->unit)[$index] }}"></td>
                            <td><input type="number" name="total[]" class="form-control form-control-sm" value="{{ json_decode($purchase->total)[$index] }}"></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label>Total Price</label>
                    <input type="number" name="total_price" class="form-control" value="{{ $purchase->total_price }}">
                </div>
                <div class="col-md-4">
                    <label>Discount</label>
                    <input type="number" name="discount" class="form-control" value="{{ $purchase->discount }}">
                </div>
                <div class="col-md-4">
                    <label>Payable Amount</label>
                    <input type="number" name="Payable_amount" class="form-control" value="{{ $purchase->Payable_amount }}">
                </div>

                <div class="col-md-4">
                    <label>Paid Amount</label>
                    <input type="number" name="paid_amount" class="form-control" value="{{ $purchase->paid_amount }}">
                </div>
                <div class="col-md-4">
                    <label>Due Amount</label>
                    <input type="number" name="due_amount" class="form-control" value="{{ $purchase->due_amount }}">
                </div>
                <div class="col-md-4">
                    <label>Status</label>
                    <input type="text" name="status" class="form-control" value="{{ $purchase->status }}">
                </div>

                <div class="col-md-4">
                    <label>Return?</label>
                    <input type="text" name="is_return" class="form-control" value="{{ $purchase->is_return }}">
                </div>
            </div>

            <div class="mt-3">
                <label>Note</label>
                <textarea name="note" class="form-control">{{ $purchase->note }}</textarea>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success px-4">Update Purchase</button>
            </div>
        </form>
    </div>
</div>

@endsection
