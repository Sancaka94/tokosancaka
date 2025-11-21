<!DOCTYPE html>
<html>
<head>
    <title>Data SPX Scan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Laporan Data SPX Scan</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Resi</th>
                <th>Pengirim</th>
                <th>Status</th>
                <th>Tanggal Scan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scans as $scan)
            <tr>
                <td>{{ $scan->id }}</td>
                <td>{{ $scan->resi }}</td>
                <td>{{ $scan->kontak->nama ?? 'N/A' }}</td>
                <td>{{ $scan->status }}</td>
                <td>{{ $scan->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
