<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Receipt #{{ $booking->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid #999;
            padding: 6px;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Booking Receipt</h2>
    <p>Booking ID: {{ $booking->id }}</p>
    <p>Date: {{ $booking->created_at->format('d-m-Y') }}</p>
</div>

<div class="section">
    <strong>Customer:</strong> {{ $booking->customer_relation->customer_name ?? 'N/A' }}<br>
    <strong>Reference:</strong> {{ $booking->reference }}
</div>

<div class="section">
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $products = explode(',', $booking->product);
                $qtys = explode(',', $booking->qty);
                $units = explode(',', $booking->unit);
                $prices = explode(',', $booking->per_price);
                $discounts = explode(',', $booking->per_discount);
                $totals = explode(',', $booking->per_total);
            @endphp

            @foreach($products as $i => $product)
            <tr>
                <td>{{ $product }}</td>
                <td>{{ $qtys[$i] ?? '' }}</td>
                <td>{{ $units[$i] ?? '' }}</td>
                <td>{{ $prices[$i] ?? '' }}</td>
                <td>{{ $discounts[$i] ?? '' }}</td>
                <td>{{ $totals[$i] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <strong>Total Amount:</strong> {{ $booking->total_net }} <br>
    <strong>In Words:</strong> {{ $booking->total_amount_Words }}
</div>

</body>
</html>
