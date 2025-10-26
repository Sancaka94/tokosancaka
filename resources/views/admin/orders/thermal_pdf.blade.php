<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Label Pengiriman' }}</title>
    <style>
        /* * Gaya CSS SANGAT MINIMAL untuk printer thermal. 
         * Hindari warna, background, margin/padding kompleks.
         * Ukuran font dan line-height mungkin perlu disesuaikan 
         * tergantung printer Anda.
         * Ukuran kertas diatur di Controller (setPaper).
        */
        body {
            font-family: Arial, sans-serif; /* Font dasar yang umum */
            font-size: 10pt; /* Ukuran font umum untuk thermal */
            line-height: 1.2;
            color: #000;
            margin: 2mm; /* Margin kecil */
            padding: 0;
            width: 76mm; /* Perkiraan lebar umum (80mm - margin) */
        }
        .label-container {
            width: 100%;
            border: 1px solid #000; /* Border tipis jika perlu */
            padding: 3mm;
            box-sizing: border-box; /* Agar padding tidak menambah lebar */
        }
        h1, h2, h3, p, strong {
            margin: 0 0 4px 0;
            padding: 0;
        }
        h3 {
            font-size: 11pt;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 2px;
        }
        .address p {
            margin-bottom: 1px;
        }
        .courier-info {
            text-align: center;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        .courier-info .service {
            font-size: 14pt; /* Buat nama layanan lebih besar */
            font-weight: bold;
        }
         .courier-info .courier {
            font-size: 11pt; 
            font-weight: bold;
        }
        .item-info {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #000;
        }
        .barcode {
             text-align: center;
             margin-top: 8px;
        }
        /* Styling untuk barcode (jika menggunakan library JS/CSS) */
        /* svg.barcode-element { width: 100%; height: 50px; } */ 
        
        .cod-amount {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-top: 5px;
            border: 1px solid #000;
            padding: 3px;
        }
    </style>
</head>
<body>
    @php
        // Ambil info pengiriman dari helper (jika belum ada di controller)
        $shippingInfo = $shippingInfo ?? \App\Helpers\ShippingHelper::parseShippingMethod($order->shipping_method ?? $order->expedition);
        
        // Cek apakah COD
        $isCOD = isset($order->cod_fee) && $order->cod_fee > 0 || (isset($order->payment_method) && strtoupper($order->payment_method) == 'CODBARANG');
        $codAmount = $order->total_amount ?? $order->price ?? 0;
    @endphp

    <div class="label-container">
        
        <div class="courier-info">
             <div class="courier">{{ $shippingInfo['courier_name'] ?? 'N/A' }}</div>
             <div class="service">{{ $shippingInfo['service_name'] ?? 'REGULAR' }}</div>
             @if ($isCOD)
                <div class="cod-amount">COD: Rp{{ number_format($codAmount, 0, ',', '.') }}</div>
             @endif
        </div>

        <div class="address">
            <h3>Kepada:</h3>
            <p><strong>{{ $order->receiver_name ?? ($order->user->nama_lengkap ?? 'Penerima') }}</strong></p>
            <p>{{ $order->receiver_address ?? ($order->shipping_address ?? 'Alamat Penerima') }}</p>
            {{-- Tambahkan No HP Penerima jika ada/perlu --}}
            <p>Telp: {{ $order->receiver_phone ?? ($order->user->no_wa ?? '-') }}</p>
        </div>

        <div class="address" style="margin-top: 8px;">
            <h3>Dari:</h3>
            <p><strong>{{ $order->sender_name ?? ($order->store->name ?? 'Pengirim') }}</strong></p>
            <p>{{ $order->sender_address ?? ($order->store->address_detail ?? 'Alamat Pengirim') }}</p>
            {{-- Tambahkan No HP Pengirim jika ada/perlu --}}
            <p>Telp: {{ $order->sender_phone ?? '-' }}</p>
        </div>

        <div class="item-info">
             <p><strong>Isi:</strong> {{ $order->item_description ?? ($order->items->first()->product->name ?? 'Paket') }}</p>
             {{-- Bisa tambahkan info berat/dimensi jika perlu --}}
             {{-- <p>Berat: ... gr</p> --}}
        </div>

        <div class="barcode">
            {{-- 
              Tempat untuk Barcode. 
              Anda perlu library (cth: Picqer Barcode Generator di backend atau JsBarcode di frontend) 
              untuk men-generate SVG atau gambar barcode dari nomor resi.
              Ini HANYA placeholder teks.
            --}}
            <p style="font-size: 9pt;">Resi: {{ $order->resi ?? ($order->shipping_reference ?? $order->invoice_number) }}</p>
            {{-- Contoh jika menggunakan SVG inline (ganti dengan SVG barcode asli): --}}
            {{-- <svg class="barcode-element">...</svg> --}} 
            <p style="font-weight: bold; font-size: 14pt;">{{ $order->resi ?? ($order->shipping_reference ?? $order->invoice_number) }}</p>
        </div>

    </div>
</body>
</html>
