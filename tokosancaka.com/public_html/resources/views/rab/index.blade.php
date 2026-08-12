@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Rencana Anggaran Biaya (RAB)</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar rincian pekerjaan dan biaya proyek.</p>
        </div>
        <a href="{{ route('rab.create') }}" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors">
            + Tambah Pekerjaan
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                <tr>
                    <th class="px-6 py-3 font-medium">No.</th>
                    <th class="px-6 py-3 font-medium">Kategori</th>
                    <th class="px-6 py-3 font-medium">Uraian Pekerjaan</th>
                    <th class="px-6 py-3 font-medium text-right">Vol</th>
                    <th class="px-6 py-3 font-medium">Sat</th>
                    <th class="px-6 py-3 font-medium text-right">Harga Satuan</th>
                    <th class="px-6 py-3 font-medium text-right">Total</th>
                    <th class="px-6 py-3 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($items as $index => $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $item->kategori ?: '-' }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $item->uraian_pekerjaan }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($item->volume, 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $item->satuan }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-medium text-gray-900">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center space-x-2">
                            <a href="{{ route('rab.edit', $item->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                            <form action="{{ route('rab.destroy', $item->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium" onclick="return confirm('Hapus item ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            Belum ada data RAB.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection