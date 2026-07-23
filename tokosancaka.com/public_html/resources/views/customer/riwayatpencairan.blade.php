@extends('layouts.customer')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-5xl">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <div>
            <h2 class="font-bold text-2xl text-gray-800">Riwayat Pencairan Komisi</h2>
            <p class="text-gray-500 text-sm mt-1">Daftar histori saldo komisi yang berhasil dicairkan ke akun Anda.</p>
        </div>

        <!-- Card Ringkasan Total Dicairkan -->
        <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-xl p-4 shadow-lg text-white min-w-[250px]">
            <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wide mb-1">Total Telah Dicairkan</p>
            <h3 class="text-2xl font-black">Rp {{ number_format($totalDicairkan, 0, ',', '.') }}</h3>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-700 text-sm"><i class="fa-solid fa-clock-rotate-left mr-2 text-emerald-500"></i> Mutasi Pencairan Terakhir</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="p-4 font-bold w-16 text-center">No</th>
                        <th class="p-4 font-bold">Tanggal & Waktu</th>
                        <th class="p-4 font-bold">Keterangan</th>
                        <th class="p-4 font-bold text-right">Nominal Masuk</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($riwayat as $index => $item)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-4 align-middle text-center text-gray-500">
                            {{ $riwayat->firstItem() + $index }}
                        </td>
                        <td class="p-4 align-middle text-gray-700 whitespace-nowrap">
                            <div class="font-semibold text-gray-800">
                                {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d F Y') }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                <i class="fa-regular fa-clock mr-1"></i> {{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }} WIB
                            </div>
                        </td>
                        <td class="p-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-semibold border border-emerald-100">
                                <i class="fa-solid fa-check-circle"></i>
                                {{ $item->keterangan ?? 'Pencairan komisi ke saldo agen' }}
                            </span>
                        </td>
                        <td class="p-4 align-middle text-right">
                            <span class="font-black text-emerald-600 text-base">
                                + Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-10 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="fa-solid fa-money-bill-transfer text-4xl mb-3 opacity-50"></i>
                                <p class="text-gray-500 font-medium">Belum ada riwayat pencairan.</p>
                                <p class="text-xs mt-1">Komisi Anda yang cair akan muncul di sini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($riwayat->hasPages())
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $riwayat->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
