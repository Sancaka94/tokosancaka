<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok & Barcode</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #555; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #444; padding: 5px; vertical-align: middle; }
        th { background-color: #eee; font-weight: bold; text-transform: uppercase; text-align: center; }

        /* Styling Khusus */
        .parent-row { background-color: #f9fafb; font-weight: bold; color: #333; }
        .variant-row td { background-color: #fff; }
        .variant-name { padding-left: 20px; color: #555; font-style: italic; }

        .barcode-box { padding: 3px 0; text-align: center; }
        .barcode-text { font-size: 8px; letter-spacing: 1px; display: block; margin-top: 2px; }

        /* Utility */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            padding: 2px 4px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase;
        }
        .badge-single { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-multi { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Stok & Barcode Produk</h1>
        <p>Dicetak pada: {{ date('d F Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Nama Produk / Varian</th>
                <th width="15%">SKU</th>
                <th width="20%">Barcode</th>
                <th width="15%">Harga Jual</th>
                <th width="10%">Stok</th>
                <th width="5%">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($products as $product)

                {{-- KONDISI 1: PRODUK MEMILIKI VARIAN --}}
                @if($product->has_variant)

                    {{-- 1.A. Baris INDUK (Header) --}}
                    <tr class="parent-row">
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            {{ $product->name }}
                            <span class="badge badge-multi" style="margin-left:5px;">Multi Varian</span>
                        </td>
                        <td>{{ $product->sku ?? '-' }}</td>
                        <td class="text-center">-</td> {{-- Induk biasanya tidak punya barcode jual --}}
                        <td class="text-right">-</td> {{-- Harga bervariasi --}}
                        <td class="text-center"><strong>{{ $product->stock }}</strong> (Total)</td>
                        <td class="text-center">{{ ucfirst($product->unit) }}</td>
                    </tr>

                    {{-- 1.B. Looping ANAK VARIAN --}}
                    @foreach($product->variants as $variant)
                    <tr class="variant-row">
                        <td></td> {{-- Kosongkan No --}}
                        <td class="variant-name">
                            ↳ {{ $variant->name }}
                        </td>
                        <td>{{ $variant->sku ?? '-' }}</td>
                        <td class="text-center">
                            @php $code = $variant->barcode ?? $variant->sku; @endphp
                            @if(!empty($code))
                                <div class="barcode-box">
                                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($code, 'C128', 1.2, 25) }}" alt="bc">
                                    <span class="barcode-text">{{ $code }}</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">Rp {{ number_format($variant->price, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $variant->stock }}</td>
                        <td class="text-center">{{ ucfirst($product->unit) }}</td>
                    </tr>
                    @endforeach

                {{-- KONDISI 2: PRODUK TUNGGAL (SINGLE) --}}
                @else
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            {{ $product->name }}
                        </td>
                        <td>{{ $product->sku ?? '-' }}</td>
                        <td class="text-center">
                            @php $code = $product->barcode ?? $product->sku; @endphp
                            @if(!empty($code))
                                <div class="barcode-box">
                                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($code, 'C128', 1.2, 25) }}" alt="bc">
                                    <span class="barcode-text">{{ $code }}</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $product->stock }}</td>
                        <td class="text-center">{{ ucfirst($product->unit) }}</td>
                    </tr>
                @endif

            @endforeach
        </tbody>
    </table>

</body>
</html>
