@extends('pondok.admin.layouts.app')

@section('title', 'Manajemen Paket')
@section('page_title', 'Daftar Paket Berlangganan')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Master Data Paket</h3>
            <a href="{{ route('admin.paket.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                + Tambah Paket
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 text-left font-bold text-gray-600 uppercase text-xs">Nama Paket</th>
                        <th class="py-3 px-4 text-left font-bold text-gray-600 uppercase text-xs">Periode</th>
                        <th class="py-3 px-4 text-left font-bold text-gray-600 uppercase text-xs">Harga</th>
                        <th class="py-3 px-4 text-left font-bold text-gray-600 uppercase text-xs">Fitur / Deskripsi</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-600 uppercase text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
    @forelse($paket as $p)
    <tr class="hover:bg-gray-50 transition">
        <td class="py-3 px-4 font-semibold text-gray-800">{{ $p->nama_paket }}</td>
        
        <td class="py-3 px-4">
            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                {{ $p->periode_hari }} Hari
            </span>
        </td>

        <td class="py-3 px-4 font-mono text-green-600 font-bold">
            Rp {{ number_format($p->harga, 0, ',', '.') }}
        </td>

        <td class="py-3 px-4 text-sm text-gray-500">
            {{ Str::limit($p->deskripsi ?? '-', 40) }}
        </td>

        <td class="py-3 px-4 text-center">
            <div class="flex items-center justify-center space-x-2">
                
                <a href="{{ route('admin.paket.show', $p->id) }}" class="text-blue-500 hover:text-blue-700" title="Lihat Detail">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </a>

                <a href="{{ route('admin.paket.edit', $p->id) }}" class="text-yellow-500 hover:text-yellow-700" title="Edit Paket">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>

                <form action="{{ route('admin.paket.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus paket ini?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="5" class="py-6 text-center text-gray-400">Belum ada data paket.</td>
    </tr>
    @endforelse
</tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $paket->links() }}
        </div>
    </div>
</div>
@endsection