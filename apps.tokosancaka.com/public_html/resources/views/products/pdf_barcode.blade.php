<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok & Barcode</title>
    <style>
        /* CSS KHUSUS DOMPDF */
        body {
            font-family: sans-serif;
            font-size: 10px; /* Font kecil agar muat banyak kolom */
        }

        /* HEADER LAPORAN */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            color: #111;
        }
        .header p {
            margin: 2px 0;
            color: #555;
            font-size: 11px;
        }
        .filter-info {
            font-weight: bold;
            color: #000;
        }

        /* TABEL DATA */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #444;
            padding: 6px;
            vertical-align: middle;
        }
        th {
            background-color: #e5e7eb; /* Abu-abu muda */
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            font-size: 9px;
        }

        /* STYLING BARIS KHUSUS */
        .parent-row {
            background-color: #f3f4f6; /* Abu-abu sangat muda untuk pembeda induk */
            font-weight: bold;
            color: #1f2937;
        }
        .variant-row td {
            background-color: #fff;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        /* Indentasi Nama Varian */
        .variant-name {
            padding-left: 20px !important;
            color: #4b5563;
            font-style: italic;
            position: relative;
        }
        .arrow-icon {
            font-family: sans-serif;
            margin-right: 5px;
            color: #9ca3af;
        }

        /* BARCODE BOX */
        .barcode-box {
            padding: 2px 0;
            text-align: center;
        }
        .barcode-img {
            height: 25px;
            max-width: 100%;
        }
        .barcode-text {
            font-size: 8px;
            letter-spacing: 1px;
            display: block;
            margin-top: 2px;
            font-family: monospace;
        }

        /* UTILITY */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 5px;
            border: 1px solid #ccc;
        }
        .badge-multi { background: #f3e8ff; color: #6b21a8; border-color: #d8b4fe; }
        .no-border-top { border-top: none !important; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>Laporan Stok & Barcode Produk</h1>
        <p>
            Filter: <span class="filter-info">{{ $categoryName ?? 'Semua Kategori' }}</span> |
            <span class="filter-info">{{ $typeName ?? 'Semua Jenis' }}</span>
        </p>
        <p>Dicetak pada: {{ date('d F Y, H:i') }}</p>
    </div>

    {{-- TABEL --}}
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Nama Produk / Varian</th>
                <th width="15%">SKU</th>
                <th width="20%">Barcode</th>
                <th width="15%">Harga Jual</th>
                <th width="10%">Stok</th>
                <th width="5%">Unit</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($products as $product)

                {{-- KONDISI 1: PRODUK MEMILIKI VARIAN (PARENT) --}}
                @if($product->has_variant)

                    {{-- 1.A. Baris INDUK (Header Group) --}}
                    <tr class="parent-row">
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            {{ $product->name }}
                            <span class="badge badge-multi">Multi Varian</span>
                        </td>
                        <td>{{ $product->sku ?? '-' }}</td>
                        <td class="text-center">-</td> {{-- Induk tidak punya barcode fisik --}}
                        <td class="text-right">-</td>  {{-- Harga ada di varian --}}
                        <td class="text-center">{{ $product->stock }} (Total)</td>
                        <td class="text-center">{{ ucfirst($product->unit) }}</td>
                    </tr>

                    {{-- 1.B. Looping ANAK VARIAN --}}
                    @foreach($product->variants as $variant)
                    <tr class="variant-row">
                        <td style="border-top: none; border-bottom: none;"></td> {{-- Kosongkan No --}}
                        <td class="variant-name">
                            <span class="arrow-icon">↳</span> {{ $variant->name }}
                        </td>
                        <td>{{ $variant->sku ?? '-' }}</td>
                        <td class="text-center">
                            {{-- LOGIKA BARCODE VARIAN --}}
                            @php $code = $variant->barcode ?? $variant->sku; @endphp

                            @if(!empty($code))
                                <div class="barcode-box">
                                    {{-- Parameter: Code, Type(C128), Width(1.5), Height(25) --}}
                                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($code, 'C128', 1.4, 25) }}" class="barcode-img" alt="bc">
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
                            {{-- LOGIKA BARCODE SINGLE --}}
                            @php $code = $product->barcode ?? $product->sku; @endphp

                            @if(!empty($code))
                                <div class="barcode-box">
                                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($code, 'C128', 1.4, 25) }}" class="barcode-img" alt="bc">
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
