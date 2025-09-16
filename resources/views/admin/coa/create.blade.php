@extends('layouts.admin')

@section('content')
<div class="bg-gray-800 p-6 rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-white mb-6 border-b border-gray-700 pb-4">Tambah Kode Akun Baru</h1>

    @if ($errors->any())
        <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.coa.store') }}" method="POST">
        @csrf
        <div class="space-y-6">
            <div>
                <label for="kode" class="block text-sm font-medium text-gray-300">Kode Akun</label>
                <input type="text" id="kode" name="kode" value="{{ old('kode') }}" class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Contoh: 4100" required>
            </div>
            <div>
                <label for="nama" class="block text-sm font-medium text-gray-300">Nama Akun</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Contoh: Pendapatan Ongkir" required>
            </div>
            <div>
                <label for="tipe" class="block text-sm font-medium text-gray-300">Tipe Akun</label>
                <select id="tipe" name="tipe" class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    <option value="">Pilih Tipe</option>
                    <option value="aset" {{ old('tipe') == 'aset' ? 'selected' : '' }}>Aset</option>
                    <option value="kewajiban" {{ old('tipe') == 'kewajiban' ? 'selected' : '' }}>Kewajiban</option>
                    <option value="ekuitas" {{ old('tipe') == 'ekuitas' ? 'selected' : '' }}>Ekuitas</option>
                    <option value="pendapatan" {{ old('tipe') == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                    <option value="beban" {{ old('tipe') == 'beban' ? 'selected' : '' }}>Beban</option>
                </select>
            </div>
        </div>
        <div class="mt-8 flex justify-end">
            <a href="{{ route('admin.coa.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg mr-4">Batal</a>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Simpan Akun</button>
        </div>
    </form>
</div>
@endsection
