<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Resi - {{ $pesanan->no_resi ?? 'Sancaka Express' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 0; }
        .resi-container { width: 100mm; margin: auto; }
        .resi-top { height: 100mm; border-bottom: 1px dashed #000; padding: 5px; }
        .resi-bottom { height: 50mm; padding: 5px; }
        .resi-header { display: flex; justify-content: space-between; align-items: center; }
        .resi-header .logo { height: 25px; }
        .resi-title { text-align: center; margin: 5px 0; font-size: 14px; font-weight: bold; }
        .barcode { text-align: center; margin: 5px 0; }
        .resi-info { display: flex; justify-content: space-between; margin-top: 10px; }
        .resi-info div { width: 48%; }
        .table-small { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px; }
        .table-small td { border: 1px solid #000; padding: 2px; vertical-align: top; }
        .barcode2d { text-align: center; margin: 10px 0; }
        .small { font-size: 10px; text-align: center; }
    </style>
</head>
<body onload="window.print()">

<div class="resi-container">
    <!-- Bagian Atas (100mm x 100mm) -->
    <div class="resi-top">
        <div class="resi-header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">
            <span class="expedisi">{{ strtoupper($pesanan->expedition ?? '-') }}</span>
        </div>

        <h3 class="resi-title">RESI SANCAKA</h3>
        <div class="barcode">
            {!! DNS1D::getBarcodeHTML($pesanan->no_resi ?? '000000', 'C128', 2, 50) !!}
            <p>{{ $pesanan->no_resi ?? 'No Resi Tidak Ada' }}</p>
        </div>

        <div class="resi-info">
            <div class="pengirim">
                <strong>PENGIRIM</strong><br>
                {{ $pesanan->pengirim_nama }}<br>
                {{ $pesanan->pengirim_telp }}<br>
                {{ $pesanan->pengirim_alamat }}
            </div>
            <div class="penerima">
                <strong>PENERIMA</strong><br>
                {{ $pesanan->penerima_nama }}<br>
                {{ $pesanan->penerima_telp }}<br>
                {{ $pesanan->penerima_alamat }}
            </div>
        </div>

        <table class="table-small mt-2">
            <tr>
                <td><strong>ORDER ID</strong><br>{{ $pesanan->order_number }}</td>
                <td><strong>BERAT</strong><br>{{ number_format($pesanan->berat, 2) }} gr</td>
                <td><strong>VOLUME</strong><br>{{ $pesanan->panjang }}x{{ $pesanan->lebar }}x{{ $pesanan->tinggi }} cm</td>
            </tr>
            <tr>
                <td><strong>LAYANAN</strong><br>{{ strtoupper($pesanan->layanan ?? 'REGULER') }}</td>
                <td colspan="2"><strong>EKSPEDISI</strong><br>{{ strtoupper($pesanan->expedition ?? '-') }}</td>
            </tr>
        </table>
    </div>

    <!-- Bagian Bawah (100mm x 50mm) -->
    <div class="resi-bottom">
        <p><strong>Ekspedisi:</strong> {{ strtoupper($pesanan->expedition ?? '-') }}</p>
        <p><strong>Detail Paket:</strong></p>
        <ul>
            <li>Nama Barang: {{ $pesanan->nama_barang ?? '-' }}</li>
            <li>Jumlah: {{ $pesanan->jumlah ?? 1 }}</li>
            <li>Berat: {{ number_format($pesanan->berat, 2) }} gr</li>
            <li>Volume: {{ $pesanan->panjang }}x{{ $pesanan->lebar }}x{{ $pesanan->tinggi }} cm</li>
        </ul>

        <p><strong>Pengirim:</strong> {{ $pesanan->pengirim_nama }} ({{ $pesanan->pengirim_telp }})</p>
        <p><strong>Penerima:</strong> {{ $pesanan->penerima_nama }} ({{ $pesanan->penerima_telp }})</p>
        <p><strong>No Resi:</strong> {{ $pesanan->no_resi ?? '-' }}</p>

        <div class="barcode2d">
            {!! DNS2D::getBarcodeHTML($pesanan->no_resi ?? '000000', 'QRCODE', 4, 4) !!}
        </div>

        <p class="small">
            Terima kasih telah menggunakan layanan <br> Sancaka Express.
        </p>
        <p class="small">
            {{ \Carbon\Carbon::now()->format('d M Y H:i') }}
        </p>
    </div>
</div>

</body>
</html>
