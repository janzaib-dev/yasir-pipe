@extends('admin_panel.layout.app')
@section('content')
    <style>
        div.dataTables_wrapper div.dataTables_length select {
            width: 75px !important
        }
    </style>



    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">📦 Product List</h5>
                <small class="text-muted">Manage all products here</small>
            </div>
            <div class="d-flex justify-content-between align-items-end gap-1">
                <!-- @if (auth()->user()->can('discount.products.view') || auth()->user()->email === 'admin@admin.com')
                    <a href="{{ route('discount.index') }}" class="btn btn-success btn-sm">
                        View Discount
                    </a>
                @endif -->
                @if (auth()->user()->can('products.create') || auth()->user()->email === 'admin@admin.com')
                    <a href="create_prodcut" class="btn btn-primary"> Add product</a>
                @endif

                <!-- @if (auth()->user()->can('discount.products.create') || auth()->user()->email === 'admin@admin.com')
                    <button id="createDiscountBtn" class="btn btn-success btn-sm">
                        ➡ Create Discount
                    </button>
                @endif -->
            </div>

        </div>

        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    ✅ {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="productTable" class="table table-striped table-bordered align-middle nowrap" style="width:100%">
                    <div class="mb-3">
                        <input type="text" id="search_all" class="form-control"
                            placeholder="Search Item Name, Code, Category, Brand">
                    </div>

                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>#</th>
                            <th>Code</th>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th>Stock</th>
                            <th>Trade Price</th>
                            <th>Retail Price</th>
                            <th>Brand</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $key => $product)
                            <tr>
                                <td><input type="checkbox" class="selectProduct" value="{{ $product->id }}"></td>
                                <td>{{ $key + 1 }}</td>
                                <td class="fw-bold">{{ $product->item_code }}</td>
                                <td>
                                    @if ($product->image)
                                        <img src="{{ asset('uploads/products/' . $product->image) }}" alt="Product"
                                            width="50" height="50" class="rounded border">
                                    @else
                                        <span class="badge bg-secondary">No Img</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $product->category_relation->name ?? '-' }}</strong><br>
                                    <small class="text-muted">{{ $product->sub_category_relation->name ?? '-' }}</small>
                                </td>
                                <td>{{ $product->item_name }}</td>
                                @php
                                    $stockPieces = (float) ($product->warehouse_stocks_sum_total_pieces ?? 0);
                                    $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                                    
                                    if (($product->size_mode === 'by_cartons' || $product->size_mode === 'by_size') && $ppb > 1) {
                                        $boxes = floor($stockPieces / $ppb);
                                        $loose = $stockPieces % $ppb;
                                        $stockDisplay = $loose > 0 ? "{$boxes}.{$loose} <small class='text-muted'>(Box.Loose)</small>" : "{$boxes} <small class='text-muted'>Boxes</small>";
                                    } else {
                                        $stockDisplay = "{$stockPieces} <small class='text-muted'>Pcs</small>";
                                    }

                                    // Prices based on mode
                                    $tradePrice = 0;
                                    $retailPrice = 0;
                                    if ($product->size_mode === 'by_size') {
                                        $m2PerPiece = ($product->height * $product->width) / 10000;
                                        $tradePrice = $m2PerPiece * (float)$product->purchase_price_per_m2;
                                        $retailPrice = $m2PerPiece * (float)$product->price_per_m2;
                                    } else {
                                        $tradePrice = (float)$product->purchase_price_per_piece;
                                        $retailPrice = (float)$product->sale_price_per_piece ?: (float)$product->sale_price_per_box; 
                                    }
                                @endphp
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.85rem;">{!! $stockDisplay !!}</span>
                                </td>
                                <td>Rs. {{ number_format($tradePrice, 2) }} <small class="text-muted">/pc</small></td>
                                <td>Rs. {{ number_format($retailPrice, 2) }} <small class="text-muted">/pc</small></td>
                                <td>{{ $product->brand->name ?? '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning viewProductBtn"
                                        data-id="{{ $product->id }}">
                                        View
                                    </button>


                                    @if (auth()->user()->can('products.edit') || auth()->user()->email === 'admin@admin.com')
                                        <a href="{{ route('products.edit', $product->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            ✏ Edit
                                        </a>
                                    @endif

                                    <a href="{{ route('generate-barcode-image', $product->id) }}"
                                        class="btn btn-sm btn-outline-success">
                                        🏷 Barcode
                                    </a>



                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- add product modal --}}

    <div class="modal fade bd-example-modal-lg" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">Please use the main "Add Product" page for the new per-m² flow.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail View Modal (BS4 Simple 3-Panel) -->
    <div class="modal fade" id="productViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-sm">

                <!-- Header -->
                <div class="modal-header bg-white border-bottom-0 pb-0">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark" id="view_item_name">Product Name</h5>
                        <p class="text-muted small mb-0"><i class="las la-barcode"></i> <span
                                id="view_item_code">CODE</span></p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body bg-light p-3">

                    <!-- Loading Spinner -->
                    <div id="modalLoadingSpinner" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="row" id="modalContentRow">

                        <!-- Panel 1: Information -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card h-100 border-0 shadow-sm rounded">
                                <div class="card-body p-3">
                                    <h6 class="text-uppercase text-primary font-weight-bold small mb-3 border-bottom pb-2">
                                        1. Information</h6>

                                    <div class="text-center mb-3">
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto"
                                            style="width: 100px; height: 100px; overflow: hidden; border: 1px solid #eee;">
                                            <img id="view_image_preview" src="" class="img-fluid d-none">
                                            <div id="view_image_placeholder" class="text-center">
                                                <i class="las la-image text-muted" style="font-size: 2rem;"></i>
                                                <small class="d-block text-muted" style="font-size: 10px;">No
                                                    Image</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Category</small>
                                        <span class="font-weight-bold text-dark" id="view_cat_sub">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Brand / Model</small>
                                        <span class="font-weight-bold text-dark" id="view_brand_model">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Colors</small>
                                        <span class="text-dark" id="view_color" style="font-size: 0.9rem;">-</span>
                                    </div>

                                    <div class="mb-0 border-top pt-2 mt-2">
                                        <small class="text-muted d-block">Created On</small>
                                        <span class="text-dark small" id="view_created_at">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 2: Measurement & Stock -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card h-100 border-0 shadow-sm rounded">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h6 class="text-uppercase text-info font-weight-bold small mb-0">2. Measurement
                                        </h6>
                                        <span class="badge badge-secondary" id="view_size_mode_badge">Mode</span>
                                    </div>

                                    <!-- By Size -->
                                    <div id="sec_by_size" class="d-none">
                                        <div class="row no-gutters mb-2">
                                            <div class="col-6 pr-1">
                                                <small class="text-muted d-block">Dim (HxW)</small>
                                                <span class="font-weight-bold text-dark" id="view_dimensions">-</span>
                                            </div>
                                            <div class="col-6 pl-1">
                                                <small class="text-muted d-block">m²/Pc</small>
                                                <span class="font-weight-bold text-dark" id="view_m2_piece">-</span>
                                            </div>
                                        </div>
                                        <div class="bg-light p-2 rounded mb-2 border">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Box Qty</small>
                                                <strong class="text-dark" id="view_boxes_qty_size">-</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Pcs/Box</small>
                                                <strong class="text-dark" id="view_pcs_box_size">-</strong>
                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <small class="text-muted d-block Uppercase">Total Area (m²)</small>
                                            <div class="h5 font-weight-bold text-info" id="view_total_m2">-</div>
                                        </div>
                                    </div>

                                    <!-- By Box/Carton -->
                                    <div id="sec_packing" class="d-none">
                                        <div class="row text-center mb-2 mx-0">
                                            <div class="col-4 px-1">
                                                <div class="bg-light p-1 rounded border">
                                                    <small class="d-block" style="font-size: 0.6rem;">PCS/BOX</small>
                                                    <strong class="text-dark" id="view_pcs_box">-</strong>
                                                </div>
                                            </div>
                                            <div class="col-4 px-1">
                                                <div class="bg-light p-1 rounded border">
                                                    <small class="d-block" style="font-size: 0.6rem;">BOXES</small>
                                                    <strong class="text-primary" id="view_boxes_qty">-</strong>
                                                </div>
                                            </div>
                                            <div class="col-4 px-1">
                                                <div class="bg-light p-1 rounded border">
                                                    <small class="d-block" style="font-size: 0.6rem;">LOOSE</small>
                                                    <strong class="text-warning" id="view_loose_pcs">-</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- By Piece -->
                                    <div id="sec_by_piece" class="d-none text-center mb-3">
                                        <div class="alert alert-light border">
                                            <i class="las la-layer-group text-primary" style="font-size: 1.5rem;"></i>
                                            <br>
                                            <span class="text-muted small">Unit Tracking Only</span>
                                        </div>
                                    </div>

                                    <!-- Total Stock -->
                                    <div class="mt-auto pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted font-weight-bold">TOTAL PCS</small>
                                            <span class="h4 mb-0 font-weight-bold text-success"
                                                id="view_total_stock_qty">0</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Panel 3: Financial -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded">
                                <div class="card-body p-3">
                                    <h6 class="text-uppercase text-success font-weight-bold small mb-3 border-bottom pb-2">
                                        3. Financials</h6>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted font-weight-bold" id="lbl_price_unit">Sale
                                                Price</small>
                                            <span class="font-weight-bold text-dark" id="view_price_unit">-</span>
                                        </div>
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted font-weight-bold" id="lbl_purch_unit">Purch
                                                Price</small>
                                            <span class="text-secondary" id="view_purch_unit">-</span>
                                        </div>
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar bg-secondary" style="width: 60%"></div>
                                        </div>
                                    </div>

                                    <div class="alert alert-success p-2 mb-0 mt-4 mx-0 text-center"
                                        style="background-color: #d1e7dd; border-color: #badbcc;">
                                        <small class="d-block text-success font-weight-bold text-uppercase"
                                            style="font-size: 0.7rem;">Est. Sale Value</small>
                                        <div class="font-weight-bold text-dark h4 mb-0" id="view_sale_total">-</div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Total Purch: <span id="view_purch_total"
                                                class="text-danger">-</span></small>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Simple Footer -->
                <div class="modal-footer border-top-0 py-2 bg-white rounded-bottom">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4"
                        data-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>




    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- product model --}}
    <script>
        $(document).on('click', '.viewProductBtn', function() {
            let productId = $(this).data('id');

            // 1. Reset & Loading State
            $('#modalContentRow').addClass('d-none');
            $('#modalLoadingSpinner').removeClass('d-none');
            $('#productViewModal').modal('show');

            $.ajax({
                url: "/productview/" + productId,
                type: "GET",
                success: function(product) {

                    // 2. Hide Spinner, Show Content
                    $('#modalLoadingSpinner').addClass('d-none');
                    $('#modalContentRow').removeClass('d-none');

                    // --- Basic ---
                    $('#view_item_name').text(product.item_name ?? 'Unknown Product');
                    $('#view_item_code').text(product.item_code ?? 'N/A');
                    $('#view_cat_sub').text((product.category_relation?.name ?? '') + (product
                        .sub_category_relation ? ' • ' + product.sub_category_relation.name : ''
                    ));
                    $('#view_brand_model').text((product.brand?.name ?? '-') + (product.model ? ' / ' +
                        product.model : ''));

                    $('#view_created_at').text(product.created_at ? new Date(product.created_at)
                        .toLocaleDateString() : '-');

                    // --- Image ---
                    if (product.image) {
                        $('#view_image_preview').attr('src', '/uploads/products/' + product.image)
                            .removeClass('d-none');
                        $('#view_image_placeholder').addClass('d-none');
                    } else {
                        $('#view_image_preview').addClass('d-none');
                        $('#view_image_placeholder').removeClass('d-none');
                    }

                    // --- Colors ---
                    if (product.color) {
                        try {
                            let colors = JSON.parse(product.color);
                            $('#view_color').text(Array.isArray(colors) ? colors.join(', ') : colors);
                        } catch (e) {
                            $('#view_color').text(product.color);
                        }
                    } else {
                        $('#view_color').text('-');
                    }

                    // --- Mode & Layout Switching ---
                    let mode = product.size_mode ?? 'by_size';

                    // Defaults
                    $('#sec_by_size, #sec_packing, #sec_by_piece').addClass('d-none');

                    let calcBoxes = product.calculated_boxes_quantity ?? 0;
                    let calcLoose = product.calculated_loose_pieces ?? 0;
                    let calcTotal = product.calculated_total_stock_qty ?? 0;

                    let salePrice = 0;
                    let purchPrice = 0;
                    let estSaleVal = 0;
                    let estPurchVal = 0;

                    if (mode === 'by_size') {
                        $('#view_size_mode_badge').text('By Size').removeClass('bg-info bg-warning')
                            .addClass('bg-light text-primary border-primary');
                        $('#sec_by_size').removeClass('d-none');

                        // Fill Size Data
                        $('#view_dimensions').text((product.height ?? 0) + ' x ' + (product.width ??
                            0));
                        let m2Piece = ((product.height * product.width) / 10000).toFixed(4);
                        $('#view_m2_piece').text(m2Piece);
                        $('#view_boxes_qty_size').text(calcBoxes); // Box count for Size mode
                        $('#view_pcs_box_size').text(product.pieces_per_box ?? 0);
                        $('#view_total_m2').text(parseFloat(product.total_m2 ?? 0).toFixed(2));

                        // Stock
                        $('#view_total_stock_qty').text(calcTotal);

                        // Price Labels
                        $('#lbl_price_unit').text('Price per m²');
                        $('#lbl_purch_unit').text('Cost per m²');
                        salePrice = product.price_per_m2;
                        purchPrice = product.purchase_price_per_m2;

                        estSaleVal = (product.total_m2 ?? 0) * calcBoxes * salePrice;
                        estPurchVal = (product.total_m2 ?? 0) * calcBoxes * purchPrice;

                    } else if (mode === 'by_cartons') {
                        $('#view_size_mode_badge').text('By Box').removeClass(
                            'bg-light text-primary border-primary bg-warning').addClass(
                            'bg-info text-white border-0');
                        $('#sec_packing').removeClass('d-none');

                        $('#view_boxes_qty').text(calcBoxes);
                        $('#view_loose_pcs').text(calcLoose);
                        $('#view_pcs_box').text(product.pieces_per_box ?? '-');

                        // Stock
                        $('#view_total_stock_qty').text(calcTotal);

                        // Price Labels
                        $('#lbl_price_unit').text('Price per Box');
                        $('#lbl_purch_unit').text('Cost per Piece');
                        salePrice = product.sale_price_per_box;
                        purchPrice = product.purchase_price_per_piece;

                        // Calc Value
                        // Sale Value: Boxes * SalePricePerBox + Loose * (SalePricePerBox/PcsPerBox)
                        let ppb = product.pieces_per_box > 0 ? product.pieces_per_box : 1;
                        let pricePerPieceScale = salePrice / ppb;
                        estSaleVal = calcTotal * pricePerPieceScale;
                        estPurchVal = calcTotal * purchPrice;

                    } else { // by_pieces
                        $('#view_size_mode_badge').text('By Piece').removeClass(
                            'bg-light text-primary border-primary bg-info text-white').addClass(
                            'bg-warning text-dark border-0');
                        $('#sec_by_piece').removeClass('d-none');

                        // Stock
                        $('#view_total_stock_qty').text(calcTotal);

                        // Price Labels
                        $('#lbl_price_unit').text('Price per Piece');
                        $('#lbl_purch_unit').text('Cost per Piece');
                        salePrice = product.sale_price_per_box;
                        purchPrice = product.purchase_price_per_piece;

                        estSaleVal = calcTotal * salePrice;
                        estPurchVal = calcTotal * purchPrice;
                    }

                    // Format Financials
                    $('#view_price_unit').text('Rs. ' + parseFloat(salePrice || 0).toFixed(2));
                    $('#view_purch_unit').text('Rs. ' + parseFloat(purchPrice || 0).toFixed(2));
                    $('#view_sale_total').text('Rs. ' + parseFloat(estSaleVal || 0).toLocaleString(
                        'en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                    $('#view_purch_total').text('Rs. ' + parseFloat(estPurchVal || 0).toLocaleString(
                        'en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));

                    $('#productViewModal').modal('show');
                },
                error: function() {
                    $('#modalLoadingSpinner').addClass('d-none');
                    Swal.fire('Error', 'Could not fetch details', 'error');
                }
            });
        });
    </script>


    <script>
        $(document).ready(function() {

            // Select/Deselect all checkboxes
            $('#selectAll').click(function() {
                $('.selectProduct').prop('checked', this.checked);
            });

            // On "Create Discount" click
            $('#createDiscountBtn').click(function() {
                var selected = [];
                $('.selectProduct:checked').each(function() {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Please select at least one product!",

                    });
                    return;
                }

                // Redirect with product IDs as query param
                window.location.href = "{{ route('discount.create') }}" + "?products=" + selected.join(
                    ',');
            });
        });
    </script>

    <script>
        $(document).ready(function() {

            function debounce(func, delay) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => func.apply(this, args), delay);
                }
            }

            let table = $('#productTable').DataTable({
                responsive: true,
                paging: false,
                ordering: true,
                info: false,
                order: [
                    [1, 'asc']
                ],
                dom: '<"top"f>rt<"bottom"><"clear">',
                language: {
                    search: "",
                    searchPlaceholder: "Search by code, name, category, brand..."
                },
                columnDefs: [{
                    targets: [0, 11],
                    searchable: false
                }, ]
            }); // Optional: fast typing experience 
            $('.dataTables_filter input').off().on('keyup', function() {
                table.search(this.value).draw();
            });
            // ===== Initialize Products DataTable =====

        });
    </script>

    <!-- DataTables CSS -->
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let cartonQuantityInput = document.getElementById("carton_quantity");
        let piecesPerCartonInput = document.getElementById("pieces_per_carton");
        let initialStockInput = document.getElementById("initial_stock");

        if (cartonQuantityInput && piecesPerCartonInput && initialStockInput) {
            function updateInitialStock() {
                let cartonQuantity = parseInt(cartonQuantityInput.value) || 0;
                let piecesPerCarton = parseInt(piecesPerCartonInput.value) || 0;
                initialStockInput.value = cartonQuantity * piecesPerCarton;
            }

            cartonQuantityInput.addEventListener("input", updateInitialStock);
            piecesPerCartonInput.addEventListener("input", updateInitialStock);
        }
    });


    $(document).ready(function() {
        // Add Product Modal: Fetch Subcategories on Category Change
        $('#categorySelect').change(function() {
            var categoryId = $(this).val();

            $('#subCategorySelect').html('<option value="">Loading...</option>');

            if (categoryId) {
                $.ajax({
                    url: "/get-subcategories/" + categoryId,

                    type: "GET",
                    data: {
                        category_id: categoryId
                    },
                    success: function(data) {
                        $('#subCategorySelect').html(
                            '<option value="">Select Sub-Category</option>');
                        $.each(data, function(key, subCategory) {
                            $('#subCategorySelect').append('<option value="' +
                                subCategory.id + '">' +
                                subCategory.name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching subcategories.');
                    }
                });
            } else {
                $('#subCategorySelect').html('<option value="">Select Sub-Category</option>');
            }
        });

        // Edit Product Modal: Fetch Subcategories when Category is Changed
        $('#edit_category').change(function() {
            var categoryId = $(this).val();
            $('#edit_sub_category').html('<option value="">Loading...</option>');

            if (categoryId) {
                $.ajax({
                    url: "/get-subcategories/" + categoryId,

                    type: "GET",
                    data: {
                        category_id: categoryId
                    },
                    success: function(data) {
                        $('#edit_sub_category').html(
                            '<option value="">Select Sub-Category</option>');
                        $.each(data, function(key, subCategory) {
                            $('#edit_sub_category').append('<option value="' +
                                subCategory.sub_category_name + '">' +
                                subCategory.sub_category_name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching subcategories.');
                    }
                });
            } else {
                $('#edit_sub_category').html('<option value="">Select Sub-Category</option>');
            }
        });
    });
</script>
