@extends('admin_panel.layout.app')
@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-primary">Expense Voucher</h2>
                <a href="{{ route('all_expense_vochers') }}" class="btn btn-outline-primary">
                    <i class="bi bi-list"></i> View All Expenses
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white"><i class="bi bi-wallet2 me-2"></i>New Expense Entry</h5>
                </div>
                <div class="card-body p-4">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('store_expense_vochers') }}" method="POST">
                        @csrf

                        {{-- Header Section: Voucher Info & Source of Funds --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-muted small text-uppercase">Voucher No</label>
                                <input type="text" class="form-control bg-light" name="evid"
                                    value="{{ $nextRvid }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-muted small text-uppercase">Date</label>
                                <input type="date" name="entry_date" class="form-control"
                                    value="{{ now()->toDateString() }}">
                            </div>

                            {{-- Paid From (Source) --}}
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small text-uppercase">Paid From (Source)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-safe"></i></span>
                                    <select name="vendor_type" class="form-select" id="payFromHead">
                                        <option value="">Select Head</option>
                                        @foreach ($AccountHeads as $head)
                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small text-uppercase d-block w-100">Account</label>
                                <select name="vendor_id" class="form-select w-100" id="payFromAccount" style="width: 100%;">
                                    <option disabled selected>Select Account</option>
                                </select>
                                <div class="form-text text-start balance-display" style="display:none;">
                                    Balance: <span class="fw-bold text-dark">0.00</span>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold text-muted small text-uppercase">Reference / Cheque
                                    #</label>
                                <input type="text" name="ref_no_header" class="form-control" placeholder="Optional">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Global Remarks</label>
                            <input type="text" name="remarks" class="form-control"
                                placeholder="Any general notes for this voucher...">
                        </div>

                        {{-- Body Section: Expense Allocations --}}
                        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-list-check me-2"></i>Expense Details</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="voucherTable">
                                <thead class="bg-light table-light text-primary small text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                                    <tr>
                                        <th style="width: 35%">Expense Category</th>
                                        <th style="width: 35%">Specific Account</th>
                                        <th style="width: 20%">Amount</th>
                                        <th style="width: 10%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="row_account_head[]"
                                                class="form-select form-select-sm border-secondary-subtle rowAccountHead" required>
                                                <option value="">Select Category</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="row_account_id[]"
                                                class="form-select form-select-sm border-secondary-subtle rowAccountSub" required>
                                                <option value="">Select Specific Account</option>
                                            </select>
                                            <!-- Hidden Narration Fields -->
                                            <input type="hidden" name="narration_id[]" value="">
                                            <input type="hidden" name="narration_text[]" value="">
                                        </td>
                                        <td class="align-middle">
                                            <input name="amount[]" type="number" step="0.01"
                                                class="form-control form-control-sm text-end fw-bold amount border-primary text-primary" placeholder="0.00" required>
                                            <input type="hidden" name="discount_value[]" class="discountValue" value="0">
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button"
                                                class="btn btn-outline-danger btn-sm py-0 px-2 removeRow" title="Remove Line">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold text-uppercase align-middle">Total Amount</td>
                                        <td>
                                            <input type="text" name="total_amount"
                                                class="form-control text-end fw-bold border-0 bg-transparent"
                                                id="totalAmount" readonly value="0.00" style="font-size: 1.25rem;">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <button type="button" class="btn btn-outline-primary shadow-sm fw-bold px-4 mt-2" id="addNewRow">
                                <i class="bi bi-plus-circle me-2"></i> Add Another Expense Line
                            </button>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-3 mt-5 pt-3 border-top">
                            <a href="{{ route('all_expense_vochers') }}" class="btn btn-light border shadow-sm px-4 fw-medium text-dark">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm"><i
                                    class="bi bi-check2-circle me-2"></i>Post Voucher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // --- Layout Logic ---
        $(document).on('change', '.narrationSelect', function() {
            let $row = $(this).closest('td');
            let $input = $row.find('.narrationInput');
            if ($(this).val() === '') {
                $input.show().focus().attr('name', 'narration_text[]');
            } else {
                $input.hide().val('').attr('name',
                'narration_text_dummy'); // prevent submission of empty specific text if select used
            }
        });

        // --- Header Source Account Logic ---
        $('#payFromHead').on('change', function() {
            let headId = $(this).val();
            let $accSelect = $('#payFromAccount');

            $accSelect.html('<option disabled selected>Loading...</option>');
            $('.balance-display').hide();

            if (headId) {
                $.get('{{ url('get-accounts-by-head') }}/' + headId, function(data) {
                    $accSelect.empty().append('<option disabled selected>Select Account</option>');
                    data.forEach(function(acc) {
                        $accSelect.append(
                            `<option value="${acc.id}" data-code="${acc.account_code}" data-bal="${acc.opening_balance}">${acc.title}</option>`
                        );
                    });
                });
            } else {
                $accSelect.empty().append('<option disabled selected>Select Account</option>');
            }
        });

        // Show balance for header account
        $('#payFromAccount').on('change', function() {
            let $opt = $(this).find(':selected');
            let bal = $opt.data('bal');
            if (bal !== undefined) {
                $('.balance-display').show().find('span').text(parseFloat(bal).toFixed(2));
            }
        });


        // --- Row Expense Account Logic ---
        $(document).on('change', '.rowAccountHead', function() {
            let headId = $(this).val();
            let $subSelect = $(this).closest('tr').find('.rowAccountSub');

            if (!headId) {
                $subSelect.html('<option value="">Select Account</option>');
                return;
            }

            $.get('{{ url('get-accounts-by-head') }}/' + headId, function(res) {
                let html = '<option value="">Select Account</option>';
                res.forEach(acc => {
                    html += `<option value="${acc.id}">${acc.title}</option>`;
                });
                $subSelect.html(html);
            });
        });

        // --- Calculations ---
        function calculateRow(row, manual = false) {
            let kg = parseFloat(row.find('.kg').val()) || 0;
            let rate = parseFloat(row.find('.rate').val()) || 0;
            let amountInput = row.find('.amount');
            let baseAmount = 0;

            if (kg > 0 && rate > 0) {
                baseAmount = kg * rate;
                amountInput.val(baseAmount.toFixed(2));
            } else if (manual) {
                // If typing manually in amount, don't override unless kg*rate changes
            }
        }

        function calculateTotal() {
            let total = 0;
            $('.amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#totalAmount').val(total.toFixed(2));
        }

        $(document).on('input', '.kg, .rate', function() {
            let row = $(this).closest('tr');
            calculateRow(row, false);
            calculateTotal();
        });

        $(document).on('input', '.amount', function() {
            calculateTotal();
        });

        // --- Dynamic Rows ---
        $('#addNewRow').on('click', function() {
            let newRow = `
            <tr>
                <td>
                    <select name="row_account_head[]" class="form-select form-select-sm border-secondary-subtle rowAccountHead" required>
                        <option value="">Select Category</option>
                        @foreach ($AccountHeads as $head)
                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="row_account_id[]" class="form-select form-select-sm border-secondary-subtle rowAccountSub" required>
                        <option value="">Select Specific Account</option>
                    </select>
                    <!-- Hidden Narration Fields -->
                    <input type="hidden" name="narration_id[]" value="">
                    <input type="hidden" name="narration_text[]" value="">
                </td>
                <td class="align-middle">
                    <input name="amount[]" type="number" step="0.01" class="form-control form-control-sm text-end fw-bold amount border-primary text-primary" placeholder="0.00" required>
                    <input type="hidden" name="discount_value[]" class="discountValue" value="0">
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 removeRow" title="Remove Line">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            </tr>
        `;
            $('#voucherTable tbody').append(newRow);
        });

        $(document).on('click', '.removeRow', function() {
            if ($('#voucherTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateTotal();
            }
        });

        // Enter key new row
        $(document).on('keypress', '.amount', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addNewRow').click();
            }
        });
    </script>
@endsection
