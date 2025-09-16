@extends('layouts.customer')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Profil Toko Saya</h2>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Nama Toko -->
                    <div class="mb-4">
                        <label for="name" class="block font-medium text-sm text-gray-700">Nama Toko</label>
                        <input id="name" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="name" value="{{ old('name', $store->name) }}" required />
                    </div>

                    <!-- Deskripsi Toko -->
                    <div class="mb-4">
                        <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi</label>
                        <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="4" required>{{ old('description', $store->description) }}</textarea>
                    </div>

                    <!-- Provinsi -->
                    <div class="mb-4">
                        <label for="province" class="block font-medium text-sm text-gray-700">Provinsi</label>
                        <input id="province" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="province" value="{{ old('province', $store->province) }}" />
                    </div>

                    <!-- Kabupaten/Kota -->
                    <div class="mb-4">
                        <label for="regency" class="block font-medium text-sm text-gray-700">Kabupaten/Kota</label>
                        <input id="regency" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="regency" value="{{ old('regency', $store->regency) }}" />
                    </div>

                    <!-- Kecamatan -->
                    <div class="mb-4">
                        <label for="district" class="block font-medium text-sm text-gray-700">Kecamatan</label>
                        <input id="district" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="district" value="{{ old('district', $store->district) }}" />
                    </div>

                    <!-- Desa/Kelurahan -->
                    <div class="mb-4">
                        <label for="village" class="block font-medium text-sm text-gray-700">Desa/Kelurahan</label>
                        <input id="village" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="village" value="{{ old('village', $store->village) }}" />
                    </div>
                    
                    <!-- Detail Alamat -->
                    <div class="mb-4">
                        <label for="address_detail" class="block font-medium text-sm text-gray-700">Detail Alamat</label>
                        <textarea id="address_detail" name="address_detail" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="3">{{ old('address_detail', $store->address_detail) }}</textarea>
                    </div>

                    <!-- Kode Pos -->
                    <div class="mb-4">
                        <label for="zip_code" class="block font-medium text-sm text-gray-700">Kode Pos</label>
                        <input id="zip_code" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" type="text" name="zip_code" value="{{ old('zip_code', $store->zip_code) }}" />
                    </div>

                    <!-- Logo Toko -->
                    <div class="mb-4">
                        <label for="logo" class="block font-medium text-sm text-gray-700">Logo Toko</label>
                        <input id="logo" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm p-2" type="file" name="logo" />
                        @if($store->seller_logo)
                        <div class="mt-2">
                            <img src="{{ $store->seller_logo }}" alt="Logo saat ini" class="w-32 h-32 object-cover rounded-full">
                            <small class="text-gray-500">Logo saat ini. Upload file baru untuk mengganti.</small>
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
