@extends('layouts.admin')

@section('content')
<!-- LOG LOG -->
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ route('proyek.index') }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-gray-900">Tambah Proyek Baru</h1>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <form action="{{ route('proyek.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Proyek</label>
                <input type="text" name="nama_proyek" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-black focus:border-black">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Proyek</label>
                <textarea name="lokasi_proyek" rows="3" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-black focus:border-black"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                <input type="text" name="nomor_hp" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-black focus:border-black">
            </div>
            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-6 rounded-md">Simpan Proyek</button>
            </div>
        </form>
    </div>
</div>
@endsection