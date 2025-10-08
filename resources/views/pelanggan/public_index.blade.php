@extends('layouts.app')

@section('title', 'Daftar Pelanggan')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Daftar Pelanggan</h1>
    
    <!-- Header: Search -->
    <div class="flex justify-center mb-6">
        <form action="{{ route('pelanggan.public.index') }}" method="GET" class="relative w-full md:w-1/2">
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari Nama, ID, atau No. WA..." value="{{ request('search') }}">
            <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full text-gray-400">
                <i class="fa-solid fa-search"></i>
            </div>
        </form>
    </div>

    <!-- Tabel Data Pelanggan -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. WA</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($pelanggans as $pelanggan)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $pelanggan->id_pelanggan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $pelanggan->nama_pelanggan }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->nomor_wa ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->alamat }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $pelanggan->keterangan ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-10 text-gray-500">
                        <i class="fa-solid fa-folder-open fa-2x mb-2"></i>
                        <p>Data pelanggan tidak ditemukan.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    <div class="mt-6">
        {{ $pelanggans->appends(request()->query())->links() }}
    </div>
</div>
@endsection

