<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi Ringkas</title>
    <style>
        /* ==========================================
           RESET & STYLE DASAR
           ========================================== */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #111;
            line-height: 1.3;
            margin: 0; padding: 0;
        }

        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 0;
        }
        .period {
            font-size: 11pt;
            margin-top: 5px;
            font-style: italic;
        }

        /* TABEL UTAMA */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        /* HEADER TABEL */
        th {
            background-color: #f3f3f3;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            text-transform: uppercase;
            font-size: 9pt;
            font-weight: bold;
        }

        /* BODY TABEL */
        td {
            padding: 6px 5px;
            border-bottom: 1px dotted #ccc;
            vertical-align: top;
        }

        /* KOLOM */
        .col-keterangan { text-align: left; width: 50%; }
        .col-angka { text-align: right; width: 25%; }

        /* UTILITAS TEXT */
        .indent { padding-left: 25px; }
        .bold { font-weight: bold; }
        .text-red { color: #c0392b; } /* Merah utk minus */
        .parenthesis { color: #c0392b; } /* Format ( ) merah */

        /* BARIS TOTAL / SECTION */
        .section-title {
            background-color: #eef2f3;
            font-weight: bold;
            padding-top: 10px;
            padding-bottom: 5px;
            border-bottom: none;
        }
        
        .row-total {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .row-grand-total {
            font-weight: bold;
            font-size: 11pt;
            background-color: #2c3e50;
            color: #fff;
            border-top: 2px solid #000;
        }

        /* TANDA TANGAN */
        .signature-wrapper { margin-top: 50px; }
        .signature-box {
            float: right;
            width: 220px;
            text-align: center;
        }
    </style>
</head>
<body>

    @php
        // PERBAIKAN:
        // 1. Tambahkan (int) untuk memaksa string menjadi angka
        // 2. Tambahkan ->day(1) untuk mencegah error tanggal (misal: tgl 31 diubah ke Feb jadi Maret)
        $namaBulan = \Carbon\Carbon::create()
            ->day(1)
            ->month((int) $bulanDipilih)
            ->locale('id')
            ->isoFormat('MMMM');
    @endphp

    <div class="header">
        <div class="company-name">CV. SANCAKA KARYA HUTAMA</div>
        <h1 class="report-title">LAPORAN LABA RUGI</h1>
        <p class="period">Periode: {{ $namaBulan }} {{ $tahun }} vs Akumulasi Tahun {{ $tahun }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-keterangan">KETERANGAN</th>
                <th class="col-angka">BULAN {{ strtoupper($namaBulan) }}</th>
                <th class="col-angka">TOTAL TAHUN {{ $tahun }}</th>
            </tr>
        </thead>
        <tbody>

            <tr><td colspan="3" class="section-title">A. PENDAPATAN USAHA</td></tr>

            @php 
                $totalPendBulan = 0; 
                $totalPendTahun = 0; 
                $listPendapatan = ['Ekspedisi', 'PPOB', 'Marketplace', 'Top Up Saldo', 'Lain-lain'];
            @endphp

            @foreach($listPendapatan as $sumber)
                @php
                    // 1. Ambil Data Bulan Terpilih
                    $valBulan = $report[$bulanDipilih]['pendapatan'][$sumber] ?? 0;
                    
                    // 2. Hitung Total Setahun (Loop 1-12)
                    $valTahun = 0;
                    foreach($months as $m => $name) {
                        $valTahun += $report[$m]['pendapatan'][$sumber] ?? 0;
                    }

                    // 3. Akumulasi Total
                    $totalPendBulan += $valBulan;
                    $totalPendTahun += $valTahun;
                @endphp
                <tr>
                    <td class="indent">{{ $sumber }}</td>
                    <td class="col-angka">{{ number_format($valBulan, 0, ',', '.') }}</td>
                    <td class="col-angka">{{ number_format($valTahun, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr class="row-total">
                <td class="col-keterangan">TOTAL PENDAPATAN</td>
                <td class="col-angka">{{ number_format($totalPendBulan, 0, ',', '.') }}</td>
                <td class="col-angka">{{ number_format($totalPendTahun, 0, ',', '.') }}</td>
            </tr>


            <tr><td colspan="3" class="section-title">B. BEBAN POKOK PENDAPATAN</td></tr>

            @php 
                $totalHPPBulan = 0; 
                $totalHPPTahun = 0; 
                $listHPP = ['Beban Pokok Ekspedisi', 'Beban Pokok PPOB', 'Beban Pokok Marketplace', 'Beban Pokok Top Up'];
            @endphp

            @foreach($listHPP as $hpp)
                @php
                    $valBulan = $report[$bulanDipilih]['hpp'][$hpp] ?? 0;
                    
                    $valTahun = 0;
                    foreach($months as $m => $name) {
                        $valTahun += $report[$m]['hpp'][$hpp] ?? 0;
                    }

                    $totalHPPBulan += $valBulan;
                    $totalHPPTahun += $valTahun;
                @endphp
                <tr>
                    <td class="indent">{{ str_replace('Beban Pokok ', '', $hpp) }}</td>
                    <td class="col-angka parenthesis">({{ number_format($valBulan, 0, ',', '.') }})</td>
                    <td class="col-angka parenthesis">({{ number_format($valTahun, 0, ',', '.') }})</td>
                </tr>
            @endforeach

            <tr class="row-total">
                <td class="col-keterangan">TOTAL BEBAN POKOK</td>
                <td class="col-angka parenthesis">({{ number_format($totalHPPBulan, 0, ',', '.') }})</td>
                <td class="col-angka parenthesis">({{ number_format($totalHPPTahun, 0, ',', '.') }})</td>
            </tr>


            @php
                $labaKotorBulan = $totalPendBulan - $totalHPPBulan;
                $labaKotorTahun = $totalPendTahun - $totalHPPTahun;
            @endphp
            <tr class="bold" style="background-color: #ecf0f1;">
                <td class="col-keterangan" style="padding-left: 5px;">LABA KOTOR</td>
                <td class="col-angka">{{ number_format($labaKotorBulan, 0, ',', '.') }}</td>
                <td class="col-angka">{{ number_format($labaKotorTahun, 0, ',', '.') }}</td>
            </tr>


            <tr><td colspan="3" class="section-title">C. BEBAN OPERASIONAL</td></tr>

            @php 
                $totalBebanBulan = 0; 
                $totalBebanTahun = 0; 
            @endphp

            @if(count($listKategoriBeban) > 0)
                @foreach($listKategoriBeban as $beban)
                    @php
                        $valBulan = $report[$bulanDipilih]['beban'][$beban] ?? 0;
                        
                        $valTahun = 0;
                        foreach($months as $m => $name) {
                            $valTahun += $report[$m]['beban'][$beban] ?? 0;
                        }

                        $totalBebanBulan += $valBulan;
                        $totalBebanTahun += $valTahun;
                    @endphp
                    <tr>
                        <td class="indent">{{ $beban }}</td>
                        <td class="col-angka parenthesis">({{ number_format($valBulan, 0, ',', '.') }})</td>
                        <td class="col-angka parenthesis">({{ number_format($valTahun, 0, ',', '.') }})</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="3" style="text-align:center; font-style:italic; color:#7f8c8d;">- Tidak ada pengeluaran operasional -</td></tr>
            @endif

            <tr class="row-total">
                <td class="col-keterangan">TOTAL BEBAN OPS</td>
                <td class="col-angka parenthesis">({{ number_format($totalBebanBulan, 0, ',', '.') }})</td>
                <td class="col-angka parenthesis">({{ number_format($totalBebanTahun, 0, ',', '.') }})</td>
            </tr>


            <tr><td colspan="3" style="height: 20px; border:none;"></td></tr> @php
                $labaBersihBulan = $labaKotorBulan - $totalBebanBulan;
                $labaBersihTahun = $labaKotorTahun - $totalBebanTahun;
            @endphp

            <tr class="row-grand-total">
                <td class="col-keterangan" style="padding-left: 5px;">LABA BERSIH (NET INCOME)</td>
                <td class="col-angka">{{ number_format($labaBersihBulan, 0, ',', '.') }}</td>
                <td class="col-angka">{{ number_format($labaBersihTahun, 0, ',', '.') }}</td>
            </tr>

        </tbody>
    </table>

    <div class="signature-wrapper">
        <div class="signature-box">
            <p>Dicetak pada: {{ date('d F Y') }}</p>
            <br><br><br><br>
            <p style="border-top: 1px solid #000; padding-top: 5px; font-weight: bold;">
                Admin Keuangan
            </p>
        </div>
    </div>

</body>
</html>