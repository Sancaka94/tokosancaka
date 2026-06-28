<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->invoice_number }}</title>
    <style>
        @page { margin: 25px 30px; size: a4 portrait; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #333; line-height: 1.4; margin: 0; padding: 0; }
        .container { width: 100%; margin: 0 auto; page-break-after: avoid; }
        .header { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .header table { width: 100%; border-collapse: collapse; }
        .company-name { font-size: 22px; font-weight: bold; color: #2c3e50; margin: 0 0 4px 0; }
        .company-detail { font-size: 11px; color: #7f8c8d; margin: 0; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #e74c3c; text-align: right; margin: 0; }
        .invoice-number { font-size: 14px; font-weight: bold; text-align: right; margin: 4px 0; }
        .invoice-date { font-size: 11px; text-align: right; color: #7f8c8d; margin: 0; }
        .info-section { width: 100%; margin-bottom: 20px; }
        .info-section table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .info-box { background-color: #f8f9fa; padding: 10px 12px; border-radius: 5px; border: 1px solid #e9ecef; }
        .info-title { font-size: 11px; font-weight: bold; color: #34495e; text-transform: uppercase; margin-bottom: 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .table-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-items tr { page-break-inside: avoid; }
        .table-items th { background-color: #2c3e50; color: #fff; padding: 8px 10px; text-align: left; font-size: 12px; }
        .table-items td { border-bottom: 1px solid #eee; padding: 10px; font-size: 12px; vertical-align: top; }
        .table-items th.right, .table-items td.right { text-align: right; }
        .table-items th.center, .table-items td.center { text-align: center; }
        .totals-container { width: 100%; margin-bottom: 20px; }
        .totals-table { width: 50%; float: right; border-collapse: collapse; }
        .totals-table td { padding: 6px 10px; font-size: 13px; }
        .totals-table .bold { font-weight: bold; color: #2c3e50; }
        .totals-table .grand-total { font-size: 16px; font-weight: bold; color: #e74c3c; border-top: 2px solid #e74c3c; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 15px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 11px; color: white; }
        .bg-green { background-color: #27ae60; }
        .bg-yellow { background-color: #f1c40f; color: #333;}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                        <h1 class="company-name">CV. Sancaka Karya Hutama</h1>
                        <p class="company-detail">JL. DR. WAHIDIN NO.18A, NGAWI</p>
                        <p class="company-detail">Telp/WA: 085745808809</p>
                    </td>
                    <td style="width: 40%; vertical-align: top; text-align: right;">
                        @php
                            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=" . urlencode($order->invoice_number);
                            $qrData = base64_encode(file_get_contents($qrUrl));
                            $qrSrc = 'data:image/png;base64,' . $qrData;
                        @endphp
                        <img src="{{ $qrSrc }}" style="width: 60px; height: 60px; margin-bottom: 5px;">

                        <h2 class="invoice-title">INVOICE</h2>
                        <p class="invoice-number">#{{ $order->invoice_number }}</p>
                        <p class="invoice-date">Tanggal: {{ $order->created_at->format('d F Y, H:i') }}</p>
                        <p style="text-align: right; margin-top: 5px;">
                            Status:
                            @if(in_array(strtolower($order->status), ['paid', 'processing', 'shipped', 'completed']))
                                <span class="badge bg-green">LUNAS</span>
                            @else
                                <span class="badge bg-yellow">{{ strtoupper($order->status) }}</span>
                            @endif
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        @php
            $isPureDigitalShipping = str_contains(strtolower($order->shipping_method), 'digital');
        @endphp
        
        <div class="info-section">
            <table>
                <tr>
                    <td style="width: 48%; padding-right: 2%; vertical-align: top;">
                        <div class="info-box">
                            <div class="info-title">{{ $isPureDigitalShipping ? 'Informasi Penerima' : 'Penerima & Pengiriman' }}</div>
                            <p style="font-weight: bold; margin: 0 0 3px 0;">{{ $order->receiver_name ?? ($order->user->nama_lengkap ?? 'Guest / Tamu') }}</p>
                            <p style="margin: 0 0 3px 0;">{{ $order->receiver_phone ?? ($order->user->no_wa ?? '-') }}</p>
                            <p style="margin: 0;">{{ $order->shipping_address ?? 'Alamat tidak tersedia' }}</p>
                        </div>
                    </td>
                    <td style="width: 48%; padding-left: 2%; vertical-align: top;">
                        <div class="info-box">
                            <div class="info-title">Detail Pengiriman</div>
                            @if($isPureDigitalShipping)
                                <p style="margin: 0 0 3px 0;">Sistem: <strong>Pengiriman Otomatis (E-Ticket)</strong></p>
                            @else
                                @php
                                    $kurir = explode('-', $order->shipping_method);
                                    $namaKurir = strtoupper(($kurir[1] ?? 'KURIR') . ' - ' . ($kurir[2] ?? ''));
                                @endphp
                                <p style="margin: 0 0 3px 0;">Kurir: <strong>{{ $namaKurir }}</strong></p>
                                
                                @if(in_array(strtolower($order->status), ['paid', 'processing', 'shipped', 'completed']))
                                    <p style="margin: 0;">No. Resi: <strong>{{ !empty($order->shipping_reference) && $order->shipping_reference !== '-' && $order->shipping_reference !== 'Menunggu Penjual' ? $order->shipping_reference : 'Menunggu Update Kurir' }}</strong></p>
                                @else
                                    <p style="margin: 0; color: #7f8c8d; font-style: italic;">Resi muncul setelah lunas</p>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="table-items">
            <thead>
                <tr>
                    <th>Deskripsi Produk</th>
                    <th class="center" style="width: 20%;">Harga</th>
                    <th class="center" style="width: 10%;">Qty</th>
                    <th class="right" style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                @php
                    $katObj = $item->product ? $item->product->category()->first() : null;
                    $isItemDigital = ($katObj && in_array($katObj->category_group, ['produk_digital', 'jasa'])) || $isPureDigitalShipping;
                @endphp
                <tr>
                    <td>
                        <strong style="font-size: 13px;">{{ $item->product->name ?? 'Produk Dihapus' }}</strong>
                        @if($item->variant)
                            <br><span style="font-size: 10px; color: #7f8c8d;">Varian: {{ $item->variant->name }}</span>
                        @endif

                        {{-- LOGIKA QR CODE DIGITAL (Hanya untuk item berjenis digital) --}}
                        @if($isItemDigital && in_array(strtolower($order->status), ['paid', 'processing', 'completed', 'shipped']))
                            @php
                                $qrDataContent = null;
                                if ($item->product) {
                                    if (!empty($item->product->digital_url)) {
                                        $qrDataContent = $item->product->digital_url;
                                    } elseif (!empty($item->product->digital_file_path)) {
                                        $qrDataContent = asset('public/storage/' . $item->product->digital_file_path);
                                    } elseif (!empty($order->shipping_reference) && $order->shipping_reference !== 'Menunggu Penjual' && $isPureDigitalShipping) {
                                        $qrDataContent = $order->shipping_reference;
                                    }
                                }

                                $qrSrcItem = '';
                                if ($qrDataContent && $qrDataContent !== 'Menunggu Penjual') {
                                    try {
                                        $qrUrlItem = "https://api.qrserver.com/v1/create-qr-code/?size=90x90&margin=1&data=" . urlencode($qrDataContent);
                                        $qrDataItem = base64_encode(file_get_contents($qrUrlItem));
                                        $qrSrcItem = 'data:image/png;base64,' . $qrDataItem;
                                    } catch(\Exception $e) {}
                                }
                            @endphp
                            
                            @if($qrSrcItem)
                                <div style="margin-top: 10px;">
                                    <div style="font-size: 9px; font-weight: bold; color: #27ae60; margin-bottom: 3px;">SCAN AKSES PRODUK INI:</div>
                                    <img src="{{ $qrSrcItem }}" style="width: 65px; height: 65px; border: 1px solid #ccc; padding: 3px; border-radius: 4px;">
                                </div>
                            @endif
                        @endif
                    </td>
                    <td class="center">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-container clearfix">
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($order->shipping_cost > 0)
                <tr>
                    <td>Ongkos Kirim</td>
                    <td class="right">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($order->cod_fee > 0)
                <tr>
                    <td>Biaya Layanan COD</td>
                    <td class="right">Rp {{ number_format($order->cod_fee, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($order->insurance_cost > 0)
                <tr>
                    <td>Asuransi Pengiriman</td>
                    <td class="right">Rp {{ number_format($order->insurance_cost, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="bold grand-total" style="padding-top: 10px;">TOTAL TAGIHAN</td>
                    <td class="right bold grand-total" style="padding-top: 10px;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p style="margin-bottom: 3px;">Terima kasih telah berbelanja di CV. Sancaka Karya Hutama.</p>
            <p style="margin-top: 0;">Dokumen ini adalah bukti pembayaran yang sah dan diterbitkan secara elektronik oleh sistem.</p>
        </div>
    </div>
</body>
</html>