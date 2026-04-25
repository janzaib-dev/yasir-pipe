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
            /* Dark Blue/Slate */
            --accent-color: #3498db;
            /* Blue */
            --border-color: #bdc3c7;
            --text-color: #2c3e50;
        }

        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }

        .invoice-container {
            max-width: 210mm;
            /* A4 Width */
            margin: 20px auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-height: 297mm;
            /* A4 Height */
            position: relative;
        }

        .header-title {
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
        }

        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            color: var(--accent-color);
            margin: 20px 0;
            letter-spacing: 2px;
        }

        /* Bordered Boxes for Info */
        .info-box {
            border: 1px solid var(--border-color);
            padding: 10px;
            height: 100%;
            border-radius: 4px;
            background-color: #fff;
        }

        .info-box-header {
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 8px;
            padding-bottom: 4px;
            color: var(--primary-color);
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 80px;
            display: inline-block;
        }

        /* Table Styling */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .invoice-table th {
            background-color: var(--primary-color);
            color: #fff;
            text-transform: uppercase;
            font-size: 12px;
            padding: 12px 8px;
            border: 1px solid var(--primary-color);
        }

        .invoice-table td {
            border: 1px solid var(--border-color);
            padding: 8px;
            vertical-align: middle;
            font-size: 13px;
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

        /* Footer Section */
        .footer-section {
            margin-top: 30px;
            border-top: 2px solid var(--primary-color);
            padding-top: 10px;
        }

        .terms-box {
            font-size: 12px;
            color: #666;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-table .total-row td {
            border-top: 2px solid var(--primary-color);
            font-weight: bold;
            font-size: 16px;
            color: var(--primary-color);
        }

        .signature-area {
            margin-top: 60px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 5px;
        }

        .print-btn-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        /* Print Media Query */
        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 15px;
                box-shadow: none;
                border: none;
            }

            .print-btn-container {
                display: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- Print Button -->
    <div class="print-btn-container">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                class="bi bi-printer-fill me-2" viewBox="0 0 16 16">
                <path
                    d="M0 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9zm4-6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2H4V3z" />
                <path d="M2.5 14.5A1.5 1.5 0 0 1 1 13V9a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v4a1.5 1.5 0 0 1-1.5 1.5h-13z" />
            </svg>
            Print Invoice
        </button>
        <a href="{{ route('sale.index') }}" class="btn btn-secondary btn-lg shadow ms-2">Back</a>
    </div>

    <div class="invoice-container">
        <!-- Company Header -->
        <div class="company-info">
            <div class="company-name">Yasir Pipe Store </div>
            <div>SADAR BAZAR </div>
            <div>HYDERABAD SINDH </div>
        </div>

        <div class="invoice-title">Sales Invoice</div>

        <!-- Info Grid -->
        <div class="row g-3 mb-4">
            <!-- Left Box: Customer Info -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Bill To</div>
                    <div style="font-size: 15px; font-weight: bold;">
                        {{ $sale->customer_relation->customer_name ?? 'Walking Customer' }}
                    </div>
                    <div>{{ $sale->customer_relation->address ?? '' }}</div>
                    <div class="mt-2 text-muted small">
                        Mob: {{ $sale->customer_relation->mobile ?? '' }}
                    </div>
                </div>
            </div>

            <!-- Middle Box: Sales Person / Meta -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Details</div>
                    <div><span class="info-label">Invoice Maker:</span> {{ auth()->user()->name ?? 'Admin' }}</div>
                    <div><span class="info-label">Sales Person:</span> {{ auth()->user()->name ?? 'Admin' }}</div>
                    <div><span class="info-label">Type:</span> {{ $sale->sale_status ?? 'Final' }}</div>
                </div>
            </div>

            <!-- Right Box: Invoice Specifics -->
            <div class="col-4">
                <div class="info-box">
                    <div class="info-box-header">Reference</div>
                    <div><span class="info-label">Invoice No:</span> #{{ $sale->id }}</div>
                    <div><span class="info-label">Date:</span> {{ $sale->created_at->format('d-m-Y') }}</div>
                    <div><span class="info-label">Ref:</span> {{ $sale->reference ?? '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Ship To / Remarks (Full Width if needed, or split) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="info-box" style="min-height: auto; padding: 8px 15px; background-color: #f1f5f9;">
                    <strong>Remarks/Note:</strong> {{ $sale->return_note ?? 'Thank you for your business.' }}
                </div>
            </div>
        </div>

        <!-- Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="text-start" style="width: 40%">Description</th>
                    <th class="text-center">Packing</th>
                    <th class="text-center">Quantity (Shipped)</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-center">Disc.</th>
                    <th class="text-end">Extended Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saleItems as $item)
                    <tr>
                        <td class="text-start">
                            <strong>{{ $item['item_name'] }}</strong>
                            @if (!empty($item['item_code']))
                                <br><small class="text-muted">Code: {{ $item['item_code'] }}</small>
                            @endif
                            @if (!empty($item['color']))
                                <br>
                                @foreach ($item['color'] as $clr)
                                    <span class="badge bg-light text-dark border">{{ $clr }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $item['pieces_per_box'] }} Pcs/Box
                            <div class="small text-muted">{{ $item['unit'] }}</div>
                        </td>
                        <td class="text-center">
                            <span style="font-size: 1.1em; font-weight: bold;">
                                {{ (float) $item['qty'] }} Box
                            </span>
                            @if ($item['loose_pieces'] > 0)
                                <br><small class="text-danger">+ {{ $item['loose_pieces'] }} Loose</small>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($item['price'], 2) }}</td>
                        <td class="text-center">
                            @if ($item['discount'] > 0)
                                {{ number_format($item['discount'], 1) }}%
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach

                <!-- Fill empty rows if needed for visual height (Optional, skipping for dynamic content) -->
            </tbody>
        </table>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-7">
                <div class="terms-box pt-3">
                    <p class="fw-bold mb-1">Terms & Conditions:</p>
                    <ul>
                        <li>10% will be deducted on return of purchase goods within 7 days.</li>
                        <li>Loose & Water Soak products will not be RETURNED.</li>
                        <li>Please bring this invoice for any returns or exchanges.</li>
                    </ul>
                </div>

                <div class="mt-5 pt-4">
                    <div class="signature-area">
                        Authorized Signature
                    </div>
                    <div class="small text-muted mt-1">
                        Printed on: {{ date('d-m-Y h:i A') }}
                    </div>
                </div>
            </div>

            <div class="col-5">
                <div class="info-box" style="border: none; padding: 0;">
                    <table class="totals-table">
                        <tr>
                            <td>Sub Total</td>
                            <td class="text-end">{{ number_format($sale->total_bill_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Extra Discount</td>
                            <td class="text-end text-danger">-{{ number_format($sale->total_extradiscount, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Net Total</td>
                            <td class="text-end">{{ number_format($sale->total_net, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Paid</td>
                            <td class="text-end">{{ number_format($sale->cash, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Change Return</td>
                            <td class="text-end">{{ number_format($sale->change, 2) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="text-end mt-2">
                    <small class="text-muted fst-italic">Amount in Words: {{ $sale->total_amount_Words }}</small>
                </div>
            </div>
        </div>

    </div>
</body>

</html>
