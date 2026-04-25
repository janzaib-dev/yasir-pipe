<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - {{ $purchase->invoice_no }}</title>
    <!-- Use Bootstrap for grid and utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #000000ff;
            --accent-color: #000000ff;
            --border-color: #000000ff;
            --text-color: #000000ff;
        }

        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            /* Reduced base font size */
        }

        .invoice-container {
            max-width: 210mm;
            margin: 10px auto;
            background: #fff;
            padding: 20px;
            /* Reduced padding */
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
            /* Reduced */
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 2px;
        }

        .invoice-title {
            text-align: center;
            font-size: 18px;
            /* Reduced */
            font-weight: bold;
            text-transform: uppercase;
            color: var(--accent-color);
            margin: 15px 0 10px 0;
            letter-spacing: 2px;
        }

        .info-box {
            border: 1px solid var(--border-color);
            padding: 8px;
            /* Reduced padding */
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
            color: #000000ff;
            min-width: 70px;
            display: inline-block;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .invoice-table th {
            background-color: white
            /* color: #fff; */
            color: black;
            text-transform: uppercase;
            font-size: 11px;
            padding: 6px 4px;
            /* Reduced padding */
            border: 1px solid var(--primary-color);
        }

        .invoice-table td {
            border: 1px solid var(--border-color);
            padding: 4px 6px;
            /* Reduced padding */
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

        .footer-section {
            margin-top: 20px;
            border-top: 2px solid var(--primary-color);
            padding-top: 10px;
        }

        .terms-box {
            font-size: 11px;
            color: #666;
        }

        .terms-box ul {
            padding-left: 20px;
            margin-bottom: 0;
        }

        .terms-box li {
            margin-bottom: 2px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .totals-table td {
            padding: 4px 8px;
            /* Reduced padding */
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

            .no-print {
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
        <a href="{{ route('Purchase.home') }}" class="btn btn-secondary btn-sm shadow ms-2">Back</a>
    </div>

    <div class="invoice-container">
        <!-- Company Header -->
        <div class="company-info">
            <!-- year dynamic -->
            <div class="company-name">Yasir Pipe Store - <span>{{ date('Y') }}</span></div>
            <div style="font-size: 12px;">Gulshan-e-Ilahi, Hyderabad.</div>
        </div>

        <div class="invoice-title">Purchase Invoice</div>

        <!-- Info Grid -->
        <div class="row g-2 mb-2">
            <!-- Left Box: Vendor Info -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Vendor Details</div>
                    <div
                        style="font-size: 13px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $purchase->vendor->name ?? 'N/A' }}
                    </div>
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 11px;">
                        {{ $purchase->vendor->address ?? '' }}</div>
                    <div class="text-dark small" style="font-size: 11px;">
                        Mob: {{ $purchase->vendor->phone ?? '' }}
                    </div>
                </div>
            </div>

            <!-- Middle Box: Details -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Details</div>
                    <div><span class="info-label">Type:</span> {{ $purchase->status_purchase ?? 'Confirmed' }}</div>
                    <div><span class="info-label">Warehouse:</span> {{ $purchase->warehouse->warehouse_name ?? 'Main' }}
                    </div>
                </div>
            </div>

            <!-- Right Box: Invoice Specifics -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Reference</div>
                    <div><span class="info-label">Inv #:</span> <strong>INV-{{ $purchase->id }}</strong></div>
                    <div><span class="info-label">Date:</span>
                        {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        @if ($purchase->note)
            <div class="row mb-2">
                <div class="col-12">
                    <div class="info-box"
                        style="min-height: auto; padding: 4px 8px; background-color: #f1f5f9; font-style: italic;">
                        <strong>Note:</strong> {{ $purchase->note }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 12%">Code</th>
                    <th class="text-start" style="width: 33%">Description</th>
                    <th class="text-center" style="width: 12%">Qty</th>
                    <th class="text-center" style="width: 10%">UOM</th>
                    <th class="text-end" style="width: 10%">Price</th>
                    <th class="text-end" style="width: 10%">Disc</th>
                    <th class="text-end" style="width: 13%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    @php
                        // Dimensions
                        $height = $item->length ?? 0; // mapped to length column
                        $width = $item->width ?? 0;

                        // Calculation Logic (same as Sale but using item snapshot)
                        $piecesPerBox = (int) ($item->pieces_per_box ?? 1);
                        $m2PerPiece = $item->pieces_per_m2 ?? 0; // In purchase_items, this is m2_per_piece based on my previous analysis
                        $m2PerBox = $m2PerPiece * $piecesPerBox;

                        // Calculate boxes and loose
                        $totalPieces = (int) $item->qty;
                        $boxes = $piecesPerBox > 0 ? floor($totalPieces / $piecesPerBox) : $totalPieces;
                        $loosePieces = $piecesPerBox > 0 ? $totalPieces % $piecesPerBox : 0;

                        // Total M2
                        $totalM2Line = $m2PerPiece * $totalPieces;
                        $sizeMode = $item->size_mode ?? 'by_pieces';
                    @endphp
                    <tr>
                        <td class="text-center" style="vertical-align: middle; font-size: 11px; font-weight: bold;">
                            {{ $item->product->item_code ?? '-' }}
                        </td>

                        <td class="text-start">
                            <div style="font-weight: bold; font-size: 12px; margin-bottom: 2px;">
                                {{ $item->product->item_name ?? 'Item' }}
                            </div>

                            <div style="font-size: 11px; color: #111111ff; line-height: 1.2;">
                                @if ($sizeMode == 'by_size')
                                    <span class='d-inline-block ms-1'>
                                        @if ($height > 0 || $width > 0)
                                            Dims: {{ $width }}x{{ $height }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="text-center" style="vertical-align: middle;">
                            <div style="font-weight: bold; color: #2c3e50;">

                                @if ($sizeMode == 'by_pieces')
                                    {{ $totalPieces }} Pcs
                                @else
                                    @if ($boxes > 0 && $loosePieces > 0)
                                        {{ $boxes }} Box + {{ $loosePieces }} Pc
                                    @elseif ($boxes > 0)
                                        {{ $boxes }} Box
                                    @else
                                        {{ $loosePieces }} Pcs
                                    @endif
                                @endif
                            </div>
                            <small class="text-dark" style="font-size: 10px;">({{ $totalPieces }} pcs)</small>
                        </td>

                        <td class="text-center" style="vertical-align: middle;">
                            @if ($sizeMode == 'by_pieces')
                                <span class="fw-bold">Pcs</span>
                            @elseif ($sizeMode == 'by_cartons')
                                <span class="fw-bold">Box</span>
                            @elseif ($sizeMode == 'by_size')
                                <span class="fw-bold">{{ number_format($totalM2Line, 4) }}</span> m²
                            @else
                                {{ $item->unit }}
                            @endif
                        </td>

                        <td class="text-end" style="vertical-align: middle;">
                            {{ number_format($item->price, 2) }}
                        </td>

                        <td class="text-end" style="vertical-align: middle; color: #c0392b;">
                            @if ($item->item_discount > 0)
                                @php
                                    $grossLine = $item->line_total + $item->item_discount;
                                    $discPercent = $grossLine > 0 ? ($item->item_discount / $grossLine) * 100 : 0;
                                @endphp
                                <div style="font-size: 10px; line-height: 1;">{{ number_format($discPercent, 1) }}%</div>
                                <div style="font-size: 11px; font-weight: bold;">{{ number_format($item->item_discount, 2) }}</div>
                            @else
                                -
                            @endif
                        </td>

                        <td class="text-end fw-bold" style="vertical-align: middle;">
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
                    <div class="signature-area">
                        Authorized Signature
                    </div>
                </div>
            </div>

            <div class="col-5">
                <div class="info-box" style="border: none; padding: 0;">
                    <table class="totals-table">
                        <tr style="border-bottom: 2px solid #eee;">
                            <td class="text-dark">Subtotal</td>
                            <td class="text-end">{{ number_format($purchase->subtotal, 2) }}</td>
                        </tr>
                        @if ($purchase->extra_cost > 0)
                            <tr>
                                <td>Extra Cost</td>
                                <td class="text-end">{{ number_format($purchase->extra_cost, 2) }}</td>
                            </tr>
                        @endif
                        @if ($purchase->discount > 0)
                            <tr>
                                <td>Discount</td>
                                <td class="text-end text-danger">
                                    @php
                                        $billDiscPercent = $purchase->subtotal > 0 ? ($purchase->discount / $purchase->subtotal) * 100 : 0;
                                    @endphp
                                    <span style="font-size: 10px;" class="me-1">({{ number_format($billDiscPercent, 1) }}%)</span>
                                    -{{ number_format($purchase->discount, 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr class="total-row" style="background-color: #e9ecef;">
                            <td>Total Net</td>
                            <td class="text-end">{{ number_format($purchase->net_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Paid</td>
                            <td class="text-end text-success">{{ number_format($purchase->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Bill Due</td>
                            <td class="text-end fw-bold">
                                {{ number_format($purchase->net_amount - $purchase->paid_amount, 2) }}</td>
                        </tr>
                        <tr style="border-top: 1px solid #000;">
                            <td class="fw-bold text-danger">Total Closing Bal</td>
                            <td class="text-end fw-bold text-danger">
                                {{ number_format($vendor_balance, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</body>

</html>
