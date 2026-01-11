<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi - CV. SANCAKA KARYA HUTAMA</title>
    <style>
        /* ==========================================================
           1. RESET & BASIC STYLING (DomPDF Friendly)
           ========================================================== */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt; /* Ukuran font kecil agar muat 12 bulan */
            color: #1f2937; /* Gray-800 equivalent */
            margin: 0;
            padding: 0;
        }

        /* ==========================================================
           2. HEADER LAPORAN
           ========================================================== */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #4b5563;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            color: #374151;
            margin: 0;
        }
        .period {
            font-size: 10pt;
            color: #6b7280;
            margin-top: 5px;
        }

        /* ==========================================================
           3. TABEL DATA
           ========================================================== */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Memastikan lebar kolom konsisten */
        }

        /* Header Kolom (Bulan) */
        th {
            background-color: #f3f4f6; /* Gray-100 */
            color: #374151;
            font-weight: bold;
            font-size: 7pt;
            text-align: right;
            padding: 8px 4px;
            border: 1px solid #d1d5db; /* Gray-300 */
            text-transform: uppercase;
        }

        /* Kolom Pertama (Keterangan) */
        th.col-first, td.col-first {
            text-align: left;
            width: 140px; /* Lebar fix untuk keterangan */
            padding-left: 8px;
        }

        /* Kolom Terakhir (Total) */
        th.col-total, td.col-total {
            background-color: #e5e7eb; /* Gray-200 */
            border-left: 2px solid #9ca3af;
            font-weight: bold;
            color: #111827;
        }

        /* Sel Data Biasa */
        td {
            padding: 5px 4px;
            text-align: right;
            border: 1px solid #e5e7eb; /* Gray-200 */
            vertical-align: middle;
        }

        /* Helper Text */
        .zero { color: #d1d5db; } /* Angka 0 jadi abu-abu muda */
        .text-red { color: #ef4444; } /* Merah */
        
        /* ==========================================================
           4. STYLING PER SECTION (Mirip Tailwind Anda)
           ========================================================== */
        
        /* A. PENDAPATAN (Blue Theme) */
        .header-blue {
            background-color: #eff6ff; /* blue-50 */
            color: #1e40af; /* blue-800 */
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #bfdbfe;
        }
        .row-total-pendapatan {
            background-color: #f3f4f6;
            color: #1e3a8a; /* blue-900 */
            font-weight: bold;
            border-top: 2px solid #d1d5db;
        }

        /* B. HPP (Red Theme) */
        .header-red {
            background-color: #fef2f2; /* red-50 */
            color: #991b1b; /* red-800 */
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #fecaca;
        }
        .row-total-hpp {
            background-color: #f3f4f6;
            color: #7f1d1d; /* red-900 */
            font-weight: bold;
            border-top: 2px solid #d1d5db;
        }

        /* C. LABA KOTOR (Green Theme) */
        .row-laba-kotor {
            background-color: #dcfce7; /* green-100 */
            color: #14532d; /* green-900 */
            font-weight: bold;
            border-top: 2px solid #86efac;
            border-bottom: 2px solid #86efac;
        }

        /* D. BEBAN OPERASIONAL (Gray Theme) */
        .header-gray {
            background-color: #f9fafb; /* gray-50 */
            color: #1f2937; /* gray-800 */
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* E. LABA BERSIH (Indigo Theme - Highlight) */
        .row-laba-bersih {
            background-color: #4338ca; /* Indigo-700 */
            color: #ffffff;
            font-weight: bold;
            font-size: 9pt;
            border-top: 3px solid #312e81; /* Indigo-900 */
        }
        .row-laba-bersih td {
            border: 1px solid #6366f1; /* Indigo-500 */
        }
        .row-laba-bersih .col-total {
            background-color: #312e81; /* Indigo-900 */
            color: #ffffff;
            border-left: 1px solid #818cf8;
        }

        /* Footer Tanda Tangan */
        .signature-wrapper {
            margin-top: 40px;
            width: 100%;
        }
        .signature-box {
            float: right;
            width: 250px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">CV. SANCAKA KARYA HUTAMA</div>
        <h1 class="report-title">LAPORAN LABA RUGI</h1>
        <p class="period">Periode: Januari - Desember {{ $tahun }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-first">AKUN / KATEGORI</th>
                @foreach($months as $m => $name)
                    <th>{{ substr($name, 0, 3) }}</th> @endforeach
                <th class="col-total">TOTAL</th>
            </tr>
        </thead>
        <tbody>

            <tr>
                <td colspan="14" class="header-blue">A. PENDAPATAN USAHA</td>
            </tr>

            @php $grandTotalPendapatan = 0; @endphp
            @foreach(['Ekspedisi', 'PPOB', 'Marketplace', 'Lain-lain'] as $sumber)
                <tr>
                    <td class="col-first">Pendapatan {{ $sumber }}</td>
                    @php $sumRow = 0; @endphp
                    @foreach($months as $m => $name)
                        @php 
                            $val = $report[$m]['pendapatan'][$sumber]; 
                            $sumRow += $val; 
                        @endphp
                        <td class="{{ $val == 0 ? 'zero' : '' }}">
                            {{ $val != 0 ? number_format($val, 0, ',', '.') : '-' }}
                        </td>
                    @endforeach
                    <td class="col-total">
                        {{ number_format($sumRow, 0, ',', '.') }}
                    </td>
                    @php $grandTotalPendapatan += $sumRow; @endphp
                </tr>
            @endforeach

            <tr class="row-total-pendapatan">
                <td class="col-first">TOTAL PENDAPATAN</td>
                @foreach($months as $m => $name)
                    <td>{{ number_format($report[$m]['total_pendapatan'], 0, ',', '.') }}</td>
                @endforeach
                <td class="col-total">{{ number_format($grandTotalPendapatan, 0, ',', '.') }}</td>
            </tr>


            <tr>
                <td colspan="14" class="header-red" style="padding-top: 15px;">B. BEBAN POKOK PENDAPATAN</td>
            </tr>

            @php $grandTotalHPP = 0; @endphp
            @foreach(['Beban Pokok Ekspedisi', 'Beban Pokok PPOB', 'Beban Pokok Marketplace'] as $sumber)
                <tr>
                    <td class="col-first">{{ $sumber }}</td>
                    @php $sumRow = 0; @endphp
                    @foreach($months as $m => $name)
                        @php 
                            $val = $report[$m]['hpp'][$sumber]; 
                            $sumRow += $val; 
                        @endphp
                        <td class="{{ $val == 0 ? 'zero' : '' }}">
                            {{ $val != 0 ? '('.number_format($val, 0, ',', '.').')' : '-' }}
                        </td>
                    @endforeach
                    <td class="col-total">
                        ({{ number_format($sumRow, 0, ',', '.') }})
                    </td>
                    @php $grandTotalHPP += $sumRow; @endphp
                </tr>
            @endforeach

            <tr class="row-total-hpp">
                <td class="col-first">TOTAL BEBAN POKOK</td>
                @foreach($months as $m => $name)
                    <td>({{ number_format($report[$m]['total_hpp'], 0, ',', '.') }})</td>
                @endforeach
                <td class="col-total">({{ number_format($grandTotalHPP, 0, ',', '.') }})</td>
            </tr>


            <tr class="row-laba-kotor">
                <td class="col-first">LABA KOTOR</td>
                @php $grandTotalLabaKotor = 0; @endphp
                @foreach($months as $m => $name)
                    @php $grandTotalLabaKotor += $report[$m]['laba_kotor']; @endphp
                    <td>{{ number_format($report[$m]['laba_kotor'], 0, ',', '.') }}</td>
                @endforeach
                <td class="col-total">{{ number_format($grandTotalLabaKotor, 0, ',', '.') }}</td>
            </tr>


            <tr>
                <td colspan="14" class="header-gray" style="padding-top: 15px;">C. BEBAN OPERASIONAL</td>
            </tr>

            @php $grandTotalBebanOps = 0; @endphp
            @if(count($listKategoriBeban) > 0)
                @foreach($listKategoriBeban as $kat)
                    <tr>
                        <td class="col-first">{{ $kat }}</td>
                        @php $sumRow = 0; @endphp
                        @foreach($months as $m => $name)
                            @php 
                                $val = $report[$m]['beban'][$kat] ?? 0;
                                $sumRow += $val;
                            @endphp
                            <td class="{{ $val == 0 ? 'zero' : '' }}">
                                {{ $val != 0 ? '('.number_format($val, 0, ',', '.').')' : '-' }}
                            </td>
                        @endforeach
                        <td class="col-total">
                            ({{ number_format($sumRow, 0, ',', '.') }})
                        </td>
                        @php $grandTotalBebanOps += $sumRow; @endphp
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="14" style="text-align: center; font-style: italic; color: #9ca3af;">- Tidak ada data beban manual -</td>
                </tr>
            @endif

            <tr class="row-total-pendapatan" style="background-color: #f9fafb; color: #374151;">
                <td class="col-first">TOTAL BEBAN OPS</td>
                @foreach($months as $m => $name)
                    <td>({{ number_format($report[$m]['total_beban'], 0, ',', '.') }})</td>
                @endforeach
                <td class="col-total">({{ number_format($grandTotalBebanOps, 0, ',', '.') }})</td>
            </tr>


            <tr><td colspan="14" style="border:none; height: 10px;"></td></tr>

            <tr class="row-laba-bersih">
                <td class="col-first" style="padding-top: 10px; padding-bottom: 10px;">LABA BERSIH</td>
                @php $grandTotalNetIncome = 0; @endphp
                @foreach($months as $m => $name)
                    @php $grandTotalNetIncome += $report[$m]['laba_bersih']; @endphp
                    <td>{{ number_format($report[$m]['laba_bersih'], 0, ',', '.') }}</td>
                @endforeach
                <td class="col-total">{{ number_format($grandTotalNetIncome, 0, ',', '.') }}</td>
            </tr>

        </tbody>
    </table>

    <div class="signature-wrapper">
        <div class="signature-box">
            <p>Dicetak pada: {{ date('d-m-Y H:i') }}</p>
            <br><br><br>
            <p style="border-top: 1px solid #000; display: inline-block; width: 100%; padding-top: 5px;">
                <strong>Bagian Keuangan</strong>
            </p>
        </div>
    </div>

</body>
</html>