<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            padding: 40px;
        }

        /* Container Card */
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 30px;
            overflow: hidden; /* Agar sudut tabel tdk lancip */
        }

        /* Header Laporan */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
        }
        .header-title h2 { margin: 0; color: #2c3e50; font-size: 24px; }
        .header-title p { margin: 5px 0 0; color: #7f8c8d; font-size: 14px; }
        .header-meta { text-align: right; font-size: 13px; color: #7f8c8d; }

        /* Tabel Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        
        thead {
            background: linear-gradient(90deg, #4e73df 0%, #224abe 100%); /* Warna Biru Modern */
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Rounded corners untuk header tabel */
        th:first-child { border-top-left-radius: 8px; }
        th:last-child { border-top-right-radius: 8px; }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8f9fc; transition: 0.2s; }

        /* Status Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 50px; /* Pill shape */
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .bg-success { background-color: #e6fffa; color: #2c7a7b; }
        .bg-pending { background-color: #fffaf0; color: #dd6b20; }
        .bg-failed { background-color: #fff5f5; color: #c53030; }

        /* Typography Utilities */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 600; color: #2d3748; }
        .text-muted { color: #a0aec0; font-size: 12px; }
        .sn-box {
            font-family: 'Courier New', monospace;
            background: #edf2f7;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #4a5568;
            display: inline-block;
            max-width: 200px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="header-section">
            <div class="header-title">
                <h2>Riwayat Transaksi</h2>
                <p>Sancaka Store Official Report</p>
            </div>
            <div class="header-meta">
                User: <strong>{{ Auth::user()->nama_lengkap ?? 'Guest' }}</strong><br>
                Cetak: {{ date('d M Y, H:i') }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%" class="text-center">#</th>
                    <th width="15%">Order ID</th>
                    <th width="15%">Produk</th>
                    <th width="15%">Tujuan</th>
                    <th width="20%">Keterangan / SN</th>
                    <th width="15%" class="text-right">Harga</th>
                    <th width="15%" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                <tr>
                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $trx->order_id }}</div>
                        <div class="text-muted">{{ $trx->created_at->format('d/m H:i') }}</div>
                    </td>
                    <td><span class="font-bold" style="color:#4e73df;">{{ strtoupper($trx->buyer_sku_code) }}</span></td>
                    <td>{{ $trx->customer_no }}</td>
                    <td>
                        @if($trx->sn)
                            <span class="sn-box">{{ $trx->sn }}</span>
                        @else
                            <span class="text-muted" style="font-style: italic;">{{ Str::limit($trx->message, 30) }}</span>
                        @endif
                    </td>
                    <td class="text-right font-bold">Rp {{ number_format($trx->selling_price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @php
                            $st = strtolower($trx->status);
                            $cls = match(true) {
                                $st == 'success' || $st == 'sukses' => 'bg-success',
                                in_array($st, ['pending', 'process', 'processing']) => 'bg-pending',
                                default => 'bg-failed'
                            };
                        @endphp
                        <span class="badge {{ $cls }}">{{ $trx->status }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 40px; color: #999;">Tidak ada data transaksi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>