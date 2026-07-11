@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 bg-white text-black min-h-screen font-sans selection:bg-black selection:text-white">
    
    {{-- HEADER & TOMBOL IMPORT --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-3xl font-extrabold tracking-tight">Area AutoKirim</h1>
        
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.autokirim.create') }}" class="bg-black text-white px-5 py-2.5 text-sm font-bold hover:bg-gray-800 transition-colors border border-black rounded-none">
                + Tambah Manual
            </a>
            <form action="{{ route('admin.autokirim.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center">
                @csrf
                <label class="sr-only" for="file_upload">Upload File Excel</label>
                <input type="file" id="file_upload" name="file" class="text-sm border border-gray-300 px-3 py-2 focus:outline-none focus:border-black rounded-none h-[42px]" required accept=".xlsx,.xls,.csv">
                <button type="submit" class="border border-black border-l-0 bg-white text-black px-5 py-2.5 text-sm font-bold hover:bg-gray-100 transition-colors h-[42px] rounded-none">
                    Import Excel
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 border border-black bg-black text-white text-sm font-semibold flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-white opacity-70 hover:opacity-100">&times;</button>
        </div>
    @endif

    {{-- CARDS STATISTIK --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="border border-gray-200 p-5 flex flex-col justify-center items-start bg-gray-50/50 hover:border-black transition-colors">
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1">Total Provinsi</span>
            <span class="text-3xl font-black text-black">{{ number_format($totalProvinsi) }}</span>
        </div>
        <div class="border border-gray-200 p-5 flex flex-col justify-center items-start bg-gray-50/50 hover:border-black transition-colors">
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1">Total Kota / Kab</span>
            <span class="text-3xl font-black text-black">{{ number_format($totalKota) }}</span>
        </div>
        <div class="border border-gray-200 p-5 flex flex-col justify-center items-start bg-gray-50/50 hover:border-black transition-colors">
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1">Total Desa / Kec</span>
            <span class="text-3xl font-black text-black">{{ number_format($totalDesa) }}</span>
        </div>
    </div>

    {{-- FILTER & PENCARIAN --}}
    <form method="GET" action="{{ route('admin.autokirim.index') }}" class="mb-8 p-5 border border-gray-200 bg-gray-50/30 flex flex-col lg:flex-row gap-4 items-end">
        
        <div class="w-full lg:w-1/3">
            <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Pencarian Umum</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kodepos, Desa, Kota..." class="w-full border border-gray-300 px-3 py-2.5 text-sm focus:border-black focus:outline-none rounded-none bg-white transition-colors">
        </div>
        
        <div class="w-full lg:w-1/4">
            <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Filter Provinsi</label>
            <select name="province" class="w-full border border-gray-300 px-3 py-2.5 text-sm focus:border-black focus:outline-none rounded-none bg-white transition-colors cursor-pointer appearance-none">
                <option value="">-- Semua Provinsi --</option>
                @foreach($provinces as $prov)
                    <option value="{{ $prov }}" {{ request('province') == $prov ? 'selected' : '' }}>{{ $prov }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full lg:w-1/4">
            <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Filter Kota/Kab</label>
            <select name="regency" class="w-full border border-gray-300 px-3 py-2.5 text-sm focus:border-black focus:outline-none rounded-none bg-white transition-colors cursor-pointer appearance-none">
                <option value="">-- Semua Kota/Kabupaten --</option>
                @foreach($regencies as $reg)
                    <option value="{{ $reg }}" {{ request('regency') == $reg ? 'selected' : '' }}>{{ $reg }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full lg:w-auto flex gap-2">
            <button type="submit" class="flex-1 lg:flex-none bg-black text-white px-6 py-2.5 text-sm font-bold border border-black hover:bg-gray-800 transition-colors rounded-none">
                Terapkan
            </button>
            @if(request()->anyFilled(['search', 'province', 'regency']))
                <a href="{{ route('admin.autokirim.index') }}" class="flex-1 lg:flex-none bg-white text-black px-4 py-2.5 text-sm font-bold border border-gray-300 hover:border-black hover:bg-gray-50 text-center transition-colors rounded-none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- TABEL DATA --}}
    <div class="overflow-x-auto border border-gray-200">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="border-b border-gray-200 bg-gray-50/50">
                <tr>
                    <th class="px-5 py-4 font-extrabold text-[11px] uppercase tracking-wider text-gray-500">ZIP</th>
                    <th class="px-5 py-4 font-extrabold text-[11px] uppercase tracking-wider text-gray-500">District ID</th>
                    <th class="px-5 py-4 font-extrabold text-[11px] uppercase tracking-wider text-gray-500">District Name (Kec/Desa)</th>
                    <th class="px-5 py-4 font-extrabold text-[11px] uppercase tracking-wider text-gray-500">Regency (Kota/Kab)</th>
                    <th class="px-5 py-4 font-extrabold text-[11px] uppercase tracking-wider text-gray-500">Province</th>
                    <th class="px-5 py-4 font-extrabold text-[11px] uppercase tracking-wider text-gray-500 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $item)
                <tr class="hover:bg-gray-50/80 transition-colors group">
                    <td class="px-5 py-3.5 text-gray-900 font-medium">{{ $item->zip }}</td>
                    <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $item->district_id }}</td>
                    <td class="px-5 py-3.5 font-bold text-black">{{ $item->district_name }}</td>
                    <td class="px-5 py-3.5 text-gray-700">{{ $item->regency_name }}</td>
                    <td class="px-5 py-3.5 text-gray-700">{{ $item->province_name }}</td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('admin.autokirim.edit', $item->id) }}" class="text-gray-400 hover:text-black mr-4 font-semibold transition-colors text-xs uppercase tracking-wider">Edit</a>
                        <form action="{{ route('admin.autokirim.destroy', $item->id) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 font-semibold transition-colors text-xs uppercase tracking-wider" onclick="return confirm('Yakin ingin menghapus area ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="text-gray-400 font-bold text-sm">Tidak ada data ditemukan.</div>
                        <div class="text-gray-400 text-xs mt-1">Coba sesuaikan filter atau import data Excel baru.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-6">
        {{ $data->links() }}
    </div>
</div>
@endsection