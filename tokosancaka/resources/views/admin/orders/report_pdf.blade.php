<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #666;
        }
        .summary {
            margin-bottom: 20px;
            width: 100%;
        }
        .summary td {
            padding: 5px;
            font-size: 14px;
        }
        .summary-label {
            font-weight: bold;
            width: 150px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, 
        table.data-table td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        table.data-table th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .badge {
            padding: 2px 5px;
            font-size: 10px;
            border-radius: 3px;
            color: #000;
            border: 1px solid #ccc;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #999;
            text-align: right;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Penjualan</h1>
        <p>Periode: {{ $startDate->translatedFormat('d F Y') }} - {{ $endDate->translatedFormat('d F Y') }}</p>
    </div>

    {{-- Ringkasan Laporan --}}
    <table class="summary">
        <tr>
            <td class="summary-label">Total Pesanan:</td>
            <td>{{ $totalOrders }} Transaksi</td>
        </tr>
        <tr>
            <td class="summary-label">Total Pendapatan:</td>
            <td>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Tanggal Cetak:</td>
            <td>{{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
    </table>

    {{-- Tabel Data --}}
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="20%">No. Invoice</th>
                <th width="25%">Pelanggan</th>
                <th width="15%">Status</th>
                <th width="20%">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">
                        {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d/m/Y') }}
                        <br>
                        <small style="color: #666;">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }}</small>
                    </td>
                    <td>
                        <strong>{{ $order->invoice_number }}</strong>
                        @if(isset($order->is_pesanan) && $order->is_pesanan)
                            <br><small style="color: blue;">(Manual)</small>
                        @endif
                    </td>
                    <td>
                        {{ $order->user->nama_lengkap ?? 'Guest' }}
                    </td>
                    <td class="text-center">
                        @php
                            // Mapping Status Sederhana untuk PDF agar terlihat rapi
                            $status = $order->status;
                            $label = ucfirst($status);
                            
                            // Normalisasi teks status
                            if(in_array($status, ['completed', 'Selesai'])) $label = 'Selesai';
                            if(in_array($status, ['paid', 'processing', 'Menunggu Pickup'])) $label = 'Proses';
                            if(in_array($status, ['shipped', 'delivered', 'Sedang Dikirim', 'Terkirim', 'Diproses'])) $label = 'Dikirim';
                            if(in_array($status, ['cancelled', 'failed', 'Batal', 'Gagal', 'rejected'])) $label = 'Batal';
                        @endphp
                        <span class="badge">{{ $label }}</span>
                    </td>
                    <td class="text-right">
                        {{ number_format($order->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data penjualan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL PENDAPATAN</th>
                <th class="text-right">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak oleh Administrator - {{ config('app.name') }}
    </div>

</body>
</html>