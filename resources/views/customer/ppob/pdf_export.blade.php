<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi PPOB</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-success { color: green; }
        .status-pending { color: orange; }
        .status-failed { color: red; }
        .sn-code { font-family: monospace; background-color: #f9f9f9; padding: 2px 4px; border: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Riwayat Transaksi PPOB</h2>
        <p>User: {{ Auth::user()->nama_lengkap }} | Tanggal Cetak: {{ date('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Order ID</th>
                <th style="width: 10%;">Produk</th>
                <th style="width: 15%;">Pelanggan</th>
                <th style="width: 12%;" class="text-right">Harga</th>
                <th style="width: 10%;" class="text-center">Status</th>
                <th style="width: 20%;">SN / Pesan</th>
                <th style="width: 13%;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $trx->order_id }}</td>
                <td>{{ strtoupper($trx->buyer_sku_code) }}</td>
                <td>{{ $trx->customer_no }}</td>
                <td class="text-right">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</td>
                <td class="text-center">
                    @php
                        $statusClass = match($trx->status) {
                            'Success' => 'status-success',
                            'Pending', 'Processing' => 'status-pending',
                            default => 'status-failed',
                        };
                    @endphp
                    <span class="{{ $statusClass }}">{{ $trx->status }}</span>
                </td>
                <td>
                    @if($trx->sn)
                        <span class="sn-code">{{ $trx->sn }}</span>
                    @else
                        <span style="font-style: italic; color: #777;">{{ $trx->message }}</span>
                    @endif
                </td>
                <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data transaksi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>