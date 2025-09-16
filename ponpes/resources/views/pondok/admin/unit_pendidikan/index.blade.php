@extends('pondok.admin.layouts.app')

@section('title', 'Manajemen Unit Pendidikan')
@section('page_title', 'Unit Pendidikan')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-700">Daftar Unit Pendidikan</h2>
            <a href="{{ route('admin.unit-pendidikan.create') }}" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                + Tambah Unit Baru
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kepala Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($unitPendidikan as $unit)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $unit->nama_unit }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $unit->nama_kepala_unit ?? 'Belum Diatur' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ Str::limit($unit->keterangan, 50) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="{{ route('admin.unit-pendidikan.edit', $unit->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                            <form action="{{ route('admin.unit-pendidikan.destroy', $unit->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                            Belum ada data unit pendidikan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $unitPendidikan->links() }}
        </div>
    </div>
</div>
@endsection
