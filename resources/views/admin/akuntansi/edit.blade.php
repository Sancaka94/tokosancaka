@extends('layouts.admin')

@section('title', 'Koreksi Transaksi')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.akuntansi.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i> Batal
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Edit / Koreksi Jurnal</h1>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-yellow-200 p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-bl-lg">MODE EDIT</div>

        <form action="{{ route('admin.akuntansi.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- 1. Tanggal & Jenis --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal Transaksi</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $data->tanggal) }}" class="w-full border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Arus Kas</label>
                    <select name="jenis" class="w-full border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 bg-gray-50">
                        <option value="Pengeluaran" {{ $data->jenis == 'Pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                        <option value="Pemasukan" {{ $data->jenis == 'Pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                    </select>
                </div>
            </div>

            {{-- 2. Pilih Akun --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Akun / Pos Keuangan</label>
                <select name="kode_akun" class="w-full border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 py-3" required>
                    @foreach($akunList as $akun)
                        <option value="{{ $akun->kode_akun }}" {{ $data->kode_akun == $akun->kode_akun ? 'selected' : '' }}>
                            [{{ $akun->kode_akun }}] {{ $akun->nama_akun }} ({{ $akun->unit_usaha }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. Nominal --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah Nominal (Rp)</label>
                <input type="number" name="jumlah" value="{{ old('jumlah', $data->jumlah) }}" class="w-full border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 py-3 text-lg font-bold text-gray-700" required>
            </div>

            {{-- 4. Keterangan --}}
            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan</label>
                <textarea name="keterangan" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500" required>{{ old('keterangan', $data->keterangan) }}</textarea>
            </div>

            {{-- Tombol --}}
            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="px-6 py-2 rounded-lg bg-yellow-500 text-white font-bold hover:bg-yellow-600 shadow-md transition">
                    <i class="fas fa-save mr-2"></i> Update Perubahan
                </button>
            </div>

        </form>
    </div>
</div>
@endsection