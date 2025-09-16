@extends('pondok.admin.layouts.app')

@section('title', 'Manajemen Kamar')
@section('page_title', 'Manajemen Kamar')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-700">Daftar Kamar</h2>
            <a href="{{ route('admin.kamar.create') }}" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                + Tambah Kamar Baru
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kamar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kapasitas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($kamar as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->nama_kamar }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->kapasitas }} orang</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->keterangan ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('admin.kamar.edit', $item->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form action="{{ route('admin.kamar.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            Belum ada data kamar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            {{ $kamar->links() }}
        </div>
    </div>
</div>
@endsection
