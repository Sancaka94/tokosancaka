@extends('pondok.admin.layouts.app')

@section('title', 'Tambah Unit Pendidikan Baru')
@section('page_title', 'Tambah Unit Pendidikan')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">Formulir Unit Pendidikan Baru</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.unit-pendidikan.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="nama_unit" class="block text-gray-700 text-sm font-medium mb-2">Nama Unit</label>
                <input type="text" id="nama_unit" name="nama_unit" value="{{ old('nama_unit') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
            
            <div class="mb-4">
                <label for="kepala_unit_id" class="block text-gray-700 text-sm font-medium mb-2">Kepala Unit (Opsional)</label>
                <select id="kepala_unit_id" name="kepala_unit_id" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Pilih Kepala Unit</option>
                    @foreach($pegawai as $p)
                        <option value="{{ $p->id }}" {{ old('kepala_unit_id') == $p->id ? 'selected' : '' }}>{{ $p->nama_lengkap }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="keterangan" class="block text-gray-700 text-sm font-medium mb-2">Keterangan (Opsional)</label>
                <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('keterangan') }}</textarea>
            </div>

            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.unit-pendidikan.index') }}" class="bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Batal
                </a>
                <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
