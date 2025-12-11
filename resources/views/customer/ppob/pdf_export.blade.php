<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi PPOB</title>
    <style>
        /* Reset & Base Font */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        /* Header Styling */
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            text-transform: uppercase;
            font-size: 16pt;
            color: #2c3e50;
        }
        .header-meta {
            font-size: 9pt;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
            padding: 10px 8px;
            border-bottom: 2px solid #ddd;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        /* Zebra Striping */
        tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* Utilities */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-small { font-size: 8pt; color: #777; }

        /* Status Badges (Pills) */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .bg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .bg-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .bg-failed { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* SN Column Specific */
        .sn-box {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            word-break: break-all; /* Penting agar token panjang turun ke bawah */
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            font-size: 8pt;
            text-align: right;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border: none; margin: 0;">
            <tr>
                <td style="border: none; padding: 0;">
                    <h2>Laporan Riwayat Transaksi</h2>
                    <div class="header-meta">
                        Dicetak oleh: {{ Auth::user()->nama_lengkap ?? 'Admin' }}
                    </div>
                </td>
                <td style="border: none; padding: 0; text-align: right; vertical-align: bottom;">
                    <div class="header-meta" style="justify-content: flex-end;">
                        Tanggal: {{ date('d M Y, H:i') }} WIB
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">Order ID / Tgl</th>
                <th style="width: 15%;">Produk</th>
                <th style="width: 15%;">Pelanggan</th>
                <th style="width: 20%;">SN / Keterangan</th>
                <th style="width: 15%;" class="text-right">Harga Jual</th>
                <th style="width: 15%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                
                <td>
                    <span class="text-bold">{{ $trx->order_id }}</span><br>
                    <span class="text-small">{{ \Carbon\Carbon::parse($trx->created_at)->format('d/m/Y H:i') }}</span>
                </td>
                
                <td>
                    {{ strtoupper($trx->buyer_sku_code) }}
                </td>
                
                <td>
                    {{ $trx->customer_no }}
                </td>
                
                <td>
                    @if($trx->sn)
                        <div class="sn-box">{{ $trx->sn }}</div>
                    @else
                        <span class="text-small" style="font-style: italic;">
                            {{ Str::limit($trx->message, 30) }}
                        </span>
                    @endif
                </td>

                <td class="text-right">
                    Rp {{ number_format($trx->selling_price, 0, ',', '.') }}
                </td>

                <td class="text-center">
                    @php
                        $statusLower = strtolower($trx->status);
                        $badgeClass = match(true) {
                            $statusLower == 'success' || $statusLower == 'sukses' => 'bg-success',
                            in_array($statusLower, ['pending', 'processing', 'proses']) => 'bg-pending',
                            default => 'bg-failed',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ $trx->status }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 30px; color: #777;">
                    -- Tidak ada data transaksi ditemukan --
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($transactions) > 0)
        <tfoot>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="5" class="text-right">TOTAL TRANSAKSI</td>
                <td class="text-right">Rp {{ number_format($transactions->sum('selling_price'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Generated by System Sancaka Store
    </div>

</body>
</html>