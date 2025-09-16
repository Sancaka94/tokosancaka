@extends('pondok.admin.layouts.app')

@section('title', 'Tambah Santri Baru')
@section('page_title', 'Tambah Santri Baru')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">Formulir Pendaftaran Santri</h2>

        {{-- Menampilkan Error Validasi --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Oops! Terjadi kesalahan.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Menampilkan Pesan Error Umum dari Controller --}}
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('admin.santri.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Kolom Kiri --}}
                <div>
                    <div class="mb-4">
                        <label for="nama_lengkap" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="nis" class="block text-gray-700 text-sm font-medium mb-2">Nomor Induk Santri (NIS)</label>
                        <input type="text" id="nis" name="nis" value="{{ old('nis') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="tempat_lahir" class="block text-gray-700 text-sm font-medium mb-2">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="tanggal_lahir" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                     <div class="mb-4">
                        <label for="jenis_kelamin" class="block text-gray-700 text-sm font-medium mb-2">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Laki-laki" {{ old('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ old('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="alamat" class="block text-gray-700 text-sm font-medium mb-2">Alamat Lengkap</label>
                        <textarea id="alamat" name="alamat" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('alamat') }}</textarea>
                    </div>
                </div>

                {{-- Kolom Kanan --}}
                <div>
                     <div class="mb-4">
                        <label for="unit_id" class="block text-gray-700 text-sm font-medium mb-2">Unit Pendidikan</label>
                        <select id="unit_id" name="unit_id" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Pilih Unit</option>
                            @foreach($units as $item)
                                <option value="{{ $item->id }}" {{ old('unit_id') == $item->id ? 'selected' : '' }}>{{ $item->nama_unit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="kelas_id" class="block text-gray-700 text-sm font-medium mb-2">Kelas</label>
                        <select id="kelas_id" name="kelas_id" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Pilih Kelas</option>
                            @foreach($kelas as $item)
                                <option value="{{ $item->id }}" {{ old('kelas_id') == $item->id ? 'selected' : '' }}>{{ $item->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="kamar_id" class="block text-gray-700 text-sm font-medium mb-2">Kamar</label>
                        <select id="kamar_id" name="kamar_id" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Pilih Kamar</option>
                            @foreach($kamar as $item)
                                <option value="{{ $item->id }}" {{ old('kamar_id') == $item->id ? 'selected' : '' }}>{{ $item->nama_kamar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="nama_ayah" class="block text-gray-700 text-sm font-medium mb-2">Nama Ayah</label>
                        <input type="text" id="nama_ayah" name="nama_ayah" value="{{ old('nama_ayah') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                     <div class="mb-4">
                        <label for="nama_ibu" class="block text-gray-700 text-sm font-medium mb-2">Nama Ibu</label>
                        <input type="text" id="nama_ibu" name="nama_ibu" value="{{ old('nama_ibu') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="telepon_wali" class="block text-gray-700 text-sm font-medium mb-2">Telepon Wali</label>
                        <input type="text" id="telepon_wali" name="telepon_wali" value="{{ old('telepon_wali') }}" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                     <div class="mb-4">
                        <label for="status" class="block text-gray-700 text-sm font-medium mb-2">Status Santri</label>
                        <select id="status" name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Aktif" {{ old('status', 'Aktif') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="Lulus" {{ old('status') == 'Lulus' ? 'selected' : '' }}>Lulus</option>
                             <option value="Pindah" {{ old('status') == 'Pindah' ? 'selected' : '' }}>Pindah</option>
                            <option value="Dikeluarkan" {{ old('status') == 'Dikeluarkan' ? 'selected' : '' }}>Dikeluarkan</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.santri.index') }}" class="bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Batal
                </a>
                <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

