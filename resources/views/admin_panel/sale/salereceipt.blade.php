<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Receipt - {{ $sale->id }}</title>
    <style>
        /* Base Print Styles */
        @media print {
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 20px 0;
            color: #000;
        }

        .receipt-container {
            width: 80mm; /* Standard 80mm thermal paper width */
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Typography */
        h1, h2, h3, p {
            margin: 0;
            padding: 0;
        }

        .company-name {
            font-size: 18px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .company-info {
            font-size: 11px;
            text-align: center;
            color: #333;
            line-height: 1.4;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .divider-thick {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        /* Header Details */
        .meta-info {
            font-size: 11px;
            margin-bottom: 2px;
            display: flex;
            justify-content: space-between;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .items-table th {
            border-bottom: 1px dashed #000;
            border-top: 1px dashed #000;
            padding: 4px 0;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .item-name {
            font-weight: bold;
            font-size: 11px;
            padding-bottom: 2px;
        }

        .item-meta {
            font-size: 10px;
            color: #444;
        }

        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* Totals */
        .totals-section {
            font-size: 11px;
            margin-top: 5px;
        }

        .tot-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .tot-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            padding: 5px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        /* Balances */
        .balance-section {
            font-size: 11px;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 15px;
        }

        .footer p {
            font-size: 11px;
            font-weight: bold;
        }

        .footer .soft-credit {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
            font-weight: normal;
        }

        /* Controls */
        .print-controls {
            width: 80mm;
            margin: 0 auto 15px auto;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary { background: #000; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        
        .remarks-box {
            font-size: 11px;
            font-style: italic;
            text-align: center;
            margin: 5px 0;
        }
    </style>
</head>

<body>

    <div class="print-controls no-print">
        <a href="javascript:window.print()" class="btn btn-primary">🖨️ Print Receipt</a>
        <a href="{{ route('sale.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="company-name">Yasir Pipe Store</div>
        <div class="company-info">
            <div>Gulshan-e-Ilahi, Hyderabad.</div>
            <div>Ph: 0327-9226901</div>
        </div>

        <div class="divider"></div>

        <!-- Meta -->
        <div class="meta-info">
            <span><strong>Inv #:</strong> {{ $sale->id }}</span>
            <span>{{ $sale->created_at->format('d-m-Y h:i A') }}</span>
        </div>
        <div class="meta-info">
            <span><strong>Cust:</strong> {{ Str::limit($sale->customer_relation->customer_name ?? 'Walking Customer', 22) }}</span>
        </div>
        @if (auth()->check())
        <div class="meta-info">
            <span><strong>User:</strong> {{ auth()->user()->name }}</span>
        </div>
        @endif
        
        <!-- Remarks/Ref -->
        @if($sale->reference)
        <div class="remarks-box" style="text-align: left;">
            Remarks: {{ $sale->reference }}
        </div>
        @endif

        <div class="divider"></div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item Description</th>
                    <th style="width: 15%;" class="text-center">Qty</th>
                    <th style="width: 35%;" class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleItems as $item)
                    @php
                        // Size mode logic for display
                        $sizeMode = $item['size_mode'] ?? 'std';
                        $totalPieces = (int) $item['total_pieces'];

                        // Display quantity string
                        $qtyDisplay = $totalPieces;
                        if ($sizeMode == 'by_cartons' || $sizeMode == 'by_size') {
                            $piecesPerBox = (int)($item['pieces_per_box'] ?? 1);
                            if ($piecesPerBox <= 0) $piecesPerBox = 1;
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
                        <td colspan="3" class="item-name">
                            {{ \Illuminate\Support\Str::limit($item['item_name'], 30) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="item-meta">
                            {{ number_format($item['price'], 2) }} @if((float)($item['discount_amount'] ?? 0) > 0) <span style="font-size:9px;">(D: {{ number_format($item['discount_amount'], 2) }})</span>@endif
                        </td>
                        <td class="text-center font-weight-bold">{{ $qtyDisplay }}</td>
                        <td class="text-end">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="tot-row">
                <span>Sub Total:</span>
                <span>{{ number_format($sale->total_bill_amount, 2) }}</span>
            </div>

            @if ($sale->total_extradiscount > 0)
                <div class="tot-row">
                    <span>Discount:</span>
                    <span>- {{ number_format($sale->total_extradiscount, 2) }}</span>
                </div>
            @endif

            <div class="tot-row grand-total">
                <span>TOTAL PAYABLE:</span>
                <span>{{ number_format($sale->total_net, 2) }}</span>
            </div>
        </div>

        <!-- Ledger -->
        <div class="balance-section">
            <div class="tot-row">
                <span>Prev Balance:</span>
                <span>{{ number_format(abs($previousBalance), 2) }} {{ $previousBalance >= 0 ? 'Dr' : 'Cr' }}</span>
            </div>
            <div class="tot-row">
                <span>Paid Amount:</span>
                <span>{{ number_format($sale->cash, 2) }}</span>
            </div>

            @php
                $finalBalance = $previousBalance + $sale->total_net - $sale->cash;
            @endphp
            <div class="tot-row" style="margin-top: 3px; font-weight: bold;">
                <span>Closing Balance:</span>
                <span>{{ number_format(abs($finalBalance), 2) }} {{ $finalBalance >= 0 ? 'Dr' : 'Cr' }}</span>
            </div>
        </div>


        <!-- Footer -->
         <div class="footer">
    <p>Thank you for shopping with us!</p>
    <p>Powered by <strong>Prowave Technologies</strong></p>
    <p>📞 +92 317 3836 223</p>
</div>

<style>
.footer {
    text-align: center;
    padding: 15px 10px;
    font-size: 14px;
    color: #555;
    border-top: 1px solid #ddd;
    margin-top: 20px;
}

.footer p {
    margin: 5px 0;
}
</style>
                        
    </div>

</body>

</html>
