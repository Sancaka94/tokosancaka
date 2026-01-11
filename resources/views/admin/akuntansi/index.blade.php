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
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 transition text-sm">
                    <i class="fas fa-sync-alt"></i> Sync Data Otomatis
                </button>
            </form>

            {{-- Tombol Tambah Manual --}}
            <a href="{{ route('admin.akuntansi.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 transition text-sm">
                <i class="fas fa-plus-circle"></i> Catat Manual
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r shadow-sm" role="alert">
            <p class="font-bold flex items-center"><i class="fas fa-check-circle mr-2"></i> Berhasil!</p>
            <p class="text-sm">{{ session('success') }}</p>
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r shadow-sm" role="alert">
            <p class="font-bold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> Error!</p>
            <p class="text-sm">{{ session('error') }}</p>
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
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500 shadow-sm">
            </div>
            <div class="w-full md:w-auto">
                <label class="text-xs text-gray-500 font-bold mb-1 block">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500 shadow-sm">
            </div>
            <div class="flex-1 w-full">
                <label class="text-xs text-gray-500 font-bold mb-1 block">Cari (Invoice / Akun / Ket)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..." class="pl-10 border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg text-sm hover:bg-gray-700 w-full md:w-auto font-medium transition shadow-sm">Filter</button>
                <a href="{{ route('admin.akuntansi.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 w-full md:w-auto text-center font-medium transition border border-gray-200">Reset</a>
            </div>
        </form>
    </div>

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase font-bold text-xs border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">No. Invoice / Unit</th>
                        <th class="px-6 py-4">Akun (COA)</th>
                        <th class="px-6 py-4 w-1/3">Keterangan</th>
                        <th class="px-6 py-4 text-right">Debit (Masuk)</th>
                        <th class="px-6 py-4 text-right">Kredit (Keluar)</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($jurnal as $row)
                    <tr class="hover:bg-gray-50 transition group">
                        
                        {{-- Tanggal --}}
                        <td class="px-6 py-3 whitespace-nowrap text-gray-500">
                            <div class="font-medium text-gray-700">{{ date('d M Y', strtotime($row->tanggal)) }}</div>
                            <div class="text-[10px]">{{ date('H:i', strtotime($row->created_at)) }}</div>
                        </td>

                        {{-- Invoice & Unit --}}
                        <td class="px-6 py-3">
                            <div class="font-mono font-medium text-gray-700 text-xs bg-gray-100 px-2 py-0.5 rounded w-fit mb-1">
                                {{ $row->nomor_invoice }}
                            </div>
                            <div class="text-xs text-gray-500 font-semibold flex items-center gap-1">
                                <i class="fas fa-building text-[10px]"></i> {{ $row->unit_usaha }}
                            </div>
                        </td>

                        {{-- Akun (Kode & Nama) --}}
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-bold font-mono border border-blue-100">
                                    {{ $row->kode_akun }}
                                </span>
                                <span class="font-medium text-gray-700 text-xs">
                                    {{-- Nama Akun Final diambil dari Controller (COALESCE) --}}
                                    {{ $row->nama_akun_final }}
                                </span>
                            </div>
                        </td>

                        {{-- Keterangan --}}
                        <td class="px-6 py-3 text-gray-600 text-xs leading-snug">
                            {{ Str::limit($row->keterangan, 50) }}
                        </td>
                        
                        {{-- Debit (Pemasukan) --}}
                        <td class="px-6 py-3 text-right font-medium text-green-600 bg-green-50/30">
                            @if($row->jenis == 'Pemasukan')
                                +{{ number_format($row->jumlah, 0, ',', '.') }}
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>

                        {{-- Kredit (Pengeluaran) --}}
                        <td class="px-6 py-3 text-right font-medium text-red-600 bg-red-50/30">
                            @if($row->jenis == 'Pengeluaran')
                                -{{ number_format($row->jumlah, 0, ',', '.') }}
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-6 py-3 text-center">
                            <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.akuntansi.edit', $row->id) }}" class="p-1.5 rounded-lg text-yellow-600 hover:bg-yellow-50 transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.akuntansi.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Hapus jurnal ini? Tindakan tidak bisa dibatalkan.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-gray-300">
                                    <i class="fas fa-book-open text-xl"></i>
                                </div>
                                <p>Belum ada data jurnal.</p>
                                <p class="text-xs mt-1">Silakan catat manual atau lakukan Sync Data Otomatis.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($jurnal->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            {{ $jurnal->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection