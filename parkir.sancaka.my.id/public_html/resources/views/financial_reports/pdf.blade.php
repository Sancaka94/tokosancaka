<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; width: 50%; float: right; }
        .summary th, .summary td { border: none; padding: 5px; }
    </style>
</head>
<body>
    <h2 class="text-center">Laporan Buku Kas Manual</h2>
    <p class="text-center">Dicetak pada: {{ date('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th class="text-right">Pemasukan</th>
                <th class="text-right">Pengeluaran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                <td>{{ $row->kategori }}</td>
                <td>{{ $row->keterangan ?? '-' }}</td>
                <td class="text-right">{{ $row->jenis == 'pemasukan' ? 'Rp ' . number_format($row->nominal, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $row->jenis == 'pengeluaran' ? 'Rp ' . number_format($row->nominal, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <th>Total Pemasukan:</th>
            <td class="text-right">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Total Pengeluaran:</th>
            <td class="text-right">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Saldo Akhir:</th>
            <td class="text-right"><strong>Rp {{ number_format($saldo, 0, ',', '.') }}</strong></td>
        </tr>
    </table>
</body>
</html>
