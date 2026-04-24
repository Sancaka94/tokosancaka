@extends('layouts.admin')

@section('content')
<div class="w-full px-4 py-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <h3 class="text-2xl font-bold text-gray-800">Riwayat Nota</h3>
        
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('nota.export-excel') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm flex items-center transition">
                <i class="fa-solid fa-file-excel mr-2"></i> Excel
            </a>
            <a href="{{ route('nota.export-pdf') }}" class="bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm flex items-center transition">
                <i class="fa-solid fa-file-pdf mr-2"></i> PDF
            </a>
            <a href="{{ route('nota.create') }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-sm flex items-center transition">
                <i class="fa-solid fa-plus mr-2"></i> Buat Nota
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">No. Nota</th>
                        <th class="px-6 py-4">Kepada</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($notas as $nota)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($nota->tanggal)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $nota->no_nota }}</td>
                        <td class="px-6 py-4">{{ $nota->kepada }}</td>
                        <td class="px-6 py-4 font-bold text-emerald-600">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('nota.download', $nota->id) }}" class="text-blue-600 hover:bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-md transition flex items-center" title="Download PDF">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                
                                <a href="{{ route('nota.edit', $nota->id) }}" class="text-amber-600 hover:bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-md transition flex items-center" title="Edit Nota">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                
                                <form action="{{ route('nota.destroy', $nota->id) }}" method="POST" onsubmit="return confirm('Hapus nota ini?');" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-white border border-red-500 hover:bg-red-500 font-medium rounded-md text-xs px-3 py-1.5 transition-colors duration-200 flex items-center justify-center" title="Hapus Nota">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection