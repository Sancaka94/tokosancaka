<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi {{ $tahun }}</title>
    <style>
        /* RESET & DASAR */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt; /* Font kecil agar muat 12 bulan */
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* HEADER LAPORAN */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .header p {
            margin: 2px 0;
            font-size: 10pt;
        }
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* TABEL UTAMA */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        /* HEADER TABEL */
        th {
            background-color: #f2f2f2;
            color: #000;
            font-weight: bold;
            text-align: right;
            padding: 6px 2px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 7pt; /* Nama bulan dikecilkan */
            text-transform: uppercase;
        }
        
        /* KOLOM PERTAMA (KETERANGAN) LEBIH LEBAR */
        th:first-child, td:first-child {
            text-align: left;
            width: 120px; /* Lebar tetap untuk label */
            padding-left: 5px;
        }

        /* ISI TABEL */
        td {
            padding: 4px 2px;
            text-align: right;
            border-bottom: 1px dotted #ccc;
        }

        /* FORMAT ANGKA NOL */
        .zero {
            color: #ccc;
        }

        /* GAYA BARIS TOTAL & JUDUL SEKSI */
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            padding-top: 10px;
            padding-bottom: 5px;
        }
        
        .row-total {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .row-grand-total {
            font-weight: bold;
            background-color: #e0e0e0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 9pt;
        }

        /* TANDA TANGAN */
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            float: right;
            width: 200px;
            text-align: center;
        }
        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #000;
        }

        /* Helper Utility */
        .text-red { color: #d9534f; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">NAMA PERUSAHAAN ANDA</div>
        <h1>Laporan Laba Rugi</h1>
        <p>Periode Tahun Buku: {{ $tahun }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>KETERANGAN</th>
                @foreach($months as $m)
                    <th>{{ substr($m, 0, 3) }}</th> @endforeach
                </tr>
        </thead>
        <tbody>
            
            <tr>
                <td colspan="13" class="section-title">PENDAPATAN USAHA</td>
            </tr>
            @php
                $sumberPendapatan = ['Ekspedisi', 'PPOB', 'Marketplace', 'Top Up Saldo', 'Lain-lain'];
            @endphp

            @foreach($sumberPendapatan as $sumber)
            <tr>
                <td>{{ $sumber }}</td>
                @foreach($months as $key => $val)
                    @php $nilai = $report[$key]['pendapatan'][$sumber] ?? 0; @endphp
                    <td class="{{ $nilai == 0 ? 'zero' : '' }}">
                        {{ $nilai != 0 ? number_format($nilai, 0, ',', '.') : '-' }}
                    </td>
                @endforeach
            </tr>
            @endforeach

            <tr class="row-total">
                <td>TOTAL PENDAPATAN</td>
                @foreach($months as $key => $val)
                    <td>{{ number_format($report[$key]['total_pendapatan'], 0, ',', '.') }}</td>
                @endforeach
            </tr>

            <tr>
                <td colspan="13" class="section-title" style="padding-top:15px;">BEBAN POKOK PENDAPATAN</td>
            </tr>
            @php
                $sumberHPP = [
                    'Beban Pokok Ekspedisi', 
                    'Beban Pokok PPOB', 
                    'Beban Pokok Marketplace',
                    'Beban Pokok Top Up'
                ];
            @endphp

            @foreach($sumberHPP as $hpp)
            <tr>
                <td>{{ str_replace('Beban Pokok ', '', $hpp) }}</td> @foreach($months as $key => $val)
                    @php $nilai = $report[$key]['hpp'][$hpp] ?? 0; @endphp
                    <td class="{{ $nilai == 0 ? 'zero' : '' }}">
                        {{ $nilai != 0 ? '('.number_format($nilai, 0, ',', '.').')' : '-' }}
                    </td>
                @endforeach
            </tr>
            @endforeach

            <tr class="row-total">
                <td>LABA KOTOR</td>
                @foreach($months as $key => $val)
                    <td>{{ number_format($report[$key]['laba_kotor'], 0, ',', '.') }}</td>
                @endforeach
            </tr>

            <tr>
                <td colspan="13" class="section-title" style="padding-top:15px;">BEBAN OPERASIONAL</td>
            </tr>
            
            @if(count($listKategoriBeban) > 0)
                @foreach($listKategoriBeban as $beban)
                <tr>
                    <td>{{ $beban }}</td>
                    @foreach($months as $key => $val)
                        @php $nilai = $report[$key]['beban'][$beban] ?? 0; @endphp
                        <td class="{{ $nilai == 0 ? 'zero' : '' }}">
                            {{ $nilai != 0 ? '('.number_format($nilai, 0, ',', '.').')' : '-' }}
                        </td>
                    @endforeach
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="13" style="text-align:center; color:#999; font-style:italic;">Tidak ada beban operasional tercatat</td>
                </tr>
            @endif

            <tr class="row-total">
                <td>TOTAL BEBAN</td>
                @foreach($months as $key => $val)
                    <td>{{ number_format($report[$key]['total_beban'], 0, ',', '.') }}</td>
                @endforeach
            </tr>

            <tr class="row-grand-total">
                <td>LABA BERSIH</td>
                @foreach($months as $key => $val)
                    @php $bersih = $report[$key]['laba_bersih']; @endphp
                    <td class="{{ $bersih < 0 ? 'text-red' : '' }}">
                        {{ number_format($bersih, 0, ',', '.') }}
                    </td>
                @endforeach
            </tr>

        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <p>Dicetak pada: {{ date('d F Y') }}</p>
            <br>
            <p>Mengetahui,</p>
            <div class="signature-line"></div>
            <p><strong>Finance Manager</strong></p>
        </div>
    </div>

</body>
</html>