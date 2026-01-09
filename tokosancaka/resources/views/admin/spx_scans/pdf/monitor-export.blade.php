<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Surat Jalan</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Monitoring Surat Jalan</h1>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Surat Jalan</th>
                <th>Nama Pengirim</th>
                <th class="text-center">Jumlah Paket</th>
                <th>Tanggal Dibuat</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suratJalans as $index => $sj)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $sj->kode_surat_jalan }}</td>
                <td>{{ $sj->user->nama_lengkap ?? ($sj->kontak->nama ?? 'N/A') }}</td>
                <td class="text-center">{{ $sj->jumlah_paket }}</td>
                <td>{{ \Carbon\Carbon::parse($sj->created_at)->translatedFormat('d M Y, H:i') }}</td>
                <td>{{ $sj->status ?? 'Tidak Diketahui' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada data yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Laporan ini dibuat secara otomatis oleh sistem SancakaExpress.
    </div>

</body>
</html>
