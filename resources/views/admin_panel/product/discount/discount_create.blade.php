@extends('admin_panel.layout.app')
@section('content')
<div class="card">
    <div class="card-header">
        <h5>Create Discount for Selected Products</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('discount.store') }}" method="POST" id="discountForm">
            @csrf
            <table class="table table-bordered" id="discountCreateTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Original Price</th>
                        <th>Discount %</th>
                        <th>Discount PKR</th>
                        <th>Total Discount</th>
                        <th>Final Price</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $key => $product)
                    <tr>
                        <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                        <input type="hidden" name="actual_price[]" value="{{ $product->price }}">

                        <td>{{ $key + 1 }}</td>
                        <td>{{ $product->item_code }}</td>
                        <td>{{ $product->item_name }}</td>
                        <td class="originalPrice">{{ $product->price }}</td>

                        <td>
                            <input type="number" step="0.01" min="0" max="100"
                                   name="discount_percentage[]"
                                   class="form-control discountPercentage @error('discount_percentage.'.$key) is-invalid @enderror"
                                   value="{{ old('discount_percentage.'.$key, 0) }}">
                            {{-- live error --}}
                            <small class="text-danger live-error d-none"></small>
                            {{-- server error --}}
                            @error('discount_percentage.'.$key)
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="number" step="0.01" min="0"
                                   name="discount_amount[]"
                                   class="form-control discountAmount @error('discount_amount.'.$key) is-invalid @enderror"
                                   value="{{ old('discount_amount.'.$key, 0) }}">
                            {{-- live error --}}
                            <small class="text-danger live-error d-none"></small>
                            {{-- server error --}}
                            @error('discount_amount.'.$key)
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="number" step="0.01" name="total_discount[]"
                                   class="form-control totalDiscount" readonly
                                   value="{{ old('total_discount.'.$key) }}">
                        </td>

                        <td>
                            <input type="number" name="final_price[]"
                                   class="form-control finalPrice fw-bold" readonly
                                   value="{{ old('final_price.'.$key) }}">
                        </td>

                       <td>
                            <input type="date" name="date[]" class="form-control"
                                value="{{ old('date.'.$key, now()->toDateString()) }}">
                            @error('date.'.$key)
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <select name="status[]" class="form-control">
                                <option value="1" {{ old('status.'.$key, 1)==1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status.'.$key, 1)==0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="submit" class="btn btn-primary mt-3">Save Discounts</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){

    function showInlineError($inputs, msg){
        $inputs.each(function(){
            const $inp = $(this);
            $inp.addClass('is-invalid');
            const $err = $inp.closest('td').find('.live-error');
            $err.text(msg).removeClass('d-none');
        });
    }

    function clearInlineError($inputs){
        $inputs.each(function(){
            const $inp = $(this);
            $inp.removeClass('is-invalid');
            const $err = $inp.closest('td').find('.live-error');
            $err.text('').addClass('d-none');
        });
    }

    function clamp(val, min, max){
        return Math.min(Math.max(val, min), max);
    }

    function updateFinalPrice(row){
        const $percInput = row.find('.discountPercentage');
        const $amtInput  = row.find('.discountAmount');

        const original = parseFloat(row.find('input[name="actual_price[]"]').val()) || 0;
        let   perc     = parseFloat($percInput.val()) || 0;
        let   amt      = parseFloat($amtInput.val()) || 0;

        // local validations
        clearInlineError($percInput.add($amtInput));

        // % cap 0..100
        if (perc < 0 || perc > 100){
            showInlineError($percInput, 'Percentage must be between 0 and 100.');
            perc = clamp(perc, 0, 100);
            $percInput.val(perc);
        }

        // negative PKR not allowed
        if (amt < 0){
            showInlineError($amtInput, 'Discount PKR cannot be negative.');
            amt = 0;
            $amtInput.val(0);
        }

        // compute
        const percDiscount = (original * perc / 100);
        let   totalDiscount = percDiscount + amt;

        // combined limit
        if (totalDiscount > original) {
            const overflow = (totalDiscount - original).toFixed(2);
            showInlineError($percInput.add($amtInput), 'Total discount exceeds price by ' + overflow);
        }

        // final price not below 0
        const cappedTotal = Math.min(totalDiscount, original);
        const finalPrice  = original - cappedTotal;

        row.find('.totalDiscount').val(cappedTotal.toFixed(2));
        row.find('.finalPrice').val(finalPrice.toFixed(2));
    }

    // events
    $(document).on('input', '.discountPercentage, .discountAmount', function(){
        const row = $(this).closest('tr');
        updateFinalPrice(row);
    });

    // initial pass (handles old() values)
    $('#discountCreateTable tbody tr').each(function(){
        updateFinalPrice($(this));
    });
});
</script>
@endsection
