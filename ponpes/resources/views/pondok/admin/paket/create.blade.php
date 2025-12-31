@extends('pondok.admin.layouts.app')

@section('title', 'Tambah Paket')
@section('page_title', 'Form Paket Baru')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 shadow-sm rounded-lg">
    <h3 class="text-lg font-bold mb-4">Input Paket Berlangganan</h3>
    
    <form action="{{ route('admin.paket.store') }}" method="POST">
        @csrf
        <div class="space-y-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Paket</label>
                <input type="text" name="nama_paket" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="Contoh: Premium 30 Hari">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Harga (Rp)</label>
                    <input type="number" name="harga" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="0">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Periode Aktif (Hari)</label>
                    <input type="number" name="periode_hari" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="Contoh: 30">
                    <p class="text-xs text-gray-500 mt-1">Isi 30 untuk 1 bulan, 365 untuk 1 tahun.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea name="deskripsi" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Fitur (Opsional)</label>
                <input type="text" name="fitur" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Contoh: Akses Video, Download Ebook">
            </div>

            <div class="flex justify-end pt-4">
                <a href="{{ route('admin.paket.index') }}" class="mr-3 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan Data</button>
            </div>
        </div>
    </form>
</div>
@endsection