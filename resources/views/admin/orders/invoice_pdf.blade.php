<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Faktur Pesanan' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #555;
        }
        .invoice-details {
            margin-bottom: 30px;
            overflow: hidden; /* Clear floats */
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 5px 0;
        }
        .address-section {
            width: 48%;
            margin-bottom: 20px;
        }
        .address-section h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 14px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .address-section p {
            margin: 2px 0;
        }
        .from-address {
            float: left;
        }
        .to-address {
            float: right;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        .items-table td.qty, .items-table td.price, .items-table td.total {
            text-align: right;
        }
        .items-table td.desc {
             width: 50%;
        }
        .summary-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 5px 8px;
            border: 1px solid #ddd;
        }
        .summary-table tr.grand-total td {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .notes {
             margin-top: 30px;
             padding-top: 10px;
             border-top: 1px solid #eee;
             font-size: 11px;
             color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{-- Ganti dengan nama/logo toko Anda jika perlu --}}
            <h1>FAKTUR</h1> 
            <p><strong>{{ $order->store->name ?? config('app.name', 'Toko Anda') }}</strong><br>
               {{-- Alamat toko bisa ditambahkan di sini --}}
               {{ $order->store->address_detail ?? 'Alamat Toko Anda' }}
            </p>
        </div>

        <div class="invoice-details clearfix">
            {{-- Detail Invoice --}}
            <table style="margin-bottom: 20px;">
                <tr>
                    <td style="width: 60%;">
                        <strong>Invoice #:</strong> {{ $order->invoice_number }}<br>
                        <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d F Y') }}<br>
                        <strong>Status:</strong> {{ ucfirst($order->status) }}<br>
                         <strong>Metode Bayar:</strong> {{ strtoupper(str_replace('_', ' ', $order->payment_method ?? '-')) }}
                    </td>
                    <td style="width: 40%; text-align: right;">
                        {{-- Bisa tambahkan info lain jika perlu --}}
                    </td>
                </tr>
            </table>

            {{-- Alamat Pengirim dan Penerima --}}
            <div class="address-section from-address">
                <h3>Dari:</h3>
                <p><strong>{{ $order->sender_name ?? ($order->store->name ?? 'Pengirim') }}</strong><br>
                   {{ $order->sender_address ?? ($order->store->address_detail ?? 'Alamat Pengirim') }} <br>
                   {{-- Tambahkan No HP Pengirim jika ada/perlu --}}
                   {{-- {{ $order->sender_phone ?? '' }} --}}
                </p>
            </div>
            <div class="address-section to-address">
                <h3>Kepada:</h3>
                 <p><strong>{{ $order->receiver_name ?? ($order->user->nama_lengkap ?? 'Penerima') }}</strong><br>
                    {{ $order->receiver_address ?? ($order->shipping_address ?? 'Alamat Penerima') }} <br>
                    {{-- Tambahkan No HP Penerima jika ada/perlu --}}
                    {{-- {{ $order->receiver_phone ?? ($order->user->no_wa ?? '') }} --}}
                 </p>
            </div>
        </div>

        <div class="items-section clearfix">
            <h3>Rincian Pesanan:</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th class="desc">Deskripsi</th>
                        <th class="qty">Jml</th>
                        <th class="price">Harga Satuan</th>
                        <th class="total">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    {{-- Loop untuk Order Items --}}
                    @if(isset($order->items) && $order->items->count() > 0)
                        @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $counter++ }}</td>
                            <td class="desc">
                                {{ $item->product->name ?? ($item->product_name ?? 'Produk Tidak Ditemukan') }} 
                                @if($item->variant) 
                                    <br><small>Varian: {{ $item->variant->combination_string }}</small> 
                                @endif
                                {{-- Jika dari Pesanan, deskripsi ada di $order->item_description --}}
                            </td>
                            <td class="qty">{{ $item->quantity }}</td>
                            <td class="price">Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="total">Rp{{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    {{-- Tampilkan dari Pesanan jika tidak ada relasi 'items' --}}
                    @elseif(isset($order->item_description)) 
                         <tr>
                            <td>{{ $counter++ }}</td>
                            <td class="desc">{{ $order->item_description }}</td>
                            <td class="qty">1</td> {{-- Asumsi Qty 1 jika dari Pesanan --}}
                            <td class="price">Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td> {{-- Harga ambil dari subtotal --}}
                            <td class="total">Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="5" style="text-align: center;">Tidak ada item detail.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="summary-section clearfix">
            <table class="summary-table">
                <tbody>
                    <tr>
                        <td>Subtotal</td>
                        <td style="text-align: right;">Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Ongkos Kirim</td>
                        <td style="text-align: right;">Rp{{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @if (isset($order->cod_fee) && $order->cod_fee > 0)
                    <tr>
                        <td>Biaya COD</td>
                        <td style="text-align: right;">Rp{{ number_format($order->cod_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    {{-- Bisa tambahkan biaya lain jika ada, misal diskon, pajak --}}
                    <tr class="grand-total">
                        <td>Total</td>
                        <td style="text-align: right;">Rp{{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="notes clearfix">
            <p><strong>Catatan:</strong><br>
               Terima kasih telah berbelanja!
               {{-- Tambahkan catatan lain jika perlu --}}
            </p>
        </div>

    </div>
</body>
</html>
