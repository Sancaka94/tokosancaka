@extends('layouts.app')
@section('title', 'Laporan Harian')

@section('content')
<style>
    /* Sembunyikan elemen yang tidak perlu saat di-print */
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .card { box-shadow: none; border: none; }
        /* Memastikan warna background pada header/footer tabel tetap tercetak */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 no-print">
    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan Harian</h1>
    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-bold flex items-center gap-2 shadow-md transition-colors w-full sm:w-auto justify-center">
        🖨️ Cetak Laporan
    </button>
</div>

<div class="bg-white rounded-xl mb-6 border-t-4 border-blue-600 shadow-sm no-print p-4 md:p-5">
    <form action="{{ route('laporan.harian') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-auto">
            <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Tanggal</label>
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-md font-bold transition-colors shadow-sm">
            Tampilkan
        </button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-white border-b-2 border-gray-100 text-center py-5">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wider">Rekap Pendapatan Parkir Harian</h2>
        <p class="text-gray-500 font-medium mt-1">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
    </div>

    <div class="overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">No</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Plat Nomor / TRX</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Jam Keluar</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Operator</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tarif + Toilet</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($transactions as $index => $trx)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center text-sm text-gray-700 align-top">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 align-top">
                        <span class="font-bold text-gray-800 whitespace-nowrap">{{ $trx->plate_number }}</span> <br>
                        <span class="text-xs text-gray-500 font-medium whitespace-nowrap">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm capitalize text-gray-700 align-top">{{ $trx->vehicle_type }}</td>
                    <td class="px-4 py-3 text-sm align-top">
                        <span class="font-bold text-blue-600 whitespace-nowrap">
                            {{ $trx->exit_time ? \Carbon\Carbon::parse($trx->exit_time)->translatedFormat('H:i') . ' WIB' : '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 align-top whitespace-nowrap">
                        {{ optional($trx->operator)->name ?? 'Sistem' }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-800 align-top whitespace-nowrap">
                        Rp {{ number_format($trx->fee + ($trx->toilet_fee ?? 0), 0, ',', '.') }}
                        @if(($trx->toilet_fee ?? 0) > 0)
                            <span class="text-[10px] text-blue-500 block font-normal">(Termasuk Toilet)</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 italic">Tidak ada transaksi keluar pada tanggal ini.</td>
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

    <div class="card shadow-sm border-0 mt-8">
    <div class="card-header bg-white border-b-2 border-green-500 text-center py-4">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wider">Rekap Kas Manual (Luar Sistem)</h2>
    </div>

    <div class="card-body p-0 overflow-x-auto">
        <table class="table-custom min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">No</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori Utama</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Nominal</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($kasManual as $index => $kas)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center text-sm text-gray-700 align-top">{{ $index + 1 }}</td>
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
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 italic">Tidak ada catatan kas manual pada tanggal ini.</td>
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
                    <td colspan="3" class="px-4 py-4 text-right font-black text-blue-900 uppercase tracking-wider text-lg">TOTAL PENDAPATAN BERSIH HARI INI :<br><span class="text-xs font-normal tracking-normal text-blue-700">(Parkir Otomatis + Pemasukan Manual - Pengeluaran)</span></td>
                    <td class="px-4 py-4 text-right font-black text-2xl text-blue-700 whitespace-nowrap">Rp {{ number_format($total + $totalPemasukanManual - $totalPengeluaranManual, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</div>
@endsection
