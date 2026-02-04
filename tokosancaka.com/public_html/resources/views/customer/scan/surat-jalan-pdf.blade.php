<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }

        /* --- Header Baru --- */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 5px;
        }
        .logo-container {
            width: 25%;
        }
        .logo-container .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }
        .company-details {
            width: 45%;
        }
        .company-details h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
        }
        .company-details p {
            margin: 2px 0;
        }
        .barcode-header-container {
            width: 30%;
        }
        .barcode-header-container .barcode-wrapper {
            margin: 0;
            padding: 0;
        }
        .barcode-header-container .barcode-text {
            letter-spacing: 2px;
            margin-top: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        /* --- Akhir Header Baru --- */

        .content {
            margin-top: 20px;
        }
        .info-table, .resi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px 0;
        }
        .resi-table th, .resi-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .resi-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .resi-table .no {
            width: 5%;
            text-align: center;
        }
        .resi-table .waktu {
            width: 20%;
            text-align: center;
        }

        /* --- Tanda Tangan Baru --- */
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
        .signature-box {
            height: 80px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .signature-content {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .signature-qr {
            margin-right: 15px;
        }
        .signature-text {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 70px; /* Sesuaikan dengan tinggi QR code */
            text-align: left;
        }
        /* --- Akhir Tanda Tangan Baru --- */

        .signature-name {
            font-weight: bold;
        }
        hr {
            border: 0;
            border-top: 1px solid #ccc;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header dengan Logo, Judul, dan Barcode -->
        <table class="header-table">
            <tr>
                <td class="logo-container text-left">
                    <!-- Ganti dengan tag <img> jika Anda memiliki file logo -->
                    <div class="logo-text">Sancaka Express</div>
                    <p>Layanan Pengiriman Terpercaya</p>
                </td>
                <td class="company-details text-center">
                    <h1>SURAT JALAN</h1>
                </td>
                <td class="barcode-header-container text-right">
                    <div class="barcode-wrapper">
                        {!! DNS1D::getBarcodeHTML($suratJalan->kode_surat_jalan, 'C128', 1.5, 40) !!}
                        <p class="barcode-text">{{ $suratJalan->kode_surat_jalan }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <hr>

        <main class="content">
            <!-- Info Surat Jalan -->
            <table class="info-table">
                <tr>
                    <td style="width: 20%;"><strong>Nama Pelanggan</strong></td>
                    <td style="width: 2%;">:</td>
                    <td>{{ $customer->nama_lengkap }}</td>
                </tr>
                <tr>
                    <td><strong>Tanggal Cetak</strong></td>
                    <td>:</td>
                    <td>{{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Kode Surat Jalan</strong></td>
                    <td>:</td>
                    <td><strong>{{ $suratJalan->kode_surat_jalan }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Jumlah Paket</strong></td>
                    <td>:</td>
                    <td>{{ $suratJalan->jumlah_paket }} Koli</td>
                </tr>
            </table>

            <!-- Tabel Resi -->
            <h3 class="text-center" style="margin-bottom: 15px; margin-top: 30px;">DAFTAR RESI PAKET</h3>
            <table class="resi-table">
                <thead>
                    <tr>
                        <th class="no">No</th>
                        <th>Nomor Resi</th>
                        <th class="waktu">Waktu Scan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scans as $index => $scan)
                        <tr>
                            <td class="no">{{ $index + 1 }}</td>
                            <td>{{ $scan->resi_number }}</td>
                            <td class="waktu">{{ $scan->created_at->translatedFormat('H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">Tidak ada data resi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Tanda Tangan -->
            <table class="signatures">
                <tr>
                    <td>
                        <div class="signature-box">
                            <div class="signature-content">
                                
                                
                                <div class="signature-text">
                                    <p style="margin:0;">Diserahkan oleh,</p><br>
                                    
                                    <div class="signature-qr">
                                    {!! DNS2D::getBarcodeHTML($suratJalan->kode_surat_jalan, 'QRCODE', 4, 4) !!}
                                    </div><br>
                                    
                                    <p class="signature-name" style="margin:0;">( {{ $customer->nama_lengkap }} )</p>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <p>Diterima oleh Kurir,</p>
                        <div class="signature-box">
                            {{-- Ruang kosong untuk tanda tangan manual --}}
                        </div>
                        <p class="signature-name">( ................................. )</p>
                    </td>
                </tr>
            </table>
        </main>
    </div>
</body>
</html>
