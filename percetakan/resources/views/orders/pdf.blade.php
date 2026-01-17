<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            padding: 0;
            font-size: 16pt;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            color: #555;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .meta-info td {
            vertical-align: top;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        table.data th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        .badge {
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            color: #000;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 2px solid #000;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN TRANSAKSI</h1>
        <p>{{ config('app.name', 'Toko Sancaka') }}</p>
        <p style="font-size: 9pt;">Dicetak pada: {{ date('d F Y, H:i') }}</p>
    </div>

    <table class="meta-info">
        <tr>
            <td width="60%">
                <strong>Filter Laporan:</strong><br>
                @if(request('date_range'))
                    Periode: {{ request('date_range') }}
                @else
                    Periode: Semua Waktu
                @endif
                <br>
                @if(request('status'))
                    Status: {{ strtoupper(request('status')) }}
                @endif
            </td>
            <td width="40%" class="text-right">
                <strong>Total Transaksi:</strong> {{ $orders->count() }} Data<br>
                <strong>Total Omzet:</strong> Rp {{ number_format($orders->sum('final_price'), 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Waktu</th>
                <th width="15%">No. Invoice</th>
                <th width="20%">Pelanggan</th>
                <th width="10%">Status</th>
                <th width="10%">Pembayaran</th>
                <th width="15%">Ekspedisi</th>
                <th width="10%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
                    <td style="font-family: monospace;">{{ $order->order_number }}</td>
                    <td>
                        <strong>{{ $order->customer_name }}</strong><br>
                        <span style="font-size: 8pt; color: #555;">{{ $order->customer_phone }}</span>
                    </td>
                    <td class="text-center">
                        {{ strtoupper($order->status) }}
                    </td>
                    <td class="text-center">
                        {{ strtoupper($order->payment_method) }}<br>
                        <span style="font-size: 8pt;">({{ $order->payment_status }})</span>
                    </td>
                    <td>
                        {{ $order->courier_service ?? 'Pickup' }}<br>
                        @if($order->shipping_ref)
                            <span style="font-size: 8pt; font-family: monospace;">Ref: {{ $order->shipping_ref }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        Rp {{ number_format($order->final_price, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        Tidak ada data transaksi untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($orders->count() > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAL PENDAPATAN</td>
                <td class="text-right">Rp {{ number_format($orders->sum('final_price'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
