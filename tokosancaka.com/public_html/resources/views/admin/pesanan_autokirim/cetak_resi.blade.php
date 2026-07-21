<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $pesanan->order_id }}</title>

    <!-- FontAwesome untuk Icon Tombol -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Script untuk fitur Download Gambar & PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        /* Pengaturan Kertas Thermal 100mm x 150mm */
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #525659;
            display: flex;
            align-items: flex-start; /* Posisi pojok kiri atas */
            justify-content: flex-start; /* Posisi pojok kiri atas */
            gap: 20px; /* Jarak antara resi dan panel tombol */
        }

        /* Container Resi (Ukuran Pasti 100x150mm) */
        .receipt-container {
            background: #fff;
            width: 100mm;
            height: 150mm; /* Tinggi statis 150mm */
            padding: 15px;
            box-sizing: border-box; /* Agar padding tidak menambah lebar 100mm */
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        /* Panel Tombol Aksi */
        .action-panel {
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 250px;
        }
        .action-panel h3 { margin: 0 0 10px 0; font-size: 16px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .btn { padding: 12px 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; color: white; font-size: 13px; transition: 0.2s; width: 100%; justify-content: center;}
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-print { background-color: #3b82f6; } /* Biru */
        .btn-png { background-color: #10b981; } /* Hijau */
        .btn-pdf { background-color: #ef4444; } /* Merah */

        /* --- Elemen Internal Resi --- */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .logo-left img { max-height: 35px; }
        .logo-right img { max-height: 35px; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 10px; }

        .barcode-container { text-align: center; margin-bottom: 15px; }
        .barcode-container img { width: 100%; height: 55px; object-fit: cover; }
        .barcode-text { font-size: 13px; font-weight: bold; margin-top: 3px; letter-spacing: 1px; }

        .address-grid { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .col { width: 48%; font-size: 11px; line-height: 1.4; }
        .col-title { font-weight: 900; font-size: 12px; margin-bottom: 4px; text-transform: uppercase; }
        .name { font-weight: bold; text-transform: uppercase; }

        .middle-grid { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .rincian { font-size: 10px; line-height: 1.5; width: 60%; }
        .rincian strong { font-size: 11px; display: block; margin-bottom: 2px;}
        .qr-box { width: 35%; text-align: center; }
        .qr-box img { width: 80px; height: 80px; margin: 0 auto; border: 1px solid #ccc; padding: 4px; border-radius: 5px; }
        .qr-text { font-size: 9px; font-weight: bold; margin-top: 4px; }

        .total-box { margin-bottom: 10px; }
        .total-title { color: #16a34a; font-size: 13px; font-weight: bold; margin-bottom: 2px; }
        .total-amount { color: #dc2626; font-size: 22px; font-weight: 900; }

        .helpdesk { text-align: center; font-size: 9px; margin-bottom: 10px; line-height: 1.4; }

        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 8px 0; text-align: center; font-size: 9px; }
        .info-item .lbl { font-weight: 900; text-transform: uppercase; margin-bottom: 2px; font-size: 10px;}

        .footer { text-align: center; font-size: 10px; margin-top: 12px; position: absolute; bottom: 15px; left: 0; right: 0; }

        /* Hilangkan UI ekstra saat diprint */
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .action-panel { display: none; }
            .receipt-container { box-shadow: none; border-radius: 0; margin: 0; }
        }
    </style>
</head>
<body>

@php
    // Fungsi sensor nama & HP
    function maskString($string) {
        $length = strlen($string);
        if ($length <= 4) return $string;
        return substr($string, 0, 3) . str_repeat('*', $length - 6) . substr($string, -3);
    }

    $parsedKurir = \App\Helpers\ShippingHelper::parseShippingMethod($pesanan->kurir);
    $resiTrack = $pesanan->awb_number ?? $pesanan->order_id;
@endphp

<!-- WADAH RESI (Target Download/Print) -->
<div class="receipt-container" id="printableArea">

    <!-- HEADER LOGO -->
    <div class="header">
        <div class="logo-left">
            <img src="https://tokosancaka.com/storage/uploads/sancaka.png" alt="Sancaka Express">
        </div>
        <div class="logo-right">
            @if($parsedKurir['logo_url'])
                <img src="{{ $parsedKurir['logo_url'] }}" alt="{{ $parsedKurir['courier_name'] }}">
            @else
                <strong>{{ $parsedKurir['courier_name'] }}</strong>
            @endif
        </div>
    </div>

    <div class="title">NOMOR RESI TOKOSANCAKA.COM</div>

    <!-- BARCODE -->
    <div class="barcode-container">
        <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $resiTrack }}&code=Code128&translate-esc=on" alt="Barcode">
        <div class="barcode-text">{{ strtoupper($resiTrack) }}</div>
    </div>

    <!-- ALAMAT -->
    <div class="address-grid">
        <div class="col">
            <div class="col-title">PENGIRIM:</div>
            <div class="name">{{ maskString($pesanan->pengirim_nama) }}</div>
            <div class="phone">{{ maskString($pesanan->pengirim_hp) }}</div>
            <div style="margin-top: 4px;">{{ strtoupper($pesanan->pengirim_alamat) }}, Kodepos: {{ $pesanan->pengirim_kodepos }}</div>
        </div>
        <div class="col">
            <div class="col-title">PENERIMA:</div>
            <div class="name">{{ maskString($pesanan->penerima_nama) }}</div>
            <div class="phone">{{ maskString($pesanan->penerima_hp) }}</div>
            <div style="margin-top: 4px;">{{ strtoupper($pesanan->penerima_alamat) }}, Kodepos: {{ $pesanan->penerima_kodepos }}</div>
        </div>
    </div>

    <!-- RINCIAN & QR -->
    <div class="middle-grid">
        <div class="rincian">
            <strong>Rincian Paket:</strong>
            - Berat: {{ number_format($pesanan->berat_gram, 2) }} Gram<br>
            - Harga Barang: Rp {{ number_format($pesanan->nilai_barang, 0, ',', '.') }}<br>
            - Isi Paket: {{ $pesanan->deskripsi_barang }}<br>
            - Dimensi: {{ $pesanan->panjang_cm }}x{{ $pesanan->lebar_cm }}x{{ $pesanan->tinggi_cm }} cm<br>
            - Layanan: {{ $pesanan->layanan }}
        </div>
        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://tokosancaka.com/tracking/search?resi={{ $resiTrack }}" alt="QR Code">
            <div class="qr-text">TRACKING ME</div>
        </div>
    </div>

    <!-- TOTAL ONGKIR -->
    <div class="middle-grid" style="margin-bottom: 5px;">
        <div class="total-box">
            <div class="total-title">Total Ongkir:</div>
            <div class="total-amount">Rp {{ number_format($pesanan->ongkir, 0, ',', '.') }}</div>
        </div>
        <div class="helpdesk">
            CV. SANCAKA KARYA HUTAMA<br>
            Helpdesk: 08574580809
        </div>
    </div>

    <!-- INFORMASI BAWAH -->
    <div class="info-grid" style="border-bottom: none; padding-bottom: 0;">
        <div class="info-item">
            <div class="lbl">ORDER ID / RESI</div>
            <div>{{ $resiTrack }}</div>
        </div>
        <div class="info-item">
            <div class="lbl">BERAT</div>
            <div>{{ number_format($pesanan->berat_gram, 2) }} Gram</div>
        </div>
        <div class="info-item">
            <div class="lbl">VOLUME (cm)</div>
            <div>{{ $pesanan->panjang_cm }} x {{ $pesanan->lebar_cm }} x {{ $pesanan->tinggi_cm }}</div>
        </div>
    </div>

    <div class="info-grid" style="border-top: none; padding-top: 8px;">
        <div class="info-item">
            <div class="lbl">LAYANAN</div>
            <div>{{ $pesanan->layanan }}</div>
        </div>
        <div class="info-item">
            <div class="lbl">EKSPEDISI</div>
            <div style="text-transform: uppercase;">{{ $parsedKurir['courier_name'] }}</div>
        </div>
        <div class="info-item">
            <div class="lbl">PEMBAYARAN</div>
            <div style="text-transform: uppercase;">{{ str_replace('_', ' ', $pesanan->metode_pembayaran) }}</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        Terima kasih telah menggunakan <strong>Sancaka Express</strong>.<br>
        <strong>{{ $pesanan->created_at->format('d M Y H:i') }} Kirim Paket DI TOKOSANCAKA.COM</strong>
    </div>

</div>

<!-- PANEL TOMBOL (Tidak akan ikut tercetak) -->
<div class="action-panel">
    <h3>Aksi Resi</h3>
    <button class="btn btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Thermal (100x150)
    </button>
    <button class="btn btn-png" onclick="downloadImage()">
        <i class="fa-solid fa-image"></i> Download Gambar (PNG)
    </button>
    <button class="btn btn-pdf" onclick="downloadPDF()">
        <i class="fa-solid fa-file-pdf"></i> Download File PDF
    </button>
</div>

<!-- SCRIPTS LOGIC DOWNLOAD -->
<script>
    // Konfigurasi Scale HTML2Canvas (Agar gambar jernih/HD)
    const scaleOption = { scale: 3, useCORS: true, logging: false };

    // Fungsi Download PNG
    function downloadImage() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            let link = document.createElement('a');
            link.download = 'Resi_{{ $resiTrack }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    // Fungsi Download PDF (Ukuran Thermal 100x150mm)
    function downloadPDF() {
        const element = document.getElementById('printableArea');
        html2canvas(element, scaleOption).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;

            // Atur canvas jsPDF ukuran 100mm x 150mm (Portrait)
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: [100, 150]
            });

            pdf.addImage(imgData, 'PNG', 0, 0, 100, 150);
            pdf.save('Resi_{{ $resiTrack }}.pdf');
        });
    }
</script>

</body>
</html>
