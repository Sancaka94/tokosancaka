@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <div class="flex flex-wrap justify-between items-center mb-6 border-b border-gray-700 pb-4 gap-4">
        <h1 class="text-3xl font-bold text-white">Manajemen Kode Akun</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.coa.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                <i class="fa-solid fa-plus mr-2"></i> Tambah Akun
            </a>
             <a href="{{ route('admin.coa.import.form') }}" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                <i class="fa-solid fa-file-import mr-2"></i> Import Excel
            </a>
            <a href="{{ route('admin.coa.export.excel') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                <i class="fa-solid fa-file-excel mr-2"></i> Export Excel
            </a>
            <a href="{{ route('admin.coa.export.pdf') }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                <i class="fa-solid fa-file-pdf mr-2"></i> Export PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-6">{{ session('error') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-gray-900 rounded-lg">
            <thead>
                <tr>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-300 uppercase">Kode</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-300 uppercase">Nama Akun</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-300 uppercase">Tipe</th>
                    <th class="py-3 px-4 text-center text-sm font-semibold text-gray-300 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($coas as $coa)
                <tr>
                    <td class="py-3 px-4 text-white font-mono">{{ $coa->kode }}</td>
                    <td class="py-3 px-4 text-white">{{ $coa->nama }}</td>
                    <td class="py-3 px-4 text-white">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @switch($coa->tipe)
                                @case('aset') bg-blue-500 text-blue-100 @break
                                @case('kewajiban') bg-yellow-500 text-yellow-100 @break
                                @case('ekuitas') bg-purple-500 text-purple-100 @break
                                @case('pendapatan') bg-green-500 text-green-100 @break
                                @case('beban') bg-red-500 text-red-100 @break
                            @endswitch
                        ">{{ ucfirst($coa->tipe) }}</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('admin.coa.edit', $coa->id) }}" class="text-blue-400 hover:text-blue-300 mr-4">Edit</a>
                        <form action="{{ route('admin.coa.destroy', $coa->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-300">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-gray-400">Belum ada data kode akun.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">
        {{ $coas->links() }}
    </div>
</div>
@endsection

