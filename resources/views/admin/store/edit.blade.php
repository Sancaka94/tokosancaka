@extends('layouts.admin')
@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Toko: {{ $store->name }}</h1>
    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" action="{{ route('admin.stores.update', $store->id) }}">
            @csrf
            @method('PUT')
            <!-- Nama Toko -->
            <div class="mb-4">
                <label for="name" class="block font-medium text-sm text-gray-700">Nama Toko</label>
                <input id="name" class="block mt-1 w-full" type="text" name="name" value="{{ old('name', $store->name) }}" required />
            </div>
            <!-- Deskripsi Toko -->
            <div class="mb-4">
                <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi Toko</label>
                <textarea id="description" name="description" class="block mt-1 w-full" rows="4" required>{{ old('description', $store->description) }}</textarea>
            </div>
            <div class="flex items-center justify-end mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection