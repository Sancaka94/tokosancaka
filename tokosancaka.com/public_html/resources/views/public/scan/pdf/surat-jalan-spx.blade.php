<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }
        .barcode-rect {
            width: 100%;
            text-align: center;
            margin-bottom: 15px;
        }
        .barcode-rect img {
            height: 40px;
            max-width: 300px;
        }
        .details {
            width: 100%;
            margin-bottom: 15px;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .details table {
            width: 100%;
        }
        .details td {
            vertical-align: top;
            padding: 2px 0;
        }
        .details .label {
            font-weight: bold;
            width: 120px;
        }
        .package-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .package-table th,
        .package-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .package-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .package-table td.center {
            text-align: center;
        }
        .footer {
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 10px;
            font-size: 11px;
        }
        .footer table {
            width: 100%;
        }
        .footer td {
            width: 50%;
            vertical-align: top;
        }
        .footer .signature {
            text-align: center;
        }
        .qr-code {
            width: 80px;
            height: 80px;
            margin-bottom: 5px;
        }
        .location-text {
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        
        
        <div class="header">
            <h2>SURAT JALAN PICKUP</h2>
            <p>Nomor: <strong>{{ $suratJalan->kode_surat_jalan }}</strong></p>
        </div>

        <div class="barcode-rect">
            {{-- Barcode 1D (Persegi Panjang) dari Controller --}}
            @if ($barcodeRectBase64)
                <img src="data:image/png;base64,{{ $barcodeRectBase64 }}" alt="Barcode">
            @endif
        </div>

        <div class="details">
            <table>
                <tr>
                    <td class="label">Pengirim</td>
                    <td>: <strong>{{ $suratJalan->kontak->nama ?? 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Alamat</td>
                    <td>: {{ $suratJalan->kontak->alamat ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal</td>
                    <td>: {{ \Carbon\Carbon::parse($suratJalan->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i') }} WIB</td>
                </tr>
                <tr>
                    <td class="label">Jumlah Paket</td>
                    <td>: <strong>{{ $suratJalan->jumlah_paket }} Paket</strong></td>
                </tr>
            </table>
        </div>

        <table class="package-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No.</th>
                    <th>Nomor Resi</th>
                    <th style="width: 150px;">Waktu Scan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($packages as $index => $pkg)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $pkg->resi_number }}</td>
                        <td>{{ $pkg->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center">Tidak ada data resi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            <table>
                <tr>
{{-- Kolom Kiri: QR Lokasi --}}
<td>
    <strong>Lokasi Pickup Kurir:</strong><br><br>

    {{-- Gunakan kode ini --}}
    @if (isset($locationQrCodeBase64) && $locationQrCodeBase64)
        <img class="qr-code" src="data:image/png;base64,{{ $locationQrCodeBase64 }}" alt="QR Lokasi">
    @else
        <p class="location-text">Lokasi tidak tersedia</p>
    @endif
</td>
                    
                    {{-- Kolom Kanan: QR Tanda Tangan --}}
                    <td class="signature">
                        <strong>Hormat Kami,</strong><br><br>
                        
                        {{-- Ini akan menampilkan QR Tanda Tangan dari Controller --}}
                        @if ($qrCodeBase64)
                            <img class="qr-code" src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Surat Jalan">
                        @endif
                        <br>
                        ( {{ $suratJalan->kontak->nama ?? 'Pengirim' }} )
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>