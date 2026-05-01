<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Umum {{ $bulan }}-{{ $tahun }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        h2 { text-align: center; margin-bottom: 2px; }
        .subtitle { text-align: center; color: #555; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-green { color: green; }
        .text-red { color: red; }
        .bg-light { background-color: #f9f9f9; }
        /* Hilangkan border untuk area tanda tangan */
        .no-border td { border: none; } 
    </style>
</head>
<body>

    <h2>RINGKASAN LAPORAN KEUANGAN - AZKEN PARKIR</h2>
    <div class="subtitle">
        Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}
    </div>

    <!-- Tabel Ringkasan (Tanpa Detail Transaksi/Plat Nomor) -->
    <table>
        <thead>
            <tr>
                <th>Deskripsi Keterangan</th>
                <th width="35%" class="text-right">Total Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Pendapatan Tiket Parkir & Toilet</td>
                <td class="text-right font-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pemasukan Kas Manual (Luar Sistem)</td>
                <td class="text-right font-bold text-green">+ Rp {{ number_format($totalPemasukanManual, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pengeluaran Kas Manual (Luar Sistem)</td>
                <td class="text-right font-bold text-red">- Rp {{ number_format($totalPengeluaranManual, 0, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="bg-light">
                <td class="text-right font-bold" style="font-size: 16px; padding-top: 15px; padding-bottom: 15px;">
                    TOTAL PENDAPATAN BERSIH (UANG SISA):
                </td>
                <td class="text-right font-bold" style="font-size: 16px; padding-top: 15px; padding-bottom: 15px;">
                    Rp {{ number_format($total + $totalPemasukanManual - $totalPengeluaranManual, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <br><br>
    
    <!-- Area Tanda Tangan -->
    <table class="no-border" style="width: 100%; border: none;">
        <tr style="border: none;">
            <td style="text-align: right; width: 100%; padding-right: 20px;">
                Mengetahui,<br><br><br><br><br>
                <strong>Pengelola Azken Parkir</strong>
            </td>
        </tr>
    </table>

</body>
</html>