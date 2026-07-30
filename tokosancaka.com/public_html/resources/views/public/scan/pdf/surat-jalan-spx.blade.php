<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>

    <!-- FontAwesome untuk Icon Tombol -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Script untuk fitur Download Gambar, PDF & Generator Barcode Lokal -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>

    <style>
        /* Pengaturan Kertas A4 (Standar Surat Jalan) */
        @page {
            size: A4;
            margin: 0;
        }

        /* --- RESPONSIVE WRAPPER --- */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #525659;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: center;
            gap: 20px;
        }

        /* --- WADAH SURAT JALAN --- */
        .document-container {
            background: #fff;
            width: 210mm; /* Lebar A4 */
            min-height: 297mm; /* Tinggi A4 */
            padding: 40px;
            box-sizing: border-box;
            border-radius: 6px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            color: #333;
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
            width: 100%;
            max-width: 280px;
        }
        .action-panel h3 { margin: 0 0 10px 0; font-size: 16px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .btn { padding: 12px 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; color: white; font-size: 13px; transition: all 0.2s; justify-content: center;}
        .btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15);}
        .btn-print { background-color: #3b82f6; }
        .btn-png { background-color: #10b981; }
        .btn-pdf { background-color: #ef4444; }

        /* --- INTERNAL DOKUMEN ELEMEN --- */
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .barcode-rect {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }
        .barcode-rect svg {
            max-height: 50px;
            width: 100%;
            max-width: 350px;
            object-fit: contain;
        }

        .details {
            width: 100%;
            margin-bottom: 20px;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
        }
        .details table { width: 100%; }
        .details td { vertical-align: top; padding: 4px 0; }
        .details .label { font-weight: bold; width: 150px; }

        .package-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .package-table th, .package-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .package-table th { background-color: #f9f9f9; font-weight: bold; }
        .package-table td.center { text-align: center; }

        .footer {
            width: 100%;
            margin-top: 40px;
            font-size: 12px;
        }
        .footer table { width: 100%; }
        .footer td { width: 50%; vertical-align: top; }
        .footer .signature { text-align: center; }

        .qr-code {
            width: 90px;
            height: 90px;
            margin-bottom: 5px;
            border: 1px solid #eee;
            padding: 3px;
            border-radius: 4px;
        }
        .location-text { font-size: 11px; color: #555; }

        /* --- ATURAN KHUSUS SAAT DICETAK PRINTER --- */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { background: #fff; padding: 0; justify-content: flex-start; align-items: flex-start;}
            .action-panel { display: none; }
            .document-container {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                padding: 0;
                width: 100%;
                min-height: auto;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>

<!-- WADAH DOKUMEN (Target Download/Print) -->
<div class="document-container" id="printableArea">

    <div class="header">
        <h2>SURAT JALAN PICKUP</h2>
        <p>Nomor: <strong>{{ $suratJalan->kode_surat_jalan }}</strong></p>
    </div>

    <!-- BARCODE 1D GENERATED LOCALLY VIA JSBARCODE -->
    <div class="barcode-rect">
        <svg id="sj-barcode"></svg>
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
                <th style="width: 40px; text-align: center;">No.</th>
                <th>Nomor Resi</th>
                <th style="width: 180px;">Waktu Scan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($packages as $index => $pkg)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td><strong>{{ $pkg->resi_number }}</strong></td>
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
                {{-- Kolom Kiri: QR Lokasi Menggunakan API Pihak Ketiga --}}
                <td>
                    <strong>Lokasi Pickup Kurir:</strong><br><br>
                    @if ($suratJalan->latitude && $suratJalan->longitude)
                        @php
                            $mapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
                        @endphp
                        <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($mapsUrl) }}" alt="QR Lokasi" crossorigin="anonymous">
                    @else
                        <p class="location-text">Lokasi tidak tersedia</p>
                    @endif
                </td>

                {{-- Kolom Kanan: QR Tanda Tangan Menggunakan API Pihak Ketiga --}}
                <td class="signature">
                    <strong>Hormat Kami,</strong><br><br>
                    <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($suratJalan->kode_surat_jalan) }}" alt="QR Surat Jalan" crossorigin="anonymous">
                    <br><br>
                    ( <strong>{{ $suratJalan->kontak->nama ?? 'Pengirim' }}</strong> )
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- PANEL TOMBOL -->
<div class="action-panel">
    <h3>Aksi Surat Jalan</h3>
    <button class="btn btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak A4
    </button>
    <button class="btn btn-png" onclick="downloadImage()">
        <i class="fa-solid fa-image"></i> Download Gambar (PNG)
    </button>
    <button class="btn btn-pdf" onclick="downloadPDF()">
        <i class="fa-solid fa-file-pdf"></i> Download File PDF
    </button>
</div>

<!-- SCRIPTS LOGIC -->
<script>
    // 1. Generate Barcode Surat Jalan secara Lokal
    document.addEventListener("DOMContentLoaded", function() {
        JsBarcode("#sj-barcode", "{{ $suratJalan->kode_surat_jalan }}", {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 50,
            displayValue: false, // Disembunyikan karena kode sudah tampil di teks header
            margin: 0
        });
    });

    // 2. Konfigurasi Resolusi Download (HD)
    const scaleOption = {
        scale: 2, // Cukup scale 2 untuk ukuran A4 agar file tidak terlalu raksasa
        useCORS: true, // Wajib true agar gambar QR dari API luar bisa dirender
        allowTaint: true,
        logging: false
    };

    // Fungsi Download PNG
    function downloadImage() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            let link = document.createElement('a');
            link.download = 'SuratJalan_{{ $suratJalan->kode_surat_jalan }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    // Fungsi Download PDF (Ukuran A4)
    function downloadPDF() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;

            // Ukuran A4 standar (210 x 297 mm)
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });

            // Menghitung rasio gambar agar proporsional di PDF
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('SuratJalan_{{ $suratJalan->kode_surat_jalan }}.pdf');
        });
    }
</script>

</body>
</html>
