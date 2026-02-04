@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Buat Toko Baru</h1>

    <div class="bg-white shadow-md rounded-lg p-6">
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.stores.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Pemilik Toko (User) -->
            <div class="mb-4">
                <label for="user_id" class="block font-medium text-sm text-gray-700">Pemilik Toko (User)</label>
                <select id="user_id" name="user_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="">Pilih User</option>
                    {{-- Anda perlu mengirimkan variabel $users dari controller --}}
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Nama Toko -->
            <div class="mb-4">
                <label for="name" class="block font-medium text-sm text-gray-700">Nama Toko</label>
                <input id="name" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="name" value="{{ old('name') }}" required autofocus />
            </div>

            <!-- Deskripsi Toko -->
            <div class="mb-4">
                <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi Toko</label>
                <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="4" required>{{ old('description') }}</textarea>
            </div>

            <!-- Provinsi -->
            <div class="mb-4">
                <label for="province" class="block font-medium text-sm text-gray-700">Provinsi</label>
                <input id="province" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="province" value="{{ old('province') }}" />
            </div>

            <!-- Kabupaten/Kota -->
            <div class="mb-4">
                <label for="regency" class="block font-medium text-sm text-gray-700">Kabupaten/Kota</label>
                <input id="regency" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="regency" value="{{ old('regency') }}" />
            </div>

            <!-- Kecamatan -->
            <div class="mb-4">
                <label for="district" class="block font-medium text-sm text-gray-700">Kecamatan</label>
                <input id="district" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="district" value="{{ old('district') }}" />
            </div>

            <!-- Desa/Kelurahan -->
            <div class="mb-4">
                <label for="village" class="block font-medium text-sm text-gray-700">Desa/Kelurahan</label>
                <input id="village" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="village" value="{{ old('village') }}" />
            </div>

            <!-- Detail Alamat -->
            <div class="mb-4">
                <label for="address_detail" class="block font-medium text-sm text-gray-700">Detail Alamat</label>
                <textarea id="address_detail" name="address_detail" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="3">{{ old('address_detail') }}</textarea>
            </div>

            <!-- Kode Pos -->
            <div class="mb-4">
                <label for="zip_code" class="block font-medium text-sm text-gray-700">Kode Pos</label>
                <input id="zip_code" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="zip_code" value="{{ old('zip_code') }}" />
            </div>

             <!-- Logo Toko -->
             <div class="mb-4">
                <label for="logo" class="block font-medium text-sm text-gray-700">Logo Toko (Opsional)</label>
                <input id="logo" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm p-2" type="file" name="logo" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <a href="{{ route('admin.stores.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Buat Toko
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
