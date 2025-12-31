@extends('pondok.admin.layouts.app')

@section('title', 'Tambah Transaksi')
@section('page_title', 'Catat Transaksi Baru')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">

            <div class="flex items-center justify-between mb-6 border-b pb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Formulir Transaksi</h3>
                    <p class="text-sm text-gray-500">Silakan isi detail pemasukan atau pengeluaran.</p>
                </div>
                <a href="{{ route('admin.transaksi-kas-bank.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm flex items-center gap-1">
                    &larr; Kembali
                </a>
            </div>

            @if ($errors->any())
                <div class="mb-5 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <p class="font-bold">Terjadi kesalahan input:</p>
                    <ul class="list-disc list-inside text-sm mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.transaksi-kas-bank.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label for="tanggal_transaksi" class="block text-sm font-medium text-gray-700">Tanggal Transaksi <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_transaksi" id="tanggal_transaksi" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div>
                        <label for="jenis_transaksi" class="block text-sm font-medium text-gray-700">Jenis Transaksi <span class="text-red-500">*</span></label>
                        <select name="jenis_transaksi" id="jenis_transaksi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Masuk" class="text-green-600 font-bold" {{ old('jenis_transaksi') == 'Masuk' ? 'selected' : '' }}>
                                (+ Pemasukan) Uang Masuk
                            </option>
                            <option value="Keluar" class="text-red-600 font-bold" {{ old('jenis_transaksi') == 'Keluar' ? 'selected' : '' }}>
                                (- Pengeluaran) Uang Keluar
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="akun_id" class="block text-sm font-medium text-gray-700">Akun Keuangan (Kas/Bank) <span class="text-red-500">*</span></label>
                        <select name="akun_id" id="akun_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <option value="">-- Pilih Akun --</option>
                            @forelse($akun as $a)
                                <option value="{{ $a->id }}" {{ old('akun_id') == $a->id ? 'selected' : '' }}>
                                    {{ $a->nama_akun }} ({{ $a->kode_akun }})
                                </option>
                            @empty
                                <option value="" disabled>Belum ada data Akun (Silakan buat di Master Akun)</option>
                            @endforelse
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih sumber dana (Kas Tunai, Bank, dll).</p>
                    </div>

                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700">Nominal (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative mt-1 rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah') }}" min="0" step="100" placeholder="0" 
                                class="block w-full rounded-md border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi Singkat <span class="text-red-500">*</span></label>
                        <input type="text" name="deskripsi" id="deskripsi" value="{{ old('deskripsi') }}" placeholder="Contoh: Bayar Listrik Bulan Januari" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan Detail (Opsional)</label>
                        <textarea name="keterangan" id="keterangan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('keterangan') }}</textarea>
                    </div>

                </div>

                <div class="mt-8 flex items-center justify-end gap-3 border-t pt-4">
                    <a href="{{ route('admin.transaksi-kas-bank.index') }}" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Simpan Transaksi
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection