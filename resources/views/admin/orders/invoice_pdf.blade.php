<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Faktur Pesanan' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* HEADER */
        .header {
            width: 100%;
            border-bottom: 2px solid #4a5568;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        /* LOGO & COMPANY NAME */
        .header-logo {
            float: left;
            width: 60%; 
        }
        .header-logo img {
            float: left;
            height: 50px; 
            margin-right: 15px;
            width: auto;
        }
        .company-details {
            float: left;
        }
        .company-details h1 {
            margin: 0;
            color: #2d3748;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1; 
        }
        .company-details p {
            margin: 4px 0 0;
            font-size: 11px;
            color: #718096;
        }

        /* INVOICE LABEL (KANAN) */
        .invoice-label {
            text-align: right;
            float: right;
            width: 30%;
        }
        .invoice-label h2 {
            margin: 0;
            font-size: 30px;
            color: #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 2px;
            line-height: 1;
        }
        .invoice-status {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            color: #fff;
            background-color: #0f9d58;
            font-size: 10px;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 5px;
        }

        /* INFO GRID */
        .info-section {
            width: 100%;
            margin-bottom: 30px;
            clear: both; /* Pastikan tidak ketumpuk header */
            padding-top: 10px;
        }
        .layout-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
        }
        .layout-table td {
            vertical-align: top;
            width: 33.33%;
            padding: 0;
        }
        
        .info-box {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 6px;
            height: 170px;
        }
        .box-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 10px;
            border-bottom: 1px solid #cbd5e0;
            padding-bottom: 5px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .label-col {
            width: 85px;
            color: #718096;
            font-size: 11px;
        }
        .val-col {
            font-weight: bold;
            color: #2d3748;
            font-size: 12px;
        }

        .address-text {
            font-size: 11px;
            color: #2d3748;
            line-height: 1.4;
        }
        .address-text strong {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
            color: #1a202c;
        }

        /* TABEL BARANG */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table.items th {
            background-color: #2d3748;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
            border: 1px solid #2d3748;
        }
        table.items td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            vertical-align: top;
        }
        table.items tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        /* FOOTER / TOTALS */
        .footer-layout {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-layout td {
            vertical-align: top;
        }
        .notes-cell {
            width: 55%;
            padding-right: 20px;
        }
        .notes-box {
            font-size: 11px;
            color: #718096;
            background-color: #fff;
            border: 1px dashed #cbd5e0;
            padding: 10px;
            border-radius: 4px;
        }
        .totals-cell {
            width: 45%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 0;
            border-bottom: 1px solid #edf2f7;
        }
        .totals-table .label {
            color: #718096;
            font-weight: 500;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
            color: #2d3748;
        }
        .grand-total-box {
            background-color: #2d3748;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* UTILS */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .badge {
            background: #e2e8f0;
            color: #2d3748;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        
        {{-- HEADER dengan LOGO --}}
        <div class="header clearfix">
            
            {{-- KIRI: Logo & Nama Perusahaan --}}
            <div class="header-logo">
         
              
                <div class="company-details">
                    <h1>{{ config('app.name', 'Toko Sancaka') }}</h1>
                    <p> CV. SANCAKA KARYA HUTAMA</br>
                        Jl. Dr. Wahidin No.18A, Ketanggi, Ngawi, Jawa Timur 63211<br>
                        WA: 085-745-808-809 | Email: admin@tokosancaka.com</br>
                        Website: TOKOSANCAKA.COM
                    </p>
                </div>
            </div>

            {{-- KANAN: Label Invoice & Status --}}
            <div class="invoice-label">
                <h2>INVOICE</h2>
                @php
                    $status = ucfirst($order->status ?? $order->status_pesanan ?? '-');
                @endphp
                <div class="text-right">
                    <span class="invoice-status">{{ $status }}</span>
                </div>
            </div>
        </div>

        {{-- INFO SECTION --}}
        <div class="info-section">
            <table class="layout-table">
                <tr>
                    {{-- KOLOM 1: INFO PESANAN --}}
                    <td>
                        <div class="info-box">
                            <div class="box-title">Data Pesanan</div>
                            <table class="detail-table">
                                <tr>
                                    <td class="label-col">No. Invoice</td>
                                    <td class="val-col">#{{ $order->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td class="label-col">Tgl. Pesanan</td>
                                    <td class="val-col">{{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="label-col">Ekspedisi</td>
                                    <td class="val-col">
                                        @php
                                            $expRaw = $order->shipping_method;
                                            $expParts = explode('-', $expRaw);
                                            $expName = isset($expParts[1]) ? strtoupper($expParts[1]) : strtoupper($expRaw);
                                            $service = isset($expParts[2]) ? strtoupper($expParts[2]) : '';
                                        @endphp
                                        {{ $expName }} {{ $service }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-col">No. Resi</td>
                                    <td class="val-col">{{ $order->shipping_reference ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>

                    {{-- PREPARE DATA PENGIRIM & PENERIMA --}}
                    @php
                        // Pengirim
                        $sName = $order->store->name ?? $order->sender_name ?? config('app.name');
                        $sPhone = $order->store->phone ?? $order->sender_phone ?? '-';
                        $sAddr = $order->store->address_detail ?? $order->sender_address ?? '-';
                        $sVill = $order->store->village ?? $order->sender_village ?? '';
                        $sDist = $order->store->district ?? $order->sender_district ?? '';
                        $sReg = $order->store->regency ?? $order->sender_regency ?? '';
                        $sProv = $order->store->province ?? $order->sender_province ?? '';
                        $sPost = $order->store->postal_code ?? $order->sender_postal_code ?? '';

                        // Penerima
                        $rName = $order->user->nama_lengkap ?? $order->receiver_name ?? 'Guest';
                        $rPhone = $order->user->no_wa ?? $order->receiver_phone ?? '-';
                        $rAddr = $order->user->address_detail ?? $order->receiver_address ?? $order->shipping_address ?? '-';
                        $rVill = $order->user->village ?? $order->receiver_village ?? '';
                        $rDist = $order->user->district ?? $order->receiver_district ?? '';
                        $rReg = $order->user->regency ?? $order->receiver_regency ?? '';
                        $rProv = $order->user->province ?? $order->receiver_province ?? '';
                        $rPost = $order->user->postal_code ?? $order->receiver_postal_code ?? '';
                    @endphp

                    {{-- KOLOM 2: PENGIRIM --}}
                    <td>
                        <div class="info-box">
                            <div class="box-title">Pengirim (Seller)</div>
                            <div class="address-text">
                                <strong>{{ $sName }}</strong>
                                <div>{{ $sPhone }}</div>
                                <br>
                                {{ $sAddr }}<br>
                                @if($sVill) DS. {{ $sVill }}, @endif 
                                @if($sDist) KEC. {{ $sDist }} @endif<br>
                                @if($sReg) {{ $sReg }} @endif<br>
                                @if($sProv) {{ $sProv }} @endif 
                                @if($sPost) - {{ $sPost }} @endif
                            </div>
                        </div>
                    </td>

                    {{-- KOLOM 3: PENERIMA --}}
                    <td>
                        <div class="info-box">
                            <div class="box-title">Penerima (Buyer)</div>
                            <div class="address-text">
                                <strong>{{ $rName }}</strong>
                                <div>{{ $rPhone }}</div>
                                <br>
                                {{ $rAddr }}<br>
                                @if($rVill) DS. {{ $rVill }}, @endif 
                                @if($rDist) KEC. {{ $rDist }} @endif<br>
                                @if($rReg) {{ $rReg }} @endif<br>
                                @if($rProv) {{ $rProv }} @endif 
                                @if($rPost) - {{ $rPost }} @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- TABEL BARANG --}}
        <table class="items">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="45%">Deskripsi Barang</th>
                    <th width="15%" class="text-center">Berat</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="25%" class="text-right">Harga Satuan</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($order->items) && count($order->items) > 0)
                    @foreach($order->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong style="color: #2d3748;">{{ $item->product->name ?? 'Produk' }}</strong>
                                @if(isset($item->variant) && $item->variant)
                                    <br><span class="badge" style="margin-top: 2px;">Varian: {{ $item->variant->combination_string }}</span>
                                @endif
                                @if(isset($item->product->sku_code))
                                    <br><small style="color: #718096;">SKU: {{ $item->product->sku_code }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ ($item->product->weight ?? 0) * $item->quantity }} gr</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">Rp{{ number_format($item->price_per_item ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @else
                    {{-- Fallback untuk data manual (Pesanan) --}}
                    <tr>
                        <td class="text-center">1</td>
                        <td>
                            <strong>{{ $order->item_description ?? 'Paket' }}</strong>
                            <br><small style="color: #718096;">Deskripsi manual</small>
                        </td>
                        <td class="text-center">{{ $order->weight ?? 0 }} gr</td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- FOOTER / TOTALS --}}
        <table class="footer-layout">
            <tr>
                <td class="notes-cell">
                    <div class="notes-box">
                        <strong>Metode Pembayaran:</strong> {{ strtoupper($order->payment_method ?? '-') }}<br><br>
                        <strong>Catatan:</strong><br>
                        Terima kasih telah berbelanja di Toko Sancaka. Mohon simpan faktur ini sebagai bukti pembelian yang sah.
                        Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan kecuali ada perjanjian sebelumnya.
                    </div>
                </td>
                <td class="totals-cell">
                    <table class="totals-table">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Ongkos Kirim ({{ $order->weight ?? 0 }} gr)</td>
                            <td class="value">Rp{{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @if(!empty($order->insurance_cost) && $order->insurance_cost > 0)
                        <tr>
                            <td class="label">Asuransi</td>
                            <td class="value">Rp{{ number_format($order->insurance_cost, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if(!empty($order->cod_fee) && $order->cod_fee > 0)
                        <tr>
                            <td class="label">Biaya Layanan (COD)</td>
                            <td class="value">Rp{{ number_format($order->cod_fee, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </table>
                    
                    {{-- Grand Total Box --}}
                    <div class="grand-total-box">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="color: #fff; font-weight: bold; font-size: 14px;">TOTAL TAGIHAN</td>
                                <td style="color: #fff; font-weight: bold; font-size: 14px; text-align: right;">Rp{{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>

                    {{-- SECTION BARCODE 2D & TRACKING LINK --}}
                    <div style="margin-top: 15px; text-align: right;">
                        @php
                            $ref = $order->shipping_reference ?? $order->invoice_number;
                            $trackingUrl = "https://tokosancaka.com/tracking/search?resi=" . $ref;
                        @endphp
                        
                        <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($trackingUrl, 'QRCODE', 4, 4) }}" alt="barcode" style="width: 80px; height: 80px;">
                        
                        <div style="margin-top: 5px; font-size: 10px; font-weight: bold; color: #4a5568; text-transform: uppercase;">
                            Tracking Me
                        </div>
                    </div>

                </td>
            </tr>
        </table>

        <div style="text-align: center; margin-top: 40px; font-size: 10px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 10px;">
            Dicetak otomatis pada {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} oleh Sistem Admin Sancaka.
        </div>

    </div>

</body>
</html>