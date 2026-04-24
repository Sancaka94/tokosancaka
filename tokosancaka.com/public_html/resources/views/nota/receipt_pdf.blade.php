<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota - {{ $nota->no_nota }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.4; }
        .container { width: 100%; margin: 0 auto; }
        
        /* Header / Kop */
        .header { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header td { vertical-align: middle; }
        .logo { max-height: 60px; }
        .company-title { font-size: 18px; font-weight: bold; margin: 0; }
        .company-sub { font-size: 11px; margin: 2px 0 0 0; color: #555; }
        .nota-title { font-size: 24px; font-weight: bold; text-align: right; text-transform: uppercase; }

        /* Info Nota */
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { vertical-align: top; }

        /* Tabel Barang */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 6px; }
        .items-table th { background-color: #f4f4f4; text-align: center; }
        
        /* Utilitas */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        /* Tanda Tangan */
        .ttd-table { width: 100%; margin-top: 30px; text-align: center; page-break-inside: avoid; }
        .ttd-img { max-height: 80px; margin: 10px 0; }
        .ttd-name { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        
        <table class="header">
            <tr>
                <td width="15%">
                    <img src="https://tokosancaka.com/storage/uploads/sancaka.png" class="logo" alt="Logo">
                </td>
                <td width="55%">
                    <h1 class="company-title">SANCAKA KARYA HUTAMA</h1>
                    <p class="company-sub">Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)<br>Telp: 0881-9435-180</p>
                </td>
                <td width="30%">
                    <div class="nota-title">NOTA</div>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td width="50%">
                    <strong>NOTA NO.</strong><br>
                    {{ $nota->no_nota }}
                </td>
                <td width="50%">
                    <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}<br>
                    <strong>Kepada:</strong> {{ $nota->kepada }}
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="10%">QTY</th>
                    <th width="40%">NAMA BARANG</th>
                    <th width="25%">HARGA</th>
                    <th width="25%">JUMLAH</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nota->items as $item)
                <tr>
                    <td class="text-center">{{ $item->banyaknya }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right fw-bold">Jumlah Rp.</td>
                    <td class="text-right fw-bold">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="ttd-table">
            <tr>
                <td width="50%">
                    <p>Tanda Terima,</p>
                    
                    @if($nota->ttd_pembeli)
                        <img src="{{ public_path('storage/' . $nota->ttd_pembeli) }}" class="ttd-img">
                    @else
                        <br><br><br><br>
                    @endif
                    
                    <div class="ttd-name">{{ $nota->nama_pembeli ?? '.........................' }}</div>
                </td>
                <td width="50%">
                    <p>Hormat Kami,</p>

                    @if($nota->ttd_penjual)
                        <img src="{{ public_path('storage/' . $nota->ttd_penjual) }}" class="ttd-img">
                    @else
                        <br><br><br><br>
                    @endif
                    
                    <div class="ttd-name">{{ $nota->nama_penjual ?? 'Sancaka Express' }}</div>
                </td>
            </tr>
        </table>

    </div>
</body>
</html>