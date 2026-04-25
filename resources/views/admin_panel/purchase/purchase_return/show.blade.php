<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Return Invoice - {{ $return->return_invoice }}</title>
    <!-- Use Bootstrap for grid and utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #c0392b;
            /* Red for Return */
            --accent-color: #e74c3c;
            --border-color: #bdc3c7;
            --text-color: #2c3e50;
        }

        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 10px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-height: 297mm;
            position: relative;
        }

        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 2px;
        }

        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: var(--accent-color);
            margin: 15px 0 10px 0;
            letter-spacing: 2px;
        }

        .info-box {
            border: 1px solid var(--border-color);
            padding: 8px;
            height: 100%;
            border-radius: 4px;
            background-color: #fff;
        }

        .info-box-header {
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 4px;
            padding-bottom: 2px;
            color: var(--primary-color);
            font-size: 11px;
            text-transform: uppercase;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 70px;
            display: inline-block;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .invoice-table th {
            background-color: var(--primary-color);
            color: #fff;
            text-transform: uppercase;
            font-size: 11px;
            padding: 6px 4px;
            border: 1px solid var(--primary-color);
        }

        .invoice-table td {
            border: 1px solid var(--border-color);
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 12px;
        }

        .invoice-table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .totals-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #eee;
        }

        .totals-table .total-row td {
            border-top: 2px solid var(--primary-color);
            font-weight: bold;
            font-size: 14px;
            color: var(--primary-color);
        }

        .signature-area {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 180px;
            text-align: center;
            padding-top: 5px;
        }

        .print-btn-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        @media print {
            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }

            .invoice-container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 10px;
                box-shadow: none;
                border: none;
                min-height: auto;
            }

            .print-btn-container {
                display: none;
            }

            @page {
                margin: 5mm;
            }
        }
    </style>
</head>

<body>

    <!-- Print Button -->
    <div class="print-btn-container">
        <button onclick="window.print()" class="btn btn-primary btn-sm shadow">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                class="bi bi-printer-fill me-2" viewBox="0 0 16 16">
                <path
                    d="M0 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9zm4-6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2H4V3z" />
                <path d="M2.5 14.5A1.5 1.5 0 0 1 1 13V9a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v4a1.5 1.5 0 0 1-1.5 1.5h-13z" />
            </svg>
            Print
        </button>
        <a href="{{ route('purchase.return.index') }}" class="btn btn-secondary btn-sm shadow ms-2">Back</a>
    </div>

    <div class="invoice-container">
        <!-- Company Header -->
        <div class="company-info">
            <div class="company-name">Yasir Pipe Store</div>
            <div style="font-size: 12px;">Purchase Return Note</div>
        </div>

        <div class="invoice-title">Purchase Return Invoice</div>

        <!-- Info Grid -->
        <div class="row g-2 mb-2">
            <!-- Left Box: Vendor Info -->
            <div class="col-6">
                <div class="info-box">
                    <div class="info-box-header">Vendor Details</div>
                    <div style="font-size: 13px; font-weight: bold;">{{ $return->vendor->name ?? 'N/A' }}</div>
                    <div style="font-size: 11px;">{{ $return->vendor->address ?? '' }}</div>
                    <div class="text-muted small">Phone: {{ $return->vendor->phone ?? '' }}</div>
                </div>
            </div>

            <!-- Right Box: Invoice Specifics -->
            <div class="col-6">
                <div class="info-box">
                    <div class="info-box-header">Return Reference</div>
                    <div><span class="info-label">Return #:</span> <strong>{{ $return->return_invoice }}</strong></div>
                    <div><span class="info-label">Date:</span>
                        {{ \Carbon\Carbon::parse($return->return_date)->format('d-m-Y') }}</div>
                    <div><span class="info-label">Warehouse:</span> {{ $return->warehouse->warehouse_name ?? 'Main' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        @if ($return->remarks)
            <div class="row mb-2">
                <div class="col-12">
                    <div class="info-box"
                        style="min-height: auto; padding: 4px 8px; background-color: #f1f5f9; font-style: italic;">
                        <strong>Note:</strong> {{ $return->remarks }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-start" style="width: 45%">Description</th>
                    <th class="text-center" style="width: 15%">Qty</th>
                    <th class="text-center" style="width: 15%">UOM</th>
                    <th class="text-end" style="width: 12%">Price</th>
                    <th class="text-end" style="width: 13%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($return->items as $item)
                    @php
                        // Check Product Defaults (since not stored on item)
                        $product = $item->product;
                        $piecesPerBox = $product->pieces_per_box ?? 1;
                        $sizeMode = $product->size_mode ?? 'by_pieces';

                        $totalPieces = (int) $item->qty;
                        $boxes = $piecesPerBox > 1 ? floor($totalPieces / $piecesPerBox) : 0;
                        $loosePieces = $piecesPerBox > 1 ? $totalPieces % $piecesPerBox : $totalPieces;
                    @endphp
                    <tr>
                        <td class="text-start">
                            <div style="font-weight: bold; font-size: 12px;">{{ $product->item_name ?? 'Item' }}</div>
                            <small class="text-muted">{{ $product->item_code ?? '' }}</small>
                        </td>

                        <td class="text-center">
                            @if ($sizeMode == 'by_pieces')
                                {{ $totalPieces }} Pcs
                            @elseif ($piecesPerBox > 1)
                                @if ($boxes > 0 && $loosePieces > 0)
                                    {{ $boxes }} Box + {{ $loosePieces }} Pc
                                @elseif ($boxes > 0)
                                    {{ $boxes }} Box
                                @else
                                    {{ $loosePieces }} Pcs
                                @endif
                            @else
                                {{ $totalPieces }} Pcs
                            @endif
                        </td>

                        <td class="text-center">
                            {{ $item->unit ?? 'pc' }}
                        </td>

                        <td class="text-end">
                            {{ number_format($item->price, 2) }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($item->line_total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer -->
        <div class="row mt-2">
            <div class="col-7">
                <div class="mt-4 pt-2">
                    <div class="signature-area">Authorized Signature</div>
                </div>
            </div>

            <div class="col-5">
                <div class="info-box" style="border: none; padding: 0;">
                    <table class="totals-table">
                        <tr>
                            <td class="text-muted">Subtotal</td>
                            <td class="text-end">{{ number_format($return->bill_amount, 2) }}</td>
                        </tr>
                        @if ($return->item_discount > 0)
                            <tr>
                                <td>Item Discount</td>
                                <td class="text-end">-{{ number_format($return->item_discount, 2) }}</td>
                            </tr>
                        @endif
                        @if ($return->extra_discount > 0)
                            <tr>
                                <td>Extra Discount</td>
                                <td class="text-end">-{{ number_format($return->extra_discount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row" style="background-color: #fbecec;">
                            <td>Total Refund Info</td>
                            <td class="text-end">{{ number_format($return->net_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Paid / Adjusted</td>
                            <td class="text-end text-success">{{ number_format($return->paid, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Balance</td>
                            <td class="text-end fw-bold">{{ number_format($return->net_amount - $return->paid, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
