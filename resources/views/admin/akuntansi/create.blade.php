@extends('layouts.admin')

@section('title', 'Catat Transaksi Baru')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.akuntansi.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Input Transaksi Manual</h1>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <form action="{{ route('admin.akuntansi.store') }}" method="POST">
            @csrf

            {{-- 1. Tanggal & Jenis --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal Transaksi <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('tanggal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Arus Kas <span class="text-red-500">*</span></label>
                    <select name="jenis" id="jenis_transaksi" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                        <option value="Pengeluaran" {{ old('jenis') == 'Pengeluaran' ? 'selected' : '' }}>Pengeluaran (Uang Keluar)</option>
                        <option value="Pemasukan" {{ old('jenis') == 'Pemasukan' ? 'selected' : '' }}>Pemasukan (Uang Masuk)</option>
                    </select>
                    @error('jenis') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 2. Pilih Akun (COA) --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Akun / Pos Keuangan <span class="text-red-500">*</span></label>
                <select name="kode_akun" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 py-3" required>
                    <option value="" disabled selected>-- Pilih Akun Terkait --</option>
                    
                    {{-- Group Pemasukan --}}
                    <optgroup label="--- AKUN PEMASUKAN ---" class="font-bold text-green-700">
                        @foreach($akunPemasukan as $akun)
                            <option value="{{ $akun->kode_akun }}" {{ old('kode_akun') == $akun->kode_akun ? 'selected' : '' }}>
                                [{{ $akun->kode_akun }}] {{ $akun->nama_akun }} ({{ $akun->unit_usaha }})
                            </option>
                        @endforeach
                    </optgroup>

                    {{-- Group Pengeluaran --}}
                    <optgroup label="--- AKUN PENGELUARAN & BEBAN ---" class="font-bold text-red-700">
                        @foreach($akunPengeluaran as $akun)
                            <option value="{{ $akun->kode_akun }}" {{ old('kode_akun') == $akun->kode_akun ? 'selected' : '' }}>
                                [{{ $akun->kode_akun }}] {{ $akun->nama_akun }} ({{ $akun->unit_usaha }})
                            </option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="text-xs text-gray-400 mt-1">Pilih akun yang sesuai agar laporan Laba Rugi akurat.</p>
                @error('kode_akun') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 3. Nominal Uang --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah Nominal (Rp) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">Rp</span>
                    </div>
                    <input type="number" name="jumlah" value="{{ old('jumlah') }}" placeholder="0" class="pl-10 w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 py-3 text-lg font-bold text-gray-700" required min="0">
                </div>
                @error('jumlah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 4. Keterangan --}}
            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan Detail <span class="text-red-500">*</span></label>
                <textarea name="keterangan" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Pembayaran listrik gudang bulan Januari..." required>{{ old('keterangan') }}</textarea>
                @error('keterangan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex items-center justify-end gap-3">
                <button type="reset" class="px-6 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">Reset</button>
                <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                    <i class="fas fa-save mr-2"></i> Simpan Transaksi
                </button>
            </div>

        </form>
    </div>
</div>
@endsection