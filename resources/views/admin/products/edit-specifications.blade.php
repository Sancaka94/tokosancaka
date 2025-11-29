@extends('layouts.admin')

@section('title', 'Edit Spesifikasi: ' . $product->name)

@section('content')
<div class="max-w-4xl mx-auto">
    
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Kategori & Spesifikasi</h1>
            <p class="text-sm text-gray-500">Produk: <span class="font-semibold">{{ $product->name }}</span></p>
        </div>
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="text-gray-500 hover:text-indigo-600 font-medium text-sm flex items-center">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Edit Produk
        </a>
    </div>

    <form action="{{ route('admin.products.update.specifications', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            
            {{-- 1. PENGATURAN DATA (SKU, Kategori, Tags) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Pengaturan Data</h2>
                
                <div class="space-y-4">
                    {{-- SKU --}}
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU Induk</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 required-label">Kategori <span class="text-red-500">*</span></label>
                        <select name="category_id" id="category_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                    data-attributes-url="{{ route('admin.categories.attributes', $category->id) }}" 
                                    {{-- PERBAIKAN DI SINI: Gunakan category_id langsung --}}
                                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-yellow-600 mt-1"><i class="fa-solid fa-triangle-exclamation"></i> Mengubah kategori akan mereset form spesifikasi di bawah.</p>
                    </div>

                    {{-- Tags --}}
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', $product->tags) }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jasa, Perizinan, Cepat">
                    </div>
                </div>
            </div>

            {{-- ... Sisa kode card spesifikasi dinamis & tombol simpan ... --}}
            {{-- Bagian bawah tidak perlu diubah karena tidak menyebabkan error ini --}}
            {{-- Pastikan penutup form dan div ada --}}
            
            {{-- 2. SPESIFIKASI TAMBAHAN (Dinamis) --}}
            <div id="attributes-card" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Spesifikasi Tambahan</h2>
                <div id="dynamic-attributes-container" class="space-y-4">
                    {{-- Diisi via JS --}}
                </div>
            </div>

        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.products.edit', $product->slug) }}" class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium shadow-md hover:bg-indigo-700 transition flex items-center">
                <i class="fa-solid fa-save mr-2"></i> Simpan Spesifikasi
            </button>
        </div>
    </form>
</div>
@endsection