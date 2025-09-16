@extends('pondok.admin.layouts.app')

@section('title', 'Tambah Kelas Baru')
@section('page_title', 'Tambah Kelas Baru')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">Formulir Kelas Baru</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.kelas.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="nama_kelas" class="block text-gray-700 text-sm font-medium mb-2">Nama Kelas</label>
                <input type="text" id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
            
            {{-- --- PERBAIKAN --- --}}
            <div class="mb-4">
                <label for="unit_id" class="block text-gray-700 text-sm font-medium mb-2">Unit Pendidikan</label>
                <select id="unit_id" name="unit_id" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">Pilih Unit Pendidikan</option>
                    @foreach($unitPendidikan as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->nama_unit }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="keterangan" class="block text-gray-700 text-sm font-medium mb-2">Keterangan (Opsional)</label>
                <textarea id="keterangan" name="keterangan" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('keterangan') }}</textarea>
            </div>

            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.kelas.index') }}" class="bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
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

