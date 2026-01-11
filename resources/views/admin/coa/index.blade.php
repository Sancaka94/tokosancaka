@extends('layouts.admin')

@section('title', 'Manajemen Akun (COA)')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Master Data Akun (COA)</h1>
            <p class="text-gray-500 mt-1">Kelola daftar akun untuk keperluan jurnal & laporan.</p>
        </div>
        <a href="{{ route('admin.coa.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg hover:shadow-blue-200 hover:-translate-y-0.5 transition-all font-medium flex items-center gap-2">
            <i class="fas fa-plus-circle"></i> Tambah Akun Baru
        </a>
    </div>

    {{-- TABS UNIT USAHA --}}
    <div class="mb-6 flex space-x-2 border-b border-gray-200">
        <a href="{{ route('admin.coa.index', ['unit' => 'Ekspedisi']) }}" 
           class="px-6 py-3 font-bold text-sm rounded-t-lg transition-colors {{ $unit == 'Ekspedisi' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
           <i class="fas fa-truck-fast mr-2"></i> Jasa Ekspedisi
        </a>
        <a href="{{ route('admin.coa.index', ['unit' => 'Percetakan']) }}" 
           class="px-6 py-3 font-bold text-sm rounded-t-lg transition-colors {{ $unit == 'Percetakan' ? 'bg-purple-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
           <i class="fas fa-print mr-2"></i> Percetakan
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm flex justify-between items-center">
            <div>
                <p class="font-bold">Berhasil!</p>
                <p>{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-800"><i class="fas fa-times"></i></button>
        </div>
    @endif

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase font-bold text-xs border-b">
                    <tr>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Akun</th>
                        <th class="px-6 py-4">Kategori (Induk)</th>
                        <th class="px-6 py-4">Jenis Laporan</th>
                        <th class="px-6 py-4 text-center">Tipe Arus</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($accounts as $acc)
                    <tr class="hover:bg-blue-50/30 transition duration-150">
                        <td class="px-6 py-3 font-bold text-blue-600">{{ $acc->kode_akun }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $acc->nama_akun }}</td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 bg-gray-100 rounded text-gray-600 text-xs border border-gray-200">
                                {{ $acc->kategori }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            @if($acc->jenis_laporan == 'Laba Rugi')
                                <span class="text-orange-600 font-medium">Laba Rugi</span>
                            @else
                                <span class="text-indigo-600 font-medium">Neraca</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($acc->tipe_arus == 'Pemasukan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-arrow-down mr-1"></i> Pemasukan
                                </span>
                            @elseif($acc->tipe_arus == 'Pengeluaran')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-arrow-up mr-1"></i> Pengeluaran
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-circle mr-1 text-[8px]"></i> Netral
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('admin.coa.edit', $acc->id) }}" class="text-yellow-500 hover:text-yellow-600 transition" title="Edit">
                                    <i class="fas fa-pencil-alt text-lg"></i>
                                </a>
                                <form action="{{ route('admin.coa.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akun {{ $acc->nama_akun }}? Data historis mungkin terpengaruh.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600 transition" title="Hapus">
                                        <i class="fas fa-trash text-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-folder-open text-4xl mb-3 block opacity-30"></i>
                            Belum ada data akun untuk unit usaha ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection