@extends('admin_panel.layout.app')
@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-header">
            <h5>➕ New Stock Transfer</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('stock_transfers.store') }}" method="POST">
                @csrf
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="mb-3">
                    <label>From Warehouse</label>
                    <select name="from_warehouse_id" id="from_warehouse_id" class="form-control" required>
                        <option value="">Select Warehouse</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <div class="row">
                        <div class="col-lg-6">
                            <label>To</label>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-check-label" for="toShop">Transfer to Shop</label>

                        </div>

                        <div class="col-6">
                            <select name="to_warehouse_id" class="form-control">
                                <option value="">Select Warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <input class="form-check-input form-control" type="checkbox" name="to_shop" value="1"
                                id="toShop">


                        </div>
                    </div>
                </div>

                <table class="w-100 border text-center" id="product_table">
                    <thead>
                        <tr class="bg-light">
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Qty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="product_body">
                        <tr class="product_row">
                            <td>
                                <select name="product_id[]" class="form-control product-select" required>
                                    <option value="">Select Warehouse First</option>
                                </select>
                                <div class="invalid-feedback product-error"
                                    style="display:none; font-size: 0.85rem; text-align: left;">
                                    Product already selected
                                </div>
                            </td>
                            <td>
                                <input type="number" name="available_stock[]" class="form-control stock" readonly>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="form-control quantity" required>
                                <div class="invalid-feedback stock-error"
                                    style="display:none; font-size: 0.85rem; text-align: left;">
                                    Insufficient Stock
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger remove-row">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mb-3">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control"></textarea>
                </div>

                <button type="submit" class="btn btn-success">Transfer Stock</button>
            </form>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        let availableProducts = [];

        // 1. Fetch Products when Warehouse Changes
        $('#from_warehouse_id').change(function() {
            var warehouseId = $(this).val();
            var productTableBody = $('#product_body');

            // Allow changing warehouse, keeping rows but resetting products? 
            // Better: warn user or reset table. For now, we reset products dropdowns.

            if (!warehouseId) {
                availableProducts = [];
                updateAllProductDropdowns();
                return;
            }

            $.ajax({
                url: "{{ route('get.products.by.warehouse') }}",
                data: {
                    warehouse_id: warehouseId
                },
                success: function(products) {
                    availableProducts = products;
                    updateAllProductDropdowns();
                },
                error: function() {
                    alert('Error fetching products for this warehouse');
                }
            });
        });

        // Update all dropdowns with available products
        function updateAllProductDropdowns() {
            $('.product-select').each(function() {
                var currentVal = $(this).val(); // preserve selection if still valid (unlikely but safe)
                var options = '<option value="">Select Product</option>';

                if (availableProducts.length === 0) {
                    options = '<option value="">No stock in this warehouse</option>';
                } else {
                    availableProducts.forEach(function(p) {
                        var selected = (p.id == currentVal) ? 'selected' : '';
                        options +=
                            `<option value="${p.id}" ${selected}>${p.item_name}</option>`;
                    });
                }
                $(this).html(options);

                // If previous selection is no longer valid, clear stock/qty
                if (!availableProducts.find(p => p.id == currentVal)) {
                    $(this).closest('tr').find('.stock').val('');
                    $(this).closest('tr').find('.quantity').val('').removeAttr('max');
                }
            });
        }

        // 2. Fetch Stock Quantity when Product Selected
        $(document).on('change', '.product-select', function() {
            var currentRow = $(this).closest('tr');
            var selectedProduct = $(this).val();
            var fromWarehouse = $('#from_warehouse_id').val();

            if (selectedProduct && fromWarehouse) {
                $.ajax({
                    url: "{{ route('warehouse.stock.quantity') }}",
                    method: 'GET',
                    data: {
                        warehouse_id: fromWarehouse,
                        product_id: selectedProduct
                    },
                    success: function(response) {
                        currentRow.find('.stock').val(response.quantity);
                        currentRow.find('.quantity').attr('max', response.quantity);

                        // Show visual feedback if 0
                        if (response.quantity <= 0) {
                            // Optional: Could add a red border to the stock field or standard visual cue
                            currentRow.find('.stock').addClass('text-danger fw-bold');
                        } else {
                            currentRow.find('.stock').removeClass('text-danger fw-bold');
                        }

                        validateForm(); // Re-validate in case user already typed a number
                    }
                });
            } else {
                currentRow.find('.stock').val('');
                currentRow.find('.quantity').removeAttr('max');
            }

            // Auto-add new row if it's the last one
            if ($('#product_body tr:last').is(currentRow) && selectedProduct) {
                addNewRow();
            }
        });

        // 3. Validate Quantity & Duplicates
        function validateForm() {
            let isValid = true;
            let productCounts = {};

            // Count occurrences of each product
            $('.product-select').each(function() {
                let val = $(this).val();
                if (val) {
                    productCounts[val] = (productCounts[val] || 0) + 1;
                }
            });

            // Validate Products (Duplicates)
            $('.product-select').each(function() {
                let val = $(this).val();
                let errorDiv = $(this).next('.product-error');

                if (val && productCounts[val] > 1) {
                    $(this).addClass('is-invalid');
                    errorDiv.show();
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                    errorDiv.hide();
                }
            });

            $('.quantity').each(function() {
                var entered = parseInt($(this).val()) || 0;
                var max = parseInt($(this).attr('max')) || 0;

                // If entered > max, it's invalid.
                var errorDiv = $(this).next('.stock-error');

                if (entered > max) {
                    $(this).addClass('is-invalid');
                    errorDiv.text('Insufficient Stock! Available: ' + max).show();
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                    errorDiv.hide();
                }
            });

            // Disable/Enable submit button
            $('button[type="submit"]').prop('disabled', !isValid);
        }

        $(document).on('input', '.quantity', function() {
            validateForm();
        });

        $(document).on('change', '.product-select', function() {
            validateForm();
        });

        // 4. Remove Row
        $(document).on('click', '.remove-row', function() {
            if ($('#product_body tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                // Clear the last remaining row instead of removing it
                var row = $(this).closest('tr');
                row.find('select').val('');
                row.find('input').val('');
                row.find('.stock').val('');
                row.find('.quantity').removeClass('is-invalid');
            }
            validateForm();
        });

        // 5. Add New Row Function
        function addNewRow() {
            var options = '<option value="">Select Product</option>';
            if (availableProducts.length > 0) {
                availableProducts.forEach(function(p) {
                    options += `<option value="${p.id}">${p.item_name}</option>`;
                });
            } else {
                options = '<option value="">Select Warehouse First</option>';
            }

            var row = `
                <tr class="product_row">
                    <td>
                        <select name="product_id[]" class="form-control product-select" required>
                            ${options}
                        </select>
                        <div class="invalid-feedback product-error" style="display:none; font-size: 0.85rem; text-align: left;">
                            Product already selected
                        </div>
                    </td>
                    <td>
                        <input type="number" name="available_stock[]" class="form-control stock" readonly>
                    </td>
                    <td>
                        <input type="number" name="quantity[]" class="form-control quantity" required min="1">
                        <div class="invalid-feedback stock-error" style="display:none; font-size: 0.85rem; text-align: left;">
                            Insufficient Stock
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-row">Remove</button>
                    </td>
                </tr>
            `;
            $('#product_body').append(row);
        }
    });
</script>
