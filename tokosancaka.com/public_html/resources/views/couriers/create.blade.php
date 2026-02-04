@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Tambah Kurir Baru</h1>
        <p class="text-gray-600 mt-1">Isi formulir di bawah untuk mendaftarkan kurir baru.</p>
    </header>

    <main class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
        <form action="{{ route('admin.couriers.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- ID Kurir -->
                <div>
                    <label for="courier_id" class="block text-sm font-medium text-gray-700">ID Kurir</label>
                    <input type="text" name="courier_id" id="courier_id" value="{{ old('courier_id') }}" placeholder="Contoh: KR-005" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>

                <!-- Nomor HP -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Nomor HP</label>
                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                </div>

                <!-- Alamat -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea name="address" id="address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>{{ old('address') }}</textarea>
                </div>
                 
                {{-- Status diatur secara otomatis di controller --}}
                <input type="hidden" name="status" value="Aktif">
            </div>

            <!-- Tombol Aksi -->
            <div class="mt-8 flex justify-end gap-4">
                <a href="{{ route('admin.couriers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                    Batal
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Simpan Kurir
                </button>
            </div>
        </form>
    </main>
</div>
@endsection
