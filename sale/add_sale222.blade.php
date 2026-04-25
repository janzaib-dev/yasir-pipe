  @extends('admin_panel.layout.app')

  @section('content')
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
      <style>
          /* ================= RESPONSIVE SALES UI ================= */

          /* allow smooth horizontal scroll on small devices */
          .table-responsive {
              overflow-x: auto;
              -webkit-overflow-scrolling: touch;
          }

          /* base table width */
          .sales-table {
              min-width: 1000px;
          }

          /* 🔹 DISCOUNT COLUMN – THORI SI BARI */
          .sales-table td.large-col {
              min-width: 95px;
              width: 95px;
              padding: 4px;
          }

          /* 🔹 DISCOUNT LAYOUT */
          .discount-wrapper {
              position: relative;
              display: flex;
              align-items: center;
              gap: 4px;
              flex-wrap: nowrap;
          }

          /* 🔹 INPUT – NOT TOO SMALL */
          .discount-wrapper .discount-value {
              width: 60px;
              min-width: 60px;
              font-size: 0.8rem;
              padding: 4px 6px;
          }

          /* 🔹 PLUS ICON – NEAT & SMALL */
          .discount-wrapper .discount-plus {
              width: 22px;
              height: 22px;
              padding: 0;
              font-size: 13px;
              line-height: 1;
          }

          /* 🔹 DROPDOWN */
          .discount-wrapper .discount-type {
              position: absolute;
              right: 0;
              top: 115%;
              width: 65px;
              font-size: 0.75rem;
              z-index: 30;
          }



          /* ---------- TABLET (<= 992px) ---------- */
          @media (max-width: 992px) {

              .main-container {
                  max-width: 100%;
              }

              .sales-table {
                  min-width: 1000px;
              }

              .minw-350 {
                  min-width: 100%;
              }

          }

          /* ---------- MOBILE (<= 768px) ---------- */
          @media (max-width: 768px) {

              .header-text {
                  font-size: 1rem;
              }

              .btn {
                  padding: .35rem .5rem;
              }

              /* stack header buttons */
              .d-flex.justify-content-between.align-items-center {
                  flex-wrap: wrap;
                  gap: 8px;
              }

              /* customer + invoice panel full width */
              .minw-350 {
                  width: 100%;
              }

              /* reduce input font */
              .form-control,
              .form-select {
                  font-size: .8rem;
              }

          }

          /* ---------- VERY SMALL DEVICES ---------- */
          @media (max-width: 576px) {

              .sales-table {
                  min-width: 950px;
              }

              .discount-wrapper .discount-value {
                  min-width: 90px;
              }

          }
      </style>
      <style>
          .main-container {
              font-size: .85rem;
              max-width: 98%;
              /* Widen container */
          }

          .header-text {
              font-size: 1.1rem;
          }

          .form-control,
          .form-select,
          .btn {
              font-size: .82rem;
              /* Slightly smaller for density */
              padding: .3rem .4rem;
              /* Reduce padding */
              height: auto;
          }

          .invalid-cell {
              background-color: #fff5f5 !important;
              /* soft red */
              border: 1px solid #e3342f !important;
              /* red border */
          }

          .invalid-select,
          .invalid-input {
              border-color: #e3342f !important;
              box-shadow: none !important;
          }

          .input-readonly {
              background: #f9fbff;
          }

          .section-title {
              font-weight: 700;
              color: #6c757d;
              letter-spacing: .3px;
          }

          .table {
              --bs-table-padding-y: .35rem;
              --bs-table-padding-x: .5rem;
              font-size: .85rem;
          }

          .table thead th {
              position: sticky;
              top: 0;
              z-index: 2;
              background: #f8f9fa;
              text-align: center;
          }

          .table-responsive {
              /* max-height removed to allow expansion */
              overflow-x: auto;
              overflow-y: visible;
              border: 1px solid #eee;
              border-radius: .5rem;
              min-height: 200px;
          }

          .minw-350 {
              min-width: 360px;
          }

          .w-70 {
              width: 70px
          }

          .w-90 {
              width: 90px
          }

          .w-110 {
              width: 110px
          }

          .w-120 {
              width: 120px
          }

          .w-150 {
              width: 150px
          }

          .totals-card {
              background: #fcfcfe;
              border: 1px solid #eee;
              border-radius: .5rem;
          }

          .totals-card .row+.row {
              border-top: 1px dashed #e5e7eb;
          }

          .badge-soft {
              background: #eef2ff;
              color: #3730a3;
          }
      </style>
      <style>
          /* ===== Sales Table UI Fix ===== */
          .sales-table {
              min-width: 1500px;
              /* Ensure enough space for all columns */
          }

          .sales-table th {
              font-weight: 600;
              text-transform: uppercase;
              font-size: 0.75rem;
              letter-spacing: 0.5px;
              vertical-align: middle;
              color: #495057;
          }

          /* Column Widths */
          .col-product {
              min-width: 220px;
          }

          /* Slightly reduced */
          .col-warehouse {
              min-width: 160px;
          }

          .col-stock {
              width: 90px;
          }

          .col-qty {
              width: 90px;
          }

          .col-loose {
              width: 90px;
          }

          .col-price {
              width: 110px;
          }

          /* Retail Price */
          .col-disc {
              width: 130px;
          }

          /* Disc % + input */
          .col-disc-amt {
              width: 90px;
          }

          /* New Columns */
          .col-pieces {
              width: 100px;
          }

          .col-price-p {
              width: 100px;
          }

          .col-price-m2 {
              width: 100px;
          }

          .col-amount {
              width: 120px;
          }

          .col-action {
              width: 50px;
              text-align: center;
          }

          .input-readonly {
              background: #f8f9fa;
              color: #6c757d;
              font-weight: 500;
              border-color: #dee2e6;
          }

          .form-control:focus,
          .form-select:focus {
              border-color: #86b7fe;
              box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
          }

          /* Premium Table Look */
          .table-bordered> :not(caption)>*>* {
              border-width: 1px;
              border-color: #e9ecef;
          }
      </style>



      <div class="container-fluid py-2">
          <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3">

              <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

              <form id="saleForm" autocomplete="off">
                  @csrf
                  <input type="hidden" id="booking_id" name="booking_id" value="">
                  <input type="hidden" id="action" name="action" value="sale">

                  {{-- HEADER --}}
                  <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                      <div>
                          <small class="text-secondary" id="entryDateTime">Entry Date_Time: --</small> <br>
                          <a href="{{ route('sale.index') }}" target="_blank" rel="noopener"
                              class="btn btn-sm btn-outline-secondary" title="Sales List (opens new tab)">
                              Sales List
                          </a>
                      </div>


                      <h2 class="header-text text-secondary fw-bold mb-0">Sales</h2>


                      <div class="d-flex align-items-center gap-2">
                          <small class="text-secondary me-2" id="entryDate">Date: --</small>
                          <button type="button" class="btn btn-sm btn-light border" id="btnHeaderPosted"
                              disabled>Posted</button>
                      </div>
                  </div>

                  <div class="d-flex gap-3 align-items-start border-bottom py-3">
                      {{-- LEFT: Invoice & Customer --}}
                      <div class="p-3 border rounded-3 minw-350">
                          <div class="section-title mb-3">Invoice & Customer</div>

                          <div class="mb-2 d-flex align-items-center gap-2">
                              <label class="form-label fw-bold mb-0">Invoice No.</label>
                              <input type="text" class="form-control input-readonly" name="Invoice_no"
                                  style="width:150px" value="{{ $nextInvoiceNumber }}" readonly>
                              <label class="form-label fw-bold mb-0">M. Inv#</label>
                              <input type="text" class="form-control" name="Invoice_main" placeholder="Manual invoice">
                          </div>

                          {{-- Type toggle --}}
                          <div class="mb-2">
                              <label class="form-label fw-bold mb-1 d-block">Type</label>
                              <div class="btn-group" role="group" id="partyTypeGroup">
                                  <input type="radio" class="btn-check" name="partyType" id="typeCustomers"
                                      value="Main Customer" checked>
                                  <label class="btn btn-outline-primary btn-sm" for="typeCustomers">Customers</label>

                                  <input type="radio" class="btn-check" name="partyType" id="typeWalkin"
                                      value="Walking Customer">
                                  <label class="btn btn-outline-primary btn-sm" for="typeWalkin">Walk-in</label>

                                  {{-- <input type="radio" class="btn-check" name="partyType" id="typeVendors" value="vendor">
          <label class="btn btn-outline-primary btn-sm" for="typeVendors">Vendors</label> --}}
                              </div>
                          </div>

                          <!-- CUSTOMER SELECT -->
                          <div class="mb-2">
                              <label class="form-label fw-bold mb-1">Select Customer</label>
                              <select class="form-select" id="customerSelect" name="customer">
                                  <option selected disabled>Loading…</option>
                              </select>
                              <small class="text-muted" id="customerCountHint"></small>
                          </div>

                          <div class="mb-2">
                              <label class="form-label fw-bold">Address</label>
                              <textarea class="form-control" id="address"></textarea>
                          </div>

                          <div class="mb-2">
                              <label class="form-label fw-bold">Tel</label>
                              <input type="text" class="form-control" id="tel">
                          </div>

                          <div class="mb-2">
                              <label class="form-label fw-bold">Remarks</label>
                              <textarea class="form-control" id="remarks"></textarea>
                          </div>

                          <div class="mb-2 d-flex justify-content-between">
                              <span>Previous Balance</span>
                              <input type="text" class="form-control w-25 text-end" id="previousBalance" value="0"
                                  readonly>
                          </div>
                          <div class="mb-2 d-flex justify-content-between">
                              <span>Range Balance</span>
                              <input type="text" class="form-control w-25 text-end" id="rangeBalance" value="0"
                                  readonly>
                          </div>

                          <div class="text-end mt-3">
                              <button id="clearCustomerData" type="button"
                                  class="btn btn-sm btn-secondary">Clear</button>
                          </div>
                      </div>

                      {{-- RIGHT: Items --}}
                      <div class="flex-grow-1" style="min-width: 0;"> <!-- Fix flex overflow -->
                          <div class="d-flex justify-content-between align-items-center mb-2">
                              <div class="section-title mb-0">Items</div>
                              <button type="button" class="btn btn-sm btn-primary" id="btnAdd">Add Row</button>
                          </div>

                          <div class="table-responsive">
                              <table class="table table-bordered sales-table mb-0">

                                  <thead>
                                      <tr>
                                          <th class="col-product">Product</th>
                                          <th class="col-warehouse">Warehouse</th>
                                          <th class="col-stock">Stock</th>
                                          <th class="col-qty">Total Pcs</th>
                                          <th class="col-qty">Pack Size</th>
                                          <th class="col-loose">Loose</th>
                                          <th class="col-pieces">Boxes</th>
                                          <th class="col-price">Retail Price</th>
                                          <th class="col-disc">Disc %</th>
                                          <th class="col-disc-amt">Disc Amt</th>
                                          <th class="col-price-p">Price/Pc</th>
                                          <th class="col-amount">Amount</th>
                                          <th class="col-action">—</th>
                                      </tr>
                                  </thead>
                                  <tbody id="salesTableBody">

                                  </tbody>
                                  <tfoot>
                                      <tr>
                                          <td colspan="11" class="text-end fw-bold">Total:</td>
                                          <td class="text-end fw-bold"><span id="totalAmount">0.00</span></td>
                                          <td></td>
                                      </tr>
                                  </tfoot>
                              </table>
                          </div>
                      </div>
                  </div>

                  {{-- Totals + Receipts --}}
                  <div class="row g-3 mt-3">
                      <div class="col-lg-7">
                          <div class="section-title mb-2">Receipt Vouchers</div>
                          <div id="rvWrapper" class="border rounded-3 p-2">
                              <div class="d-flex gap-2 align-items-center mb-2 rv-row">
                                  <select class="form-select rv-account" name="receipt_account_id[]"
                                      style="max-width: 320px">
                                      {{-- @foreach ($accounts as $acc)
                  <option value="" disabled>Select account</option>
                  <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                  @endforeach --}}
                                  </select>
                                  <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]"
                                      placeholder="0.00" style="max-width:160px">
                                  <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddRV">Add
                                      more</button>
                              </div>
                              <div class="text-end">
                                  <span class="me-2">Receipts Total:</span>
                                  <span class="fw-bold" id="receiptsTotal">0.00</span>
                              </div>
                          </div>
                      </div>

                      <div class="col-lg-5">
                          <div class="section-title mb-2">Totals</div>
                          <div class="totals-card p-3">
                              <div class="row py-1">
                                  <div class="col-7 text-muted">Total Qty</div>
                                  <div class="col-5 text-end"><span id="tQty">0</span></div>
                              </div>
                              <div class="row py-1">
                                  <div class="col-7 text-muted">Invoice Gross (Σ Sales Price × Qty)</div>
                                  <div class="col-5 text-end"><span id="tGross">0.00</span></div>
                              </div>
                              <div class="row py-1">
                                  <div class="col-7 text-muted">Line Discount (on Retail)</div>
                                  <div class="col-5 text-end"><span id="tLineDisc">0.00</span></div>
                              </div>
                              <div class="row py-1">
                                  <div class="col-7 fw-semibold">Sub-Total</div>
                                  <div class="col-5 text-end fw-semibold"><span id="tSub">0.00</span></div>
                              </div>
                              <div class="row py-1">
                                  <div class="col-7">Aditional Discount %</div>
                                  <div class="col-5 text-end">
                                      <input type="text" class="form-control text-end" name="discountPercent"
                                          id="discountPercent" value="0" style="max-width:120px; margin-left:auto">
                                  </div>
                              </div>
                              <div class="row py-1">
                                  <div class="col-7 text-muted">Aditional Discount Rs</div>
                                  <div class="col-5 text-end"><span id="tOrderDisc">0.00</span></div>
                              </div>
                              <div class="row py-1">
                                  <div class="col-7 text-danger">Previous Balance</div>
                                  <div class="col-5 text-end text-danger"><span id="tPrev">0.00</span></div>
                              </div>
                              <div class="row py-2">
                                  <div class="col-7 fw-bold text-primary">Payable / Total Balance</div>
                                  <div class="col-5 text-end fw-bold text-primary"><span id="tPayable">0.00</span></div>
                              </div>

                              {{-- hidden mirrors for backend --}}
                              {{-- Maps to 'total_bill_amount' in DB (SubTotal AFTER line discounts) --}}
                              <input type="hidden" name="subTotal1" id="subTotal1" value="0">
                              <input type="hidden" name="total_subtotal" id="subTotal2" value="0">

                              {{-- Maps to 'total_extradiscount' in DB --}}
                              <input type="hidden" name="total_extra_cost" id="discountAmount" value="0">

                              {{-- Maps to 'total_net' in DB --}}
                              <input type="hidden" name="total_net" id="totalBalance" value="0">

                              {{-- Default values for nullable fields to satisfy controller --}}
                              <input type="hidden" name="cash" value="0">
                              <input type="hidden" name="card" value="0">
                              <input type="hidden" name="change" value="0">
                          </div>
                      </div>
                  </div>

                  {{-- Buttons --}}
                  <div class="d-flex flex-wrap gap-2 justify-content-center p-3 mt-3 border-top">
                      <button type="button" class="btn btn-sm btn-primary" id="btnEdit">Edit</button>
                      <button type="button" class="btn btn-sm btn-warning" id="btnRevert">Revert</button>

                      <button type="button" class="btn btn-sm btn-success" id="btnSave">Save</button>
                      <button type="button" class="btn btn-sm btn-outline-success" id="btnPosted"
                          disabled>Posted</button>

                      <button type="button" class="btn btn-sm btn-secondary" id="btnPrint">Print</button>
                      <button type="button" class="btn btn-sm btn-secondary" id="btnPrint2">Print-2</button>
                      <button type="button" class="btn btn-sm btn-secondary" id="btnDCPrint">DC Print</button>

                      <button type="button" class="btn btn-sm btn-danger" id="btnDelete">Delete</button>
                      <button type="button" class="btn btn-sm btn-dark" id="btnExit">Exit</button>
                  </div>
              </form>
          </div>
      </div>

      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

      {{-- sjadlfksal --}}

      <script>
          /* ========== DISCOUNT TOGGLE (% ↔ PKR) ========== */

          // $(document).on('click', '.discount-toggle', function () {

          //     const $btn = $(this);
          //     const currentType = $btn.data('type');

          //     if (currentType === 'percent') {
          //         // switch to PKR
          //         $btn.data('type', 'pkr');
          //         $btn.text('PKR');
          //     } else {
          //         // switch to %
          //         $btn.data('type', 'percent');
          //         $btn.text('%');
          //     }

          //     // focus back to input
          //     $btn.closest('.discount-wrapper')
          //         .find('.discount-value')
          //         .focus();
          // });
      </script>














      {{-- hajshdsadsdsksa --}}

      <script>
          // Load products once
          // When product changes, load warehouses for that product
          $(document).on('change', '.product', function() {

              const $row = $(this).closest('tr');
              const productId = $(this).val();

              const $warehouse = $row.find('.warehouse');
              const $stock = $row.find('.stock');
              const $retailPrice = $row.find('.retail-price');

              // reset
              $retailPrice.val('0');
              $stock.val('');

              if (!productId) {
                  $warehouse.html('<option value="">Select Warehouse</option>');
                  return;
              }

              /* ================== LOAD WAREHOUSES ================== */
              $warehouse.html('<option>Loading warehouses...</option>');

              $.get('{{ route('warehouses.get') }}', {
                      product_id: productId
                  })
                  .done(function(warehouses) {

                      let html = '<option value="">Select Warehouse</option>';

                      warehouses.forEach(function(w) {
                          html += `<option value="${w.warehouse_id}" data-stock="${w.stock}">
                            ${w.warehouse_name} -- ${w.stock}
                        </option>`;
                      });

                      $warehouse.html(html);
                  })
                  .fail(function() {
                      $warehouse.html('<option value="">Error loading warehouses</option>');
                  });

              /* ================== LOAD RETAIL PRICE ================== */
              $.get('{{ route('products.price') }}', {
                      product_id: productId
                  })
                  .done(function(res) {
                      // console.log(res);
                      $retailPrice.val(parseFloat(res.retail_price || 0).toFixed(2));
                  })
                  .fail(function() {
                      $retailPrice.val('0');
                  });

          });


          // When warehouse is selected, update stock
          $(document).on('change', '.warehouse', function() {
              const $row = $(this).closest('tr');
              const stock = $(this).find(':selected').data('stock') || 0;
              $row.find('.stock').val(stock);
          });
      </script>




      <!--fgdffhjkjkhgkhkh  -->

      <script>
          $(document).ready(function() {
              function loadAccountsInto($select) {
                  console.log('Fetching accounts...');
                  $select.prop('disabled', true).empty().append('<option value="">Loading...</option>');

                  $.get('{{ route('narrations.fetch') }}', {
                          type: 'receipt'
                      })
                      .done(function(rows) {
                          console.log('Accounts fetched:', rows);
                          $select.empty().append('<option value="">Select account</option>');
                          if (rows && rows.length > 0) {
                              rows.forEach(function(a) {
                                  $select.append('<option value="' + a.id + '">' + a.narration +
                                      '</option>');
                              });
                          } else {
                              $select.append('<option disabled>No receipt accounts found</option>');
                          }
                          $select.prop('disabled', false);
                      })
                      .fail(function(xhr) {
                          console.error('Accounts fetch failed:', xhr);
                          $select.empty().append('<option value="">Error loading</option>').prop('disabled',
                              false);
                      });
              }

              function init() {
                  addNewRow();
                  loadCustomersByType('Main Customer');
                  loadAccountsInto($('.rv-account').first());
                  updateGrandTotals();
                  refreshPostedState();
              }

              init();
              // 🔹 Load customers on page load
              // loadCustomersByType('customer');

              // 🔹 Change customer type (radio)
              $(document).on('change', 'input[name="partyType"]', function() {
                  $('#customerSelect').val('');
                  $('#address,#tel,#remarks').val('');
                  $('#previousBalance').val('0');
                  loadCustomersByType(this.value);
              });

              // 🔹 Load customers list
              function loadCustomersByType(type) {
                  //  alert('loadCustomersByType CALLED → ' + type);
                  $('#customerSelect')
                      .prop('disabled', true)
                      .html('<option selected disabled>Loading…</option>');

                  $.get('{{ route('salecustomers.index') }}', {
                      type: type
                  }, function(data) {

                      let html = '<option value="">-- Select --</option>';

                      if (data.length > 0) {
                          data.forEach(row => {
                              html += `<option value="${row.id}">
                        ${row.customer_id} -- ${row.customer_name}
                      </option>`;
                          });
                          $('#customerCountHint').text(data.length + ' record(s) found');
                      } else {
                          html += '<option disabled>No record found</option>';
                          $('#customerCountHint').text('No record found');
                      }

                      $('#customerSelect').html(html).prop('disabled', false);
                  });
              }

              // 🔹 When customer selected → load detail
              $(document).on('change', '#customerSelect', function() {
                  const id = $(this).val();
                  if (!id) return;

                  $.get(
                      '{{ route('salecustomers.show', '__ID__') }}'.replace('__ID__', id),
                      function(d) {
                          $('#address').val(d.address || '');
                          $('#tel').val(d.mobile || '');
                          $('#remarks').val(d.status || '');
                          $('#previousBalance').val((+d.previous_balance || 0).toFixed(2));
                      }
                  );
              });

              // 🔹 Clear button
              $('#clearCustomerData').on('click', function() {
                  $('#customerSelect').val('');
                  $('#address,#tel,#remarks').val('');
                  $('#previousBalance').val('0');
              });

              // 🔹 Add Receipt Row
              $('#btnAddRV').on('click', function() {
                  $('#rvWrapper .rv-row:last').after(`
                  <div class="d-flex gap-2 align-items-center mb-2 rv-row">
                    <select class="form-select rv-account" name="receipt_account_id[]" style="max-width:320px">
                      <option value="">Select account</option>
                    </select>
                    <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]" placeholder="0.00" style="max-width:160px">
                    <button type="button" class="btn btn-outline-danger btn-sm btnRemRV">&times;</button>
                  </div>
                `);
                  loadAccountsInto($('#rvWrapper .rv-row:last .rv-account'));
              });

          });
      </script>




      {{-- jsdakjdskldjlasd --}}

      <script>
          function initProductSelect2($el) {
              $el.select2({
                  placeholder: 'Search Product (Name / SKU / Barcode)',
                  allowClear: true,
                  width: '100%',
                  ajax: {
                      url: '{{ route('products.ajax.search') }}',
                      dataType: 'json',
                      delay: 250, // Throttle searches
                      data: function(params) {
                          return {
                              term: params.term, // search term
                              page: params.page || 1
                          };
                      },
                      processResults: function(data, params) {
                          params.page = params.page || 1;
                          return {
                              results: data.results,
                              pagination: {
                                  more: data.pagination.more
                              }
                          };
                      },
                      cache: true
                  },
                  minimumInputLength: 0, // Allow empty search (Load first 10 on open)
                  templateResult: formatProduct,
                  templateSelection: formatSelection
              });
          }

          function formatProduct(repo) {
              if (repo.loading) return repo.text;

              let stock = repo.stock !== undefined ? repo.stock : 0;
              let sku = repo.sku || 'N/A';
              let badgeClass = stock > 0 ? 'bg-success' : 'bg-danger';

              return $(`
              <div class="clearfix">
                  <div class="float-start">
                      <div class="fw-bold">${repo.name || repo.text}</div>
                      <small class="text-muted">SKU: ${sku}</small>
                  </div>
                  <div class="float-end">
                      <span class="badge ${badgeClass} rounded-pill">Stock: ${stock}</span>
                  </div>
              </div>
          `);
          }

          function formatSelection(repo) {
              return repo.name || repo.text;
          }

          /* Fetch Warehouses & Price Logic */
          $(document).ready(function() {
              console.log('Product Logic Validated'); // Debug

              // Bind to table body for better performance/specificity
              $('#salesTableBody').on('change select2:select', '.product', function(e) {
                  // De-duplicate: Select2 native change vs select2:select
                  if (e.type === 'change' && $(this).data('select2')) {
                      // Select2 triggers change automatically, but we might want to prioritize select2:select for data access
                      // actually, simply relying on value is enough
                  }

                  var productId = $(this).val();
                  if (!productId) return;

                  var $row = $(this).closest('tr');
                  console.log('Product Change Detected (' + e.type + '). ID:', productId);

                  loadWarehousesForProduct($row, productId);
                  fetchProductPrice($row, productId);
              });

              // update stock when warehouse changes
              $('#salesTableBody').on('change', '.warehouse', function() {
                  var stock = $(this).find(':selected').data('stock') || 0;
                  $(this).closest('tr').find('.stock').val(stock);
              });
          });

          function fetchProductPrice($row, productId) {
              // Direct call to get-price
              console.log('Fetching price for product:', productId);
              $.get('{{ route('get-price') }}', {
                  product_id: productId
              }).done(function(pRes) {
                  console.log('Price fetched:', pRes);

                  // Populate Fields
                  // Retail Price = Box Price (if by_carton)
                  $row.find('.retail-price').val(pRes.retail_price || 0);

                  // Pack Size
                  $row.find('.pack-qty').val(pRes.pieces_per_box || 1);

                  // Piece Price
                  $row.find('.price-per-piece').val(pRes.sale_price_per_piece || 0);

                  // Store Metadata for calculations
                  $row.data('size_mode', pRes.size_mode);
                  $row.data('pieces_per_box', pRes.pieces_per_box || 1);
                  $row.data('price_per_m2', pRes.price_per_m2 || 0);

                  computeRow($row);
              }).fail(function(err) {
                  console.error('Price fetch failed', err);
              });
          }

          function loadWarehousesForProduct($row, productId) {
              var $whSelect = $row.find('.warehouse');
              $whSelect.html('<option value="">Loading...</option>');
              $row.find('.stock').val('');
              console.log('Fetching warehouses for product:', productId);

              $.get('{{ route('warehouses.get') }}', {
                      product_id: productId
                  })
                  .done(function(warehouses) {
                      console.log('Warehouses fetched:', warehouses);

                      var validWarehouses = (Array.isArray(warehouses) ? warehouses : []).filter(function(w) {
                          return w.stock > 0;
                      });

                      if (validWarehouses.length > 0) {
                          var options = '<option value="">Select Warehouse</option>';
                          validWarehouses.forEach(function(w) {
                              options +=
                                  `<option value="${w.warehouse_id}" data-stock="${w.stock}">${w.warehouse_name} (Stock: ${w.stock})</option>`;
                          });
                          $whSelect.html(options);

                          // Auto-select if only one
                          if (validWarehouses.length === 1) {
                              $whSelect.val(validWarehouses[0].warehouse_id).trigger('change');
                          }
                      } else {
                          $whSelect.html('<option value="">Out of Stock in All Warehouses</option>');
                      }
                  })
                  .fail(function(xhr) {
                      console.error('Warehouse fetch error:', xhr);
                      $whSelect.html('<option value="">Error Loading Data</option>');
                  });
          }
      </script>


      <script>
          /* ---------- helpers ---------- */
          function pad(n) {
              return n < 10 ? '0' + n : n
          }

          function setNowStamp() {
              const d = new Date();
              const dt =
                  `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${String(d.getFullYear()).slice(-2)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
              const dOnly = `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${String(d.getFullYear()).slice(-2)}`;
              $('#entryDateTime').text('Entry Date_Time: ' + dt);
              $('#entryDate').text('Date: ' + dOnly);
          }
          setNowStamp();
          setInterval(setNowStamp, 60 * 1000);
          $('.js-customer').select2();

          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('input[name="_token"]').val()
              }
          });

          function showAlert(type, msg) {
              const el = $('#alertBox');
              el.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).text(msg);
              setTimeout(() => el.addClass('d-none'), 2500);
          }






          function addNewRow() {
              const rowHtml = `
  <tr>
    <!-- PRODUCT -->
    <td class="col-product">
      <select class="form-select product" name="product_id[]" style="width:100%">
        <option value=""></option>
      </select>
    </td>

    <!-- WAREHOUSE -->
    <td class="col-warehouse">
      <select class="form-select warehouse" name="warehouse_id[]">
        <option value="">Select Warehouse</option>
      </select>
    </td>

    <!-- STOCK -->
    <td class="col-stock">
      <input type="text" class="form-control stock text-center input-readonly" readonly tabindex="-1">
    </td>

    <!-- Qty Pieces (Input) -->
    <td class="col-qty">
      <input type="text" class="form-control sales-qty text-end" name="qty[]" id="sales-qty" placeholder="Pcs">
    </td>

    <!-- PACK QTY (Hidden) -->
    <!-- Pack Size (Readonly) -->
    <td class="col-qty">
       <input type="text" class="form-control pack-qty text-end input-readonly" name="pack_qty[]" readonly placeholder="Size" tabindex="-1">
    </td>

    <!-- LOOSE PIECES (Editable) -->
    <td class="col-loose">
      <input type="text" class="form-control loose-pieces text-end" name="loose_pieces[]" placeholder="Loose">
    </td>

    <!-- Packet/Box (Calculated) -->
    <td class="col-pieces">
      <input type="text" class="form-control total-pieces text-end input-readonly" name="total_pieces[]" readonly placeholder="Box" tabindex="-1">
    </td>

    <!-- RETAIL PRICE -->
    <td class="col-price">
      <input type="text" class="form-control retail-price text-end input-readonly" name="price[]" value="0" readonly tabindex="-1">
    </td>

    <!-- DISCOUNT -->
    <td class="col-disc">
      <div class="discount-wrapper">
        <input type="number"
               class="form-control discount-value text-end"
               name="item_disc[]"
               placeholder="">

        <button type="button"
                class="btn btn-outline-secondary discount-toggle"
                data-type="percent" tabindex="-1">%</button>
      </div>
    </td>

    <!-- DISCOUNT AMOUNT -->
    <td class="col-disc-amt">
      <input type="text" class="form-control discount-amount text-end">
    </td>

    <!-- Price/Piece -->
    <td class="col-price-p">
      <input type="text" class="form-control price-per-piece text-end input-readonly" name="price_per_piece[]" readonly placeholder="0.00" tabindex="-1">
    </td>



    <!-- NET AMOUNT -->
    <td class="col-amount">
      <input type="text" class="form-control sales-amount text-end input-readonly" name="total[]" value="0" readonly tabindex="-1">
    </td>

    <!-- ACTION -->
    <td class="col-action">
      <button type="button" class="btn btn-sm btn-outline-danger del-row" tabindex="-1">&times;</button>
    </td>
  </tr>`;

              const $row = $(rowHtml);
              $('#salesTableBody').append($row);
              initProductSelect2($row.find('.product'));
          }

          // discunt % field
          $(document).on('click', '.discount-toggle', function() {

              const $btn = $(this);
              const currentType = $btn.data('type');

              if (currentType === 'percent') {
                  $btn.data('type', 'pkr').text('PKR');
              } else {
                  $btn.data('type', 'percent').text('%');
              }

              // re-calc row
              const $row = $btn.closest('tr');
              computeRow($row);
              updateGrandTotals();
          });








          function canPost() {
              let ok = false;
              $('#salesTableBody tr').each(function() {
                  const pid = $(this).find('.product').val();
                  const qty = parseFloat($(this).find('.sales-qty').val() || '0');
                  if (pid && qty > 0) {
                      ok = true;
                      return false;
                  }
              });
              return ok;
          }

          function refreshPostedState() {
              const state = canPost();
              $('#btnPosted, #btnHeaderPosted').prop('disabled', !state);
          }

          /* ---------- SAVE/POST ---------- */
          function serializeForm() {
              return $('#saleForm').serialize();
          }

          function ensureSaved() {
              return new Promise(function(resolve, reject) {
                  const existing = $('#booking_id').val();
                  if (existing) return resolve(existing);

                  $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', true); // disable while saving

                  $.post('{{ route('sales.store') }}', serializeForm())
                      .done(function(res) {
                          $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', false);
                          if (res?.ok) {
                              $('#booking_id').val(res.booking_id);
                              Swal.fire('Saved', 'Booking #' + res.booking_id + ' saved successfully', 'success');
                              resolve(res.booking_id);
                          } else {
                              Swal.fire('Error', res.msg || 'Save failed', 'error');
                              reject(res);
                          }
                      })
                      .fail(function(xhr) {
                          $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', false);
                          console.error(xhr.responseText);
                          Swal.fire('Error', 'Save error: ' + (xhr.statusText || 'Unknown'), 'error');
                          reject(xhr);
                      });
              });
          }

          function postNow() {
              $.post('{{ route('sales.post_final') }}', serializeForm())
                  .done(function(res) {
                      if (res?.ok) {
                          window.open(res.invoice_url, '_blank');
                          Swal.fire({
                              title: 'Success!',
                              text: 'Posted & invoice opened',
                              icon: 'success',
                              timer: 2000,
                              showConfirmButton: false
                          });
                          // Optional: maybe reload page or disable buttons after post?
                          // For now, let's reload to prevent double post if user clicks again (though controller blocks it)
                          setTimeout(() => window.location.href = "{{ route('sale.index') }}", 2000);
                      } else {
                          Swal.fire('Post Failed', res.msg || 'Post failed', 'error');
                      }
                  })
                  .fail(function(xhr) {
                      console.error(xhr.responseText);
                      Swal.fire('Error', 'Post error', 'error');
                  });
          }

          /* ---------- Events top buttons ---------- */
          $('#btnAdd').on('click', addNewRow);
          $('#btnEdit').on('click', () => alert('Edit mode activated'));
          $('#btnRevert').on('click', () => location.reload());
          $('#btnDelete').on('click', function() {
              if (!confirm('Reset all fields?')) return;
              $('#saleForm')[0].reset();
              $('#booking_id').val('');
              $('#salesTableBody').html('');
              addNewRow();
              $('#totalAmount').text('0.00');
              updateGrandTotals();
              refreshPostedState();
              showAlert('success', 'Form cleared');
          });
          $('#btnSave').on('click', function() {
              ensureSaved();
          });
          $('#btnPrint').on('click', function() {
              ensureSaved().then(id => window.open('{{ url('booking/print') }}/' + id, '_blank'));
          });
          $('#btnPrint2').on('click', function() {
              ensureSaved().then(id => window.open('{{ url('booking/print2') }}/' + id, '_blank'));
          });
          $('#btnDCPrint').on('click', function() {
              ensureSaved().then(id => window.open('{{ url('booking/dc') }}/' + id, '_blank'));
          });
          $('#btnExit').on('click', function() {
              ensureSaved().finally(() => {
                  window.location.href = "{{ route('sale.index') }}";
              });
          });
          $('#btnHeaderPosted, #btnPosted').on('click', function() {
              if (!canPost()) return;
              ensureSaved().then(postNow);
          });

          /* ---------- Customer type & list ---------- */
          // function loadCustomersByType(type) {
          //   const $sel = $('#customerSelect').prop('disabled', true).empty().append('<option selected disabled>Loading...</option>');
          //   $.get(, {
          //     type
          //   }, function(list) {
          //     $sel.empty().append('<option selected disabled>Select ' + (type === 'vendor' ? 'vendor' : (type === 'walking' ? 'walk-in customer' : 'customer')) + '</option>');
          //     list.forEach(r => $sel.append('<option value="' + r.id + '">' + r.text + '</option>'));
          //     $('#customerCountHint').text(list.length + ' ' + type + (list.length === 1 ? ' found' : 's found'));
          //     $sel.prop('disabled', false);
          //   }).fail(function() {
          //     $sel.empty().append('<option selected disabled>Error loading</option>').prop('disabled', false);
          //     $('#customerCountHint').text('');
          //   });
          // }

          // loadCustomersByType('customer');
          // $(document).on('change', 'input[name="partyType"]', function() {
          //   $('#customerSelect').val(null).trigger('change');
          //   $('#address,#tel,#remarks').val('');
          //   loadCustomersByType(this.value);
          // });
          $(document).on('change', '#customerSelect', function() {
              let id = $(this).val();
              if (!id) return;

              // Reset fields
              $('#previousBalance').val('...');
              $('#rangeBalance').val('...');

              // Fetch customer details
              let url = "{{ url('sale/customers') }}/" + id + "?t=" + new Date().getTime();

              $.get(url, function(d) {
                  // d is the customer object
                  $('#address').val(d.address || '');
                  $('#tel').val(d.mobile || '');
                  $('#remarks').val(d.remarks || '');

                  // Log DB values for debugging
                  console.log("Customer Fetch Result:", d);
                  console.log("Server Previous Balance:", d.previous_balance);
                  console.log("Server Opening Balance:", d.opening_balance);

                  // Use DB 'previous_balance' column strictly
                  let prevBal = parseFloat(d.previous_balance || 0);
                  $('#previousBalance').val(prevBal.toFixed(2));

                  let rangeBal = parseFloat(d.balance_range || 0);
                  $('#rangeBalance').val(rangeBal.toFixed(2));

                  updateGrandTotals();
              }).fail(function() {
                  console.error("Failed to load customer data");
              });
          });
          //   if (!id) return;

          //   let type = $('input[name="partyType"]:checked').val(); // Get the selected type (customer/vendor)

          //  $.get('{{ route('salecustomers.index') }}', function(d) {
          //   console.log(d);
          //     // Fill in the customer/vendor details
          //     $('#address').val(d.address || '');
          //     $('#tel').val(d.mobile || '');
          //     $('#remarks').val(d.remarks || '');
          //     $('#previousBalance').val((+d.previous_balance || 0).toFixed(2)); // Set previous balance for customer
          //     updateGrandTotals(); // Update other totals if needed
          //   });
          // });

          // $('#clearCustomerData').on('click', function() {
          //   $('#customerSelect').val(null).trigger('change');
          //   $('#address,#tel,#remarks').val('');
          //   $('#previousBalance').val('0');
          //   updateGrandTotals();
          // });

          /* ---------- Warehouse -> products ---------- */
          // $(document).on('change', '.warehouse', function() {
          //   var wid = $(this).val();
          //   var $row = $(this).closest('tr');
          //   var $product = $row.find('.product');
          //   var $stock = $row.find('.stock');
          //   var $sp = $row.find('.sales-price');
          //   var $rp = $row.find('.retail-price');

          //   $product.prop('disabled', true).empty().append('<option value="">Loading...</option>');
          //   $stock.val('');
          //   $sp.val('0');
          //   $rp.val('0');

          //   if (!wid) {
          //     $product.empty().append('<option value="">Select Product</option>').prop('disabled', false);
          //     return;
          //   }

          //   // $.get(.replace(':id', wid), function(list) {
          //   //   $product.empty().append('<option value="">Select Product</option>');
          //   //   console.log(list);
          //   //   (list || []).forEach(p => $product.append('<option value="' + p.id + '">' + p.name + '</option>'));
          //   //   $product.prop('disabled', false);
          //   // }).fail(function() {
          //   //   $product.empty().append('<option value="">Error loading</option>').prop('disabled', false);
          //   // });
          // });

          /* ---------- Product -> stock + prices ---------- */
          // $(document).on('change', '.product', function() {

          //   var $row = $(this).closest('tr');
          //   var pid = $(this).val();
          //   // alert(pid);
          //   var $stock = $row.find('.stock');
          //   var $sp = $row.find('.sales-price');
          //   var $rp = $row.find('.retail-price');
          //   if (!pid) {
          //     $stock.val('');
          //     $sp.val('0');
          //     $rp.val('0');
          //     refreshPostedState();
          //     return;
          //   }

          //   $.get('{{ url('get-stock') }}/' + pid, function(d) {
          //     console.log(d);
          //     $stock.val((d.stock ?? 0));
          //     $sp.val((+d.sales_price || 0).toFixed(2));
          //     $rp.val((+d.retail_price || 0).toFixed(2));
          //     computeRow($row);
          //     updateGrandTotals();
          //     refreshPostedState();
          //   }).fail(function() {
          //     $stock.val('');
          //     $sp.val('0');
          //     $rp.val('0');
          //     computeRow($row);
          //     updateGrandTotals();
          //     refreshPostedState();
          //   });
          // });

          /* ---------- Row compute ---------- */
          function toNum(v) {
              return parseFloat(v || 0) || 0;
          }

          // Compute Row Logic
          function computeRow($row, isManual = false) {
              const rp = toNum($row.find('.retail-price').val()); // Box Price

              // Input fields: 
              // sales-qty = Total Pieces (User Input)
              const qtyPieces = toNum($row.find('.sales-qty').val());
              const loose = toNum($row.find('.loose-pieces').val()); // Extra Loose pieces

              const packQty = parseFloat($row.find('.pack-qty').val()) || 1;

              const stock = toNum($row.find('.stock').val());

              const discValue = toNum($row.find('.discount-value').val());
              const discType = $row.find('.discount-toggle').data('type');

              let dam = toNum($row.find('.discount-amount').val());

              // Calculate Boxes for Display (Visual only)
              let boxes = 0;
              if (packQty > 0) {
                  boxes = qtyPieces / packQty;
              }

              // Update UI (.total-pieces column now holds Calculated Boxes)
              $row.find('.total-pieces').val(Number.isInteger(boxes) ? boxes : boxes.toFixed(2));

              // Total Pieces for Calculation
              const totalPieces = qtyPieces + loose;

              // 🔹 GROSS CALCULATION
              // "fix total amount with piece which user input"
              // Use Price Per Piece explicitly
              let unitPrice = toNum($row.find('.price-per-piece').val());

              const gross = totalPieces * unitPrice;

              /* ===== AUTO DISCOUNT ===== */
              if (!isManual) {
                  if (discValue > 0) {
                      if (discType === 'percent') {
                          dam = (gross * discValue) / 100;
                      } else {
                          // Fixed Amount (PKR)
                          dam = discValue;
                      }
                  } else {
                      dam = 0;
                  }
                  $row.find('.discount-amount').val(dam.toFixed(2));
              }

              /* ===== ROW AMOUNT (GROSS) ===== */
              // "in amount wont show disscouted" -> Show Gross
              $row.find('.sales-amount').val(gross.toFixed(2));
          }






          // Main Input Trigger 
          $(document).on('input', '.sales-qty, .pack-qty, .loose-pieces, .discount-value', function() {
              const $row = $(this).closest('tr');
              computeRow($row);
              updateGrandTotals();
              refreshPostedState();
          });

          $(document).on('input', '.discount-amount', function() {
              const $row = $(this).closest('tr');
              computeRow($row, true); // manual amount respected
              updateGrandTotals();
              refreshPostedState();
          });

          /* ---------- Delete row ---------- */
          $(document).on('click', '.del-row', function() {
              const $tr = $(this).closest('tr');
              const $tbody = $('#salesTableBody');
              if ($tbody.find('tr').length > 1) {
                  $tr.remove();
                  updateGrandTotals();
                  refreshPostedState();
              }
          });

          /* ---------- Totals ---------- */
          function updateGrandTotals() {

              let tQty = 0;
              let tGross = 0;
              let tLineDisc = 0;
              let tNet = 0;

              $('#salesTableBody tr').each(function() {

                  const $r = $(this);

                  // Amount Column is now GROSS
                  // We re-read it or re-calculate? Reading is safer to match UI.
                  const gross = toNum($r.find('.sales-amount').val());
                  const dam = toNum($r.find('.discount-amount').val());

                  // Net for this row
                  const net = Math.max(0, gross - dam);

                  // Total Pieces
                  const qtyPcs = toNum($r.find('.sales-qty').val());
                  const loose = toNum($r.find('.loose-pieces').val());
                  const totalPieces = qtyPcs + loose;

                  tQty += totalPieces;
                  tGross += gross;
                  tLineDisc += dam;
                  tNet += net;
              });

              // ===== ORDER LEVEL =====
              const orderPct = toNum($('#discountPercent').val());
              const orderDisc = (tNet * orderPct) / 100;

              const prev = toNum($('#previousBalance').val());
              const receipts = toNum($('#receiptsTotal').text());

              const payable = Math.max(0, tNet - orderDisc + prev - receipts);
              const currentInvoiceTotal = Math.max(0, tNet - orderDisc);

              // ===== UI UPDATE =====
              $('#tQty').text(tQty.toFixed(0));
              $('#tGross').text(tGross.toFixed(2));
              $('#tLineDisc').text(tLineDisc.toFixed(2));
              $('#tSub').text(tNet.toFixed(2));
              $('#tOrderDisc').text(orderDisc.toFixed(2));
              $('#tPrev').text(prev.toFixed(2));
              $('#tPayable').text(payable.toFixed(2));

              // 🔥 TABLE FOOTER TOTAL
              $('#totalAmount').text(tNet.toFixed(2));

              // ===== BACKEND MIRRORS =====
              $('#subTotal1').val(tGross.toFixed(2));
              $('#subTotal2').val(tNet.toFixed(2));
              $('#discountAmount').val(orderDisc.toFixed(2));

              // Correct: total_net sent to backend should be just this invoice's amount
              $('#totalBalance').val(currentInvoiceTotal.toFixed(2));
          }

          $(document).on('input', '#previousBalance, #discountPercent', updateGrandTotals);

          /* ---------- Row auto-add ---------- */
          $('#salesTableBody').on('input', '.sales-qty', function() {
              const $row = $(this).closest('tr');
              computeRow($row);
              updateGrandTotals();
              refreshPostedState();
          });

          /* ---------- Add new row when user presses Enter in Disc % (only on last row) ---------- */
          $('#salesTableBody').on('keydown', '.discount-value', function(e) {
              if (e.key === 'Enter' || e.keyCode === 13) {
                  e.preventDefault(); // prevent accidental form submit
                  const $current = $(this).closest('tr');

                  // compute current row first (in case user typed value and pressed Enter)
                  computeRow($current);
                  updateGrandTotals();
                  refreshPostedState();

                  // only add new row when this is the last row AND discount has some value OR qty > 0 or product selected
                  const isLast = $current.is(':last-child');
                  const discVal = parseFloat($(this).val() || '0') || 0;
                  const qtyVal = parseFloat($current.find('.sales-qty').val() || '0') || 0;
                  const prodSelected = !!$current.find('.product').val();

                  // require at least one 'meaningful' value so blank Enter doesn't create rows
                  if (isLast && (discVal !== 0 || qtyVal > 0 || prodSelected)) {
                      addNewRow();
                      // focus on new row product for quick entry
                      const $newRow = $('#salesTableBody tr:last-child');
                      setTimeout(() => $newRow.find('.warehouse').focus(), 0);
                  }
              }
          });


          /* ---------- Receipts (accounts) ---------- */
          // loadAccountsInto moved to top scope

          function recomputeReceipts() {
              let sum = 0;
              // Calculate the total receipt amount
              $('.rv-amount').each(function() {
                  sum += toNum($(this).val()); // Sum up all the receipt amounts
              });
              $('#receiptsTotal').text(sum.toFixed(2)); // Display total in the respective element
              updateGrandTotals(); // Update other totals if needed
          }

          $('#btnAddRV').on('click', function() {
              $('#rvWrapper .rv-row:last').after(`
      <div class="d-flex gap-2 align-items-center mb-2 rv-row">
        <select class="form-select rv-account" name="receipt_account_id[]" style="max-width:320px">
          <option value="">Select account</option>
        </select>
        <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]" placeholder="0.00" style="max-width:160px">
        <button type="button" class="btn btn-outline-danger btn-sm btnRemRV">&times;</button>
      </div>
    `);

              // Load accounts into the newly added row
              loadAccountsInto($('#rvWrapper .rv-row:last .rv-account'));
          });
          $(document).on('click', '.btnRemRV', function() {
              $(this).closest('.rv-row').remove();
              recomputeReceipts(); // Recompute total receipts after removal
          });

          // Recompute total receipt amounts when input changes
          $(document).on('input', '.rv-amount', recomputeReceipts);

          /* ---------- init ---------- */
          // function init() {
          //   addNewRow();
          //   loadCustomersByType('customer');
          //   // loadAccountsInto($('.rv-account').first());
          //   updateGrandTotals();
          //   refreshPostedState();
          // }

          // init();

          function markInvalid($el) {
              // add visuals; $el can be input/select/td
              $el.addClass('invalid-input invalid-select');
              // also add class to closest td for table cells
              $el.closest('td').addClass('invalid-cell');
          }

          function clearInvalid($el) {
              $el.removeClass('invalid-input invalid-select');
              $el.closest('td').removeClass('invalid-cell');
          }

          function clearAllInvalids() {
              $('.invalid-input, .invalid-select').removeClass('invalid-input invalid-select');
              $('.invalid-cell').removeClass('invalid-cell');
          }

          $(document).on('input change', 'select, input, textarea', function() {
              clearInvalid($(this));
          });

          function validateRows() {
              let ok = true;
              let firstMessage = null;
              let firstEl = null;

              $('#salesTableBody tr').each(function(rowIndex) {
                  const $row = $(this);
                  const $wh = $row.find('.warehouse');
                  const $prod = $row.find('.product');
                  const $qty = $row.find('.sales-qty');

                  // Warehouse
                  if (!$wh.val()) {
                      ok = false;
                      if (!firstMessage) {
                          firstMessage = 'Please select Warehouse for row ' + (rowIndex + 1);
                          firstEl = $wh;
                      }
                      markInvalid($wh);
                  }

                  // Product / Item
                  if (!$prod.val()) {
                      ok = false;
                      if (!firstMessage) {
                          firstMessage = 'Please select Item for row ' + (rowIndex + 1);
                          firstEl = $prod;
                      }
                      markInvalid($prod);
                  }

                  // Qty > 0
                  const qtyVal = parseFloat($qty.val() || '0') || 0;
                  if (qtyVal <= 0) {
                      ok = false;
                      if (!firstMessage) {
                          firstMessage = 'Please enter Item qty (> 0) for row ' + (rowIndex + 1);
                          firstEl = $qty;
                      }
                      markInvalid($qty);
                  }
              });

              return {
                  ok,
                  firstMessage,
                  firstEl
              };
          }

          /**
           * validateReceipts() -> if any receipt amount > 0 then account must be selected
           * returns { ok, firstMessage, firstEl }
           */
          function validateReceipts() {
              let ok = true,
                  firstMessage = null,
                  firstEl = null;
              $('#rvWrapper .rv-row').each(function(i) {
                  const $row = $(this);
                  const $acc = $row.find('.rv-account');
                  const $amt = $row.find('.rv-amount');
                  const amtVal = parseFloat($amt.val() || '0') || 0;

                  if (amtVal > 0 && (!$acc.val() || $acc.val() === "")) {
                      ok = false;
                      if (!firstMessage) {
                          firstMessage = 'Please select Account for receipt row ' + (i + 1);
                          firstEl = $acc;
                      }
                      markInvalid($acc);
                  }
              });
              return {
                  ok,
                  firstMessage,
                  firstEl
              };
          }

          /**
           * validateHeader() -> Type & Party mandatory
           */
          function validateHeader() {
              let ok = true,
                  firstMessage = null,
                  firstEl = null;
              // Type (partyType) - we expect a radio selected
              const partyType = $('input[name="partyType"]:checked').val();
              if (!partyType) {
                  ok = false;
                  firstMessage = 'Please select Type';
                  firstEl = $('input[name="partyType"]').first();
                  // mark buttons visually
                  $('#partyTypeGroup').addClass('invalid-cell');
              } else {
                  $('#partyTypeGroup').removeClass('invalid-cell');
              }

              // Party / Customer
              const cust = $('#customerSelect').val();
              if (!cust) {
                  ok = false;
                  if (!firstMessage) {
                      firstMessage = 'Please select Party (Customer / Vendor)';
                      firstEl = $('#customerSelect');
                  }
                  markInvalid($('#customerSelect'));
              }

              return {
                  ok,
                  firstMessage,
                  firstEl
              };
          }

          /**
           * validateFormAll() -> run header, rows, receipts
           * returns { ok, message, el }
           */
          function validateFormAll() {
              clearAllInvalids();

              // header
              const h = validateHeader();
              if (!h.ok) {
                  return {
                      ok: false,
                      message: h.firstMessage,
                      el: h.firstEl
                  };
              }

              // rows
              const r = validateRows();
              if (!r.ok) {
                  return {
                      ok: false,
                      message: r.firstMessage,
                      el: r.firstEl
                  };
              }

              // receipts
              const rec = validateReceipts();
              if (!rec.ok) {
                  return {
                      ok: false,
                      message: rec.firstMessage,
                      el: rec.firstEl
                  };
              }

              // if all ok
              return {
                  ok: true
              };
          }

          /* ---------- Hook validation into Save / Post ---------- */

          // override Save button to validate first
          $('#btnSave').off('click').on('click', function() {
              cleanupEmptyRows(); // remove empty rows
              updateGrandTotals(); // recompute totals after cleanup
              refreshPostedState();

              // run the existing validation pipeline
              const v = validateFormAll();
              if (!v.ok) {
                  Swal.fire({
                      icon: 'warning',
                      title: 'Validation Error',
                      text: v.message,
                  });
                  if (v.el && v.el.length) {
                      v.el.focus();
                      if (v.el.hasClass('js-customer')) v.el.select2?.('open');
                  }
                  return;
              }

              // proceed to save
              ensureSaved();
          });


          // override Post buttons to validate first
          $('#btnHeaderPosted, #btnPosted').off('click').on('click', function() {
              cleanupEmptyRows();
              updateGrandTotals();
              refreshPostedState();

              const v = validateFormAll();
              if (!v.ok) {
                  Swal.fire({
                      icon: 'warning',
                      title: 'Validation Error',
                      text: v.message,
                  });
                  if (v.el && v.el.length) {
                      v.el.focus();
                      if (v.el.hasClass('js-customer')) v.el.select2?.('open');
                  }
                  return;
              }

              // Credit Limit Validation
              // "sale post will done when total amount < range balance and opening balnce < range balnce"
              const rangeBal = toNum($('#rangeBalance').val());
              if (rangeBal > 0) {
                  const prevBal = toNum($('#previousBalance').val());
                  const currentTotal = toNum($('#totalAmount').text());

                  // 1. Check Opening Balance
                  if (prevBal >= rangeBal) {
                      Swal.fire({
                          icon: 'error',
                          title: 'Credit Limit Exceeded',
                          text: 'Previous Balance (' + prevBal + ') exceeds Credit Limit (' + rangeBal + ')',
                          confirmButtonColor: '#d33'
                      });
                      return;
                  }

                  // 2. Check Resulting Balance (Implies Total Amount Check)
                  if ((prevBal + currentTotal) > rangeBal) {
                      Swal.fire({
                          icon: 'error',
                          title: 'Credit Limit Exceeded',
                          text: 'Total Outstanding (' + (prevBal + currentTotal).toFixed(2) +
                              ') exceeds Credit Limit (' + rangeBal + ')',
                          confirmButtonColor: '#d33'
                      });
                      return;
                  }
              }

              if (!canPost()) {
                  Swal.fire({
                      icon: 'warning',
                      title: 'Validation Error',
                      text: 'No valid item lines to post. Please add at least one item with qty > 0.',
                  });
                  return;
              }

              ensureSaved().then(postNow);
          });


          function isRowMeaningful($row) {
              const prod = $row.find('.product').val();
              const wh = $row.find('.warehouse').val();
              const qty = parseFloat($row.find('.sales-qty').val() || '0') || 0;
              const discPct = parseFloat($row.find('.discount-value.discount-percent').val() || '0') || 0;
              const discAmt = parseFloat($row.find('.discount-amount').val() || '0') || 0;

              // consider row meaningful if product selected OR qty > 0 OR discount entered OR warehouse selected
              return !!prod || !!wh || qty > 0 || discPct !== 0 || discAmt !== 0;
          }

          function cleanupEmptyRows() {
              $('#salesTableBody tr').each(function() {
                  const $r = $(this);
                  const prod = $r.find('.product').val();
                  const wh = $r.find('.warehouse').val();
                  const qty = parseFloat($r.find('.sales-qty').val() || '0') || 0;

                  // Remove row when qty is zero or (product empty AND warehouse empty)
                  // We want to remove:
                  //  - rows where qty <= 0 (user didn't enter qty) because they are meaningless,
                  //  - or rows that are fully empty.
                  if ((qty <= 0) || ((!prod || prod === '') && (!wh || wh === ''))) {
                      // ensure we keep at least one row in UI
                      if ($('#salesTableBody tr').length > 1) {
                          $r.remove();
                      } else {
                          // if only one row left, clear its fields instead of removing (keeps UI stable)
                          $r.find('select').val('');
                          $r.find('input').val('');
                          $r.find('.stock').val('');
                          $r.find('.sales-amount').val('0');
                      }
                  }
              });

              // ensure at least one blank row exists
              if ($('#salesTableBody tr').length === 0) addNewRow();
          }

          $(document).ready(function() {
              if ($('#salesTableBody tr').length === 0) {
                  addNewRow();
              }
          });
      </script>
  @endsection
