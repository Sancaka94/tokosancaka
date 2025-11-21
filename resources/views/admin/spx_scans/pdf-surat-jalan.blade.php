<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .info { margin-top: 20px; }
        .info table { width: 50%; border: none; }
        .info td { border: none; padding: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Surat Jalan Pickup Paket</h1>
        <p>Sancaka Express</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>Kode Surat Jalan:</strong></td>
                <td>{{ $suratJalan->kode_surat_jalan }}</td>
            </tr>
            <tr>
                <td><strong>Nama Pelanggan:</strong></td>
                <td>{{ $suratJalan->user->nama_lengkap ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal:</strong></td>
                <td>{{ $suratJalan->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Total Paket:</strong></td>
                <td>{{ $suratJalan->jumlah_paket }} Koli</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th>Nomor Resi</th>
                <th>Waktu Scan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suratJalan->packages as $index => $package)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $package->resi_number }}</td>
                    <td>{{ $package->created_at->format('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Tidak ada data resi untuk surat jalan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
