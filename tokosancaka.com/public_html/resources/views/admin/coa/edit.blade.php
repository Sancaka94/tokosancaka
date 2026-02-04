@extends('layouts.admin')

@section('title', 'Edit Akun')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Edit Akun</h1>
        <a href="{{ route('admin.coa.index', ['unit' => $account->unit_usaha]) }}" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Batal
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-yellow-200 p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 bg-yellow-100 text-yellow-800 text-xs font-bold px-4 py-1 rounded-bl-lg">MODE EDIT</div>

        <form action="{{ route('admin.coa.update', $account->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- 1. Unit Usaha --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Unit Usaha</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="unit_usaha" value="Ekspedisi" class="peer sr-only" {{ $account->unit_usaha == 'Ekspedisi' ? 'checked' : '' }}>
                        <div class="p-3 rounded-lg border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 flex items-center gap-2 transition-all">
                            <i class="fas fa-truck-fast text-blue-500"></i> <span class="font-medium text-gray-700">Ekspedisi</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="unit_usaha" value="Percetakan" class="peer sr-only" {{ $account->unit_usaha == 'Percetakan' ? 'checked' : '' }}>
                        <div class="p-3 rounded-lg border border-gray-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:bg-gray-50 flex items-center gap-2 transition-all">
                            <i class="fas fa-print text-purple-500"></i> <span class="font-medium text-gray-700">Percetakan</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- 2. Kode Akun --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Kode Akun</label>
                    <input type="text" name="kode_akun" value="{{ old('kode_akun', $account->kode_akun) }}" 
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/20 outline-none transition-all">
                </div>

                {{-- 3. Nama Akun --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Akun</label>
                    <input type="text" name="nama_akun" value="{{ old('nama_akun', $account->nama_akun) }}" 
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/20 outline-none transition-all">
                </div>
            </div>

            {{-- 4. Kategori --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Induk</label>
                <input type="text" name="kategori" value="{{ old('kategori', $account->kategori) }}" list="kategori_list"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/20 outline-none transition-all">
                
                <datalist id="kategori_list">
                    @foreach($existingCategories as $cat)
                        <option value="{{ $cat }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- 5. Jenis Laporan --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Masuk Laporan</label>
                    <select name="jenis_laporan" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/20 outline-none bg-white">
                        <option value="Laba Rugi" {{ $account->jenis_laporan == 'Laba Rugi' ? 'selected' : '' }}>Laba Rugi</option>
                        <option value="Neraca" {{ $account->jenis_laporan == 'Neraca' ? 'selected' : '' }}>Neraca</option>
                    </select>
                </div>

                {{-- 6. Tipe Arus --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Arus</label>
                    <select name="tipe_arus" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/20 outline-none bg-white">
                        <option value="Pengeluaran" {{ $account->tipe_arus == 'Pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                        <option value="Pemasukan" {{ $account->tipe_arus == 'Pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="Netral" {{ $account->tipe_arus == 'Netral' ? 'selected' : '' }}>Netral</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="bg-yellow-500 text-white font-bold py-2.5 px-8 rounded-lg hover:bg-yellow-600 shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                    <i class="fas fa-save mr-2"></i> Update Perubahan
                </button>
            </div>

        </form>
    </div>
</div>
@endsection