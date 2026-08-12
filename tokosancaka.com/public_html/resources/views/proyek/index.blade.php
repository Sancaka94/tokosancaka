@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Daftar Proyek</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data proyek dan rencana anggaran biaya.</p>
        </div>
        <a href="{{ route('proyek.create') }}" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors shadow-sm">
            + Tambah Proyek
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold text-xs tracking-wider uppercase">
                <tr>
                    <th class="px-6 py-4 w-16 text-center">No.</th>
                    <th class="px-6 py-4">Nama Proyek</th>
                    <th class="px-6 py-4">Lokasi Proyek</th>
                    <th class="px-6 py-4">Nomor HP</th>
                    <th class="px-6 py-4 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($proyek as $index => $p)
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-6 py-4 text-center text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $p->nama_proyek }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $p->lokasi_proyek }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $p->nomor_hp }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- Tombol Mata (Lihat RAB) -->
                                <a href="{{ route('proyek.show', $p->id) }}" class="text-blue-600 hover:text-blue-800" title="Lihat RAB">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                                <!-- Tombol Edit -->
                                <a href="{{ route('proyek.edit', $p->id) }}" class="text-gray-500 hover:text-gray-800" title="Edit Proyek">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                <!-- Tombol Hapus -->
                                <form action="{{ route('proyek.destroy', $p->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Hapus proyek beserta seluruh data RAB-nya?')" title="Hapus Proyek">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            Belum ada data proyek.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection