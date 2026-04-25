<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Receipt - {{ $purchase->invoice_no }}</title>
    <style>
        @media print {
            body {
                width: 72mm;
                margin: 0;
                padding: 0;
                font-family: 'Courier New', Courier, monospace;
            }

            .no-print {
                display: none;
            }

            @page {
                size: 72mm auto;
                margin: 0;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 72mm;
            /* Preview width */
            margin: 0 auto;
            padding: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .sub-header {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .items-table th,
        .items-table td {
            text-align: left;
            vertical-align: top;
            padding: 2px 0;
        }

        .items-table th {
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .text-end {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .totals-section {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }

        .btn-print {
            padding: 10px;
            text-align: center;
            background: #eee;
            margin-bottom: 10px;
            cursor: pointer;
            display: block;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <a href="javascript:window.print()" class="btn-print no-print">PRINT RECEIPT</a>

    <div class="header">
        <div class="company-name">Yasir Pipe Store</div>
        <div class="sub-header">Gulshan-e-Ilahi, Hyderabad.</div>
        <div class="sub-header">0327-9226901</div>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Inv #: {{ $purchase->invoice_no }}</span>
        <span>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') }}</span>
    </div>
    <div class="info-row">
        <span>Vendor: {{ Str::limit($purchase->vendor->name ?? 'N/A', 15) }}</span>
    </div>
    @if (auth()->check())
        <div class="info-row">
            <span>User: {{ auth()->user()->name }}</span>
        </div>
    @endif

    <div class="divider"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Item</th>
                <th style="width: 20%;" class="text-center">Qty</th>
                <th style="width: 35%;" class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                @php
                    // Size mode logic for display
                    $sizeMode = $item->size_mode ?? 'by_pieces';
                    $totalPieces = (int) $item->qty;

                    // Display quantity string
                    $qtyDisplay = $totalPieces;
                    if ($sizeMode == 'by_cartons' || $sizeMode == 'by_size') {
                        $piecesPerBox = (int) ($item->pieces_per_box ?? 1);
                        // Prevent div by zero
                        $piecesPerBox = $piecesPerBox > 0 ? $piecesPerBox : 1;

                        $boxes = floor($totalPieces / $piecesPerBox);
                        $loose = $totalPieces % $piecesPerBox;

                        if ($boxes > 0 && $loose > 0) {
                            $qtyDisplay = "$boxes.$loose";
                        } elseif ($boxes > 0) {
                            $qtyDisplay = $boxes;
                        } else {
                            $qtyDisplay = $loose;
                        }
                    }
                @endphp
                <tr>
                    <td colspan="3" style="font-weight: 600;">
                        {{ \Illuminate\Support\Str::limit($item->product->item_name ?? 'Item', 25) }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 10px; padding-left: 5px;">
                        {{ number_format($item->price, 2) }} x
                    </td>
                    <td class="text-center">{{ $qtyDisplay }}</td>
                    <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="info-row">
            <span>Subtotal:</span>
            <span>{{ number_format($purchase->subtotal, 2) }}</span>
        </div>

        @if ($purchase->discount > 0)
            <div class="info-row">
                <span>Discount:</span>
                <span>-{{ number_format($purchase->discount, 2) }}</span>
            </div>
        @endif

        @if ($purchase->extra_cost > 0)
            <div class="info-row">
                <span>Extra Cost:</span>
                <span>{{ number_format($purchase->extra_cost, 2) }}</span>
            </div>
        @endif

        <div class="total-row" style="font-size: 14px; border-top: 1px solid #000; margin-top: 5px; padding-top: 2px;">
            <span>TOTAL NET:</span>
            <span>{{ number_format($purchase->net_amount, 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span>Paid:</span>
            <span>{{ number_format($purchase->paid_amount, 2) }}</span>
        </div>

        <div class="info-row" style="font-weight: bold;">
            <span>Balance:</span>
            <span>{{ number_format($purchase->net_amount - $purchase->paid_amount, 2) }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Purchase Record</p>
        <p style="font-size: 9px;">Software by: Antigravity AI</p>
    </div>

</body>

</html>
