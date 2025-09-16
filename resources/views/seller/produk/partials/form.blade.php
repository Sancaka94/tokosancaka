@extends('layouts.customer')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 sm:p-8 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Tambah Produk Baru</h2>

                {{-- Menampilkan error validasi --}}
                @if ($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                        <strong class="font-bold">Oops!</strong>
                        <span class="block sm:inline">Ada beberapa masalah dengan input Anda.</span>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('seller.produk.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Kolom Kiri -->
                        <div>
                            <!-- Nama Produk -->
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700">Nama Produk</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            </div>

                            <!-- SKU -->
                            <div class="mb-4">
                                <label for="sku" class="block text-sm font-medium text-gray-700">SKU (Stock Keeping Unit)</label>
                                <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                             <!-- Kategori -->
                            <div class="mb-4">
                                <label for="category" class="block text-sm font-medium text-gray-700">Kategori</label>
                                <input type="text" name="category" id="category" value="{{ old('category') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                            <!-- Deskripsi -->
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="description" id="description" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <!-- Kolom Kanan -->
                        <div>
                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Harga -->
                                <div class="mb-4">
                                    <label for="price" class="block text-sm font-medium text-gray-700">Harga (Rp)</label>
                                    <input type="number" name="price" id="price" value="{{ old('price') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                </div>

                                <!-- Stok -->
                                <div class="mb-4">
                                    <label for="stock" class="block text-sm font-medium text-gray-700">Stok</label>
                                    <input type="number" name="stock" id="stock" value="{{ old('stock') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                </div>
                             </div>

                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Berat -->
                                <div class="mb-4">
                                    <label for="weight" class="block text-sm font-medium text-gray-700">Berat (gram)</label>
                                    <input type="number" name="weight" id="weight" value="{{ old('weight') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                </div>

                                <!-- Jenis Barang -->
                                <div class="mb-4">
                                    <label for="jenis_barang" class="block text-sm font-medium text-gray-700">Jenis Barang (KiriminAja)</label>
                                     <select name="jenis_barang" id="jenis_barang" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                        <option value="1" @selected(old('jenis_barang') == 1)>Paket</option>
                                        <option value="2" @selected(old('jenis_barang') == 2)>Dokumen</option>
                                        <option value="3" @selected(old('jenis_barang') == 3)>Makanan</option>
                                        <option value="4" @selected(old('jenis_barang') == 4)>Kosmetik</option>
                                        <option value="5" @selected(old('jenis_barang') == 5)>Baju</option>
                                        <option value="6" @selected(old('jenis_barang') == 6)>Elektronik</option>
                                        <option value="7" @selected(old('jenis_barang') == 7)>Gadget</option>
                                        <option value="8" @selected(old('jenis_barang') == 8)>Cairan</option>
                                     </select>
                                </div>
                             </div>

                             <!-- Status -->
                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="active" @selected(old('status', 'active') == 'active')>Aktif</option>
                                    <option value="inactive" @selected(old('status') == 'inactive')>Tidak Aktif</option>
                                </select>
                            </div>

                            <!-- Gambar Produk -->
                            <div class="mb-6">
                                <label for="image" class="block text-sm font-medium text-gray-700">Gambar Produk</label>
                                <input type="file" name="image" id="image" class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-8 flex items-center justify-end border-t border-gray-200 pt-6">
                        <a href="{{ route('seller.produk.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
