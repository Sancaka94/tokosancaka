@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="font-bold text-2xl text-gray-800">Riwayat Pencairan Komisi</h2>
            <p class="text-gray-500 text-sm mt-1">Daftar histori pencairan komisi yang masuk ke saldo agen.</p>
        </div>
        <div>
            <a href="{{ route('admin.komisi-agent.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg shadow-sm transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Komisi
            </a>
        </div>
    </div>

    <!-- PENCARIAN -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('admin.riwayat-pencairan.index') }}" method="GET" class="flex gap-4">
            <div class="flex-grow">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama Agen / Toko / WA..." class="w-full border-gray-200 rounded-lg text-sm px-4 py-2.5 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg px-6 py-2.5 text-sm transition">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
                </button>
            </div>
        </form>
    </div>

    <!-- TABEL RIWAYAT -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-emerald-50 border-b border-emerald-100 text-xs text-emerald-700 uppercase tracking-wider">
                        <th class="p-4 font-bold">Tanggal</th>
                        <th class="p-4 font-bold">Profil Agen</th>
                        <th class="p-4 font-bold">Keterangan</th>
                        <th class="p-4 font-bold text-right">Nominal Pencairan</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($riwayat as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 align-middle text-gray-600 whitespace-nowrap">
                            <i class="fa-regular fa-calendar text-emerald-500 mr-1"></i>
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }}
                        </td>
                        <td class="p-4 align-middle">
                            <p class="font-bold text-gray-800">{{ $item->nama_lengkap }}</p>
                            <p class="text-[11px] text-gray-500 uppercase"><i class="fa-solid fa-store"></i> {{ $item->store_name ?? '-' }}</p>
                        </td>
                        <td class="p-4 align-middle">
                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded text-xs border border-gray-200">
                                {{ $item->keterangan }}
                            </span>
                        </td>
                        <td class="p-4 align-middle text-right font-black text-emerald-600 text-base">
                            + Rp {{ number_format($item->nominal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-500">Belum ada riwayat pencairan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $riwayat->links() }}
        </div>
    </div>
</div>
@endsection
