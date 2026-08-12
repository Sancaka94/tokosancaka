@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Rencana Anggaran Biaya (RAB)</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar rincian pekerjaan dan biaya proyek.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <!-- Form Upload Excel -->
            <form action="{{ route('rab.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-white border border-gray-200 rounded-md px-2 py-1 shadow-sm hover:border-gray-300 transition-colors">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required 
                    class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors whitespace-nowrap">
                    Upload Excel
                </button>
            </form>

            <!-- Tombol PDF -->
            <a href="{{ route('rab.pdf') }}" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm text-sm font-medium py-2 px-4 rounded-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                PDF
            </a>

            <!-- Tombol Tambah Manual -->
            <a href="{{ route('rab.create') }}" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors shadow-sm whitespace-nowrap">
                + Tambah Item
            </a>
        </div>
    </div>

    <!-- Alert Success -->
    @if (session('success'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Table Section -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-100 border-b-2 border-gray-200 text-gray-900 font-bold text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-center w-12 border-r border-gray-200">No.</th>
                    <th class="px-4 py-3 border-r border-gray-200">URAIAN PEKERJAAN</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-24">VOL</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-24">SAT</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-40">HARGA SATUAN</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-40">TOTAL</th>
                    <th class="px-4 py-3 text-center w-28 text-gray-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @php 
                    $grandTotal = 0; 
                    $romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                    $categoryIndex = 0;
                    
                    // Mengelompokkan item berdasarkan kategori langsung dari Blade
                    $groupedItems = collect($items)->groupBy('kategori');
                @endphp

                @forelse ($groupedItems as $kategori => $kategoriItems)
                    @php 
                        $subTotal = $kategoriItems->sum('total'); 
                        $grandTotal += $subTotal;
                        $roman = $romanNumerals[$categoryIndex] ?? ($categoryIndex + 1);
                    @endphp

                    <!-- Category Title Row -->
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 text-center font-bold text-gray-900 border-r border-gray-200 align-top">{{ $roman }}</td>
                        <td colspan="6" class="px-4 py-3 font-bold text-gray-900 uppercase">
                            {{ $kategori ?: 'PEKERJAAN UMUM' }}
                        </td>
                    </tr>

                    <!-- Items Loop -->
                    @foreach ($kategoriItems as $index => $item)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-4 py-2 text-center text-gray-500 border-r border-gray-200">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 text-gray-800 border-r border-gray-200">{{ $item->uraian_pekerjaan }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 border-r border-gray-200">{{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-2 text-center text-gray-500 border-r border-gray-200">{{ $item->satuan }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 border-r border-gray-200">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 font-medium border-r border-gray-200">{{ number_format($item->total, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('rab.edit', $item->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-xs">Edit</a>
                                <form action="{{ route('rab.destroy', $item->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs" onclick="return confirm('Hapus item ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach

                    <!-- Sub Total Row -->
                    <tr>
                        <td class="px-4 py-3 border-r border-gray-200"></td>
                        <td colspan="4" class="px-4 py-3 text-center font-bold text-gray-900 border-r border-gray-200">
                            Sub Total {{ $roman }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 border-r border-gray-200 bg-gray-50">
                            {{ number_format($subTotal, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>

                    @php $categoryIndex++; @endphp
                @empty
                    <!-- Empty State -->
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 bg-white">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="font-medium text-gray-600">Belum ada data RAB.</span>
                                <span class="text-xs mt-1">Silakan tambah item secara manual atau upload melalui file Excel.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            
            <!-- Grand Total -->
            @if(count($items) > 0)
            <tfoot class="bg-gray-100 border-t-4 border-double border-gray-300">
                <tr>
                    <td class="px-4 py-4 border-r border-gray-200"></td>
                    <th colspan="4" class="px-4 py-4 text-center font-bold text-gray-900 border-r border-gray-200 uppercase text-sm tracking-wide">
                        TOTAL KESELURUHAN
                    </th>
                    <th class="px-4 py-4 text-right font-bold text-gray-900 border-r border-gray-200 text-sm">
                        {{ number_format($grandTotal, 0, ',', '.') }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection