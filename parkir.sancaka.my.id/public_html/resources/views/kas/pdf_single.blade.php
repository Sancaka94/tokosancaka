<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kas - {{ \Carbon\Carbon::parse($kas->tanggal_mulai)->format('d M Y') }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        /* --- KOP SURAT --- */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo {
            max-height: 70px;
            width: auto;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            color: #000;
        }
        .company-address {
            margin: 2px 0;
            color: #444;
        }
        
        /* --- JUDUL --- */
        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .report-period {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
            color: #555;
        }

        /* --- SUMMARY BOX --- */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            width: 33.33%;
        }
        .summary-table th {
            background-color: #f2f2f2;
            font-size: 11px;
            text-transform: uppercase;
        }
        .summary-table td {
            font-size: 14px;
            font-weight: bold;
        }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }

        /* --- TABEL RINCIAN --- */
        .details-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .data-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* --- TANDA TANGAN --- */
        .signature-table {
            width: 100%;
            margin-top: 40px;
            text-align: center;
        }
        .signature-table td {
            width: 50%;
            vertical-align: bottom;
        }
        .ttd-image {
            max-height: 80px;
            margin: 10px 0;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT (Harus pakai tabel agar aman di PDF) -->
    <table class="header-table">
        <tr>
            <td width="15%" class="text-center">
                <!-- Jika logo tidak muncul di PDF, ganti URL ini dengan path lokal, contoh: public_path('storage/uploads/sancaka.png') -->
                <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Logo" class="logo">
            </td>
            <td width="85%">
                <h1 class="company-name">SANCAKA KARYA HUTAMA</h1>
                <p class="company-address">Jl. Dr. Wahidin no. 18A (depan RSUD Soeroto Ngawi)</p>
                <p class="company-address">Telp: 0881-9435-180</p>
            </td>
        </tr>
    </table>

    <!-- JUDUL LAPORAN -->
    <div class="report-title">LAPORAN KAS (PEMASUKAN & PENGELUARAN)</div>
    <div class="report-period">
        Periode: 
        {{ \Carbon\Carbon::parse($kas->tanggal_mulai)->translatedFormat('d F Y') }}
        @if($kas->tanggal_mulai != $kas->tanggal_akhir)
            s/d {{ \Carbon\Carbon::parse($kas->tanggal_akhir)->translatedFormat('d F Y') }}
        @endif
    </div>

    <!-- RINGKASAN KEUANGAN -->
    <table class="summary-table">
        <tr>
            <th>Pemasukan Sistem</th>
            <th>Total Pengeluaran</th>
            <th>Saldo Bersih</th>
        </tr>
        <tr>
            <td class="text-success">Rp {{ number_format($kas->pemasukan_sistem, 0, ',', '.') }}</td>
            <td class="text-danger">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</td>
            <td>
                @if($kas->saldo_bersih < 0)
                    <span class="text-danger">Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}</span>
                @else
                    <span>Rp {{ number_format($kas->saldo_bersih, 0, ',', '.') }}</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- RINCIAN PENGELUARAN -->
    <div class="details-title">Rincian Pengeluaran Manual:</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="65%">Keterangan Pengeluaran</th>
                <th width="30%">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kas->pengeluaran as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item->keterangan }}</td>
                <td class="text-right">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center" style="font-style: italic; color: #666;">Tidak ada data pengeluaran manual pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-right">TOTAL PENGELUARAN :</th>
                <th class="text-right">Rp {{ number_format($kas->total_pengeluaran, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <!-- KOLOM TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                <p style="margin-bottom: 60px;">Dibuat Oleh,</p>
                
                {{-- Jika ada sistem upload gambar TTD, tampilkan di sini. Jika tidak, biarkan kosong untuk TTD basah --}}
                @if($kas->ttd_pembuat)
                    <!-- <img src="{{ public_path('storage/' . $kas->ttd_pembuat) }}" class="ttd-image" alt="TTD"> -->
                @endif
                
                <p class="signature-name">{{ $kas->nama_pembuat ?? '..................................' }}</p>
                <p style="margin:0; font-size:10px;">Admin</p>
            </td>
            <td>
                <p style="margin-bottom: 60px;">Diketahui Oleh,</p>
                
                @if($kas->ttd_pimpinan)
                    <!-- <img src="{{ public_path('storage/' . $kas->ttd_pimpinan) }}" class="ttd-image" alt="TTD"> -->
                @endif

                <p class="signature-name">{{ $kas->nama_pimpinan ?? 'PIMPINAN AZKEN PARKIR' }}</p>
                <p style="margin:0; font-size:10px;">Pimpinan</p>
            </td>
        </tr>
    </table>

</body>
</html>