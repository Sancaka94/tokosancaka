@extends('layouts.app')
@section('title', 'Laporan Bulanan')
@section('content')
<style> @media print { .no-print { display: none !important; } body { background: white; } .card { box-shadow: none; border: none; } } </style>

<div class="flex justify-between items-center mb-6 no-print">
    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan Bulanan</h1>
    <button onclick="window.print()" class="btn-primary flex items-center gap-2 shadow-md">🖨️ Cetak Laporan</button>
</div>

<div class="card mb-6 border-t-4 border-blue-600 shadow-md no-print">
    <div class="card-body bg-gray-50">
        <form action="{{ route('laporan.bulanan') }}" method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Bulan</label>
                <select name="bulan" class="form-control">
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ $bulan == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tahun</label>
                <input type="number" name="tahun" value="{{ $tahun }}" class="form-control" min="2020" max="{{ date('Y') }}">
            </div>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded font-bold">Tampilkan</button>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-gray-100 text-center">
        <h2 class="text-xl font-bold text-gray-800 uppercase">Rekap Pendapatan Bulanan</h2>
        <p class="text-gray-500 font-medium">Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
    </div>
    <div class="card-body p-0">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-center">No</th>
                    <th class="text-left">Plat Nomor</th>
                    <th class="text-left">Jenis</th>
                    <th class="text-left">Tgl Keluar</th>
                    <th class="text-right">Tarif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                <tr class="border-b border-gray-100">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $trx->plate_number }}</td>
                    <td class="capitalize">{{ $trx->vehicle_type }}</td>
                    <td>{{ $trx->exit_time ? $trx->exit_time->translatedFormat('d M Y (H:i)') : 'Belum Keluar' }}</td>
                    <td class="text-right font-medium">Rp {{ number_format($trx->fee, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-8 text-gray-500">Tidak ada transaksi keluar pada bulan ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-blue-50">
                <tr>
                    <td colspan="4" class="text-right font-bold text-lg py-3 px-4">TOTAL PENDAPATAN BULAN INI :</td>
                    <td class="text-right font-black text-xl text-green-600 py-3 px-4">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
