@extends('layouts.admin')

@section('title', 'Manajemen Kategori')
@section('page-title', 'Daftar Semua Kategori')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Semua Kategori</h2>

        <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">

            {{-- Form Filter yang Terhubung Langsung ke Controller --}}
            <form action="{{ route('admin.categories.index') }}" method="GET" class="flex flex-wrap md:flex-nowrap gap-2 w-full md:w-auto">

                {{-- Filter Tipe --}}
                <select name="type" onchange="this.form.submit()" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Tipe</option>
                    <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>Produk</option>
                    <option value="marketplace" {{ request('type') == 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                    <option value="blog" {{ request('type') == 'blog' ? 'selected' : '' }}>Blog / Artikel</option>
                </select>

                {{-- Filter Grup Kategori --}}
                <select name="category_group" onchange="this.form.submit()" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Grup</option>
                    <option value="produk_fisik" {{ request('category_group') == 'produk_fisik' ? 'selected' : '' }}>Fisik</option>
                    <option value="produk_digital" {{ request('category_group') == 'produk_digital' ? 'selected' : '' }}>Digital</option>
                    <option value="jasa" {{ request('category_group') == 'jasa' ? 'selected' : '' }}>Jasa</option>
                </select>

                {{-- Filter Flag / Pengiriman --}}
                <select name="flag" onchange="this.form.submit()" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Kurir</option>
                    <option value="non_fisik" {{ request('flag') == 'non_fisik' ? 'selected' : '' }}>Tanpa Ongkir</option>
                    <option value="fisik" {{ request('flag') == 'fisik' ? 'selected' : '' }}>KirimAja</option>
                    <option value="lokal" {{ request('flag') == 'lokal' ? 'selected' : '' }}>Mapbox (Radius)</option>
                </select>

            </form>

            <a href="{{ route('admin.categories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 whitespace-nowrap transition">
                + Tambah Kategori
            </a>
        </div>
    </div>

    @include('layouts.partials.notifications')

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe & Grup</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sistem Checkout</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ikon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $category->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ in_array($category->type, ['product', 'marketplace']) ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ ucfirst($category->type) }}
                        </span>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 mt-1 block w-max">
                            {{ ucwords(str_replace('_', ' ', $category->category_group)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($category->flag == 'fisik')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800" title="Menggunakan KirimAja">📦 Fisik (Kurir)</span>
                        @elseif($category->flag == 'non_fisik')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800" title="Bypass Halaman Ongkir">📧 Non-Fisik</span>
                        @elseif($category->flag == 'lokal')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-teal-100 text-teal-800" title="Radius Mapbox">📍 Lokal (Radius)</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <i class="fas {{ $category->icon ?? 'fa-tag' }} fa-fw text-gray-400"></i>
                        <span class="ml-1 text-xs">({{ $category->icon ?? 'Kosong' }})</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if(in_array($category->type, ['product', 'marketplace']))
                            {{ $category->products_count ?? 0 }} Produk
                        @elseif($category->type == 'blog')
                            {{ $category->posts_count ?? 0 }} Post
                        @else
                            0 Item
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus kategori ini?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-sm text-gray-500">
                        <i class="fas fa-box-open text-3xl mb-3 text-gray-300 block"></i>
                        Tidak ada kategori ditemukan. Coba ubah atau hapus filter di atas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
</div>
@endsection
