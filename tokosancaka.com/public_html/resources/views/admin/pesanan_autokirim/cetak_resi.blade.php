<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $pesanan->order_id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 0; background: #525659; display: flex; justify-content: center; }
        .receipt-container { background: #fff; width: 100mm; max-width: 400px; padding: 15px; margin: 20px auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); position: relative; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .logo-left img { max-height: 40px; }
        .logo-right img { max-height: 40px; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 10px; }

        .barcode-container { text-align: center; margin-bottom: 15px; }
        .barcode-container img { width: 100%; height: 60px; object-fit: cover; }
        .barcode-text { font-size: 14px; font-weight: bold; margin-top: 5px; letter-spacing: 1px; }

        .address-grid { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 15px; }
        .col { width: 48%; font-size: 12px; line-height: 1.4; }
        .col-title { font-weight: 900; font-size: 14px; margin-bottom: 5px; text-transform: uppercase; }
        .name { font-weight: bold; text-transform: uppercase; }

        .middle-grid { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .rincian { font-size: 11px; line-height: 1.5; width: 60%; }
        .rincian strong { font-size: 12px; display: block; margin-bottom: 3px;}
        .qr-box { width: 35%; text-align: center; }
        .qr-box img { width: 90px; height: 90px; margin: 0 auto; border: 1px solid #ccc; padding: 5px; border-radius: 5px; }
        .qr-text { font-size: 10px; font-weight: bold; margin-top: 5px; }

        .total-box { margin-bottom: 15px; }
        .total-title { color: #16a34a; font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .total-amount { color: #dc2626; font-size: 24px; font-weight: 900; }

        .helpdesk { text-align: center; font-size: 10px; margin-bottom: 15px; line-height: 1.4; }

        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 10px 0; text-align: center; font-size: 10px; }
        .info-item .lbl { font-weight: 900; text-transform: uppercase; margin-bottom: 3px; font-size: 11px;}

        .footer { text-align: center; font-size: 12px; margin-top: 15px; }

        @media print {
            body { background: #fff; }
            .receipt-container { box-shadow: none; margin: 0; padding: 0; width: 100%; max-width: 100%; border-radius: 0; }
        }
    </style>
</head>
<body onload="window.print()">

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

<div class="receipt-container">

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
        <!-- Generate Barcode Code128 menggunakan API eksternal agar tidak perlu install package -->
        <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $resiTrack }}&code=Code128&translate-esc=on" alt="Barcode">
        <div class="barcode-text">{{ strtoupper($resiTrack) }}</div>
    </div>

    <!-- ALAMAT -->
    <div class="address-grid">
        <div class="col">
            <div class="col-title">PENGIRIM:</div>
            <div class="name">{{ maskString($pesanan->pengirim_nama) }}</div>
            <div class="phone">{{ maskString($pesanan->pengirim_hp) }}</div>
            <div style="margin-top: 5px;">{{ strtoupper($pesanan->pengirim_alamat) }}, Kodepos: {{ $pesanan->pengirim_kodepos }}</div>
        </div>
        <div class="col">
            <div class="col-title">PENERIMA:</div>
            <div class="name">{{ maskString($pesanan->penerima_nama) }}</div>
            <div class="phone">{{ maskString($pesanan->penerima_hp) }}</div>
            <div style="margin-top: 5px;">{{ strtoupper($pesanan->penerima_alamat) }}, Kodepos: {{ $pesanan->penerima_kodepos }}</div>
        </div>
    </div>

    <!-- RINCIAN & QR -->
    <div class="middle-grid">
        <div class="rincian">
            <strong>Rincian Paket:</strong>
            - Berat: {{ number_format($pesanan->berat_gram, 2) }} Gram<br>
            - Harga Barang: Rp {{ number_format($pesanan->nilai_barang, 0, ',', '.') }}<br>
            - Isi Paket: {{ $pesanan->deskripsi_barang }}<br>
            - Dimensi: {{ $pesanan->panjang_cm }} x {{ $pesanan->lebar_cm }} x {{ $pesanan->tinggi_cm }} cm<br>
            - Layanan: {{ $pesanan->layanan }}
        </div>
        <div class="qr-box">
            <!-- Generate QR Code mengarah ke link tracking -->
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://tokosancaka.com/tracking/search?resi={{ $resiTrack }}" alt="QR Code">
            <div class="qr-text">TRACKING ME</div>
        </div>
    </div>

    <!-- TOTAL ONGKIR -->
    <div class="middle-grid" style="margin-bottom: 10px;">
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

    <div class="info-grid" style="border-top: none; padding-top: 10px;">
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

</body>
</html>
