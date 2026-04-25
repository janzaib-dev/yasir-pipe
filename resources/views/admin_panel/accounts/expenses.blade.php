@extends('admin_panel.layout.app')

@section('content')
    <div class="container-fluid py-4">




        {{-- Voucher Table --}}
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center text-white">
                <h6 class="mb-0">Voucher List</h6>
                @can('expense.voucher.create')
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#voucherModal">
                        <i class="bi bi-plus-circle"></i> Add Voucher
                    </button>
                @endcan
            </div>

            <div class="card-body">
                <table id="voucherTable" class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            {{--  <th>Sales Officer</th>  --}}

                            <th>id</th>
                            <th>Customer</th>
                            <th>party</th>
                            {{--  <th>Sub-Head</th>  --}}
                            <th>Narration</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vouchers as $voucher)
                            <tr>
                                <td>{{ $voucher->id }}</td>

                                <td>{{ ucfirst($voucher->type) }}</td>
                                <td>{{ $voucher->person }}</td>
                                {{--  <td>{{ $voucher->sub_head }}</td>  --}}
                                <td>{{ $voucher->narration }}</td>
                                <td>{{ number_format($voucher->amount, 2) }}</td>
                                <td>{{ $voucher->date }}</td>
                                <td>
                                    @can('expense.voucher.edit')
                                        <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $voucher->id }}"
                                            data-sales_officer="{{ $voucher->sales_officer }}" data-date="{{ $voucher->date }}"
                                            data-type="{{ $voucher->type }}" data-person="{{ $voucher->person }}"
                                            data-sub_head="{{ $voucher->sub_head }}"
                                            data-narration="{{ $voucher->narration }}" data-amount="{{ $voucher->amount }}"
                                            data-bs-toggle="modal" data-bs-target="#voucherModal">
                                            Edit
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Voucher Modal --}}
    <div class="modal fade" id="voucherModal" tabindex="-1" aria-labelledby="voucherModalLabel" aria-hidden="true">
        <div class="modal-dialog  modal-lg">
            <div class="modal-content">
                <form action="{{ route('vouchers.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="voucher_id">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="voucherModalLabel">Add Voucher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div style="overflow-x: auto; white-space: nowrap;">
                            <table class="table table-bordered" id="voucherItemsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer Type</th>
                                        <th>Customer</th>
                                        <th>Narration</th>
                                        <th>Custom</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="voucher-row">
                                        <td><input type="date" name="date[]" class="form-control" required></td>
                                        <td>
                                            <select name="type[]" class="form-select type-select" required>
                                                <option value="Main Customer">Main Customer</option>
                                                <option value="Customer">Customer</option>
                                                <option value="Walking Customer">Walking Customer</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="person[]" class="form-select person-select" required>
                                                <option value="">Select Customer</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="narration[]" class="form-select narration-select" required>
                                                @foreach ($narration as $item)
                                                    <option value="{{ $item->narration }}">{{ $item->narration }}</option>
                                                @endforeach
                                                <option value="custom">-- Custom --</option>
                                            </select>
                                        </td>
                                        <div class="col-md-4 d-none">
                                            <label class="form-label">Sub-Head</label>
                                            <select name="sub_head" class="form-select">
                                                @foreach ($narration as $item)
                                                    <option value="{{ $item->expense_head }}">{{ $item->expense_head }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <td>
                                            <input type="text" name="custom_narration[]"
                                                class="form-control custom-narration-input mt-2" style="display:none;"
                                                placeholder="Write custom narration">
                                        </td>
                                        <td>
                                            <input type="number" name="amount[]" class="form-control" step="0.01"
                                                required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total</strong></td>
                                <td><input type="text" name="total" class="form-control" readonly></td>
                                <td></td>
                            </tr>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save Voucher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            function loadCustomers(type, $personSelect) {
                $.ajax({
                    url: "{{ url('get-customers-by-type') }}",
                    type: 'GET',
                    data: {
                        type: type
                    },
                    success: function(data) {
                        $personSelect.empty();
                        $personSelect.append('<option value="">Select Customer</option>');
                        $.each(data.customers, function(i, customer) {
                            $personSelect.append('<option value="' + customer.id + '">' +
                                customer.customer_name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching customers.');
                    }
                });
            }

            // Load customers for row on type change
            $('#voucherItemsTable').on('change', '.type-select', function() {
                var selectedType = $(this).val();
                var $personSelect = $(this).closest('tr').find('.person-select');
                if (selectedType) {
                    loadCustomers(selectedType, $personSelect);
                } else {
                    $personSelect.empty().append('<option value="">Select Customer</option>');
                }
            });

            // Function to check if a row is fully filled (all fields have value)
            function isRowComplete($row) {
                var complete = true;
                $row.find('input, select').each(function() {
                    if ($(this).val() === null || $(this).val() === '') {
                        complete = false;
                        return false; // break loop
                    }
                });
                return complete;
            }

            // On input/select change in any row, check if last row is complete, then add new row automatically
            $('#voucherItemsTable').on('input change', 'input, select', function() {
                var $tbody = $('#voucherItemsTable tbody');
                var $lastRow = $tbody.find('tr:last');

                // If last row is complete and not empty, add a new blank row
                if (isRowComplete($lastRow)) {
                    // Clone last row
                    var $newRow = $lastRow.clone();

                    // Clear inputs and selects in new row
                    $newRow.find('input').val('');
                    $newRow.find('select.person-select').empty().append(
                        '<option value="">Select Customer</option>');
                    $newRow.find('select.type-select').val('Main Customer');

                    $tbody.append($newRow);

                    // Load customers for new row's default type
                    loadCustomers('Main Customer', $newRow.find('.person-select'));
                }
            });

            // Remove row button click
            $('#voucherItemsTable').on('click', '.remove-row', function() {
                var $tbody = $('#voucherItemsTable tbody');
                var rowCount = $tbody.find('tr').length;

                if (rowCount > 1) {
                    $(this).closest('tr').remove();
                } else {
                    alert('At least one voucher row is required.');
                }
            });

            // Initial load: load customers for first row default type
            $('#voucherItemsTable tbody tr').each(function() {
                var typeVal = $(this).find('.type-select').val();
                var $personSelect = $(this).find('.person-select');
                if (typeVal) {
                    loadCustomers(typeVal, $personSelect);
                }
            });

        });

        function updateTotal() {
            let total = 0;
            $('input[name="amount[]"]').each(function() {
                let val = parseFloat($(this).val()) || 0;
                total += val;
            });
            $('input[name="total"]').val(total.toFixed(2)); // 2 decimal places
        }

        // When amount changes
        $('#voucherItemsTable').on('input', 'input[name="amount[]"]', function() {
            updateTotal();
        });

        // When row removed
        $('#voucherItemsTable').on('click', '.remove-row', function() {
            setTimeout(updateTotal, 100); // delay so row is removed first
        });

        // Also update after adding new row
        $('#voucherItemsTable').on('input change', 'input, select', function() {
            updateTotal();
        });
    </script>

    <script>
        $(document).ready(function() {

            // Narration dropdown change event (event delegation for dynamic rows)
            $('#voucherItemsTable').on('change', '.narration-select', function() {
                var $row = $(this).closest('tr');
                var selectedVal = $(this).val();
                var $customInput = $row.find('.custom-narration-input');

                if (selectedVal === 'custom') {
                    $customInput.show().attr('required', true).focus();
                    // Clear the dropdown's name attribute so form submits only custom narration
                    $(this).removeAttr('name');
                    $customInput.attr('name', 'narration[]');
                } else {
                    $customInput.hide().removeAttr('required').val('');
                    // Restore dropdown's name attribute and remove from custom input
                    $(this).attr('name', 'narration[]');
                    $customInput.removeAttr('name');
                }
            });

            // Also handle page load for existing rows:
            $('#voucherItemsTable tbody tr').each(function() {
                var $select = $(this).find('.narration-select');
                $select.trigger('change'); // to apply correct visibility on load
            });

            // ... Your existing code for other features (loading customers, auto add row, remove row etc) ...

        });
    </script>
    {{-- DataTables CSS/JS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#voucherTable').DataTable();

            // Edit Button Click
            $('.edit-btn').on('click', function() {
                $('#voucherModalLabel').text('Edit Voucher');
                $('#voucher_id').val($(this).data('id'));
                $('[name="sales_officer"]').val($(this).data('sales_officer'));
                $('[name="date"]').val($(this).data('date'));
                $('[name="type"]').val($(this).data('type'));
                $('[name="person"]').val($(this).data('person'));
                $('[name="sub_head"]').val($(this).data('sub_head'));
                $('[name="narration"]').val($(this).data('narration'));
                $('[name="amount"]').val($(this).data('amount'));
            });

            // Reset Form on Add New Click
            $('[data-bs-target="#voucherModal"]').on('click', function() {
                $('#voucherModalLabel').text('Add Voucher');
                $('#voucher_id').val('');
                $('#voucherModal form')[0].reset();
            });
        });
    </script>
@endsection
