<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; }
        .packages-table { width: 100%; border-collapse: collapse; }
        .packages-table th, .packages-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .packages-table th { background-color: #f2f2f2; }
        .footer { margin-top: 40px; width: 100%; }
        .signature { float: right; text-align: center; }
        .signature p { margin-top: 20px; }
        /* Styling untuk gambar barcode */
        .barcode-square { 
            width: 80px; 
            height: 80px; 
            margin: 10px auto; 
        }
        .barcode-rect {
            display: block;
            margin: 10px auto 0;
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SURAT JALAN PICKUP</h1>
        <p><strong>Nomor:</strong> {{ $suratJalan->kode_surat_jalan }}</p>
        
        <!-- 
            CATATAN: Barcode sekarang berupa gambar yang dibuat di controller.
            Anda perlu membuat variabel $barcodeRectBase64 di controller Anda
            yang berisi data gambar barcode dalam format Base64.
            Contoh library PHP: milon/barcode
        -->
        @if(isset($barcodeRectBase64))
            <img src="data:image/png;base64,{{ $barcodeRectBase64 }}" class="barcode-rect">
        @endif
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
                <th>Waktu Scan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $index => $package)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $package->resi_number }}</td>
                <td>{{ $package->created_at->format('d-m-Y H:i:s') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Hormat Kami,</p>
            
            <!-- 
                CATATAN: QR Code sekarang juga berupa gambar dari controller.
                Anda perlu membuat variabel $qrCodeBase64 di controller Anda.
                Contoh library PHP: simplesoftwareio/simple-qrcode
            -->
            @if(isset($qrCodeBase64))
                <img src="data:image/png;base64,{{ $qrCodeBase64 }}" class="barcode-square">
            @endif
            
            <p><strong>( {{ $suratJalan->kontak->nama }} )</strong></p>
        </div>
    </div>

    <!-- Library JavaScript tidak lagi diperlukan untuk membuat PDF -->
</body>
</html>
