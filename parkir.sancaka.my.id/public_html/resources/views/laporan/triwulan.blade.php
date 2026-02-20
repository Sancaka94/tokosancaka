@extends('layouts.app')

@section('title', 'Laporan Triwulan')

@section('content')
<style>
    /* Sembunyikan elemen yang tidak perlu saat halaman di-print */
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .card { box-shadow: none; border: none; }
    }
</style>

<div class="flex justify-between items-center mb-6 no-print">
    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan Triwulan</h1>
    <button onclick="window.print()" class="btn-primary flex items-center gap-2 shadow-md">
        🖨️ Cetak Laporan
    </button>
</div>

<div class="card mb-6 border-t-4 border-blue-600 shadow-md no-print">
    <div class="card-body bg-gray-50">
        <form action="{{ route('laporan.triwulan') }}" method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Kuartal (Triwulan)</label>
                <select name="kuartal" class="form-control">
                    <option value="1" {{ $kuartal == 1 ? 'selected' : '' }}>Kuartal 1 (Januari - Maret)</option>
                    <option value="2" {{ $kuartal == 2 ? 'selected' : '' }}>Kuartal 2 (April - Juni)</option>
                    <option value="3" {{ $kuartal == 3 ? 'selected' : '' }}>Kuartal 3 (Juli - September)</option>
                    <option value="4" {{ $kuartal == 4 ? 'selected' : '' }}>Kuartal 4 (Oktober - Desember)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tahun</label>
                <input type="number" name="tahun" value="{{ $tahun }}" class="form-control" min="2020" max="{{ date('Y') }}">
            </div>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded font-bold shadow-sm transition-colors">
                Tampilkan
            </button>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-b-2 border-gray-100 text-center">
        <h2 class="text-xl font-bold text-gray-800 uppercase">Rekap Pendapatan Triwulan</h2>
        <p class="text-gray-500 font-medium">
            Periode: Kuartal {{ $kuartal }} Tahun {{ $tahun }}
        </p>
    </div>
    <div class="card-body p-0">
        <table class="table-custom min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-center w-12">No</th>
                    <th class="text-left">Plat Nomor / TRX</th>
                    <th class="text-left">Jenis</th>
                    <th class="text-left">Tanggal Keluar</th>
                    <th class="text-left">Operator</th>
                    <th class="text-right">Tarif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">
                        {{ $trx->plate_number }} <br>
                        <span class="text-xs text-gray-500 font-normal">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                    <td class="capitalize">{{ $trx->vehicle_type }}</td>
                    <td>{{ $trx->exit_time ? $trx->exit_time->translatedFormat('d M Y (H:i)') : 'Belum Keluar' }}</td>
                    <td>{{ $trx->operator->name ?? 'Sistem' }}</td>
                    <td class="text-right font-medium">Rp {{ number_format($trx->fee, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500 italic">
                        Tidak ada transaksi keluar pada kuartal ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-blue-50 border-t-2 border-blue-200">
                <tr>
                    <td colspan="5" class="text-right font-bold text-lg py-4 px-4 text-gray-800">
                        TOTAL PENDAPATAN TRIWULAN :
                    </td>
                    <td class="text-right font-black text-xl text-green-600 py-4 px-4">
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
