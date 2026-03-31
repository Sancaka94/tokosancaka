<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .container { width: 100%; margin: 0 auto; }
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .header table { width: 100%; }
        .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; margin: 0 0 5px 0; }
        .company-detail { font-size: 12px; color: #7f8c8d; margin: 0; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #e74c3c; text-align: right; margin: 0; }
        .invoice-number { font-size: 16px; font-weight: bold; text-align: right; margin: 5px 0; }
        .invoice-date { font-size: 12px; text-align: right; color: #7f8c8d; margin: 0; }

        .info-section { width: 100%; margin-bottom: 30px; }
        .info-section table { width: 100%; }
        .info-box { background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef; }
        .info-title { font-size: 12px; font-weight: bold; color: #34495e; text-transform: uppercase; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;}

        .table-items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table-items th { background-color: #2c3e50; color: #fff; padding: 10px; text-align: left; font-size: 13px; }
        .table-items td { border-bottom: 1px solid #eee; padding: 12px 10px; font-size: 13px; }
        .table-items th.right, .table-items td.right { text-align: right; }
        .table-items th.center, .table-items td.center { text-align: center; }

        .totals { width: 100%; margin-bottom: 30px; }
        .totals table { width: 100%; }
        .totals td { padding: 8px 10px; font-size: 14px; }
        .totals .bold { font-weight: bold; color: #2c3e50; }
        .totals .grand-total { font-size: 18px; font-weight: bold; color: #e74c3c; border-top: 2px solid #e74c3c; }

        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 20px;}
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; font-size: 12px; color: white;}
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
                    <td style="width: 40%; vertical-align: top;">
                        <h2 class="invoice-title">INVOICE</h2>
                        <p class="invoice-number">#{{ $order->invoice_number }}</p>
                        <p class="invoice-date">Tanggal: {{ $order->created_at->format('d F Y, H:i') }}</p>
                        <p style="text-align: right; margin-top: 10px;">
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

        <div class="info-section">
            <table>
                <tr>
                    <td style="width: 50%; padding-right: 15px; vertical-align: top;">
                        <div class="info-box">
                            <div class="info-title">Penerima Tagihan & Pengiriman</div>
                            <p style="font-weight: bold; margin: 0 0 5px 0;">{{ $order->user->nama_lengkap ?? 'Pelanggan' }}</p>
                            <p style="margin: 0 0 5px 0;">{{ $order->user->no_wa ?? '-' }}</p>
                            <p style="margin: 0;">{{ $order->shipping_address ?? $order->user->address_detail ?? '-' }}</p>
                        </div>
                    </td>
                    <td style="width: 50%; padding-left: 15px; vertical-align: top;">
                        <div class="info-box">
                            <div class="info-title">Detail Ekspedisi</div>
                            @php
                                $kurir = explode('-', $order->shipping_method);
                                $namaKurir = strtoupper(($kurir[1] ?? 'KURIR') . ' - ' . ($kurir[2] ?? ''));
                            @endphp
                            <p style="margin: 0 0 5px 0;">Kurir: <strong>{{ $namaKurir }}</strong></p>

                            @if(in_array(strtolower($order->status), ['paid', 'processing', 'shipped', 'completed']))
                                <p style="margin: 0;">No. Resi: <strong>{{ !empty($order->shipping_resi) && $order->shipping_resi !== '-' ? $order->shipping_resi : 'Menunggu Update Kurir' }}</strong></p>
                            @else
                                <p style="margin: 0; color: #7f8c8d; font-style: italic;">Resi muncul setelah lunas</p>
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
                    <th class="center">Harga</th>
                    <th class="center">Qty</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product->name ?? 'Produk Dihapus' }}</strong>
                        @if($item->variant)
                            <br><span style="font-size: 11px; color: #7f8c8d;">Varian: {{ $item->variant->name }}</span>
                        @endif
                    </td>
                    <td class="center">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td style="width: 60%;"></td>
                    <td style="width: 40%;">
                        <table style="width: 100%;">
                            <tr>
                                <td>Subtotal</td>
                                <td class="right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Ongkos Kirim</td>
                                <td class="right">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
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
                                <td class="bold grand-total" style="padding-top: 15px;">TOTAL TAGIHAN</td>
                                <td class="right bold grand-total" style="padding-top: 15px;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Terima kasih telah berbelanja di CV. Sancaka Karya Hutama.</p>
            <p>Dokumen ini adalah bukti pembayaran yang sah dan diterbitkan secara elektronik oleh sistem.</p>
        </div>
    </div>
</body>
</html>
