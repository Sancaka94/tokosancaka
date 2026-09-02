<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Riwayat Nota</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header td { vertical-align: middle; }
        .logo { max-height: 60px; }
        .header h2 { margin: 0; font-size: 20px; font-weight: bold; }
        .header p { margin: 5px 0 0 0; font-size: 12px; color: #555; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #999; padding: 8px; text-align: left; }
        table.data-table th { background-color: #f4f4f4; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; } /* Tambahan class warna untuk status UNPAID */
        .footer { margin-top: 30px; font-size: 10px; color: #777; text-align: right; font-style: italic; }
    </style>
</head>
<body>

    @php
        // Konversi Image ke Base64 agar DomPDF tidak error saat render gambar
        $logoPath = public_path('storage/uploads/sancaka.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    @endphp

    <table class="header">
        <tr>
            <td width="20%" class="text-center">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                @endif
            </td>
            <td width="80%" class="text-center" style="padding-right: 20%;">
                <h2>SANCAKA KARYA HUTAMA</h2>
                <p>Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)<br>Telp: 0881-9435-180</p>
            </td>
        </tr>
    </table>

    <div class="title">Laporan Riwayat Transaksi Nota</div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="20%">No. Nota</th>
                <th width="23%">Kepada / Pembeli</th>
                <th width="10%">Total Item</th>
                <th width="12%">Status</th> <!-- Tambahan Kolom Status -->
                <th width="18%">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotalSemua = 0;
                $totalLunas = 0; // Tambahan variabel untuk menghitung khusus yang sudah bayar
            @endphp

            @forelse($notas as $index => $nota)
                @php
                    $grandTotalSemua += $nota->total_harga;

                    // Cek status dari backend
                    $isPaid = strtoupper($nota->status ?? 'UNPAID') === 'PAID';

                    if($isPaid) {
                        $totalLunas += $nota->total_harga;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}</td>
                    <td class="font-bold">{{ $nota->no_nota }}</td>
                    <td>
                        {{ $nota->kepada }}<br>
                        <small style="color:#777; font-weight: normal;">{{ $nota->nama_pembeli }}</small>
                    </td>
                    <td class="text-center">{{ $nota->items->count() }} Brg</td>
                    <td class="text-center font-bold">
                        @if($isPaid)
                            <span class="text-success">PAID</span>
                        @else
                            <span class="text-danger">UNPAID</span>
                        @endif
                    </td>
                    <td class="text-right font-bold {{ $isPaid ? 'text-success' : '' }}">
                        Rp {{ number_format($nota->total_harga, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <!-- Colspan diubah dari 6 menjadi 7 menyesuaikan kolom yang ada -->
                    <td colspan="7" class="text-center">Tidak ada data nota untuk diekspor.</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($notas) > 0)
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">TOTAL PENDAPATAN (LUNAS) :</th>
                <th class="text-right text-success">Rp {{ number_format($totalLunas, 0, ',', '.') }}</th>
            </tr>
            <tr>
                <th colspan="6" class="text-right">TOTAL KESELURUHAN (TERMASUK UNPAID) :</th>
                <th class="text-right">Rp {{ number_format($grandTotalSemua, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak pada: {{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>
