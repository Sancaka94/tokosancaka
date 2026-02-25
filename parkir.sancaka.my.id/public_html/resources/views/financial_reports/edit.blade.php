@extends('layouts.app')

@section('content')
<div class="py-4 md:py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h2 class="text-xl md:text-2xl font-bold leading-tight text-gray-800">Edit Catatan Kas</h2>
            <p class="mt-1 text-xs md:text-sm text-gray-600">Silakan perbarui data pemasukan, pengeluaran, atau gaji di bawah ini.</p>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
            <form action="{{ route('financial.update', $kas->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-4 md:p-6 space-y-4 md:space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', \Carbon\Carbon::parse($kas->tanggal)->format('Y-m-d')) }}" required class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis Transaksi</label>
                        <select name="jenis" required class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="pemasukan" {{ old('jenis', $kas->jenis) == 'pemasukan' ? 'selected' : '' }}>Setoran Parkir (Pemasukan)</option>
                            <option value="pengeluaran" {{ old('jenis', $kas->jenis) == 'pengeluaran' ? 'selected' : '' }}>Biaya / Gaji (Pengeluaran)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                        <input type="text" name="kategori" value="{{ old('kategori', $kas->kategori) }}" required class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nominal (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 font-bold text-gray-400">Rp</span>
                            <input type="number" name="nominal" value="{{ old('nominal', (int)$kas->nominal) }}" required min="1" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 pl-10 pr-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    @if(str_contains(strtolower($kas->kategori), 'gaji'))
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                        <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2">Informasi Gaji</p>
                        <p class="text-sm text-gray-700">{{ $kas->keterangan }}</p>
                        <p class="text-xs text-gray-500 mt-2">*Catatan: Gaji dicatat sebagai pengeluaran per individu sesuai input pada saat tambah kas.</p>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan (Opsional)</label>
                        <textarea name="keterangan" rows="3" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('keterangan', $kas->keterangan) }}</textarea>
                    </div>
                </div>

                <div class="px-4 md:px-6 py-4 bg-gray-50 flex flex-col sm:flex-row-reverse gap-3 border-t border-gray-100">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-md shadow-sm px-6 py-2.5 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 transition-colors">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('financial.index') }}" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-bold text-gray-700 hover:bg-gray-100 transition-colors">
                        Batal & Kembali
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
