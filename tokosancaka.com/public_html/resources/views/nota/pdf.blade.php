<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Nota</title>
    <style>
        /* CSS Khusus agar rapi di DomPDF */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #555;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #777;
            text-align: right;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>SANCAKA KARYA HUTAMA</h2>
        <p>Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)<br>Telp: 0881-9435-180</p>
    </div>

    <div class="title">
        Laporan Riwayat Transaksi Nota
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="25%">No. Nota</th>
                <th width="25%">Kepada / Pembeli</th>
                <th width="10%">Total Item</th>
                <th width="20%">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotalSemua = 0; @endphp
            
            @forelse($notas as $index => $nota)
                @php $grandTotalSemua += $nota->total_harga; @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}</td>
                    <td class="font-bold">{{ $nota->no_nota }}</td>
                    <td>{{ $nota->kepada }}</td>
                    <td class="text-center">{{ $nota->items->count() }} Brg</td>
                    <td class="text-right font-bold text-success">
                        Rp {{ number_format($nota->total_harga, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data nota untuk diekspor.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($notas) > 0)
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL KESELURUHAN PENDAPATAN :</th>
                <th class="text-right">Rp {{ number_format($grandTotalSemua, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak pada: {{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>