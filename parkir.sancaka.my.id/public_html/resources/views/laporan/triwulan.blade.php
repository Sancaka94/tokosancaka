@extends('layouts.app')
@section('title', 'Laporan Triwulan')

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .card { box-shadow: none; border: none; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 no-print">
    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan Triwulan</h1>
    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-bold flex items-center gap-2 shadow-md transition-colors w-full sm:w-auto justify-center">
        🖨️ Cetak Laporan
    </button>
</div>

<div class="bg-white rounded-xl mb-6 border-t-4 border-blue-600 shadow-sm no-print p-4 md:p-5">
    <form action="{{ route('laporan.triwulan') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 md:flex-none">
            <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Kuartal (Triwulan)</label>
            <select name="kuartal" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                <option value="1" {{ $kuartal == 1 ? 'selected' : '' }}>Kuartal 1 (Januari - Maret)</option>
                <option value="2" {{ $kuartal == 2 ? 'selected' : '' }}>Kuartal 2 (April - Juni)</option>
                <option value="3" {{ $kuartal == 3 ? 'selected' : '' }}>Kuartal 3 (Juli - September)</option>
                <option value="4" {{ $kuartal == 4 ? 'selected' : '' }}>Kuartal 4 (Oktober - Desember)</option>
            </select>
        </div>
        <div class="w-full sm:w-auto flex-1 md:flex-none">
            <label class="block text-sm font-bold text-gray-700 mb-1">Tahun</label>
            <input type="number" name="tahun" value="{{ $tahun }}" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500" min="2020" max="{{ date('Y') }}">
        </div>
        <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-md font-bold transition-colors shadow-sm">
            Tampilkan
        </button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="bg-white border-b-2 border-gray-100 text-center py-5">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wider">Rekap Pendapatan Triwulan</h2>
        <p class="text-gray-500 font-medium mt-1">Periode: Kuartal {{ $kuartal }} Tahun {{ $tahun }}</p>
    </div>

    <div class="overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">No</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Plat Nomor / TRX</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tgl Keluar</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Operator</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tarif + Toilet</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($transactions as $index => $trx)
                <tr class="hover:bg-gray-50 border-b border-gray-100">
                    <td class="px-4 py-3 text-center text-sm text-gray-700 align-top">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 align-top">
                        <span class="font-bold text-gray-800 whitespace-nowrap">{{ $trx->plate_number }}</span><br>
                        <span class="text-xs text-gray-500 font-medium whitespace-nowrap">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm capitalize text-gray-700 align-top">{{ $trx->vehicle_type }}</td>
                    <td class="px-4 py-3 text-sm align-top whitespace-nowrap">
                        <span class="font-bold text-blue-600">
                            {{ $trx->exit_time ? \Carbon\Carbon::parse($trx->exit_time)->translatedFormat('d M Y (H:i)') : '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 align-top whitespace-nowrap">
                        {{ optional($trx->operator)->name ?? 'Sistem' }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-800 align-top whitespace-nowrap">
                        Rp {{ number_format($trx->fee + ($trx->toilet_fee ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 italic">Tidak ada transaksi keluar pada kuartal ini.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-blue-50 border-t-2 border-blue-100">
                <tr>
                    <td colspan="5" class="px-4 py-4 text-right font-bold text-gray-700 uppercase tracking-wider">TOTAL PENDAPATAN PARKIR :</td>
                    <td class="px-4 py-4 text-right font-black text-xl text-green-600 whitespace-nowrap">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-8">
    <div class="bg-white border-b-2 border-green-500 text-center py-4">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wider">Rekap Kas Manual (Luar Sistem)</h2>
    </div>

    <div class="overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori Utama</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Nominal</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($kasManual as $kas)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center text-sm font-medium text-gray-700 align-top">{{ \Carbon\Carbon::parse($kas->tanggal)->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm font-bold text-gray-800 align-top">{{ $kas->kategori }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 align-top">{{ $kas->keterangan ?? '-' }}</td>
                    <td class="px-4 py-3 text-right text-sm font-bold align-top whitespace-nowrap">
                        @if($kas->jenis == 'pemasukan')
                            <span class="text-green-600">+ Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                        @else
                            <span class="text-red-600">- Rp {{ number_format($kas->nominal, 0, ',', '.') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 italic">Tidak ada catatan kas manual pada kuartal ini.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700 uppercase">Total Pemasukan Manual :</td>
                    <td class="px-4 py-3 text-right font-black text-lg text-green-600 whitespace-nowrap">+ Rp {{ number_format($totalPemasukanManual, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700 uppercase">Total Pengeluaran Manual :</td>
                    <td class="px-4 py-3 text-right font-black text-lg text-red-600 whitespace-nowrap">- Rp {{ number_format($totalPengeluaranManual, 0, ',', '.') }}</td>
                </tr>
                <tr class="bg-blue-100 border-t border-blue-200">
                    <td colspan="3" class="px-4 py-4 text-right font-black text-blue-900 uppercase tracking-wider text-lg">TOTAL PENDAPATAN BERSIH TRIWULAN INI :<br><span class="text-xs font-normal tracking-normal text-blue-700">(Parkir Otomatis + Pemasukan Manual - Pengeluaran)</span></td>
                    <td class="px-4 py-4 text-right font-black text-2xl text-blue-700 whitespace-nowrap">Rp {{ number_format($total + $totalPemasukanManual - $totalPengeluaranManual, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
