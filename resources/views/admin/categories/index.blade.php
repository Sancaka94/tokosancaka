@extends('layouts.admin')

@section('title', 'Manajemen Kategori Produk')
@section('page-title', 'Kategori Produk')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        {{-- PERBAIKAN: Mengubah judul agar lebih spesifik --}}
        <h2 class="text-xl font-semibold text-gray-800">Daftar Kategori Produk Marketplace</h2>
        <div class="flex items-center gap-4">
             {{-- PERBAIKAN: Menghapus dropdown filter karena halaman ini sudah spesifik --}}
            <a href="{{ route('admin.categories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
                Tambah Kategori Baru
            </a>
        </div>
    </div>

    @include('layouts.partials.notifications')

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ikon</th>
                    {{-- PERBAIKAN: Mengubah header kolom --}}
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($categories as $category)
                    {{-- PERBAIKAN: Menambahkan kondisi untuk memastikan hanya tipe 'marketplace' yang tampil --}}
                    @if($category->type == 'marketplace')
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $category->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($category->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas {{ $category->icon ?? 'fa-tag' }}"></i> 
                                <span class="ml-2">({{ $category->icon ?? 'N/A' }})</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{-- PERBAIKAN: Hanya menampilkan jumlah produk --}}
                                {{ $category->products_count }}
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
                    @endif
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-sm text-gray-500">Tidak ada kategori produk ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $categories->links() }}</div>
</div>
@endsection

