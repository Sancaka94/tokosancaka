<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Harga PPOB - {{ date('d M Y') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .category-row { background-color: #eef; font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>DAFTAR HARGA RESMI</h1>
        <p>{{ config('app.name') }} - Update: {{ date('d F Y') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Produk</th>
                <th>Brand</th>
                <th style="text-align:right">Harga</th>
            </tr>
        </thead>
        <tbody>
            @php $currentCat = ''; @endphp
            @foreach($products as $product)
                @if($currentCat != $product->category)
                    <tr class="category-row"><td colspan="4">{{ $product->category }}</td></tr>
                    @php $currentCat = $product->category; @endphp
                @endif
                <tr>
                    <td>{{ $product->buyer_sku_code }}</td>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->brand }}</td>
                    <td style="text-align:right">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>