@extends('layouts.admin')

@section('title', 'Tambah Kategori Baru')
@section('page-title', 'Tambah Kategori')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div class="space-y-4">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror" required>
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Tipe Modul</label>
                <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('type') border-red-500 @enderror" required>
                    <option value="" disabled {{ old('type') ? '' : 'selected' }}>-- Pilih Tipe --</option>
                    <option value="product" {{ old('type') == 'product' ? 'selected' : '' }}>Produk (Toko / Umum)</option>
                    <option value="marketplace" {{ old('type') == 'marketplace' ? 'selected' : '' }}>Marketplace (Etalase)</option>
                    <option value="blog" {{ old('type') == 'blog' ? 'selected' : '' }}>Blog / Artikel</option>
                </select>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category_group" class="block text-sm font-medium text-gray-700">Grup Kategori</label>
                <select name="category_group" id="category_group" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('category_group') border-red-500 @enderror" required>
                    <option value="" disabled {{ old('category_group') ? '' : 'selected' }}>-- Pilih Grup --</option>
                    <option value="produk_fisik" {{ old('category_group') == 'produk_fisik' ? 'selected' : '' }}>Produk Fisik</option>
                    <option value="produk_digital" {{ old('category_group') == 'produk_digital' ? 'selected' : '' }}>Produk Digital / Virtual</option>
                    <option value="jasa" {{ old('category_group') == 'jasa' ? 'selected' : '' }}>Layanan Jasa</option>
                </select>
                @error('category_group') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="flag" class="block text-sm font-medium text-gray-700">Tipe Pengiriman (Flag Checkout)</label>
                <select name="flag" id="flag" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('flag') border-red-500 @enderror" required>
                    <option value="" disabled {{ old('flag') ? '' : 'selected' }}>-- Pilih Tipe Pengiriman --</option>
                    <option value="non_fisik" {{ old('flag') == 'non_fisik' ? 'selected' : '' }}>Non-Fisik (Tanpa Ongkir / Email / Tiket)</option>
                    <option value="fisik" {{ old('flag') == 'fisik' ? 'selected' : '' }}>Fisik (Hitung Ongkir via KirimAja)</option>
                    <option value="lokal" {{ old('flag') == 'lokal' ? 'selected' : '' }}>Lokal (Hitung Jarak via Mapbox)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Pilihan ini menentukan sistem kurir/ongkir saat pembeli melakukan checkout.</p>
                @error('flag') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="icon" class="block text-sm font-medium text-gray-700">Ikon (Contoh: fa-mobile-alt)</label>
                <input type="text" name="icon" id="icon" value="{{ old('icon') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('icon') border-red-500 @enderror" placeholder="fa-tag">
                <p class="mt-1 text-xs text-gray-500">Gunakan class dari <a href="https://fontawesome.com/v6/search?m=free" target="_blank" class="text-indigo-600 hover:underline">Font Awesome</a>.</p>
                @error('icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300 transition">Batal</a>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Simpan Kategori</button>
        </div>
    </form>
</div>
@endsection
