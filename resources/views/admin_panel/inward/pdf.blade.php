<!DOCTYPE html>
<html>
<head>
    <title>Gatepass #{{ $gatepass->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table, .table th, .table td { border: 1px solid black; }
        .table th, .table td { padding: 6px; text-align: center; }
        h2, h4 { margin: 0; padding: 4px; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Inward Gatepass</h2>
    <h4 style="text-align:center;">#{{ $gatepass->id }}</h4>

    <table class="table">
        <tr>
            <th>Branch</th>
            <td>{{ $gatepass->branch->name ?? 'N/A' }}</td>
            <th>Warehouse</th>
            <td>{{ $gatepass->warehouse->warehouse_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Vendor</th>
            <td>{{ $gatepass->vendor->name ?? 'N/A' }}</td>
            <th>Date</th>
            <td>{{ $gatepass->gatepass_date }}</td>
        </tr>
        <tr>
            <th>Note</th>
            <td colspan="3">{{ $gatepass->note ?? '-' }}</td>
        </tr>
    </table>

    <h4 style="margin-top:20px;">Items</h4>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gatepass->items as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $item->product->item_name ?? 'N/A' }}</td>
                    <td>{{ $item->qty }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br><br>
    <table style="width:100%; margin-top:30px;">
        <tr>
            <td style="text-align:left;">Received By: ____________________</td>
            <td style="text-align:right;">Checked By: ____________________</td>
        </tr>
    </table>
</body>
</html>
