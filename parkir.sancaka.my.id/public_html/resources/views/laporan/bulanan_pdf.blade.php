<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan {{ $bulan }}-{{ $tahun }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 2px; }
        .subtitle { text-align: center; color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-green { color: green; }
        .text-red { color: red; }
    </style>
</head>
<body>

    <h2>LAPORAN KEUANGAN BULANAN - AZKEN PARKIR</h2>
    <div class="subtitle">
        Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}
    </div>

    <!-- Tabel Parkir -->
    <h3>Rekap Pendapatan Parkir Bulanan</h3>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Plat Nomor / TRX</th>
                <th>Jenis</th>
                <th>Tgl Keluar</th>
                <th>Operator</th>
                <th class="text-right">Tarif + Toilet</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <b>{{ $trx->plate_number }}</b><br>
                    <small>TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</small>
                </td>
                <td>{{ ucfirst($trx->vehicle_type) }}</td>
                <td>{{ $trx->exit_time ? \Carbon\Carbon::parse($trx->exit_time)->translatedFormat('d M Y H:i') : '-' }}</td>
                <td>{{ optional($trx->operator)->name ?? 'Sistem' }}</td>
                <td class="text-right">Rp {{ number_format($trx->fee + ($trx->toilet_fee ?? 0), 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada transaksi keluar pada bulan ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold">TOTAL PENDAPATAN PARKIR :</td>
                <td class="text-right font-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Tabel Kas Manual -->
    <h3>Rekap Kas Manual (Luar Sistem)</h3>
    <table>
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th>Kategori Utama</th>
                <th>Keterangan</th>
                <th class="text-right" width="20%">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kasManual as $kas)
            <tr>
                <td class="text-center">{{ \Carbon\Carbon::parse($kas->tanggal)->format('d/m/Y') }}</td>
                <td>{{ $kas->kategori }}</td>
                <td>{{ $kas->keterangan ?? '-' }}</td>
                <td class="text-right font-bold">
                    @if($kas->jenis == 'pemasukan')
                        <span class="text-green">+ Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                    @else
                        <span class="text-red">- Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada catatan kas manual pada bulan ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold">Total Pemasukan Manual :</td>
                <td class="text-right font-bold text-green">+ Rp {{ number_format($totalPemasukanManual, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right font-bold">Total Pengeluaran Manual :</td>
                <td class="text-right font-bold text-red">- Rp {{ number_format($totalPengeluaranManual, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right font-bold" style="font-size: 14px; padding-top: 10px;">TOTAL PENDAPATAN BERSIH (UANG SISA):</td>
                <td class="text-right font-bold" style="font-size: 14px; padding-top: 10px;">
                    Rp {{ number_format($total + $totalPemasukanManual - $totalPengeluaranManual, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

</body>
</html>