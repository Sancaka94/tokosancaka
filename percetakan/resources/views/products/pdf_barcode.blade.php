<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Produk & Barcode</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px; /* Ukuran font standar agar muat banyak */
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }

        /* Lebar Kolom Biar Rapi */
        .col-no { width: 5%; text-align: center; }
        .col-sku { width: 12%; }
        .col-nama { width: 25%; }
        .col-barcode { width: 20%; text-align: center; }
        .col-harga { width: 13%; text-align: right; }
        .col-satuan { width: 10%; text-align: center; }
        .col-kategori { width: 15%; text-align: center; }

        .barcode-container {
            padding: 5px 0;
        }

        /* Agar tulisan barcode di bawah gambar tidak terlalu besar */
        .barcode-text {
            font-size: 9px;
            letter-spacing: 1px;
            margin-top: 2px;
            display: block;
        }

        /* Utility */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Data Produk</h1>
        <p>Tanggal Cetak: {{ date('d F Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-sku">SKU</th>
                <th class="col-nama">Nama Produk</th>
                <th class="col-barcode">Barcode</th>
                <th class="col-harga">Harga Jual</th>
                <th class="col-satuan">Satuan</th>
                <th class="col-kategori">Kategori</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $product->sku }}</td>
                <td>
                    <strong>{{ $product->name }}</strong>
                    @if($product->has_variant)
                        <br><span style="font-size: 9px; color: #666; font-style: italic;">(Multi Varian)</span>
                    @endif
                </td>

                <td class="text-center">
                    {{-- LOGIKA GENERATE BARCODE IMAGE (Server Side) --}}
                    @php
                        // Gunakan Barcode jika ada, jika tidak gunakan SKU
                        $code = $product->barcode ?? $product->sku;
                    @endphp

                    @if(!empty($code))
                        <div class="barcode-container">
                            {{-- Generate Gambar Barcode Base64 menggunakan Milon/Barcode --}}
                            {{-- Parameter: Kode, Jenis (C128), Lebar bar, Tinggi bar --}}
                            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($code, 'C128', 1.4, 35) }}" alt="barcode" style="max-width: 100%;">
                            <span class="barcode-text">{{ $code }}</span>
                        </div>
                    @else
                        -
                    @endif
                </td>

                <td class="text-right">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                <td class="text-center">{{ ucfirst($product->unit) }}</td>
                <td class="text-center">{{ $product->category->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
