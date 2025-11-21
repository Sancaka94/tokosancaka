@extends('layouts.admin')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori: ' . $category->name)

@section('content')
<div class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm @error('name') border-red-500 @enderror" required>
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Tipe Kategori</label>
                <select name="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="marketplace" {{ old('type', $category->type) == 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                    <option value="blog" {{ old('type', $category->type) == 'blog' ? 'selected' : '' }}>Blog</option>
                </select>
            </div>
            <div>
                <label for="icon" class="block text-sm font-medium text-gray-700">Ikon (Contoh: fa-mobile-alt)</label>
                <input type="text" name="icon" id="icon" value="{{ old('icon', $category->icon) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="fa-tag">
                <p class="mt-1 text-xs text-gray-500">Gunakan class dari <a href="https://fontawesome.com/v5/search" target="_blank" class="text-indigo-600 hover:underline">Font Awesome 5</a>.</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Batal</a>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">Perbarui</button>
        </div>
    </form>
</div>
@endsection
