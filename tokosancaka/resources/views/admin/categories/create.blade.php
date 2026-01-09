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
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror" required>
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Tipe Kategori</label>
                <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="" disabled selected>-- Pilih Tipe --</option>
                    <option value="marketplace" {{ old('type') == 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                    <option value="blog" {{ old('type') == 'blog' ? 'selected' : '' }}>Blog</option>
                </select>
            </div>
            <div>
                <label for="icon" class="block text-sm font-medium text-gray-700">Ikon (Contoh: fa-mobile-alt)</label>
                <input type="text" name="icon" id="icon" value="{{ old('icon') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="fa-tag">
                <p class="mt-1 text-xs text-gray-500">Gunakan class dari <a href="https://fontawesome.com/v5/search" target="_blank" class="text-indigo-600 hover:underline">Font Awesome 5</a>.</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Batal</a>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">Simpan</button>
        </div>
    </form>
</div>
@endsection
