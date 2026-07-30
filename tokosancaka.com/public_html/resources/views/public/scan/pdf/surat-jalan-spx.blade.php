<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>

    <!-- Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- RESET & GLOBAL --- */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e5e7eb; /* Background abu-abu lembut untuk layar */
            color: #1f2937;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 30px 20px;
            gap: 30px;
        }

        /* --- KERTAS A4 --- */
        .document-wrapper {
            background-color: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 4px;
            position: relative;
        }

        /* --- HEADER DOKUMEN --- */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #ea580c; /* Aksen oranye ala logistik */
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .doc-title h1 {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111827;
            margin-bottom: 5px;
        }
        .doc-title p {
            font-size: 13px;
            color: #6b7280;
        }
        .doc-barcode {
            text-align: right;
        }
        .doc-barcode img {
            height: 50px;
            object-fit: contain;
        }
        .doc-barcode p {
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
            letter-spacing: 2px;
        }

        /* --- INFO PENGIRIM --- */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 13px;
            line-height: 1.6;
        }
        .info-box {
            width: 48%;
        }
        .info-label {
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 500;
            color: #111827;
        }

        /* --- TABEL RESI --- */
        .table-container {
            margin-bottom: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        thead th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            text-align: left;
            padding: 12px;
            border-top: 1px solid #d1d5db;
            border-bottom: 2px solid #9ca3af;
            text-transform: uppercase;
            font-size: 12px;
        }
        tbody td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }
        .col-no { width: 50px; text-align: center; }
        .col-resi { font-weight: 600; font-size: 14px; letter-spacing: 0.5px; }
        .col-waktu { width: 180px; text-align: right; }

        /* --- FOOTER & TTD --- */
        .doc-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .sign-box {
            text-align: center;
            width: 200px;
            font-size: 13px;
        }
        .sign-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #374151;
        }
        .qr-box {
            width: 90px;
            height: 90px;
            margin: 0 auto 15px auto;
            border: 1px solid #e5e7eb;
            padding: 5px;
            border-radius: 8px;
        }
        .qr-box img {
            width: 100%;
            height: 100%;
        }
        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
        }

        /* --- PANEL TOMBOL (SIDEBAR) --- */
        .action-panel {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            width: 280px;
            position: sticky;
            top: 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .action-panel h3 {
            font-size: 15px;
            text-align: center;
            color: #111827;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .btn {
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            color: white;
            font-family: inherit;
        }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn:active { transform: translateY(0); }
        .btn-print { background-color: #ea580c; } /* Oranye */
        .btn-png { background-color: #0ea5e9; } /* Biru Muda */
        .btn-pdf { background-color: #ef4444; } /* Merah */

        /* --- PENGATURAN CETAK (PRINT) --- */
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body {
                background: none;
                padding: 0;
                margin: 0;
                display: block; /* Matikan flex agar tidak berantakan di print */
            }
            .action-panel { display: none !important; } /* Sembunyikan tombol total */
            .document-wrapper {
                width: 100%;
                min-height: auto;
                padding: 15mm 20mm; /* Margin dalam saat di-print */
                margin: 0;
                box-shadow: none;
                border-radius: 0;
                page-break-after: avoid; /* Cegah halaman kosong berlebih */
            }
        }
    </style>
</head>
<body>

    <!-- KERTAS A4 -->
    <div class="document-wrapper" id="printableArea">

        <!-- Header -->
        <div class="doc-header">
            <div class="doc-title">
                <h1>Surat Jalan Pickup</h1>
                <p>Dokumen Bukti Serah Terima Paket</p>
            </div>
            <div class="doc-barcode">
                @php
                    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                    $barcodeBase64 = base64_encode($generator->getBarcode($suratJalan->kode_surat_jalan, $generator::TYPE_CODE_128));
                @endphp
                <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode">
                <p>{{ $suratJalan->kode_surat_jalan }}</p>
            </div>
        </div>

        <!-- Info Kontak -->
        <div class="info-section">
            <div class="info-box">
                <div class="info-label">Informasi Pengirim</div>
                <div class="info-value">{{ strtoupper($suratJalan->kontak->nama ?? 'N/A') }}</div>
                <div>{{ $suratJalan->kontak->alamat ?? 'Alamat tidak tersedia' }}</div>
            </div>
            <div class="info-box" style="text-align: right;">
                <div class="info-label">Detail Cetak</div>
                <div class="info-value">Total: {{ $suratJalan->jumlah_paket }} Paket</div>
                <div>{{ \Carbon\Carbon::parse($suratJalan->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i') }} WIB</div>
            </div>
        </div>

        <!-- Tabel Paket -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Nomor Resi (Tracking ID)</th>
                        <th class="col-waktu">Waktu Scan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $index => $pkg)
                        <tr>
                            <td class="col-no">{{ $index + 1 }}</td>
                            <td class="col-resi">{{ $pkg->resi_number }}</td>
                            <td class="col-waktu">{{ $pkg->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 30px; color: #6b7280;">Tidak ada data resi dalam surat jalan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer / TTD -->
        <div class="doc-footer">
            <div class="sign-box">
                <div class="sign-title">Lokasi Pickup Kurir</div>
                <div class="qr-box">
                    @if ($suratJalan->latitude && $suratJalan->longitude)
                        @php
                            $mapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
                            $qrMaps = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(90)->margin(0)->generate($mapsUrl));
                        @endphp
                        <img src="data:image/svg+xml;base64,{{ $qrMaps }}" alt="QR Maps">
                    @else
                        <div style="font-size: 10px; padding-top: 30px; color: #9ca3af;">No Data</div>
                    @endif
                </div>
                <div style="font-size: 11px; color: #6b7280;">Scan untuk Google Maps</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">Hormat Kami,</div>
                <div class="qr-box">
                    @php
                        $qrSj = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(90)->margin(0)->generate($suratJalan->kode_surat_jalan));
                    @endphp
                    <img src="data:image/svg+xml;base64,{{ $qrSj }}" alt="QR SJ">
                </div>
                <div class="sign-name">{{ $suratJalan->kontak->nama ?? 'Pengirim' }}</div>
            </div>
        </div>

    </div>

    <!-- PANEL TOMBOL (Akan hilang saat di-print) -->
    <div class="action-panel no-print">
        <h3>Aksi Dokumen</h3>
        <button class="btn btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Cetak Dokumen (A4)
        </button>
        <button class="btn btn-png" onclick="downloadImage()">
            <i class="fa-solid fa-image"></i> Simpan Gambar (PNG)
        </button>
        <button class="btn btn-pdf" onclick="downloadPDF()">
            <i class="fa-solid fa-file-pdf"></i> Download PDF
        </button>
        <div style="font-size: 11px; text-align: center; color: #9ca3af; margin-top: 5px;">
            Gunakan Chrome/Edge untuk hasil cetak terbaik.
        </div>
    </div>

    <!-- SCRIPT DOWNLOADER -->
    <script>
        const scaleOption = {
            scale: 2, // Resolusi tinggi
            useCORS: true,
            allowTaint: true,
            logging: false,
            backgroundColor: '#ffffff' // Pastikan background putih saat diexport
        };

        function downloadImage() {
            const element = document.getElementById('printableArea');
            html2canvas(element, scaleOption).then(canvas => {
                let link = document.createElement('a');
                link.download = 'SuratJalan_{{ $suratJalan->kode_surat_jalan }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        function downloadPDF() {
            const element = document.getElementById('printableArea');
            html2canvas(element, scaleOption).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;

                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

                const pdfWidth = 210;
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('SuratJalan_{{ $suratJalan->kode_surat_jalan }}.pdf');
            });
        }
    </script>

</body>
</html>
