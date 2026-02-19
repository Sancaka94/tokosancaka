<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - Sancaka Express</title>
    <style>
        /* Reset & Base Styles */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e3a8a; /* Blue Border */
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            color: #666;
        }

        /* Summary Box (Ringkasan Saldo) */
        .summary-box {
            width: 40%;
            margin-left: auto; /* Align Right */
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9fafb;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .summary-label { font-weight: bold; color: #555; }
        .summary-val { text-align: right; float: right; }
        .total-row {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 2px solid #333;
            font-size: 14px;
            font-weight: bold;
        }

        /* Main Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
        }
        th {
            background-color: #1e3a8a; /* Blue Header */
            color: #ffffff;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background-color: #f3f4f6;
        }

        /* Helpers */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-green { color: #047857; font-weight: bold; } /* Green-700 */
        .text-red { color: #b91c1c; font-weight: bold; }   /* Red-700 */
        .font-bold { font-weight: bold; }
        .category-badge {
            font-size: 9px;
            text-transform: uppercase;
            color: #555;
            font-style: italic;
        }

        /* Signature Section */
        .footer-sign {
            margin-top: 50px;
            width: 30%;
            float: right;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SANCAKA EXPRESS</h1>
        <p>Jl. Dr. Wahidin No.18A RT.22/05 Ketanggi, Ngawi, Jawa Timur 63211</p>
        <p>Laporan Arus Kas & Keuangan</p>
    </div>

    <div class="summary-box">
        <div style="clear: both; margin-bottom: 4px;">
            <span class="summary-label">Total Pemasukan:</span>
            <span class="summary-val text-green">Rp {{ number_format($totalIncome, 0, ',', '.') }}</span>
        </div>
        <div style="clear: both; margin-bottom: 4px;">
            <span class="summary-label">Total Pengeluaran:</span>
            <span class="summary-val text-red">(Rp {{ number_format($totalExpense, 0, ',', '.') }})</span>
        </div>
        <div class="total-row" style="clear: both;">
            <span class="summary-label">Sisa Saldo Akhir:</span>
            <span class="summary-val">Rp {{ number_format($saldo, 0, ',', '.') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="15%">Kategori</th>
                <th width="20%">Nama / Kontak</th>
                <th width="28%">Keterangan</th>
                <th width="10%">Masuk (+)</th>
                <th width="10%">Keluar (-)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                <td class="text-center">
                    <span class="category-badge">
                        {{ strtoupper(str_replace('_', ' ', $item->category)) }}
                    </span>
                </td>
                <td>
                    <strong>{{ $item->name }}</strong>
                </td>
                <td>{{ $item->description ?? '-' }}</td>

                <td class="text-right">
                    @if($item->type == 'income')
                        <span class="text-green">{{ number_format($item->amount, 0, ',', '.') }}</span>
                    @else
                        -
                    @endif
                </td>

                <td class="text-right">
                    @if($item->type == 'expense')
                        <span class="text-red">{{ number_format($item->amount, 0, ',', '.') }}</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">Data tidak tersedia pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-sign">
        <p>Ngawi, {{ date('d F Y') }}</p>
        <p>Dibuat Oleh,</p>
        <br><br><br>
        <p style="text-decoration: underline; font-weight: bold;">( Admin Keuangan )</p>
    </div>

</body>
</html>
