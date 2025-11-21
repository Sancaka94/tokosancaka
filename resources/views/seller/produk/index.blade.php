@extends('layouts.customer')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">Kelola Produk Anda</h2>
            <a href="{{ route('seller.produk.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 w-full md:w-auto justify-center">
                Tambah Produk Baru
            </a>
        </div>

        {{-- Menampilkan notifikasi sukses atau error --}}
        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md relative" role="alert">
                <strong class="font-bold">Sukses!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                <strong class="font-bold">Gagal!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        {{-- Selesai notifikasi --}}

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                {{-- ============================================= --}}
                {{-- BAGIAN BARU: PENCARIAN DAN EKSPOR --}}
                {{-- ============================================= --}}
                <div class="mb-4">
                    <form action="{{ route('seller.produk.index') }}" method="GET" class="flex flex-col md:flex-row gap-2">
                        <input type="text" name="search"
                               class="block w-full md:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               placeholder="Cari nama produk atau SKU..."
                               value="{{ request('search') }}">
                        <button type="submit"
                                class="inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900">
                            Cari
                        </button>
                    </form>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('seller.produk.export.excel', ['search' => request('search')]) }}"
                           class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                            Export Excel
                        </a>
                        <a href="{{ route('seller.produk.export.pdf', ['search' => request('search')]) }}"
                           class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                            Export PDF
                        </a>
                    </div>
                </div>
                {{-- ============================================= --}}
                {{-- AKHIR BAGIAN BARU --}}
                {{-- ============================================= --}}


                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gambar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            
                            {{-- Memulai @forelse --}}
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-6 py-4">
                                        {{-- PERBAIKAN: Path asset gambar --}}
                                        <img src="{{ $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/100x100/e2e8f0/9ca3af?text=N/A' }}" 
                                             alt="{{ $product->name }}" 
                                             class="w-16 h-16 object-cover rounded"
                                             onerror="this.src='https://placehold.co/100x100/e2e8f0/9ca3af?text=N/A'">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $product->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->sku ?? '-' }}</td>
                                    
                                    {{-- PERBAIKAN: Cara memanggil nama kategori --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->category ?? '-' }}</td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $product->stock }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($product->weight, 0, ',', '.') }} gr</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        
                                        @if($product->status == 'active')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Tidak Aktif
                                            </span>
                                        @endif 
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('seller.produk.edit', $product->slug) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        
                                        <form action="{{ route('seller.produk.destroy', $product->slug) }}" method="POST" class="inline-block ml-4" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- PERBAIKAN: colspan disesuaikan menjadi 9 --}}
                                    <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                        @if(request('search'))
                                            Produk dengan nama/SKU "{{ request('search') }}" tidak ditemukan.
                                        @else
                                            Anda belum memiliki produk.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse 
                            {{-- Menutup @forelse --}}

                        </tbody>
                    </table>
                </div>

                 <div class="mt-4">
                    {{-- PERBAIKAN: Menambahkan appends() agar pagination berfungsi dengan pencarian --}}
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection