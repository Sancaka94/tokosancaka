@extends('layouts.admin')

@section('title', 'Jurnal Umum & Akuntansi')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- HEADER & NOTIFIKASI --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Jurnal Umum</h1>
            <p class="text-sm text-gray-500">Pencatatan seluruh transaksi keuangan perusahaan.</p>
        </div>
        
        <div class="flex gap-2">
            {{-- Tombol Sync Otomatis --}}
            <form action="{{ route('admin.akuntansi.sync') }}" method="POST" onsubmit="return confirm('Proses ini akan menarik data transaksi terbaru dari Ekspedisi, PPOB, dan Marketplace. Lanjutkan?');">
                @csrf
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 transition">
                    <i class="fas fa-sync-alt"></i> Sync Data Otomatis
                </button>
            </form>

            {{-- Tombol Tambah Manual --}}
            <a href="{{ route('admin.akuntansi.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 transition">
                <i class="fas fa-plus-circle"></i> Catat Manual
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p class="font-bold">Berhasil!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p class="font-bold">Error!</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- CARD RINGKASAN SALDO --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500">
            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total Pemasukan (Debit)</div>
            <div class="text-2xl font-bold text-green-700 mt-1">
                Rp {{ number_format($saldo['total_masuk'], 0, ',', '.') }}
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-red-500">
            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total Pengeluaran (Kredit)</div>
            <div class="text-2xl font-bold text-red-700 mt-1">
                Rp {{ number_format($saldo['total_keluar'], 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- FILTER DATA --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('admin.akuntansi.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-auto">
                <label class="text-xs text-gray-500 font-bold mb-1 block">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="w-full md:w-auto">
                <label class="text-xs text-gray-500 font-bold mb-1 block">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 w-full">
                <label class="text-xs text-gray-500 font-bold mb-1 block">Cari (Invoice / Akun / Ket)</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..." class="border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 w-full md:w-auto">Filter</button>
                <a href="{{ route('admin.akuntansi.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 w-full md:w-auto text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase font-bold text-xs border-b">
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">No. Invoice</th>
                        <th class="px-6 py-3">Akun (COA)</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3 text-right">Debit (Masuk)</th>
                        <th class="px-6 py-3 text-right">Kredit (Keluar)</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($jurnal as $row)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 whitespace-nowrap text-gray-500">
                            {{ date('d/m/Y', strtotime($row->tanggal)) }}
                        </td>
                        <td class="px-6 py-3 font-medium text-gray-700">
                            {{ $row->nomor_invoice }}
                            <div class="text-xs text-gray-400 font-normal">{{ $row->unit_usaha }}</div>
                        </td>
                        <td class="px-6 py-3">
                            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-bold mr-1">
                                {{ $row->kode_akun }}
                            </span>
                            <span class="text-gray-700">{{ $row->nama_akun ?? 'Tanpa Akun' }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 max-w-xs truncate" title="{{ $row->keterangan }}">
                            {{ $row->keterangan }}
                        </td>
                        
                        {{-- Logika Debit / Kredit --}}
                        <td class="px-6 py-3 text-right font-medium text-green-600">
                            @if($row->jenis == 'Pemasukan')
                                {{ number_format($row->jumlah, 0, ',', '.') }}
                            @else - @endif
                        </td>
                        <td class="px-6 py-3 text-right font-medium text-red-600">
                            @if($row->jenis == 'Pengeluaran')
                                {{ number_format($row->jumlah, 0, ',', '.') }}
                            @else - @endif
                        </td>

                        <td class="px-6 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('admin.akuntansi.edit', $row->id) }}" class="text-yellow-500 hover:text-yellow-600" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.akuntansi.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Hapus jurnal ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400 italic">
                            Belum ada data jurnal. Silakan catat manual atau lakukan Sync.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $jurnal->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection