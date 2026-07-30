<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $suratJalan->kode_surat_jalan }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>

    <style>
        /* --- RESET & WRAPPER UTAMA --- */
        body {
            font-family: Arial, sans-serif;
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
            min-height: 297mm;
            padding: 15mm;
            box-sizing: border-box;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            color: #333;
            font-size: 13px;
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
        .action-panel h3 { margin: 0 0 10px 0; font-size: 16px; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .btn { padding: 12px 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; color: white; font-size: 13px; justify-content: center;}
        .btn:hover { opacity: 0.9; }
        .btn-print { background-color: #3b82f6; }
        .btn-png { background-color: #10b981; }
        .btn-pdf { background-color: #ef4444; }

        /* --- ELEMEN DOKUMEN --- */
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; font-size: 14px; }

        .barcode-rect { text-align: center; margin-bottom: 20px; }
        .barcode-rect svg { max-height: 60px; max-width: 100%; }

        .details { margin-bottom: 20px; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 12px 0; }
        .details table { width: 100%; }
        .details td { padding: 4px 0; vertical-align: top; }
        .details .label { font-weight: bold; width: 130px; }

        .package-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .package-table th, .package-table td { border: 1px solid #aaa; padding: 8px; text-align: left; }
        .package-table th { background-color: #f0f0f0; }

        .footer { margin-top: 40px; font-size: 12px; }
        .footer table { width: 100%; text-align: center; }
        .footer td { width: 50%; vertical-align: top; }
        .qr-code { width: 90px; height: 90px; border: 1px solid #eee; padding: 3px; border-radius: 4px; margin-bottom: 5px; }

        /* --- FIX 3 HALAMAN SAAT PRINT BROWSER --- */
        @media print {
            @page { size: A4 portrait; margin: 5mm; }
            body { background: #fff; padding: 0; display: block; }
            .action-panel { display: none !important; }
            .document-container {
                width: 100%;
                min-height: auto; /* Mencegah tumpahan ke halaman baru */
                padding: 0;
                margin: 0;
                box-shadow: none;
                page-break-after: avoid; /* Memaksa berhenti di 1 halaman jika cukup */
            }
        }
    </style>
</head>
<body>

<div class="document-container" id="printableArea">
    <div class="header">
        <h2>SURAT JALAN PICKUP</h2>
        <p>Nomor: <strong>{{ $suratJalan->kode_surat_jalan }}</strong></p>
    </div>

    <!-- Container untuk Barcode dari JavaScript -->
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
                <th style="width: 50px; text-align: center;">No.</th>
                <th>Nomor Resi</th>
                <th style="width: 180px;">Waktu Scan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($packages as $index => $pkg)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $pkg->resi_number }}</strong></td>
                    <td>{{ $pkg->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Tidak ada data resi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>
                    <strong>Lokasi Pickup Kurir:</strong><br><br>
                    @if ($suratJalan->latitude && $suratJalan->longitude)
                        @php
                            $mapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
                        @endphp
                        <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($mapsUrl) }}" alt="QR Lokasi" crossorigin="anonymous">
                    @else
                        <p style="color: #666; font-size: 11px;">Lokasi tidak tersedia</p>
                    @endif
                </td>

                <td>
                    <strong>Hormat Kami,</strong><br><br>
                    <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($suratJalan->kode_surat_jalan) }}" alt="QR Surat Jalan" crossorigin="anonymous">
                    <br><br>
                    ( <strong>{{ $suratJalan->kontak->nama ?? 'Pengirim' }}</strong> )
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="action-panel">
    <h3>Aksi Surat Jalan</h3>
    <button class="btn btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Dokumen
    </button>
    <button class="btn btn-png" onclick="downloadImage()">
        <i class="fa-solid fa-image"></i> Download Gambar (PNG)
    </button>
    <button class="btn btn-pdf" onclick="downloadPDF()">
        <i class="fa-solid fa-file-pdf"></i> Download PDF
    </button>
</div>

<script>
    // 1. Eksekusi Barcode secara Lokal
    document.addEventListener("DOMContentLoaded", function() {
        JsBarcode("#sj-barcode", "{{ $suratJalan->kode_surat_jalan }}", {
            format: "CODE128",
            lineColor: "#000",
            width: 2.5,
            height: 60,
            displayValue: false, // Disembunyikan karena sudah ada di header
            margin: 0
        });
    });

    const scaleOption = { scale: 2, useCORS: true, allowTaint: true, logging: false };

    // 2. Download Gambar (PNG)
    function downloadImage() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            let link = document.createElement('a');
            link.download = 'SuratJalan_{{ $suratJalan->kode_surat_jalan }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    // 3. Download PDF Presisi A4
    function downloadPDF() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('SuratJalan_{{ $suratJalan->kode_surat_jalan }}.pdf');
        });
    }
</script>

</body>
</html>
