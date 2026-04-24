@extends('layouts.admin')

@section('content')
<div class="w-full px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h3 class="text-2xl font-bold text-gray-800">Riwayat Nota</h3>
        <a href="{{ route('nota.create') }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Buat Nota Baru
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative mb-6 flex items-center" role="alert">
            <i class="fa-solid fa-circle-check mr-2"></i>
            <span class="block sm:inline font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">Tanggal</th>
                        <th scope="col" class="px-6 py-4 font-semibold">No. Nota</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Kepada</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Total Item</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Grand Total</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($notas as $nota)
                    <tr class="bg-white hover:bg-slate-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-bold text-gray-900">{{ $nota->no_nota }}</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $nota->kepada }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold px-2.5 py-1 rounded-md">
                                {{ $nota->items->count() }} Barang
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-emerald-600">
                            Rp {{ number_format($nota->total_harga, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <form action="{{ route('nota.destroy', $nota->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus nota ini?');" class="inline-block">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-white border border-red-500 hover:bg-red-500 font-medium rounded-md text-xs px-3 py-1.5 transition-colors duration-200 flex items-center justify-center">
                                    <i class="fa-solid fa-trash-can mr-1.5"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fa-regular fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                <p>Belum ada data nota.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($notas->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-white">
            {{ $notas->links() }}
        </div>
        @endif
    </div>
</div>
@endsection