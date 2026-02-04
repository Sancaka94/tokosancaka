@extends('layouts.admin')

@section('title', 'Tambah Akun Baru')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Tambah Akun Baru</h1>
        <a href="{{ route('admin.coa.index') }}" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <form action="{{ route('admin.coa.store') }}" method="POST">
            @csrf

            {{-- 1. Unit Usaha --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Unit Usaha <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="unit_usaha" value="Ekspedisi" class="peer sr-only" checked>
                        <div class="p-3 rounded-lg border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 flex items-center gap-2 transition-all">
                            <i class="fas fa-truck-fast text-blue-500"></i> <span class="font-medium text-gray-700">Ekspedisi</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="unit_usaha" value="Percetakan" class="peer sr-only">
                        <div class="p-3 rounded-lg border border-gray-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:bg-gray-50 flex items-center gap-2 transition-all">
                            <i class="fas fa-print text-purple-500"></i> <span class="font-medium text-gray-700">Percetakan</span>
                        </div>
                    </label>
                </div>
                @error('unit_usaha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- 2. Kode Akun --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kode Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="kode_akun" value="{{ old('kode_akun') }}" placeholder="Contoh: 5101" 
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                    @error('kode_akun') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- 3. Nama Akun --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_akun" value="{{ old('nama_akun') }}" placeholder="Contoh: Biaya Listrik" 
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                    @error('nama_akun') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 4. Kategori (Datalist) --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Induk <span class="text-red-500">*</span></label>
                <input type="text" name="kategori" value="{{ old('kategori') }}" list="kategori_list" placeholder="Ketik atau pilih kategori..." 
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                
                {{-- Datalist untuk Autocomplete --}}
                <datalist id="kategori_list">
                    @foreach($existingCategories as $cat)
                        <option value="{{ $cat }}"></option>
                    @endforeach
                </datalist>
                <p class="text-xs text-gray-400 mt-1">Digunakan untuk pengelompokan di Laporan.</p>
                @error('kategori') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- 5. Jenis Laporan --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Masuk Laporan</label>
                    <select name="jenis_laporan" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none bg-white">
                        <option value="Laba Rugi" {{ old('jenis_laporan') == 'Laba Rugi' ? 'selected' : '' }}>Laba Rugi (Pendapatan/Beban)</option>
                        <option value="Neraca" {{ old('jenis_laporan') == 'Neraca' ? 'selected' : '' }}>Neraca (Aset/Utang/Modal)</option>
                    </select>
                </div>

                {{-- 6. Tipe Arus --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Arus (Filter Dropdown)</label>
                    <select name="tipe_arus" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none bg-white">
                        <option value="Pengeluaran" {{ old('tipe_arus') == 'Pengeluaran' ? 'selected' : '' }}>Pengeluaran (Hanya muncul di Uang Keluar)</option>
                        <option value="Pemasukan" {{ old('tipe_arus') == 'Pemasukan' ? 'selected' : '' }}>Pemasukan (Hanya muncul di Uang Masuk)</option>
                        <option value="Netral" {{ old('tipe_arus') == 'Netral' ? 'selected' : '' }}>Netral (Muncul di Keduanya - Cth: Kas)</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="bg-blue-600 text-white font-bold py-2.5 px-8 rounded-lg hover:bg-blue-700 shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                    <i class="fas fa-save mr-2"></i> Simpan Akun
                </button>
            </div>

        </form>
    </div>
</div>
@endsection