@extends('pondok.admin.layouts.app')

@section('title', 'Edit Paket')
@section('page_title', 'Edit Data Paket')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 shadow-sm rounded-lg">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h3 class="text-lg font-bold text-gray-800">Edit Paket: {{ $paket->nama_paket }}</h3>
    </div>
    
    <form action="{{ route('admin.paket.update', $paket->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-5">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Paket</label>
                <input type="text" name="nama_paket" value="{{ old('nama_paket', $paket->nama_paket) }}" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('nama_paket') border-red-500 @enderror" required>
                @error('nama_paket')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" value="{{ old('harga', $paket->harga) }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('harga') border-red-500 @enderror" required>
                    @error('harga')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periode Aktif (Hari)</label>
                    <input type="number" name="periode_hari" value="{{ old('periode_hari', $paket->periode_hari) }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('periode_hari') border-red-500 @enderror" required>
                    @error('periode_hari')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="3" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('deskripsi', $paket->deskripsi) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fitur Paket</label>
                <textarea name="fitur" rows="6" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" 
                    placeholder="Makan 3x Sehari&#10;Laundry Gratis&#10;Akses WiFi">{{ old('fitur', $paket->fitur) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    * Masukkan satu fitur per baris (Tekan <strong>Enter</strong> untuk fitur baru).
                </p>
            </div>

            <div class="flex justify-end pt-6 border-t mt-6 gap-3">
                <a href="{{ route('admin.paket.index') }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 font-medium transition">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection