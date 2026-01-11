<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; color: #333; }
        .header p { margin: 2px 0; font-size: 9pt; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 8pt; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        /* Warna Profit */
        .text-green { color: #166534; }
        .text-red { color: #991b1b; }

        .summary-box { margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; background: #fafafa; }
        .summary-table td { border: none; padding: 2px 10px; }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <div class="header">
        <h1>LAPORAN KEUANGAN & PROFIT</h1>
        <p><strong>TOKO SANCAKA</strong></p>
        <p>Dicetak pada: {{ date('d F Y H:i') }} | Oleh: {{ auth()->user()->name ?? 'Admin' }}</p>
        <p>Periode Data: {{ request('date_range') ?? 'Semua Waktu' }}</p>
    </div>

    {{-- RINGKASAN ATAS --}}
    <div class="summary-box">
        <table class="summary-table" style="width: auto;">
            <tr>
                <td><strong>Total Omzet:</strong></td>
                <td>Rp{{ number_format($summary['omzet'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Modal:</strong></td>
                <td>Rp{{ number_format($summary['modal'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Profit:</strong></td>
                <td class="font-bold">Rp{{ number_format($summary['profit'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- TABEL DATA --}}
    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 12%">Tanggal</th>
                <th style="width: 10%">Kategori</th>
                <th style="width: 15%">Invoice</th>
                <th style="width: 28%">Keterangan</th>
                <th style="width: 10%" class="text-right">Omzet</th>
                <th style="width: 10%" class="text-right">Modal</th>
                <th style="width: 10%" class="text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/y') }}</td>
                <td>{{ $item->kategori }}</td>
                <td style="font-size: 8pt">{{ $item->nomor_invoice }}</td>
                <td style="font-size: 8pt">{{ Str::limit($item->keterangan, 50) }}</td>
                <td class="text-right">{{ number_format($item->omzet, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->modal, 0, ',', '.') }}</td>
                <td class="text-right font-bold {{ $item->profit < 0 ? 'text-red' : 'text-green' }}">
                    {{ number_format($item->profit, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e5e7eb;">
                <td colspan="5" class="text-right font-bold">GRAND TOTAL</td>
                <td class="text-right font-bold">Rp{{ number_format($summary['omzet'], 0, ',', '.') }}</td>
                <td class="text-right font-bold">Rp{{ number_format($summary['modal'], 0, ',', '.') }}</td>
                <td class="text-right font-bold">Rp{{ number_format($summary['profit'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- TANDA TANGAN (Opsional agar terlihat resmi) --}}
    <div style="margin-top: 50px; float: right; width: 200px; text-align: center;">
        <p>Mengetahui,</p>
        <br><br><br>
        <p style="border-bottom: 1px solid #000; font-weight: bold;">( Owner / Admin )</p>
    </div>

</body>
</html>