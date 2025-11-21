@extends('layouts.admin')
@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-2">Daftarkan Toko untuk Pelanggan</h1>
    <p class="text-gray-600 mb-6">Anda sedang membuat toko untuk: <strong class="font-semibold">{{ $user->nama_lengkap }}</strong> ({{ $user->email }})</p>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" action="{{ route('admin.customer-to-seller.store', $user->id_pengguna) }}">
            @csrf
            <!-- Nama Toko -->
            <div class="mb-4">
                <label for="name" class="block font-medium text-sm text-gray-700">Nama Toko</label>
                <input id="name" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="name" value="{{ old('name', $user->store_name) }}" required />
            </div>
            <!-- Deskripsi Toko -->
            <div class="mb-4">
                <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi Singkat Toko</label>
                <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="4" required>{{ old('description') }}</textarea>
            </div>
            <div class="flex items-center justify-end mt-4">
                <a href="{{ route('admin.customer-to-seller.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Buat dan Daftarkan Toko
                </button>
            </div>
        </form>
    </div>
</div>
@endsection