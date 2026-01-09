<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        
        /* Bagian Header */
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-table td { vertical-align: middle; text-align: center; }
        .logo { width: 75px; height: auto; }
        .kop-surat { font-size: 12px; }
        .kop-surat h2 { margin: 0; font-size: 18px; }
        .kop-surat p { margin: 2px 0; font-size: 10px; }

        /* Barcode untuk Kurir */
        .barcode { text-align: center; margin-top: 15px; margin-bottom: 15px; }
        .barcode-label { font-size: 10px; margin: 0; color: #555; font-weight: bold; }
        .barcode img { height: 50px; }
        .barcode-text { letter-spacing: 4px; font-size: 14px; margin-top: 2px; }

        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        
        .packages-table { width: 100%; border-collapse: collapse; }
        .packages-table th, .packages-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .packages-table th { background-color: #f2f2f2; }
        
        /* Bagian Footer & Tanda Tangan */
        .footer-table { width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .footer-table td { width: 50%; text-align: center; vertical-align: top; }
        .signature-box { height: 70px; } /* Memberi ruang untuk tanda tangan */
        .signature-name { border-top: 1px solid #333; padding-top: 5px; display: inline-block; }

        /* Barcode QR untuk Admin di area TTD Pengirim */
        .signature-barcode { margin-top: 5px; margin-bottom: 5px; }
        .signature-barcode img { height: 70px; width: 70px; } /* Ukuran QR Code */
        
    </style>
</head>
<body>
    @php
        // Logo disematkan menggunakan Base64 agar selalu muncul di PDF
        $sancakaLogo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAYAAABccqhmAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAEZ0FNQQAAsY8L/GEFAAACRklEQVR4nO3BMQEAAADCoPVPbQ0PoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAeAMB7QAB70BSYAAAAASUVORK5CYII='; // Placeholder, ganti dengan Base64 logo Sancaka Anda
        $spxLogo = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIAJgAmAMBIgACEQEDEQH/xAAbAAEAAgMBAQAAAAAAAAAAAAAABQYDBAcCAf/EADcQAAIBAgQDBgQEBgMAAAAAAAECAwQRAAUSIQYxQRMiUWFxMoGRFCNCobHB0fAVUuEWM2JykvH/xAAZAQEAAwEBAAAAAAAAAAAAAAAAAQIDBAX/xAAlEQACAgICAgIBBQEAAAAAAAAAAQIRAxIhMQRBEyJRYXGBkaH/2gAIAQEAAT8A9+Y4444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444444-1J8t8c/9k='; // Placeholder, ganti dengan Base64 logo SPX Anda
    @endphp
    <!-- Header dengan Logo dan Kop Surat -->
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: left;">
                <img src="{{ $sancakaLogo }}" alt="Logo Sancaka" class="logo">
            </td>
            <td style="width: 60%;" class="kop-surat">
                <h2>CV. SANCAKA KARYA HUTAMA</h2>
                <p>JALAN DR.WAHIDIN NO.18A RT.22 RW.05 KEL.KETANGGI KEC.NGAWI KAB.NGAWI JAWA TIMUR 63211</p>
                <p>TELP 0881 9435 180</p>
            </td>
            <td style="width: 20%; text-align: right;">
                <img src="{{ $spxLogo }}" alt="Logo SPX" class="logo">
            </td>
        </tr>
    </table>

    <!-- Barcode untuk Kurir -->
    <div class="barcode">
        <p class="barcode-label">UNTUK KURIR PICKUP</p>
        <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($suratJalan->kode_surat_jalan, 'C128', 2, 50) }}" alt="barcode" />
        <p class="barcode-text">{{ $suratJalan->kode_surat_jalan }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Pengirim</strong></td>
            <td width="1%">:</td>
            <td>{{ $suratJalan->kontak->nama }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>:</td>
            <td>{{ $suratJalan->created_at->format('d F Y') }}</td>
        </tr>
        <tr>
            <td><strong>Jumlah Paket</strong></td>
            <td>:</td>
            <td>{{ $suratJalan->jumlah_paket }} Paket</td>
        </tr>
    </table>

    <table class="packages-table">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th>Nomor Resi</th>
                <th width="30%">Waktu Scan</th> {{-- PENAMBAHAN: Kolom Waktu Scan --}}
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $index => $package)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $package->resi }}</td>
                <td>{{ $package->created_at->format('d/m/Y H:i:s') }}</td> {{-- PENAMBAHAN: Data Waktu Scan --}}
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer dengan Tanda Tangan -->
    <table class="footer-table">
        <tr>
            <td>
                <p>Diterima oleh,</p>
                <p>Kurir</p>
                <div class="signature-box"></div>
                <p class="signature-name">(_________________________)</p>
            </td>
            <td>
                <p>Diserahkan oleh,</p>
                <p>Pengirim Paket</p>
                <!-- Barcode QR untuk Admin Gudang -->
                <div class="signature-barcode">
                    <p class="barcode-label">UNTUK ADMIN GUDANG</p>
                    {{-- PERUBAHAN: Menggunakan DNS2D untuk QR Code --}}
                    <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($suratJalan->kode_surat_jalan, 'QRCODE', 4, 4) }}" alt="qr code" />
                </div>
                <p class="signature-name"><strong>( {{ $suratJalan->kontak->nama }} )</strong></p>
            </td>
        </tr>
    </table>

</body>
</html>
