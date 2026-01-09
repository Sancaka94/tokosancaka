{{-- resources/views/admin/products/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manajemen Produk')
@section('page-title', 'Manajemen Produk')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    {{-- Header: Judul & Tombol Tambah --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">Kelola Produk Anda</h2>
        <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700 font-semibold text-sm uppercase tracking-wider">
            TAMBAH PRODUK BARU
        </a>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- Form Pencarian & Filter --}}
    <form method="GET" action="{{ route('admin.products.index') }}" class="mb-4">
        <div class="flex flex-col md:flex-row gap-4 items-end">
            {{-- Search Bar --}}
            <div class="w-full md:w-1/3">
                <div class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk atau SKU..." 
                        class="w-full border border-gray-300 rounded-l px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-600">
                    <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-r font-semibold hover:bg-gray-900 uppercase text-sm">
                        CARI
                    </button>
                </div>
            </div>

            {{-- Filter Kategori --}}
            <div class="w-full md:w-1/4">
                <select name="category" onchange="this.form.submit()" class="w-full border border-gray-300 rounded px-3 py-2 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kategori</option>
                    @isset($categories)
                        @foreach($categories as $cat)
                            <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    @endisset
                </select>
            </div>
        </div>
    </form>

    {{-- Tombol Export --}}
    <div class="flex gap-2 mb-6">
        <button type="button" class="bg-green-600 text-white px-4 py-2 rounded font-bold text-sm hover:bg-green-700 shadow uppercase">
            EXPORT EXCEL
        </button>
        <button type="button" class="bg-red-600 text-white px-4 py-2 rounded font-bold text-sm hover:bg-red-700 shadow uppercase">
            EXPORT PDF
        </button>
    </div>

    {{-- Tabel Produk --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <th class="p-4">No</th>
                    <th class="p-4">Gambar</th>
                    <th class="p-4">Nama Produk</th>
                    <th class="p-4">SKU</th>
                    <th class="p-4">Kategori</th>
                    <th class="p-4">Harga</th>
                    <th class="p-4">Stok</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($products as $index => $product)
                <tr class="hover:bg-gray-50">
                    {{-- Nomor Urut --}}
                    <td class="p-4 text-gray-500">
                        {{ $products->firstItem() + $index }}
                    </td>

                    {{-- Kolom: image_url --}}
                    <td class="p-4">
                        @if($product->image_url)
                            <img src="{{ asset('public/storage/' . $product->image_url) }}" alt="Img" class="h-12 w-12 object-cover rounded border">
                        @else
                            <div class="h-12 w-12 bg-gray-100 rounded border flex items-center justify-center text-xs text-gray-400">
                                No IMG
                            </div>
                        @endif
                    </td>

                    {{-- Kolom: name --}}
                    <td class="p-4 font-medium text-gray-900">
                        {{ $product->name }}
                        {{-- Tampilkan label NEW atau BESTSELLER jika ada --}}
                        @if($product->is_new) <span class="text-[10px] bg-blue-100 text-blue-800 px-1 rounded">New</span> @endif
                        @if($product->is_bestseller) <span class="text-[10px] bg-yellow-100 text-yellow-800 px-1 rounded">Best</span> @endif
                    </td>

                    {{-- Kolom: sku --}}
                    <td class="p-4 text-gray-500 font-mono text-sm">
                        {{ $product->sku ?? '-' }}
                    </td>

                    {{-- Kolom: category (Prioritas relasi, fallback ke kolom string) --}}
                    <td class="p-4 text-gray-500">
                        {{ $product->category_relation->name ?? ($product->category ?? '-') }}
                    </td>

                    {{-- Kolom: price --}}
                    <td class="p-4 font-medium text-gray-900">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </td>

                    {{-- Kolom: stock --}}
                    <td class="p-4">
                        <span class="{{ $product->stock <= 5 ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                            {{ $product->stock }}
                        </span>
                    </td>

                    {{-- Kolom: status (active) --}}
                    <td class="p-4">
                        @if($product->status === 'active')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Aktif
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Nonaktif
                            </span>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td class="p-4 text-center">
                        <div class="flex justify-center space-x-2">
                            {{-- Edit (Gunakan slug sesuai DB) --}}
                            <a href="{{ route('admin.products.edit', $product->slug) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            {{-- Restock --}}
                            <button onclick="openRestockModal('{{ route('admin.products.restock', $product->slug) }}', '{{ $product->name }}')" class="text-green-600 hover:text-green-900" title="Restock">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                            
                            {{-- Hapus --}}
                            <form action="{{ route('admin.products.destroy', $product->slug) }}" method="POST" onsubmit="return confirm('Yakin hapus?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="p-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <p class="text-lg">Data tidak ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        @if($products->hasPages())
            {{ $products->withQueryString()->links() }}
        @endif
    </div>
</div>

<div id="restockModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">Restock Produk</h3>
        </div>
        <form id="restockForm" action="" method="POST" class="p-6">
            @csrf
            <p class="mb-4 text-gray-600">Tambah stok untuk: <strong id="productName" class="text-gray-900"></strong></p>
            <div class="mb-4">
                <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok Baru</label>
                <input type="number" name="stock" id="stock" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500" required min="1">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('restockModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRestockModal(url, name) {
        document.getElementById('restockForm').action = url;
        document.getElementById('productName').innerText = name;
        document.getElementById('restockModal').classList.remove('hidden');
    }
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
</script>
@endsection