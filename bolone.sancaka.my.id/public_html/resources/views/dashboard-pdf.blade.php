<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Kota</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .mb-10 { margin-bottom: 20px; }
    </style>
</head>
<body>

    <h2 class="text-center">Laporan Analitik Data Kota</h2>
    <hr>
    
    <div class="mb-10">
        <p><strong>Total Seluruh Data:</strong> {{ $totalData }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%" class="text-center">No</th>
                <th>Nama Kota / Wilayah</th>
                <th width="25%" class="text-center">Jumlah Data</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chartData as $index => $data)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $data->nama_kota }}</td>
                <td class="text-center">{{ $data->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>