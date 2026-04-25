@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Premium Modal & UI Styles */
        .premium-card {
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px 30px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.25rem;
        }

        .modal-body {
            padding: 30px;
            background: #f8fafc;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .info-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .big-input {
            font-size: 1.25rem;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px;
        }

        .details-card {
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }

        .calc-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .calc-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0369a1;
        }

        /* Select2 fixes for Modal */
        .select2-container {
            width: 100% !important;
            z-index: 9999;
        }

        .select2-dropdown {
            z-index: 9999;
        }

        .select2-container--default .select2-selection--single {
            height: 45px;
            display: flex;
            align-items: center;
            border-color: #e2e8f0;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }
    </style>

    <div class="container-fluid">
        <div class="card premium-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-warehouse me-2 text-success"></i> Warehouse Stock</h5>
                @can('warehouse.stock.create')
                    <button onclick="openAddModal()" class="btn btn-success shadow-sm rounded-pill px-4">
                        <i class="fas fa-plus me-1"></i> Add Stock
                    </button>
                @endcan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="stockTable">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="text-uppercase small fw-bold">#</th>
                                <th class="text-uppercase small fw-bold">Warehouse</th>
                                <th class="text-uppercase small fw-bold">Product</th>
                                <th class="text-uppercase small fw-bold text-center">Total box</th>
                                <th class="text-uppercase small fw-bold text-center">Total Pieces</th>
                                {{-- <th class="text-uppercase small fw-bold">Remarks</th> --}}
                                <th class="text-uppercase small fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stocks as $stock)
                                <tr>
                                    <td>{{ $stocks->firstItem() + $loop->index }}</td>
                                    <td>{{ $stock->warehouse->warehouse_name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if ($stock->product && $stock->product->image)
                                                <img src="{{ asset('uploads/products/' . $stock->product->image) }}"
                                                    class="rounded me-2"
                                                    style="width: 30px; height: 30px; object-fit: cover;">
                                            @else
                                                <div class="rounded me-2 bg-secondary d-flex align-items-center justify-content-center text-white"
                                                    style="width: 30px; height: 30px; font-size: 10px;">IMG</div>
                                            @endif
                                            <div>
                                                <div class="fw-bold text-dark">{{ $stock->product->item_name ?? 'N/A' }}
                                                </div>
                                                <div class="small text-muted">{{ $stock->product->item_code ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge bg-info text-dark">{{ $stock->quantity }} Boxes</span>
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        {{ number_format($stock->total_pieces) }}</td>
                                    <td class="text-end">
                                        @can('warehouse.stock.edit')
                                            <button onclick="editStock({{ $stock->id }})"
                                                class="btn btn-sm btn-icon btn-outline-primary rounded-circle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endcan

                                        <form action="{{ route('warehouse_stocks.destroy', $stock->id) }}" method="POST"
                                            class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                            @csrf @method('DELETE')
                                            @can('warehouse.stock.delete')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger rounded-circle ms-1"><i
                                                        class="fas fa-trash"></i></button>
                                            @endcan
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $stocks->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Modal (Add & Edit) -->
    <div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stockForm">
                        @csrf
                        <input type="hidden" id="stock_id" name="stock_id">
                        <input type="hidden" id="method_field" name="_method" value="POST">

                        <div class="row g-3">
                            <!-- Warehouse (Col 1) -->
                            <div class="col-md-6">
                                <label class="info-label">Warehouse</label>
                                <select id="warehouse_id" name="warehouse_id" class="form-control" required>
                                    <option value="">Select Warehouse</option>
                                    <!-- Populated via Ajax -->
                                </select>
                            </div>

                            <!-- Product (Col 2) -->
                            <div class="col-md-6">
                                <label class="info-label">Product</label>
                                <select id="product_id" name="product_id" class="form-control" disabled>
                                    <option value="">Select Warehouse First</option>
                                </select>
                            </div>
                        </div>

                        <!-- Product Info Card -->
                        <div id="product-details" class="details-card mt-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <img id="prod-img" src=""
                                        style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; display: none;">
                                    <div id="prod-icon" class="text-secondary"><i class="fas fa-box fa-2x"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" id="prod-name">--</div>
                                    <div class="d-flex small text-muted gap-3 mt-1">
                                        <span>Code: <b id="prod-code">--</b></span>
                                        <span>PPB: <b id="prod-ppb" class="text-primary">--</b></span>
                                        <span>Current: <b id="prod-current" class="text-success">--</b></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity Input -->
                        <div class="row mt-3 align-items-end">
                            <div class="col-md-6">
                                <label class="info-label" id="qty-label">Quantity</label>
                                <input type="number" name="quantity_input" id="quantity_input"
                                    class="form-control big-input" placeholder="0" min="0" disabled>
                                <small class="text-muted" id="qty-help">Enter amount.</small>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="calc-box">
                                    <div class="text-uppercase small text-muted fw-bold mb-1">Total Boxes</div>
                                    <div class="calc-number" id="calc-result">0</div>
                                    <input type="hidden" name="total_pieces" id="total_pieces" value="0">
                                    <input type="hidden" name="total_box" id="total_box" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="info-label">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-success py-3 fw-bold shadow-sm" id="btn-save">
                                <span class="btn-text">Save Stock Entry</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('js')
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Global State
        let piecesPerBox = 0;
        let selectedProduct = null;
        let isEditMode = false;

        $(document).ready(function() {
            initSelect2();

            // Modal Events
            const modalEl = document.getElementById('stockModal');
            modalEl.addEventListener('hidden.bs.modal', function() {
                resetForm();
            });

            // Product Selection Logic
            $('#product_id').on('select2:select', function(e) {
                handleProductSelect(e.params.data);
            });

            // Warehouse Change logic
            $('#warehouse_id').on('change', function() {
                // If product is already selected, fetch stock overlap
                if (selectedProduct && $(this).val()) {
                    fetchCurrentStock($(this).val(), selectedProduct.id);
                }
            });

            // Allow product selection immediately
            $('#product_id').prop('disabled', false);

            // Calculation
            $('#quantity_input').on('input', function() {
                calculateTotal();
            });
        });

        // --- Core Functions ---

        function initSelect2() {
            $('#warehouse_id').select2({
                dropdownParent: $('#stockModal'),
                placeholder: "Select Warehouse",
                allowClear: true,
                ajax: {
                    url: "{{ route('warehouse_stock.search-warehouses') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });

            $('#product_id').select2({
                dropdownParent: $('#stockModal'),
                placeholder: "Select Product",
                allowClear: true,
                ajax: {
                    url: "{{ route('warehouse_stock.search-products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });
        }

        function openAddModal() {
            isEditMode = false;
            $('#modalTitle').text('Add Stock');
            $('#method_field').val('POST');
            $('#btn-save .btn-text').text('Add Stock');

            // Reset state handled by hidden event, but ensure UI is ready
            var myModal = new bootstrap.Modal(document.getElementById('stockModal'));
            myModal.show();
        }

        function editStock(id) {
            isEditMode = true;
            $('#modalTitle').text('Update Stock');
            $('#method_field').val('PUT'); // Simulate PUT for update
            $('#btn-save .btn-text').text('Update Stock');
            $('#stock_id').val(id);

            let url = "{{ route('warehouse_stock.edit-data', ':id') }}";
            url = url.replace(':id', id);

            // Show Loaders or Disable UI here if needed
            $.get(url, function(data) {
                // Populate Form
                // Manually Create Option for Warehouse Select2 since it is now AJAX
                let whOption = new Option(data.warehouse_name, data.warehouse_id, true, true);
                $('#warehouse_id').append(whOption).trigger('change');

                // Manually Create Option for Product Select2
                let option = new Option(data.product_name, data.product_id, true, true);
                $('#product_id').append(option).trigger('change');

                // Trigger selection logic manually to setup variables
                // We construct a mock data object similar to what Select2 provides
                let mockData = {
                    id: data.product_id,
                    item_name: data.product_name,
                    item_code: data.product_code,
                    pieces_per_box: data.pieces_per_box,
                    image: data.image
                };
                handleProductSelect(mockData);

                // Populate Quantity - User enters total pieces
                $('#quantity_input').val(data.total_pieces);
                $('#total_pieces').val(data.total_pieces);

                // Calculate and display total boxes
                if (data.pieces_per_box > 0 && data.total_pieces > 0) {
                    let boxes = Math.floor(data.total_pieces / data.pieces_per_box);
                    let loose = data.total_pieces % data.pieces_per_box;
                    $('#calc-result').html(`<span class="text-primary">${boxes} Boxes</span>` + (loose > 0 ?
                        ` <span class="text-muted text-small">+ ${loose} Loose</span>` : ''));
                    $('#total_box').val(boxes);
                } else {
                    $('#calc-result').text(data.total_pieces + ' Pcs');
                    $('#total_box').val(0);
                }

                $('#remarks').val(data.remarks);

                // Show Modal
                var myModal = new bootstrap.Modal(document.getElementById('stockModal'));
                myModal.show();
            }).fail(function() {
                Swal.fire('Error', 'Could not fetch stock data.', 'error');
            });
        }

        function handleProductSelect(data) {
            selectedProduct = data;
            piecesPerBox = parseInt(data.pieces_per_box) || 0;

            // UI Updates
            $('#product-details').fadeIn();
            $('#prod-name').text(data.item_name);
            $('#prod-code').text(data.item_code || '--');
            $('#prod-ppb').text(piecesPerBox > 0 ? piecesPerBox : 'N/A (Loose)');

            if (data.image) {
                $('#prod-img').attr('src', data.image).show();
                $('#prod-icon').hide();
            } else {
                $('#prod-img').hide();
                $('#prod-icon').show();
            }

            // Input Configuration - Always enter Total Pieces as per user request
            $('#quantity_input').prop('disabled', false).focus();
            $('#qty-label').text('Total Pieces');
            $('#qty-help').text('Enter total quantity in pieces.');

            // Fetch Current Stock
            /* 
               Only fetch if Warehouse IS selected. 
               If not selected, we just show product details. 
               The Warehouse 'change' event will trigger the fetch later.
            */
            let whId = $('#warehouse_id').val();
            if (whId) {
                fetchCurrentStock(whId, data.id);
            } else {
                $('#prod-current').text('Select Warehouse');
            }

            calculateTotal();
        }

        function fetchCurrentStock(warehouseId, productId) {
            if (!isEditMode) {
                $.get("{{ route('warehouse_stock.get-stock') }}", {
                    warehouse_id: warehouseId,
                    product_id: productId
                }, function(res) {
                    let currentTotal = parseInt(res.total_pieces) || 0;
                    let displayStock = currentTotal + " Pcs";

                    if (piecesPerBox > 0) {
                        let b = Math.floor(currentTotal / piecesPerBox);
                        let l = currentTotal % piecesPerBox;
                        displayStock = `${b} Boxes` + (l > 0 ? ` + ${l} Loose` : '');
                    }
                    $('#prod-current').text(displayStock);
                });
            } else {
                // Edit mode fallback
                let currentTotal = parseInt($('#total_pieces').val()) || 0;
                let displayStock = currentTotal + " Pcs (Current Rec)";

                if (piecesPerBox > 0) {
                    let b = Math.floor(currentTotal / piecesPerBox);
                    let l = currentTotal % piecesPerBox;
                    displayStock = `${b} Boxes` + (l > 0 ? ` + ${l} Loose` : '') + " (Current Rec)";
                }
                $('#prod-current').text(displayStock);
            }
        }

        function calculateTotal() {
            let inputQty = parseInt($('#quantity_input').val()) || 0;
            let total = inputQty;

            // Store total pieces (which is the input now)
            $('#total_pieces').val(total);

            // Display Box Estimation
            if (piecesPerBox > 0 && total > 0) {
                let boxes = Math.floor(total / piecesPerBox);
                let loose = total % piecesPerBox;
                $('#calc-result').html(`<span class="text-primary">${boxes} Boxes</span>` + (loose > 0 ?
                    ` <span class="text-muted text-small">+ ${loose} Loose</span>` : ''));

                // Set hidden box input
                $('#total_box').val(boxes);
            } else {
                $('#calc-result').text(total + ' Pcs');
                $('#total_box').val(0);
            }
        }

        function resetForm() {
            // Clear state FIRST to avoid race conditions with events
            selectedProduct = null;
            piecesPerBox = 0;

            $('#stockForm')[0].reset();
            $('#warehouse_id').val(null).trigger('change');
            $('#product_id').val(null).trigger('change');
            $('#product-details').hide();
            $('#quantity_input').prop('disabled', true);
            $('#calc-result').text('0');
            $('#stock_id').val('');
        }

        // Submit Logic
        $('#stockForm').on('submit', function(e) {
            e.preventDefault();

            let url = "{{ route('warehouse_stocks.store') }}";
            if (isEditMode) {
                let id = $('#stock_id').val();
                url = "{{ route('warehouse_stocks.update', ':id') }}";
                url = url.replace(':id', id);
            }

            let formData = $(this).serialize();
            let btn = $('#btn-save');
            btn.prop('disabled', true);
            $.ajax({
                url: url,
                method: "POST", // Method spoofing is handled by _method field
                data: formData,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Simple reload to refresh table
                    });
                },
                error: function(xhr) {
                    let msg = 'Error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Error', msg, 'error');
                    btn.prop('disabled', false);
                }
            });
        });
    </script>
@endsection
