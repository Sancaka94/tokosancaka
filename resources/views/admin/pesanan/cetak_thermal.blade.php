<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .resi-wrapper {
            width: 100mm;
            border: 1px dashed #999;
            margin: auto;
            page-break-after: always;
        }

        .resi-atas {
            width: 100mm;
            height: 100mm;
            padding: 5px;
            box-sizing: border-box;
        }

        .resi-bawah {
            width: 100mm;
            height: 50mm;
            padding: 5px;
            box-sizing: border-box;
        }

        .title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .flex {
            display: flex;
            justify-content: space-between;
        }

        .barcode, .qrcode {
            text-align: center;
            margin: 5px 0;
        }

        .section {
            border-top: 1px dashed #999;
            margin-top: 5px;
            padding-top: 5px;
        }

        @media print {
            .resi-wrapper {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="resi-wrapper">
    <!-- Bagian Atas -->
    <div class="resi-atas">
        <div class="flex">
            <img src="{{ asset('logo.png') }}" alt="Logo" height="20">
            <div><strong>{{ $order->ekspedisi }}</strong></div>
        </div>

        <div class="title">RESI SANCAKA</div>

        <div class="barcode">
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($order->no_resi, 'C128') }}" height="40">
            <div>{{ $order->no_resi }}</div>
        </div>

        <div class="flex">
            <div>
                <strong>PENGIRIM</strong><br>
                {{ $order->pengirim_nama }}<br>
                {{ $order->pengirim_telp }}<br>
                {{ $order->pengirim_alamat }}
            </div>
            <div>
                <strong>PENERIMA</strong><br>
                {{ $order->penerima_nama }}<br>
                {{ $order->penerima_telp }}<br>
                {{ $order->penerima_alamat }}
            </div>
        </div>

        <div class="section">
            <div>ORDER ID: {{ $order->kode }}</div>
            <div>Berat: {{ $order->berat }} gr</div>
            <div>Volume: {{ $order->panjang }}x{{ $order->lebar }}x{{ $order->tinggi }} cm</div>
            <div>Layanan: {{ $order->layanan }}</div>
            <div>Ekspedisi: {{ $order->ekspedisi }}</div>
        </div>

        <div class="section">
            <strong>Detail Paket:</strong><br>
            Nama Barang: {{ $order->nama_barang }}<br>
            Jumlah: {{ $order->jumlah }}<br>
            Berat Total: {{ $order->berat }} gr<br>
            Volume: {{ $order->panjang }}x{{ $order->lebar }}x{{ $order->tinggi }} cm
        </div>

        <div class="qrcode">
            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($order->no_resi, 'QRCODE') }}" width="80">
        </div>
    </div>
</div>

<div class="resi-wrapper">
    <!-- Bagian Bawah -->
    <div class="resi-bawah">
        <div class="title">RESI SANCAKA (Ringkas)</div>

        <div>Resi: {{ $order->no_resi }}</div>
        <div>Pengirim: {{ $order->pengirim_nama }} ({{ $order->pengirim_telp }})</div>
        <div>Penerima: {{ $order->penerima_nama }} ({{ $order->penerima_telp }})</div>
        <div>Ekspedisi: {{ $order->ekspedisi }} - {{ $order->layanan }}</div>
        <div>Berat: {{ $order->berat }} gr</div>

        <div class="qrcode">
            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($order->no_resi, 'QRCODE') }}" width="60">
        </div>
    </div>
</div>

</body>
</html>
