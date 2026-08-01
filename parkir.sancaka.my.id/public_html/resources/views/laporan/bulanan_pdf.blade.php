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
            @php
                // 1. Total Pendapatan = Total Omzet (Parkiran + Manual)
                $omzetTotal = $total + $totalPemasukanManual;

                // 2. Total Profit = Omzet : 2
                $totalProfit = $omzetTotal / 2;

                // 3. Total Gaji Pegawai = Omzet : 2
                $totalGajiPegawai = $omzetTotal / 2;

                // 4. Total Pengeluaran = Semua total pengeluaran (dari variabel controller)
                $totalPengeluaranSemua = $totalPengeluaranManual;

                // 5. Hitung Pengeluaran Lainnya (KECUALI Gaji & Setoran)
                $pengeluaranLainnya = 0;
                if(isset($kasManual)) {
                    foreach($kasManual as $item) {
                        if($item->jenis == 'pengeluaran') {
                            if(
                                $item->kategori !== 'Gaji Pegawai' &&
                                $item->kategori !== 'Setoran' &&
                                $item->kategori !== 'Setoran Parkir'
                            ) {
                                $pengeluaranLainnya += $item->nominal;
                            }
                        }
                    }
                }

                // 6. Total Final Profit = Profit - Pengeluaran Lainnya
                $finalProfit = $totalProfit - $pengeluaranLainnya;
            @endphp

            <!-- 1. Baris Total Pendapatan (Omzet) -->
            <tr style="background-color: #f8f9fa;">
                <td colspan="3" class="text-right font-bold" style="padding-top: 10px;">TOTAL PENDAPATAN (OMZET):</td>
                <td class="text-right font-bold text-green" style="padding-top: 10px;">
                    Rp {{ number_format($omzetTotal, 0, ',', '.') }}
                </td>
            </tr>

            <!-- 2. Baris Total Profit -->
            <tr>
                <td colspan="3" class="text-right font-bold">TOTAL PROFIT (OMZET : 2):</td>
                <td class="text-right font-bold">
                    Rp {{ number_format($totalProfit, 0, ',', '.') }}
                </td>
            </tr>

            <!-- 3. Baris Total Gaji Pegawai -->
            <tr>
                <td colspan="3" class="text-right font-bold">TOTAL GAJI PEGAWAI (OMZET : 2):</td>
                <td class="text-right font-bold">
                    Rp {{ number_format($totalGajiPegawai, 0, ',', '.') }}
                </td>
            </tr>

            <!-- 4. Baris Total Pengeluaran Keseluruhan -->
            <tr>
                <td colspan="3" class="text-right font-bold">TOTAL PENGELUARAN KESELURUHAN:</td>
                <td class="text-right font-bold text-red">
                    - Rp {{ number_format($totalPengeluaranSemua, 0, ',', '.') }}
                </td>
            </tr>

            <!-- 5. Baris Uang Sisa (Omzet - Semua Pengeluaran) -->
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-top: 10px; border-top: 2px solid #333;">
                    TOTAL PENDAPATAN BERSIH (UANG SISA):
                </td>
                <td class="text-right font-bold" style="padding-top: 10px; border-top: 2px solid #333;">
                    Rp {{ number_format($omzetTotal - $totalPengeluaranSemua, 0, ',', '.') }}
                </td>
            </tr>

            <!-- 6. Baris Final Profit -->
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-top: 15px; color: #0000ff;">
                    FINAL PROFIT <br>
                    <span style="font-size: 10px; font-weight: normal; color: #555;">
                        (Profit - Pengeluaran selain Gaji & Setoran)
                    </span>
                </td>
                <td class="text-right font-bold" style="padding-top: 15px; color: #0000ff; font-size: 14px;">
                    Rp {{ number_format($finalProfit, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
