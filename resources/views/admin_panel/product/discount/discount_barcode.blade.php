<!DOCTYPE html>
<html>
<head>
    <title>Product Barcode</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f9f9f9;
        }
        .label {
            border: 1px solid #000;
            padding: 8px;
            width: 240px;
            text-align: center;
            background: #fff;
        }
        .brand-name {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .barcode {
            margin: 4px 0;
        }
        .product-info {
            font-size: 13px;
            font-weight: bold;
            margin-top: 4px;
        }
        .price {
            font-size: 12px;
            margin-top: 4px;
        }
        .price s {
            color: #a00;
        }
        .discount-price {
            font-size: 13px;
            font-weight: bold;
            color: green;
        }
        @media print {
            body {
                height: auto;
                background: #fff;
            }
        }
    </style>
</head>
<body>

<div class="label">
    <div class="brand-name">WIJDAN</div>

    <div class="barcode" style="display: flex; justify-content: center;">
       {!! DNS1D::getBarcodeHTML($discount->product->item_code, 'C128', 1.2, 23) !!}
    </div>

    <div class="product-info">{{ $discount->product->item_name }}</div>

    <div class="price">PRICE: <s>{{ $discount->product->price }}</s></div>
    <div class="price discount-price">Discount Price: {{ $discount->final_price }}</div>
</div>

</body>
</html>
