<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        /* --- RESET & WRAPPER UTAMA --- */
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #525659;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
        }

        /* --- WADAH KERTAS A4 --- */
        .document-container {
            background: #fff;
            width: 210mm;
            /* HAPUS min-height agar tidak memicu 3 halaman jika isinya sedikit */
            padding: 15mm;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            color: #222;
            font-size: 13px;
            line-height: 1.5;
        }

        /* --- PANEL TOMBOL --- */
        .action-panel {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 250px;
            position: sticky;
            top: 20px;
        }
        .action-panel h3 { margin: 0 0 10px 0; font-size: 16px; text-align: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;}
        .btn { padding: 10px 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; color: white; font-size: 13px; justify-content: center; transition: 0.2s;}
        .btn:hover { opacity: 0.8; transform: translateY(-1px); }
        .btn-print { background-color: #3b82f6; }
        .btn-png { background-color: #10b981; }
        .btn-pdf { background-color: #ef4444; }

        /* --- ELEMEN DOKUMEN --- */
        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #222; }
        .header h2 { margin: 0; font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; font-size: 14px; }

        .barcode-rect { text-align: center; margin-bottom: 25px; }
        .barcode-rect img { height: 60px; max-width: 100%; object-fit: contain; }
        .barcode-text { font-size: 13px; font-weight: bold; letter-spacing: 2px; margin-top: 5px; }

        .details { margin-bottom: 25px; }
        .details table { width: 100%; font-size: 14px; }
        .details td { padding: 6px 0; vertical-align: top; }
        .details .label { font-weight: bold; width: 140px; }

        .package-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .package-table th, .package-table td { border: 1px solid #444; padding: 10px; text-align: left; }
        .package-table th { background-color: #f8f9fa; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 12px;}
        .package-table td { font-size: 13px; }

        .footer { margin-top: 40px; font-size: 13px; }
        .footer table { width: 100%; text-align: center; }
        .footer td { width: 50%; vertical-align: top; }
        .qr-code { width: 100px; height: 100px; margin-bottom: 10px; border: 1px solid #ccc; padding: 4px; border-radius: 4px; }

        /* --- FIX PRINT BROWSER (Mencegah blank pages) --- */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #fff; padding: 0; margin:0; display: block; }
            .action-panel { display: none !important; }
            .document-container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
                box-shadow: none;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>

<div class="document-container" id="printableArea">
    <div class="header">
        <h2>SURAT JALAN PICKUP</h2>
        <p>Nomor Resi Referensi: <strong>{{ $suratJalan->kode_surat_jalan }}</strong></p>
    </div>

    <!-- 1. BARCODE DIRENDER DI SERVER (Anti Gagal) -->
    <div class="barcode-rect">
        @php
            // Menggunakan class Picqer yang sudah kamu import di Controller
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcodeBase64 = base64_encode($generator->getBarcode($suratJalan->kode_surat_jalan, $generator::TYPE_CODE_128));
        @endphp
        <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode SJ">
        <div class="barcode-text">{{ $suratJalan->kode_surat_jalan }}</div>
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
                <td class="label">Tanggal Cetak</td>
                <td>: {{ \Carbon\Carbon::parse($suratJalan->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i') }} WIB</td>
            </tr>
            <tr>
                <td class="label">Total Paket</td>
                <td>: <strong>{{ $suratJalan->jumlah_paket }} Paket</strong></td>
            </tr>
        </table>
    </div>

    <table class="package-table">
        <thead>
            <tr>
                <th style="width: 50px;">No.</th>
                <th>Nomor Resi (Tracking ID)</th>
                <th style="width: 200px;">Waktu Scan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($packages as $index => $pkg)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong style="font-size: 15px; letter-spacing: 1px;">{{ $pkg->resi_number }}</strong></td>
                    <td style="text-align: center;">{{ $pkg->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px;">Tidak ada data resi dalam surat jalan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>
                    <strong>Lokasi Pickup Kurir</strong><br><br>
                    <!-- 2. QR MAPS DIRENDER DI SERVER (Menghindari CORS & Blank) -->
                    @if ($suratJalan->latitude && $suratJalan->longitude)
                        @php
                            $mapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
                            $qrMaps = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(1)->generate($mapsUrl));
                        @endphp
                        <img class="qr-code" src="data:image/svg+xml;base64,{{ $qrMaps }}" alt="QR Lokasi">
                    @else
                        <p style="color: #666; font-size: 11px;">Lokasi tidak tersedia</p>
                    @endif
                </td>

                <td>
                    <strong>Hormat Kami,</strong><br><br>
                    <!-- 3. QR SJ DIRENDER DI SERVER -->
                    @php
                        $qrSj = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(1)->generate($suratJalan->kode_surat_jalan));
                    @endphp
                    <img class="qr-code" src="data:image/svg+xml;base64,{{ $qrSj }}" alt="QR Surat Jalan">
                    <br><br>
                    ( <strong>{{ strtoupper($suratJalan->kontak->nama ?? 'Pengirim') }}</strong> )
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="action-panel">
    <h3>Cetak & Unduh</h3>
    <button class="btn btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Dokumen (A4)
    </button>
    <button class="btn btn-png" onclick="downloadImage()">
        <i class="fa-solid fa-image"></i> Simpan sbg Gambar
    </button>
    <button class="btn btn-pdf" onclick="downloadPDF()">
        <i class="fa-solid fa-file-pdf"></i> Download PDF
    </button>
</div>

<script>
    const scaleOption = {
        scale: 2,
        useCORS: true,
        allowTaint: true,
        logging: false
    };

    // Download Gambar
    function downloadImage() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            let link = document.createElement('a');
            link.download = 'SuratJalan_{{ $suratJalan->kode_surat_jalan }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    // Download PDF
    function downloadPDF() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;

            // Setting orientasi A4
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

            // Kalkulasi agar pas di kertas A4 tanpa terpotong
            const pdfWidth = 210;
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('SuratJalan_{{ $suratJalan->kode_surat_jalan }}.pdf');
        });
    }
</script>

</body>
</html>
