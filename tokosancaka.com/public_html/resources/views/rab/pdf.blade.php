<!-- LOG LOG -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>RAB - {{ $proyek->nama_proyek }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #2d3748;
            margin: 0;
            padding: 20px;
            /* Watermark SVG untuk PDF DOMPDF */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Ctext x='50%25' y='50%25' transform='rotate(-45 100 100)' fill='rgba(100,100,100,0.12)' font-size='11' font-family='Helvetica, Arial, sans-serif' font-weight='bold' text-anchor='middle' letter-spacing='1'%3ECV SANCAKA KARYA HUTAMA%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
        }
        
        /* Kop Surat */
        .kop-surat {
            width: 100%;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .kop-table {
            width: 100%;
            border: none;
            margin: 0;
        }
        .kop-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .kop-logo {
            width: 120px;
            text-align: left;
        }
        .kop-logo img {
            max-width: 100px;
            height: auto;
        }
        .kop-text {
            text-align: left;
            padding-left: 10px;
        }
        .kop-text h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            color: #1a202c;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kop-text p {
            margin: 3px 0;
            font-size: 12px;
            color: #4a5568;
        }

        /* Informasi Proyek */
        .info-proyek {
            margin-bottom: 20px;
        }
        .info-proyek h2 {
            text-align: center;
            font-size: 16px;
            text-decoration: underline;
            margin: 0 0 15px 0;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            border: none;
            margin-bottom: 10px;
        }
        .info-table td {
            border: none;
            padding: 3px 0;
            font-size: 12px;
        }
        .info-label {
            width: 15%;
            font-weight: bold;
        }
        .info-separator {
            width: 2%;
        }

        /* Tabel Utama */
        .rab-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .rab-table th, .rab-table td {
            border: 1px solid #718096;
            padding: 7px;
        }
        .rab-table th {
            background-color: #edf2f7;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f7fafc; }
        
        /* Catatan & Footer Branding */
        .catatan-box {
            margin-top: 20px;
            padding: 15px;
            border: 1px dashed #a0aec0;
            background-color: #f7fafc;
        }
        .catatan-box h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
        }
        .catatan-box p {
            margin: 0;
            font-size: 11px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        .footer-branding {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #cbd5e0;
            text-align: center;
        }
        .footer-branding img {
            max-width: 80px;
            margin-bottom: 10px;
        }
        .footer-branding p {
            margin: 2px 0;
            font-size: 10px;
            color: #718096;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT -->
    <div class="kop-surat">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo">
                </td>
                <td class="kop-text">
                    <h1>CV SANCAKA KARYA HUTAMA</h1>
                    <p>Jl. Dr. Wahidin No.18A RT.22 RW.05 Kel Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211</p>
                    <p>Email: admin@tokosancaka.com | Telp: +62 85 745 808 809</p>
                    <p>Website: tokosancaka.com</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFORMASI PROYEK -->
    <div class="info-proyek">
        <h2>PENAWARAN HARGA PROYEK SANCAKA</h2>
        <table class="info-table">
            <tr>
                <td class="info-label">Proyek</td>
                <td class="info-separator">:</td>
                <td class="font-bold uppercase">{{ $proyek->nama_proyek }}</td>
            </tr>
            <tr>
                <td class="info-label">Lokasi</td>
                <td class="info-separator">:</td>
                <td>{{ $proyek->lokasi_proyek }}</td>
            </tr>
            <tr>
                <td class="info-label">Kontak / No. HP</td>
                <td class="info-separator">:</td>
                <td>{{ $proyek->nomor_hp }}</td>
            </tr>
        </table>
    </div>

    <!-- TABEL RAB -->
    <table class="rab-table">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="40%">URAIAN PEKERJAAN</th>
                <th width="10%">VOL</th>
                <th width="10%">SAT</th>
                <th width="15%">HARGA SATUAN</th>
                <th width="20%">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $currentCategory = null; $no = 1; @endphp
            
            @foreach($items as $item)
                @if($item->kategori !== $currentCategory)
                    <tr class="bg-gray">
                        <td></td>
                        <td colspan="5" class="font-bold">{{ strtoupper($item->kategori ?: 'UMUM') }}</td>
                    </tr>
                    @php $currentCategory = $item->kategori; @endphp
                @endif
                
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-left">{{ $item->uraian_pekerjaan }}</td>
                    <td class="text-right">{{ number_format($item->volume, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->satuan }}</td>
                    <td class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL KESELURUHAN</th>
                <th class="text-right">Rp {{ number_format($totalKeseluruhan, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <!-- CATATAN -->
    @if($proyek->catatan)
    <div class="catatan-box">
        <h4>Catatan Tambahan:</h4>
        <p>{{ $proyek->catatan }}</p>
    </div>
    @endif

    <!-- FOOTER BRANDING -->
    <div class="footer-branding">
        <img src="https://tokosancaka.com/storage/uploads/logo.jpeg" alt="Sancaka Logo">
        <p><strong>CV SANCAKA KARYA HUTAMA</strong></p>
        <p>Jl. Dr. Wahidin No.18A RT.22 RW.05 Kel Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211</p>
        <p>Dokumen ini dicetak secara otomatis melalui sistem tokosancaka.com</p>
    </div>

</body>
</html>