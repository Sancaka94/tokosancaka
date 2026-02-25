@extends('layouts.app')

@section('content')
<div class="py-4 md:py-8">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h2 class="text-xl md:text-2xl font-bold leading-tight text-gray-800">Edit Catatan Kas & Gaji</h2>
            <p class="mt-1 text-xs md:text-sm text-gray-600">Perbarui data transaksi utama atau sesuaikan kembali nominal gaji pegawai.</p>
        </div>

        <div class="bg-white shadow-sm sm:rounded-xl border border-gray-100 overflow-hidden">
            <form action="{{ route('financial.update', $kas->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-4 md:p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal', \Carbon\Carbon::parse($kas->tanggal)->format('Y-m-d')) }}" required class="form-control">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Jenis Transaksi</label>
                            <select name="jenis" required class="form-control">
                                <option value="pemasukan" {{ $kas->jenis == 'pemasukan' ? 'selected' : '' }}>Setoran Parkir (Pemasukan)</option>
                                <option value="pengeluaran" {{ $kas->jenis == 'pengeluaran' ? 'selected' : '' }}>Biaya Operasional (Pengeluaran)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kategori Utama</label>
                            <input type="text" name="kategori" value="{{ old('kategori', $kas->kategori) }}" required class="form-control">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nominal Utama (Rp)</label>
                            <input type="number" name="nominal" value="{{ old('nominal', (int)$kas->nominal) }}" required class="form-control font-bold text-blue-600">
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <h4 class="text-sm font-bold text-blue-800 mb-4 flex items-center gap-2">
                            <span>👥</span> Koreksi Gaji Pegawai (Hari Ini)
                        </h4>

                        <div class="space-y-3">
                            @foreach($employees as $emp)
                            <div class="flex items-center justify-between bg-white p-3 rounded-lg shadow-sm border border-blue-100">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-800">{{ $emp->name }}</span>
                                    <span class="text-[10px] text-blue-600 uppercase font-bold">
                                        {{ $emp->salary_type == 'percentage' ? $emp->salary_amount . '%' : 'Rp ' . number_format($emp->salary_amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="w-32 md:w-48">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-xs font-bold text-gray-400">Rp</span>
                                        <input type="number"
                                               name="salaries[{{ $emp->id }}]"
                                               class="w-full pl-7 pr-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500"
                                               placeholder="0"
                                               {{-- Kita kosongkan value agar admin hanya mengisi jika ingin merubah --}}
                                               value="">
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-blue-500 mt-3 italic">* Kosongkan kolom gaji pegawai jika tidak ada perubahan atau sudah benar di laporan sebelumnya.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Keterangan Tambahan</label>
                        <textarea name="keterangan" rows="2" class="form-control">{{ old('keterangan', $kas->keterangan) }}</textarea>
                    </div>
                </div>

                <div class="px-4 md:px-6 py-4 bg-gray-50 flex flex-col sm:flex-row-reverse gap-3 border-t">
                    <button type="submit" class="btn-primary w-full sm:w-auto px-8 py-2.5 shadow-md font-bold">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('financial.index') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-gray-300 text-gray-700 bg-white rounded-md font-bold hover:bg-gray-100">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
