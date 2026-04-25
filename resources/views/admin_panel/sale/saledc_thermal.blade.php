<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal DC - {{ $sale->id }}</title>
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
        
        .dc-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 5px 0;
            border: 1px solid #000;
            padding: 2px;
            border-radius: 3px;
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
            border-bottom: 1px dotted #ccc;
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

        /* Footer */
        .footer {
            text-align: center;
            padding: 15px 10px;
            font-size: 14px;
            color: #555;
            border-top: 1px solid #ddd;
            margin-top: 20px;
        }

        .footer p {
            font-size: 11px;
            margin: 5px 0;    
        }
        
        .footer strong {
            color: #000;
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
            text-align: left;
            margin: 5px 0;
        }
        
        .sign-box {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
        }
        .sign-line {
            border-top: 1px solid #000;
            width: 100px;
            padding-top: 2px;
        }
    </style>
</head>

<body>

    <div class="print-controls no-print">
        <a href="javascript:window.print()" class="btn btn-primary">🖨️ Print DC</a>
        <a href="{{ route('sale.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="company-name">Yasir Pipe Store</div>
        <div class="company-info">
            <div>Gulshan-e-Ilahi, Hyderabad.</div>
            <div>Ph: 0327-9226901</div>
        </div>

        <div class="dc-title">DELIVERY CHALLAN</div>
        <div class="divider"></div>

        <!-- Meta -->
        <div class="meta-info">
            <span><strong>DC #:</strong> {{ $sale->id }}</span>
            <span>{{ $sale->created_at->format('d-m-Y h:i A') }}</span>
        </div>
        <div class="meta-info">
            <span><strong>Deliver To:</strong> {{ Str::limit($sale->customer_relation->customer_name ?? 'Walking Customer', 20) }}</span>
        </div>
        @if($sale->customer_relation?->address)
        <div class="meta-info text-muted">
            <span>{{ Str::limit($sale->customer_relation->address, 30) }}</span>
        </div>
        @endif
        @if (auth()->check())
        <div class="meta-info mt-1">
            <span><strong>Maker:</strong> {{ auth()->user()->name }}</span>
        </div>
        @endif
        
        <!-- Remarks/Ref -->
        @if($sale->reference)
        <div class="remarks-box">
            Remarks: {{ $sale->reference }}
        </div>
        @endif
        
        @if ($sale->return_note)
        <div class="remarks-box" style="background:#f9f9f9; padding:3px; font-weight:bold;">
            Note: {{ $sale->return_note }}
        </div>
        @endif

        <div class="divider"></div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Item Description</th>
                    <th style="width: 30%;" class="text-end">Qty/Boxes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleItems as $item)
                    @php
                        // Size mode logic for display
                        $sizeMode = $item['size_mode'] ?? 'std';
                        $totalPieces = (int) $item['total_pieces'];

                        // Same layout logic from existing saledc formatting
                        $piecesPerBox = (int)($item['pieces_per_box'] ?? 1);
                        if ($piecesPerBox <= 0) $piecesPerBox = 1;
                        $boxes = floor($totalPieces / $piecesPerBox);
                        $loosePieces = $totalPieces % $piecesPerBox;

                        $qtyStr = "";
                        if ($sizeMode == 'by_pieces') {
                            $qtyStr = "{$totalPieces} Pcs";
                        } else {
                            if ($boxes > 0 && $loosePieces > 0) {
                                $cStr = $sizeMode == 'by_cartons' ? 'Crtn' : 'Box';
                                $qtyStr = "{$boxes} {$cStr} + {$loosePieces} Pc";
                            } elseif ($boxes > 0) {
                                $cStr = $sizeMode == 'by_cartons' ? 'Cartons' : 'Boxes';
                                $qtyStr = "{$boxes} {$cStr}";
                            } else {
                                $qtyStr = "{$loosePieces} Pcs";
                            }
                        }
                    @endphp
                    <tr>
                        <td class="item-name">
                            {{ \Illuminate\Support\Str::limit($item['item_name'], 30) }}
                            <div class="item-meta mt-1">
                                @if (!empty($item['item_code']))
                                    ({{ $item['item_code'] }})
                                @endif
                                Total: {{ $totalPieces }} pcs
                            </div>
                        </td>
                        <td class="text-end font-weight-bold" style="vertical-align: middle;">
                            {{ $qtyStr }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures (for delivery) -->
        <div class="sign-box">
            <div>
                <div class="sign-line">Auth. Signature</div>
            </div>
            <div>
                <div class="sign-line">Receiver</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Please check items upon delivery.</p>
            <p>Powered by <strong>Prowave Technologies</strong></p>
            <p>📞 +92 317 3836 223</p>
        </div>
    </div>

</body>

</html>
