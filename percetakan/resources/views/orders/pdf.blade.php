<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #555; }

        .meta-info { width: 100%; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .meta-info td { vertical-align: top; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { border: 1px solid #000; padding: 6px; text-align: left; vertical-align: top; }
        table.data th { background-color: #f0f0f0; font-weight: bold; text-transform: uppercase; font-size: 9pt; }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }

        /* Styling untuk List Item agar rapi */
        ul.item-list { margin: 0; padding-left: 15px; }
        ul.item-list li { margin-bottom: 4px; }
        .item-meta { font-size: 8pt; color: #555; }
        .badge { padding: 2px 5px; border-radius: 4px; font-size: 8pt; color: #000; border: 1px solid #ccc; }
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
                <th width="5%" class="text-center">No</th>
                <th width="12%">Waktu</th>
                <th width="15%">No. Invoice</th>
                <th width="18%">Pelanggan</th>
                {{-- KOLOM BARU: DETAIL ITEM --}}
                <th width="35%">Detail Order (Produk, Qty, Harga)</th>
                <th width="15%" class="text-right">Total Bayar</th>
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
                        <span style="font-size: 8pt; color: #555;">{{ $order->customer_phone }}</span><br>
                        <span class="badge">{{ strtoupper($order->payment_method) }}</span>
                    </td>

                    {{-- ISI DATA DETAIL ITEM --}}
                    <td>
                        <ul class="item-list">
                            @foreach($order->items as $item)
                                <li>
                                    {{-- Nama Produk (Misal: Cuci Seprei) --}}
                                    <strong>{{ $item->product_name }}</strong>

                                    <div class="item-meta">
                                        {{-- Format: 1 kg x @7.000 = 7.000 --}}
                                        {{ $item->quantity + 0 }} {{ $item->product->unit ?? 'pcs' }}
                                        x @ {{ number_format($item->price_at_order, 0, ',', '.') }}

                                        {{-- Subtotal per item --}}
                                        = <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </td>

                    <td class="text-right font-bold">
                        Rp {{ number_format($order->final_price, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">
                        Tidak ada data transaksi untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($orders->count() > 0)
        <tfoot>
            <tr style="background-color: #f9f9f9;">
                <td colspan="5" class="text-right font-bold">TOTAL PENDAPATAN</td>
                <td class="text-right font-bold" style="border-top: 2px solid #000;">
                    Rp {{ number_format($orders->sum('final_price'), 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
