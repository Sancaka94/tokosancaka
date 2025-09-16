<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Scan Paket</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; }
        th { background-color: #f2f2f2; }
        h1 { text-align: center; }
    </style>
</head>
<body>
    <h1>Riwayat Scan Paket</h1>
    <p>Dicetak pada: {{ now()->format('d M Y, H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Nomor Resi</th>
                <th>Status</th>
                <th>Tanggal Scan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scans as $scan)
                <tr>
                    <td>{{ $scan->resi_number }}</td>
                    <td>{{ $scan->status }}</td>
                    <td>{{ $scan->created_at->format('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Tidak ada data untuk diekspor.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
