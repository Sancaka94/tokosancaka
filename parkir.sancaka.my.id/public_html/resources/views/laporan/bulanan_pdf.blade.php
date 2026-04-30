<!DOCTYPE html>
<html>
<head>
    <title>Laporan PDF</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background: #eee; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2 style="text-align:center">LAPORAN KEUANGAN BULANAN</h2>
    <p style="text-align:center">Periode: {{ $bulan }} / {{ $tahun }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Plat / TRX</th>
                <th>Jenis</th>
                <th>Tgl Keluar</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $trx)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $trx->plate_number }}</td>
                <td>{{ $trx->vehicle_type }}</td>
                <td>{{ $trx->exit_time }}</td>
                <td class="text-right">Rp {{ number_format($trx->fee + ($trx->toilet_fee ?? 0), 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>