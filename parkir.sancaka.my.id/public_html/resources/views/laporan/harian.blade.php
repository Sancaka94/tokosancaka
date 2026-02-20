@extends('layouts.app')
@section('title', 'Laporan Harian')
@section('content')
<style>
    /* Sembunyikan elemen yang tidak perlu saat di-print */
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .card { box-shadow: none; border: none; }
    }
</style>

<div class="flex justify-between items-center mb-6 no-print">
    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan Harian</h1>
    <button onclick="window.print()" class="btn-primary flex items-center gap-2 shadow-md">🖨️ Cetak Laporan</button>
</div>

<div class="card mb-6 border-t-4 border-blue-600 shadow-md no-print">
    <div class="card-body bg-gray-50">
        <form action="{{ route('laporan.harian') }}" method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" required>
            </div>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded font-bold transition-colors shadow-sm">Tampilkan</button>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-gray-100 text-center">
        <h2 class="text-xl font-bold text-gray-800 uppercase">Rekap Pendapatan Harian</h2>
        <p class="text-gray-500 font-medium">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
    </div>
    <div class="card-body p-0">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-center">No</th>
                    <th class="text-left">Plat Nomor / TRX</th>
                    <th class="text-left">Jenis</th>
                    <th class="text-left">Jam Keluar</th>
                    <th class="text-left">Operator</th>
                    <th class="text-right">Tarif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                <tr class="border-b border-gray-100">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $trx->plate_number }} <br><span class="text-xs text-gray-500 font-normal">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span></td>
                    <td class="capitalize">{{ $trx->vehicle_type }}</td>
                    <td>{{ $trx->exit_time->translatedFormat('H:i') }} WIB</td>
                    <td>{{ $trx->operator->name ?? 'Sistem' }}</td>
                    <td class="text-right font-medium">Rp {{ number_format($trx->fee, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-500 italic">Tidak ada transaksi keluar pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-blue-50">
                <tr>
                    <td colspan="5" class="text-right font-bold text-lg py-3 px-4">TOTAL PENDAPATAN :</td>
                    <td class="text-right font-black text-xl text-green-600 py-3 px-4">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
