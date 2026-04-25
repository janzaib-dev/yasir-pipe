@extends('admin_panel.layout.app')
@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --pv-primary: #4f46e5;
            --pv-bg: #f8fafc;
            --pv-border: #e2e8f0;
            --pv-text: #1e293b;
            --pv-muted: #64748b;
        }

        .pv-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
            border: 1px solid var(--pv-border);
            padding: 24px;
        }

        .pv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--pv-border);
        }

        .pv-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--pv-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pv-title i {
            color: var(--pv-primary);
            background: #e0e7ff;
            padding: 8px 10px;
            border-radius: 10px;
        }

        .pv-section-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--pv-primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pv-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--pv-border);
        }

        .pv-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--pv-muted);
            margin-bottom: 4px;
        }

        .pv-input {
            background: #fff;
            border: 1px solid var(--pv-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
            color: var(--pv-text);
            transition: all 0.2s ease;
            width: 100%;
        }
        .pv-input:focus {
            border-color: var(--pv-primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
            outline: none;
        }
        .pv-input::placeholder { color: #cbd5e1; }

        select.pv-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .pv-table {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--pv-border);
        }
        .pv-table thead th {
            background: #1e293b;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 12px;
            text-align: center;
            border: none;
        }
        .pv-table tbody td {
            padding: 8px 10px;
            vertical-align: middle;
            border-color: var(--pv-border);
        }
        .pv-table tfoot td {
            padding: 10px 12px;
            background: #f1f5f9;
            font-weight: 700;
        }

        .btn-pv-primary {
            background: var(--pv-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(79,70,229,0.3);
            transition: all 0.2s;
        }
        .btn-pv-primary:hover {
            background: #4338ca;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(79,70,229,0.4);
        }

        .btn-pv-secondary {
            background: #f1f5f9;
            color: var(--pv-text);
            border: 1px solid var(--pv-border);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-pv-secondary:hover { background: #e2e8f0; color: var(--pv-text); }

        .btn-pv-add {
            background: #ecfdf5;
            color: #059669;
            border: 1px dashed #10b981;
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-pv-add:hover { background: #d1fae5; }

        .balance-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .balance-dr { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
        .balance-cr { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
    </style>

    <div class="main-content">
        <div class="main-content-inner" style="padding: 10px;">
            <div class="container-fluid p-0">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('store_Pay_vochers') }}" method="POST" id="paymentForm">
                    @csrf

                    <div class="pv-card">
                        <!-- Header -->
                        <div class="pv-header">
                            <div class="pv-title">
                                <i class="bi bi-cash-stack"></i> Payment Voucher
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('all_Payment_vochers') }}" class="btn btn-pv-secondary">
                                    <i class="bi bi-list-ul me-1"></i> All Vouchers
                                </a>
                                <button type="submit" class="btn btn-pv-primary">
                                    <i class="bi bi-check-lg me-1"></i> Save Voucher
                                </button>
                            </div>
                        </div>

                        <!-- Row 1: Voucher Info -->
                        <div class="pv-section-label">Voucher Details</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="pv-label">PVID</label>
                                <input type="text" class="pv-input" style="background: #f1f5f9;" name="pvid"
                                    value="{{ $nextPVID }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="pv-label">Receipt Date</label>
                                <input type="date" name="receipt_date" class="pv-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="pv-label">Entry Date</label>
                                <input type="date" name="entry_date" class="pv-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="pv-label">Remarks <small class="text-muted fw-normal">(Optional)</small></label>
                                <input type="text" name="remarks" class="pv-input" id="remarks" placeholder="Auto-generated if left blank">
                            </div>
                        </div>

                        <!-- Row 2: Pay From (Source Account) -->
                        <div class="pv-section-label">Pay From (Source Account)</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="pv-label">Account Head</label>
                                <select name="header_account_head" class="pv-input" id="payFromHead">
                                    <option value="">Select Head</option>
                                    @foreach ($AccountHeads as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="pv-label">Account (Cash / Bank)</label>
                                <select name="header_account_id" class="pv-input" id="payFromAccount">
                                    <option disabled selected>Select Account</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="pv-label">Account Code</label>
                                <input type="text" id="accountCode" class="pv-input" style="background: #f1f5f9;" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="pv-label">Account Balance</label>
                                <div id="sourceBalanceDisplay" class="balance-badge balance-cr p-2 text-center" style="width:100%;">0.00 <small>Cr</small></div>
                            </div>
                        </div>

                        <!-- Payment Destination Rows -->
                        <div class="pv-section-label">Pay To (Destination)</div>
                        <div class="pv-table">
                            <table class="table table-bordered align-middle mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Type</th>
                                        <th style="width: 35%;">Party / Account</th>
                                        <th style="width: 25%;">Amount</th>
                                        <th style="width: 15%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="vendor_type[]" class="pv-input rowType">
                                                <option disabled selected>Select</option>
                                                <option value="vendor">Vendor</option>
                                                <option value="customer">Customer</option>
                                                <option value="walkin">Walk-in</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="vendor_id[]" class="pv-input rowParty">
                                                <option disabled selected>Select Party</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="amount[]"
                                                class="pv-input text-end fw-bold amount" placeholder="0.00"
                                                style="font-size: 1rem;">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm removeRow" title="Remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold" style="font-size: 1rem;">Total Amount:</td>
                                        <td>
                                            <input type="text" name="total_amount"
                                                class="pv-input text-end fw-bold" id="totalAmount" readonly
                                                value="0.00" style="background: #fef2f2; border-color: #fca5a5; font-size: 1.1rem; color: #dc2626;">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <button type="button" class="btn btn-pv-add mt-3" id="addNewRow">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Row
                        </button>

                        {{-- Hidden fields for backward compatibility --}}
                        <input type="hidden" name="narration_id[]" value="">
                        <input type="hidden" name="narration_text[]" value="">
                        <input type="hidden" name="reference_no[]" value="">
                        <input type="hidden" name="discount_value[]" value="0">
                        <input type="hidden" name="rate[]" value="0">

                    </div><!-- /pv-card -->

                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {

            // Header Source Account Logic
            $('#payFromHead').on('change', function() {
                let headId = $(this).val();
                let $accSelect = $('#payFromAccount');
                $accSelect.html('<option disabled selected>Loading...</option>');
                $('#accountCode').val('');
                updateSourceBalance(0);

                if (headId) {
                    $.get('{{ url("get-accounts-by-head") }}/' + headId, function(data) {
                        $accSelect.empty().append('<option disabled selected>Select Account</option>');
                        data.forEach(function(acc) {
                            $accSelect.append(
                                `<option value="${acc.id}" data-code="${acc.account_code || ''}" data-bal="${acc.current_balance || 0}">${acc.title}</option>`
                            );
                        });
                    });
                }
            });

            $('#payFromAccount').on('change', function() {
                let $opt = $(this).find(':selected');
                $('#accountCode').val($opt.data('code'));
                let bal = parseFloat($opt.data('bal')) || 0;
                updateSourceBalance(bal);
            });

            function updateSourceBalance(bal) {
                let $badge = $('#sourceBalanceDisplay');
                let formatted = Math.abs(bal).toFixed(2);
                if (bal >= 0) {
                    $badge.removeClass('balance-dr').addClass('balance-cr');
                    $badge.html(formatted + ' <small>Cr</small>');
                } else {
                    $badge.removeClass('balance-cr').addClass('balance-dr');
                    $badge.html(formatted + ' <small>Dr</small>');
                }
            }

            // Row Logic (Destination Parties)
            $(document).on('change', '.rowType', function() {
                let type = $(this).val();
                let $row = $(this).closest('tr');
                let $select = $row.find('.rowParty');

                $select.html('<option disabled selected>Loading...</option>');

                if (type === 'vendor' || type === 'customer' || type === 'walkin') {
                    $.get('{{ route("party.list") }}?type=' + type, function(data) {
                        $select.empty().append('<option disabled selected>Select Party</option>');
                        data.forEach(function(item) {
                            $select.append(
                                `<option value="${item.id}">${item.text}</option>`
                            );
                        });
                    });
                } else if (type) {
                    $.get('{{ url("get-accounts-by-head") }}/' + type, function(data) {
                        $select.empty().append('<option disabled selected>Select Account</option>');
                        data.forEach(function(acc) {
                            $select.append(
                                `<option value="${acc.id}">${acc.title}</option>`
                            );
                        });
                    });
                }
            });

            // Totals
            function calculateTotal() {
                let total = 0;
                $('.amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalAmount').val(total.toFixed(2));
            }
            $(document).on('input', '.amount', function() {
                calculateTotal();
            });

            // Add Row
            $('#addNewRow').on('click', function() {
                let newRow = `
                <tr>
                    <td>
                        <select name="vendor_type[]" class="pv-input rowType">
                            <option disabled selected>Select</option>
                            <option value="vendor">Vendor</option>
                            <option value="customer">Customer</option>
                            <option value="walkin">Walk-in</option>
                            @foreach ($AccountHeads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="vendor_id[]" class="pv-input rowParty">
                            <option disabled selected>Select Party</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="amount[]" class="pv-input text-end fw-bold amount" placeholder="0.00" style="font-size: 1rem;">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm removeRow"><i class="bi bi-trash"></i></button>
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
        });
    </script>
@endsection
