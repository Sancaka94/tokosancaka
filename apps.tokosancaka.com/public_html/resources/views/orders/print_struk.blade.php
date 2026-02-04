<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $order->order_number }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 0;
            width: 58mm;
            color: #000;
            background-color: #fff;
        }
        .container {
            padding: 10px 2px;
            width: 100%;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }

        .qr-code {
            margin-top: 10px;
            display: flex;
            justify-content: center;
        }

        @media print {
            body { background: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="text-center">
            <div class="bold" style="font-size: 13px;">{{ $store['name'] }}</div>
            <div>{{ $store['address'] }}</div>
            <div>WA: {{ $store['phone'] }}</div>
        </div>

        <div class="line"></div>

        <div>
            No  : {{ $order->order_number }}<br>
            Tgl : {{ $order->created_at->format('d/m/y H:i') }}<br>
            Ksr : {{ Auth::user()->name ?? 'Admin' }}<br>
            Cst : {{ $order->customer_name }}
        </div>

        <div class="line"></div>

        <table>
            @foreach($order->items as $item)
            <tr>
                <td colspan="2">{{ $item->product_name }}</td>
            </tr>
            <tr>
                <td>{{ $item->quantity }} x {{ number_format($item->price_at_order, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </table>

        <div class="line"></div>

        <table>
            <tr>
                <td>Total</td>
                <td class="text-right bold">{{ number_format($order->total_price, 0, ',', '.') }}</td>
            </tr>
            @if($order->discount_amount > 0)
            <tr>
                <td>Diskon</td>
                <td class="text-right">-{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->shipping_cost > 0)
            <tr>
                <td>Ongkir</td>
                <td class="text-right">{{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="font-size: 13px;">
                <td class="bold">Grand Total</td>
                <td class="text-right bold">Rp{{ number_format($order->final_price, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="line"></div>
        <div class="text-center">
            Metode: {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}<br>
            *** TERIMA KASIH ***<br>
            Bukti Pembayaran Sah
        </div>

        {{-- BAGIAN QR CODE UNTUK VALIDASI --}}
        <div class="text-center" style="margin-top: 15px;">
            <p style="font-size: 8px; margin-bottom: 5px;">Scan untuk Cek Validasi Nota:</p>
            <div class="qr-code">
                {{-- Generate QR Code dari Link Invoice --}}
                {!! QrCode::size(80)->generate(url('/invoice/' . $order->order_number)) !!}
            </div>
            <p style="font-size: 9px; margin-top: 5px; font-family: monospace;">{{ $order->order_number }}</p>
        </div>

        <div style="height: 30px;"></div> {{-- Spasi agar kertas tidak terpotong saat keluar --}}
    </div>
</body>
</html>
