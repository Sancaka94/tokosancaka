@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    <!-- Breadcrumb & Detail Proyek -->
    <div class="mb-6">
        <a href="{{ route('proyek.index') }}" class="text-sm text-gray-500 hover:text-blue-600 mb-3 inline-flex items-center gap-1">
            &larr; Kembali ke Daftar Proyek
        </a>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
            <h1 class="text-xl font-bold text-gray-900 uppercase">{{ $proyek->nama_proyek }}</h1>
            <div class="mt-2 flex flex-col sm:flex-row gap-4 text-sm text-gray-600">
                <div class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> {{ $proyek->lokasi_proyek }}</div>
                <div class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> {{ $proyek->nomor_hp }}</div>
            </div>
        </div>
    </div>

   <!-- Header Tabel Action -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
        <h2 class="text-lg font-bold text-gray-900">Rincian Pekerjaan</h2>
        
        <div class="flex flex-wrap items-center gap-3">
            <!-- Form Upload Excel -->
            <form action="{{ route('rab.import', $proyek->id) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-white border border-gray-200 rounded-md px-2 py-1 shadow-sm">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-xs font-medium py-1.5 px-3 rounded whitespace-nowrap">Upload Excel</button>
            </form>
            
            <!-- LOG LOG: TOMBOL SHARE LINK BARU -->
            <button onclick="copyShareLink('{{ route('proyek.public.share', $proyek->id) }}', this)" class="bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 shadow-sm text-sm font-medium py-2 px-4 rounded-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                <span>Copy Link Public</span>
            </button>
            
            <!-- Tombol PDF -->
            <a href="{{ route('rab.pdf', $proyek->id) }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm text-sm font-medium py-2 px-4 rounded-md flex items-center gap-2">
                PDF
            </a>
            
            <!-- Tombol Tambah Item -->
            <a href="{{ route('rab.create', ['proyek_id' => $proyek->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md shadow-sm">
                + Tambah Item
            </a>
        </div>
    </div>

    <!-- Gunakan kode tabel sticky yang sudah kita buat sebelumnya di sini -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-auto max-h-[60vh]">
         <!-- ... (Paste struktur <table> dari jawaban sebelumnya di sini) ... -->

             <!-- Menghapus whitespace-nowrap dari tag table -->
        <table class="w-full text-sm text-left">
            <!-- Menambahkan sticky top-0 dan z-20 agar header tetap di atas -->
            <thead class="bg-gray-100 border-b-2 border-gray-200 text-gray-900 font-bold text-xs tracking-wider sticky top-0 z-30 shadow-sm">
                <tr>
                    <th class="px-4 py-3 text-center w-12 border-r border-gray-200 whitespace-nowrap">No.</th>
                    <th class="px-4 py-3 border-r border-gray-200 whitespace-nowrap">URAIAN PEKERJAAN</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-24 whitespace-nowrap">VOL</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-24 whitespace-nowrap">SAT</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-40 whitespace-nowrap">HARGA SATUAN</th>
                    <th class="px-4 py-3 text-center border-r border-gray-200 w-40 whitespace-nowrap">TOTAL</th>
                    <th class="px-4 py-3 text-center w-28 text-gray-500 whitespace-nowrap">Aksi</th>
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
                            <td class="px-4 py-2 text-center text-gray-500 border-r border-gray-200 align-top">{{ $index + 1 }}</td>
                            <!-- Kolom ini diberi whitespace-normal dan min-w agar bisa turun ke bawah/responsif -->
                            <td class="px-4 py-2 text-gray-800 border-r border-gray-200 whitespace-normal break-words min-w-[250px] align-top">
                                {{ $item->uraian_pekerjaan }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-800 border-r border-gray-200 align-top whitespace-nowrap">{{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-2 text-center text-gray-500 border-r border-gray-200 align-top whitespace-nowrap">{{ $item->satuan }}</td>
                            <td class="px-4 py-2 text-right text-gray-800 border-r border-gray-200 align-top whitespace-nowrap">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 font-medium border-r border-gray-200 align-top whitespace-nowrap">{{ number_format($item->total, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity align-top whitespace-nowrap">
                                <a href="{{ route('rab.edit', $item->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-xs">Edit</a>
                                <form action="{{ route('rab.destroy', $item->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs" onclick="return confirm('Hapus item ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach

                    <!-- Sub Total Row (Menambahkan sticky bottom-0) -->
                    <tr class="sticky bottom-0 z-10 bg-white outline outline-1 outline-gray-200 shadow-[0_-2px_4px_rgba(0,0,0,0.02)]">
                        <td class="px-4 py-3 border-r border-gray-200 bg-white"></td>
                        <td colspan="4" class="px-4 py-3 text-center font-bold text-gray-900 border-r border-gray-200 bg-white">
                            Sub Total {{ $roman }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 border-r border-gray-200 bg-gray-50">
                            {{ number_format($subTotal, 0, ',', '.') }}
                        </td>
                        <td class="bg-white"></td>
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
            
            <!-- Grand Total (Menambahkan sticky bottom-0 dengan z-index lebih tinggi) -->
            @if(count($items) > 0)
            <tfoot class="sticky bottom-0 z-20 bg-gray-100 outline outline-1 outline-gray-300 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
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

        <!-- LOG LOG -->
    

    </div>

    <!-- Form Catatan Tambahan -->
    <div class="mt-8 bg-white border border-gray-200 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-3">Catatan Tambahan</h3>
        <form action="{{ route('proyek.catatan', $proyek->id) }}" method="POST">
            @csrf
            <textarea name="catatan" rows="4" placeholder="Tuliskan catatan khusus untuk proyek ini (Opsional)..." 
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-black focus:border-black mb-3">{{ $proyek->catatan }}</textarea>
            <div class="flex justify-end">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-6 rounded-md transition-colors">
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection