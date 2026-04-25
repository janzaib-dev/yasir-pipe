<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Payment Voucher - AMT</title>

    <!-- Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --green: #0b5a2b;
            --purple: #6b0f8a;
            --border: #1f7a2f;
            --muted: #666;
            --box-bg: #fff;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f6f6f6;
            margin: 0;
        }

        .page {
            width: 960px;
            margin: 18px auto;
            padding: 28px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        #watermark {
            position: absolute;
            left: 50%;
            top: 48%;
            transform: translate(-50%, -50%) rotate(-18deg);
            width: 720px;
            opacity: 0.08;
        }

        header {
            display: flex;
            justify-content: space-between;
        }

        .brand h1 {
            margin: 0;
            font-size: 40px;
            font-weight: 700;
        }

        .receipt-badge {
            border: 2px solid #222;
            padding: 8px 12px;
            font-weight: 700;
        }

        hr.sep {
            border-top: 2px solid #000;
            margin: 14px 0 18px;
        }

        .meta-row {
            display: flex;
            gap: 18px;
        }

        .left {
            flex: 1;
            border: 2px solid #000;
            padding: 12px 14px;
        }

        .left .line {
            display: flex;
            margin-bottom: 6px;
        }

        .left .label {
            min-width: 110px;
            font-weight: 700;
        }

        .right {
            width: 260px;
            border: 2px solid var(--border);
            padding: 10px;
        }

        .right .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .payments {
            margin-top: 18px;
        }

        .payments h3 {
            margin: 0 0 8px;
            text-decoration: underline;
        }

        .amount-words {
            margin-top: 6px;
            font-style: italic;
            font-weight: 600;
        }

        .summary {
            margin-top: 14px;
            border: 3px solid #1f7a2f;
            padding: 12px;
        }

        .summary td {
            padding: 6px 4px;
            font-weight: 600;
        }

        .summary td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .footer {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .thank {
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="page">
        <img id="watermark" src="{{ asset('amt-watermark.png') }}" alt="AMT watermark">

        <header>
            <div class="brand">
                <h1>Al–Madina Traders</h1>
                <p>Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad</p>
                <p>Mobile / Whatsapp: 0312-0252899; Tel: 022-2780942</p>
            </div>
            <div class="logo" style="text-align:right;">
                <img src="{{ asset('amt-logo.png') }}" alt="AMT Logo" style="max-width:200px;" onerror="this.style.display='none'">
                <div style="margin-top:8px;">
                    <span class="receipt-badge">PAYMENT VOUCHER</span>
                </div>
            </div>
        </header>

        <hr class="sep">

        <div class="meta-row">
            <div class="left">
                @if(is_numeric($voucher->type))
                {{-- 🏦 Paid From Account --}}
                <div class="line">
                    <div class="label">Paid From (Account):</div>
                    <div class="value">
                        {{ $party->name ?? '-' }}
                    </div>
                </div>
                <div class="line">
                    <div class="label">Account Head:</div>
                    <div class="value">
                        {{ $party->head_name ?? '-' }}
                    </div>
                </div>
                <div class="line">
                    <div class="label">Account Code:</div>
                    <div class="value">
                        {{ $party->phone ?? '-' }}
                    </div>
                </div>

                @elseif($voucher->type === 'vendor')
                {{-- 👷 Vendor --}}
                <div class="line">
                    <div class="label">Vendor Name:</div>
                    <div class="value">{{ $party->name ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Address:</div>
                    <div class="value">{{ $party->address ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Phone:</div>
                    <div class="value">{{ $party->phone ?? '-' }}</div>
                </div>

                @elseif($voucher->type === 'customer')
                {{-- 🧾 Customer --}}
                <div class="line">
                    <div class="label">Customer Name:</div>
                    <div class="value">{{ $party->customer_name ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Address:</div>
                    <div class="value">{{ $party->address ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Phone:</div>
                    <div class="value">{{ $party->mobile ?? '-' }}</div>
                </div>

                @elseif($voucher->type === 'walkin')
                {{-- 🚶 Walk-in Customer --}}
                <div class="line">
                    <div class="label">Walk-in Customer:</div>
                    <div class="value">{{ $party->customer_name ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Address:</div>
                    <div class="value">{{ $party->address ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Phone:</div>
                    <div class="value">{{ $party->mobile ?? '-' }}</div>
                </div>

                @else
                {{-- ❌ No data fallback --}}
                <div class="line">
                    <div class="label">Party:</div>
                    <div class="value">-</div>
                </div>
                <div class="line">
                    <div class="label">Address:</div>
                    <div class="value">-</div>
                </div>
                <div class="line">
                    <div class="label">Phone:</div>
                    <div class="value">-</div>
                </div>
                @endif
            </div>

            <div class="right">
                <div style="margin-bottom:8px; font-weight:700;">
                    Voucher No: <span style="float:right;">{{ $voucher->pvid }}</span>
                </div>
                <div class="meta-item">
                    <span>Voucher Date:</span>
                    <span>{{ \Carbon\Carbon::parse($voucher->receipt_date)->format('d-M-Y') }}</span>
                </div>
            </div>
        </div>

        <div class="payments">
            <h3>Payment(s) Detail.</h3>
            @foreach($rows as $key => $row)
            <p>
                <strong>{{ $key + 1 }} . Amount of Rs. {{ number_format($row['amount'], 2) }}</strong>
                &nbsp;&nbsp; Received with thanks, Dated:
                <strong>{{ \Carbon\Carbon::parse($voucher->receipt_date)->format('l, d F, Y') }}</strong>
                against supply of {{ $row['narration'] ?? 'N/A' }} towards account:
                <strong>{{ $row['account_head'] ?? '-' }}, {{ $row['account_name'] ?? '' }} Code: {{ $row['account_code'] ?? '' }}</strong>
            </p>
            @endforeach

            <div class="amount-words">
                Amount in words: <strong id="amountInWords">{{ $voucher->total_amount }}</strong> Only
            </div>
            <!-- <ul class="mini-list">
                <li>Amount in figures: <strong>Rs. {{ number_format($voucher->total_amount,2) }}</strong></li>
            </ul> -->
        </div>

        <!-- summary -->
        <div class="summary">
            @php
            $amountPayable = $previousBalance - $voucher->total_amount;
            @endphp

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <td>Previous Balance.</td>
                    <td style="color:#6b0f8a;">>>> {{ number_format($previousBalance,2) }}</td>
                </tr>
                <tr>
                    <td>Total Payment Received. (-)</td>
                    <td style="color:#6b0f8a;">>>> {{ number_format($voucher->total_amount,2) }}</td>
                </tr>
                <tr>
                    <td>Amount Payable.</td>
                    <td style="color:#0b5a2b;">>>> {{ number_format($amountPayable,2) }}</td>
                </tr>
            </table>

        </div>



        <div class="footer">
            <div>
                Print Time & Date:
                {{ now()->format('H:i:s') }} | {{ now()->format('l, d F, Y') }}
            </div>
            <div class="thank">>>> Thank You for Payment.</div>
        </div>
    </div>

    <script>
        function numberToWords(num) {
            const a = [
                '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                'Seventeen', 'Eighteen', 'Nineteen'
            ];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            if ((num = num.toString()).length > 9) return 'Overflow';
            let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
            if (!n) return;
            let str = '';
            str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + ' Crore ' : '';
            str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + ' Lakh ' : '';
            str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + ' Thousand ' : '';
            str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + ' Hundred ' : '';
            str += (n[5] != 0) ? ((str != '') ? 'and ' : '') +
                (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) + ' ' : '';
            return str.trim();
        }

        // page load pe convert karo
        document.addEventListener("DOMContentLoaded", function() {
            let amount = parseInt(document.getElementById("amountInWords").innerText);
            let words = numberToWords(amount);
            document.getElementById("amountInWords").innerText = words;
        });
    </script>


</body>

</html>