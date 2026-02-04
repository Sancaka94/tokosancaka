<!DOCTYPE html>
<html>
<head><title>Laporan Tahunan</title><style>body{font-family:sans-serif}table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #000;text-align:right}th{background:#ccc;text-align:center}td:first-child{text-align:left}</style></head>
<body>
    <h2>Perbandingan Tahunan {{ $year }}</h2>
    <table>
        <thead>
            <tr><th>Bulan</th><th>Omzet</th><th>Beban</th><th>Laba Bersih</th></tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $row['bulan'] }}</td>
                <td>{{ number_format($row['omzet']) }}</td>
                <td>{{ number_format($row['beban']) }}</td>
                <td>{{ number_format($row['bersih']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
