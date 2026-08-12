<!-- LOG LOG -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>RAB Proyek</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #999;
            padding: 7px;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f9f9f9; }
    </style>
</head>
<body>

    <div class="header">
        <h1>CV SANCAKA KARYA HUTAMA</h1>
        <p>REKAPITULASI RENCANA ANGGARAN BIAYA (RAB) PROYEK</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="40%">URAIAN PEKERJAAN</th>
                <th width="10%">VOL</th>
                <th width="10%">SAT</th>
                <th width="15%">HARGA SATUAN</th>
                <th width="20%">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $currentCategory = null; $no = 1; @endphp
            
            @foreach($items as $item)
                @if($item->kategori !== $currentCategory)
                    <tr class="bg-gray">
                        <td></td>
                        <td colspan="5" class="font-bold">{{ strtoupper($item->kategori ?: 'UMUM') }}</td>
                    </tr>
                    @php $currentCategory = $item->kategori; @endphp
                @endif
                
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-left">{{ $item->uraian_pekerjaan }}</td>
                    <td class="text-right">{{ number_format($item->volume, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->satuan }}</td>
                    <td class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL KESELURUHAN</th>
                <th class="text-right">Rp {{ number_format($totalKeseluruhan, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

</body>
</html>