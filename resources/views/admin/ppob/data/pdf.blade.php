<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi PPOB</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #333; }
        th, td { padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .badge { padding: 2px 5px; border-radius: 3px; color: white; font-size: 9px; }
        .bg-success { background-color: green; }
        .bg-pending { background-color: orange; }
        .bg-failed { background-color: red; }
        .bg-process { background-color: blue; }
        .text-right { text-align: right; }
        .summary { margin-top: 15px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2 style="margin:0;">Laporan Transaksi PPOB</h2>
        <p style="margin:5px 0;">Sancaka Express - {{ date('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20px">No</th>
                <th>Waktu</th>
                <th>Order ID</th>
                <th>User / Pelanggan</th>
                <th>Produk</th>
                <th>Tujuan</th>
                <th>Harga Jual</th>
                <th>Profit</th>
                <th>Status</th>
                <th>Keterangan / SN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $trx)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $trx->created_at->format('d/m/y H:i') }}</td>
                <td>{{ $trx->order_id }}</td>
                <td>
                    {{ $trx->user->name ?? 'Guest' }} <br>
                    <span style="color: #666; font-size: 9px;">{{ $trx->user->email ?? '' }}</span>
                </td>
                <td>
                    {{ $trx->buyer_sku_code }} <br>
                    <span style="font-size: 8px;">{{ Str::limit($trx->product_name, 15) }}</span>
                </td>
                <td>{{ $trx->customer_no }}</td>
                <td class="text-right">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</td>
                <td class="text-right" style="color: green;">Rp {{ number_format($trx->profit, 0, ',', '.') }}</td>
                <td style="text-align: center;">
                    @php
                        $color = match($trx->status) {
                            'Success' => 'bg-success',
                            'Pending' => 'bg-pending',
                            'Processing' => 'bg-process',
                            'Failed' => 'bg-failed',
                            default => 'bg-pending'
                        };
                    @endphp
                    <span class="badge {{ $color }}">{{ $trx->status }}</span>
                </td>
                <td>
                    @if($trx->sn)
                        <span style="font-family: monospace;">{{ $trx->sn }}</span>
                    @elseif($trx->status == 'Failed')
                        <span style="color: red; font-style: italic;">
                            {{ function_exists('get_ppob_message') && $trx->rc ? get_ppob_message($trx->rc) : Str::limit($trx->message, 30) }}
                        </span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total Transaksi: {{ $transactions->count() }}</p>
        <p>Total Omset: Rp {{ number_format($totalOmset, 0, ',', '.') }}</p>
        <p>Total Profit: Rp {{ number_format($totalProfit, 0, ',', '.') }}</p>
    </div>

</body>
</html>