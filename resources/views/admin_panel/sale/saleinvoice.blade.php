<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $sale->id }}</title>
    <!-- Use Bootstrap for grid and utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --border-color: #bdc3c7;
            --text-color: #2c3e50;
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
        <a href="{{ route('sale.index') }}" class="btn btn-secondary btn-sm shadow ms-2">Back</a>
    </div>

    <div class="invoice-container">
        <!-- Company Header -->
        @if(!($isEstimate ?? false))
        <div class="company-info">
            <div class="company-name">Yasir Pipe Store</div>
            <div style="font-size: 12px;">Gulshan-e-Ilahi, Hyderabad.</div>
             <p>Phone: 03072996698 </p>
        </div>
        @endif

        <div class="invoice-title">{{ ($isEstimate ?? false) ? 'Estimate' : 'Sales Invoice' }}</div>

        <!-- Info Grid -->
        @if(!($isEstimate ?? false))
        <div class="row g-2 mb-2">
            <!-- Left Box: Customer Info -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Customer</div>
                    @if($sale->customer_relation?->customer_id)
                    <div style="font-size: 11px; color: #555;">
                        Code: <strong>{{ $sale->customer_relation->customer_id }}</strong>
                    </div>
                    @endif
                    <div><span class="info-label">Name:</span> <strong>{{ $sale->customer_relation->customer_name ?? 'Walking Customer' }}</strong></div>
                    <div><span class="info-label">Address:</span> <span style="font-size:11px;">{{ $sale->customer_relation->address ?? '—' }}</span></div>
                    <div><span class="info-label">Mob:</span> <span style="font-size:11px;">{{ $sale->customer_relation->mobile ?? '—' }}</span></div>
                </div>
            </div>

            <!-- Middle Box: Sales Person / Meta -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Details</div>
                    <div><span class="info-label">Maker:</span> {{ auth()->user()->name ?? 'Admin' }}</div>
                    <div>
                        <span class="info-label">Sales Person:</span>
                        @php
                            $officer = $sale->customer_relation?->salesOfficer;
                        @endphp
                        <strong>{{ $officer?->name ?? auth()->user()->name ?? 'Admin' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Right Box: Invoice Specifics -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Reference</div>
                    <div><span class="info-label">Inv #:</span> <strong>{{ $sale->id }}</strong></div>
                    <div><span class="info-label">Date:</span> {{ $sale->created_at->format('d-m-Y') }}</div>
                    @if($sale->reference)
                    <div style="margin-top:4px; padding-top:4px; border-top:1px dashed #ddd;">
                        <span class="info-label">Remarks:</span>
                        <span style="font-size:11px; color:#333;">{{ $sale->reference }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div class="row g-2 mb-2">
             <div class="col-12 text-end">
                <div class="info-box">
                    <div><span class="info-label">Date:</span> {{ $sale->created_at->format('d-m-Y') }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Remarks -->
        @if ($sale->return_note)
            <div class="row mb-2">
                <div class="col-12">
                    <div class="info-box"
                        style="min-height: auto; padding: 4px 8px; background-color: #f1f5f9; font-style: italic;">
                        <strong>Note:</strong> {{ $sale->return_note }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-start" style="width: 38%">Description</th>
                    <th class="text-center" style="width: 14%">Shipped</th>
                    <th class="text-center" style="width: 10%">UOM</th>
                    <th class="text-end" style="width: 10%">Price</th>
                    <th class="text-end" style="width: 10%">Disc</th>
                    <th class="text-end" style="width: 13%">Net Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleItems as $item)
                    @php
                        // Get dimensions from database
                        $height = $item['height'] ?? 0;
                        $width = $item['width'] ?? 0;

                        // Calculate m² per piece and per box
                        $m2PerPiece = $height > 0 && $width > 0 ? ($height * $width) / 10000 : 0;
                        $piecesPerBox = (int)($item['pieces_per_box'] ?? 1);
                        if ($piecesPerBox <= 0) $piecesPerBox = 1;
                        $m2PerBox = $m2PerPiece * $piecesPerBox;

                        // Calculate boxes and loose pieces
                        $totalPieces = (int) $item['total_pieces'];
                        $boxes = floor($totalPieces / $piecesPerBox);
                        $loosePieces = $totalPieces % $piecesPerBox;

                        // Total M2 for line
                        $totalM2Line = $m2PerPiece * $totalPieces;
                        $sizeMode = $item['size_mode'] ?? 'by_size';
                    @endphp
                    <tr>
                        <td class="text-start">
                            <div style="font-weight: bold; font-size: 12px; margin-bottom: 2px;">
                                {{ $item['item_name'] }}
                                @if (!empty($item['item_code']))
                                    <span class="text-muted fw-normal ms-1"
                                        style="font-size: 11px;">({{ $item['item_code'] }})</span>
                                @endif
                            </div>

                            <div style="font-size: 11px; color: #555; line-height: 1.2;">
                                @if (!empty($item['color']))
                                    <span class="badge bg-light text-dark border p-1"
                                        style="font-size: 9px; line-height:1;">
                                        @foreach ($item['color'] as $clr)
                                            {{ $clr }}
                                        @endforeach
                                    </span>
                                @endif

                                @if ($sizeMode == 'by_size')
                                    <span class='d-inline-block ms-1'>
                                        @if ($height > 0 && $width > 0)
                                            Dims: {{ number_format($width, 0) }}x{{ number_format($height, 0) }}
                                        @endif
                                    </span>
                                @endif

                                @if ($piecesPerBox > 1)
                                <span class="d-inline-block ms-1">
                                    Pack: {{ $piecesPerBox }} pcs
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
                                        {{ $boxes }} {{ $sizeMode == 'by_cartons' ? 'Carton' : 'Box' }} +
                                        {{ $loosePieces }} Pc
                                    @elseif ($boxes > 0)
                                        {{ $boxes }} {{ $sizeMode == 'by_cartons' ? 'Carton' : 'Box' }}
                                    @else
                                        {{ $loosePieces }} Pcs
                                    @endif
                                @endif
                            </div>
                            <small class="text-muted" style="font-size: 10px;">({{ $totalPieces }} pcs)</small>
                        </td>

                        <td class="text-center" style="vertical-align: middle;">

                            @if ($sizeMode == 'by_pieces')
                            <span class="fw-bold">
                            Peices
                        </span> 
                            @elseif ($sizeMode == 'by_cartons')
                            <span class="fw-bold">
                            Cartons
                        </span> 
                            @elseif ($sizeMode == 'by_size')
                            <span class="fw-bold">
                                    
                                {{ number_format($totalM2Line, 4) }}
                            </span> m²
                                @endif
                        </td>

                        <td class="text-end" style="vertical-align: middle;">
                            {{ number_format($item['price'], 2) }}
                        </td>

                        {{-- DISCOUNT COLUMN --}}
                        <td class="text-end" style="vertical-align: middle;">
                            @php
                                $discAmt  = (float)($item['discount_amount'] ?? 0);
                                $discPct  = (float)($item['discount_percent'] ?? 0);
                            @endphp
                            @if ($discAmt > 0)
                                <span class="text-danger">{{ number_format($discAmt, 2) }}</span>
                                @if ($discPct > 0)
                                    <br><small class="text-muted">({{ number_format($discPct, 1) }}%)</small>
                                @else
                                    <br><small class="text-muted">PKR</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-end fw-bold" style="vertical-align: middle;">
                            {{ number_format($item['total'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer -->
        <div class="row mt-2">
            <div class="col-7">
                <div class="terms-box pt-2">
                    <p class="fw-bold mb-1">Terms & Conditions:</p>
                    <ul style="font-size: 10px;">
                        <li>10% will be deducted on return of purchase goods within 7 days.</li>
                        <li>Loose & Water Soak products will not be RETURNED.</li>
                        <li>Please bring this invoice for any returns or exchanges.</li>
                    </ul>
                </div>

                <div class="mt-4 pt-2">
                    <div class="signature-area">
                        Authorized Signature
                    </div>
                    <div class="small text-muted mt-1" style="font-size: 10px;">
                        Printed on: {{ date('d-m-Y h:i A') }}
                    </div>
                </div>
            </div>

            <div class="col-5">
                <div class="info-box" style="border: none; padding: 0;">
                    <table class="totals-table">
                        @php
                            $grossTotal  = collect($saleItems)->sum('total');
                            $totalDisc   = collect($saleItems)->sum('discount_amount');
                            $netBill     = $sale->total_net;          // after extra discount
                            $paidAmount  = (float)($sale->cash ?? 0);
                            $finalBal    = $previousBalance + $netBill - $paidAmount;
                        @endphp

                        @if ($totalDisc > 0)
                        <tr>
                            <td class="text-muted">Gross Total</td>
                            <td class="text-end text-muted">{{ number_format($grossTotal + $totalDisc, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Discount</td>
                            <td class="text-end text-danger">- {{ number_format($totalDisc, 2) }}</td>
                        </tr>
                        @endif

                        <tr style="border-bottom: 2px solid #eee;">
                            <td class="text-muted">Prev Bal</td>
                            <td class="text-end text-muted">
                                {{ number_format(abs($previousBalance), 2) }}
                                <small>{{ $previousBalance >= 0 ? 'Dr' : 'Cr' }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Current Bill</td>
                            <td class="text-end">
                                {{ number_format($netBill, 2) }}
                            </td>
                        </tr>
                        <tr class="total-row" style="background-color: #e9ecef;">
                            <td>Total</td>
                            <td class="text-end">
                                {{ number_format(abs($previousBalance + $netBill), 2) }}
                                <small>{{ ($previousBalance + $netBill) >= 0 ? 'Dr' : 'Cr' }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Paid</td>
                            <td class="text-end text-success">{{ number_format($paidAmount, 2) }}</td>
                        </tr>
                        <tr style="border-top: 2px solid var(--primary-color);">
                            <td class="fw-bold">Closing Bal</td>
                            <td class="text-end fw-bold {{ $finalBal > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format(abs($finalBal), 2) }}
                                <small>{{ $finalBal >= 0 ? 'Dr' : 'Cr' }}</small>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="text-end mt-1">
                    <small class="text-muted fst-italic"
                        style="font-size: 10px;">{{ Str::limit($sale->total_amount_Words, 60) }}</small>
                </div>
            </div>
        </div>

    </div>
</body>

</html>
