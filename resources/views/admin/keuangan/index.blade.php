@extends('layouts.admin')

@section('title', 'Laporan Keuangan & Profit')
@section('page-title', 'Laporan Keuangan')

{{-- =========================================================== --}}
{{-- BLOK CSS TAMBAHAN (FLATPICKR & CUSTOM STYLE) --}}
{{-- =========================================================== --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
<style>
    /* Styling agar input kalender terlihat bersih */
    .flatpickr-input { 
        background-color: white !important; 
        cursor: pointer !important; 
    }
    /* Pastikan kalender muncul di atas elemen lain (z-index tinggi) */
    .flatpickr-calendar { 
        z-index: 9999 !important; 
    }
    /* Animasi fade in sederhana */
    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- =========================================================== --}}
    {{-- 1. ALERT NOTIFIKASI (SUCCESS / ERROR) --}}
    {{-- =========================================================== --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-4 rounded shadow-sm flex justify-between items-center fade-in">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-xl me-3"></i>
            <div>
                <h4 class="font-bold text-sm">Berhasil!</h4>
                <p class="text-sm">{{ session('success') }}</p>
            </div>
        </div>
        <button @click="show = false" class="text-green-700 hover:text-green-900 transition"><i class="fas fa-times"></i></button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-4 rounded shadow-sm flex justify-between items-center fade-in">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-xl me-3"></i>
            <div>
                <h4 class="font-bold text-sm">Error!</h4>
                <p class="text-sm">{{ session('error') }}</p>
            </div>
        </div>
        <button @click="show = false" class="text-red-700 hover:text-red-900 transition"><i class="fas fa-times"></i></button>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- 2. HEADER FILTER & PENCARIAN --}}
    {{-- =========================================================== --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-end gap-4">
            
            {{-- Form Filter (Kiri) --}}
            <form action="{{ route('admin.keuangan.index') }}" method="GET" class="w-full md:w-3/4 flex flex-col lg:flex-row gap-4">
                
                {{-- Input Pencarian --}}
                <div class="w-full lg:w-1/2">
                    <label class="text-xs font-bold text-gray-500 mb-1 block uppercase tracking-wider">Cari Transaksi</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition shadow-sm" 
                            placeholder="Ketik No. Invoice, Resi, atau Keterangan...">
                    </div>
                </div>

                {{-- Input Filter Tanggal --}}
                <div class="w-full lg:w-1/2">
                    <label class="text-xs font-bold text-gray-500 mb-1 block uppercase tracking-wider">Filter Tanggal</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <input type="text" id="date_range_picker" name="date_range" value="{{ request('date_range') }}"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white transition shadow-sm cursor-pointer"
                            placeholder="Pilih Rentang Tanggal..." readonly>
                        
                        {{-- Tombol Clear Tanggal --}}
                        <button type="button" id="clearDateBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 hidden cursor-pointer transition" style="z-index: 10;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>

                {{-- Tombol Submit Filter --}}
                <div class="lg:w-auto mt-auto">
                    <button type="submit" class="w-full lg:w-auto h-[42px] bg-indigo-600 hover:bg-indigo-700 text-white px-6 rounded-lg text-sm font-medium shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fas fa-filter"></i> Terapkan
                    </button>
                </div>
            </form>

            {{-- Tombol Tambah Manual (Kanan) --}}
            <div class="w-full md:w-auto flex flex-col gap-2">
                <label class="hidden md:block text-xs font-bold text-transparent mb-1 select-none">Aksi</label>
                <button type="button" onclick="openModal('modalCreate')" class="h-[42px] bg-emerald-600 hover:bg-emerald-700 text-white px-5 rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-plus-circle"></i> Input Manual
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- 3. CARD RINGKASAN DINAMIS (OMZET - MODAL = PROFIT) --}}
    {{-- =========================================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Omzet</p>
                    <h3 class="text-2xl font-extrabold text-gray-800">
                        Rp{{ number_format($totalOmzet, 0, ',', '.') }}
                    </h3>
                    <div class="mt-2 flex items-center text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full w-fit">
                        <i class="fas fa-info-circle me-1"></i> Pemasukan Kotor
                    </div>
                </div>
                <div class="bg-blue-100 p-3 rounded-xl text-blue-600 shadow-inner">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500 hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Modal</p>
                    <h3 class="text-2xl font-extrabold text-gray-800">
                        Rp{{ number_format($totalModal, 0, ',', '.') }}
                    </h3>
                    <div class="mt-2 flex items-center text-xs text-red-600 bg-red-50 px-2 py-1 rounded-full w-fit">
                        <i class="fas fa-info-circle me-1"></i> Pengeluaran + Modal
                    </div>
                </div>
                <div class="bg-red-100 p-3 rounded-xl text-red-600 shadow-inner">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Profit Bersih</p>
                    <h3 class="text-2xl font-extrabold {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Rp{{ number_format($totalProfit, 0, ',', '.') }}
                    </h3>
                    <div class="mt-2 flex items-center text-xs text-green-700 bg-green-50 px-2 py-1 rounded-full w-fit">
                        <i class="fas fa-check-circle me-1"></i> Omzet - Modal
                    </div>
                </div>
                <div class="bg-green-100 p-3 rounded-xl text-green-600 shadow-inner">
                    <i class="fas fa-coins text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- 4. TABEL DATA TRANSAKSI --}}
    {{-- =========================================================== --}}
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-list-ul text-blue-500"></i> Rincian Data
            </h3>
            <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">
                Total Data: <strong>{{ $transaksi->total() }}</strong>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-extrabold text-center w-12">No</th>
                        <th scope="col" class="px-4 py-3 font-extrabold">Tanggal</th>
                        <th scope="col" class="px-4 py-3 font-extrabold">Kategori</th>
                        <th scope="col" class="px-4 py-3 font-extrabold">Keterangan / Invoice</th>
                        {{-- Kolom Keuangan dengan Background khusus agar menonjol --}}
                        <th scope="col" class="px-4 py-3 font-extrabold text-right text-blue-800 bg-blue-50 border-l border-blue-100">Omzet</th>
                        <th scope="col" class="px-4 py-3 font-extrabold text-right text-red-800 bg-red-50 border-l border-red-100">Modal</th>
                        <th scope="col" class="px-4 py-3 font-extrabold text-right text-green-800 bg-green-50 border-l border-green-100">Profit</th>
                        <th scope="col" class="px-4 py-3 font-extrabold text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksi as $index => $item)
                    <tr class="hover:bg-gray-50 transition duration-150 ease-in-out group">
                        
                        {{-- NO --}}
                        <td class="px-4 py-3 text-center text-gray-500">
                            {{ $transaksi->firstItem() + $index }}
                        </td>

                        {{-- TANGGAL --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-gray-700">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</div>
                            <div class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($item->tanggal)->format('H:i') }} WIB</div>
                        </td>

                        {{-- KATEGORI BADGE --}}
                        <td class="px-4 py-3">
                            @php
                                $cat = strtolower($item->kategori);
                                // Default Style
                                $badgeClass = 'bg-gray-100 text-gray-600 ring-1 ring-gray-200';
                                $icon = 'fa-file-alt';

                                // Custom Style per Kategori
                                if(str_contains($cat, 'ppob')) {
                                    $badgeClass = 'bg-purple-100 text-purple-700 ring-1 ring-purple-200';
                                    $icon = 'fa-mobile-screen-button';
                                } elseif(str_contains($cat, 'ekspedisi')) {
                                    $badgeClass = 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200';
                                    $icon = 'fa-truck-fast';
                                } elseif(str_contains($cat, 'marketplace')) {
                                    $badgeClass = 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';
                                    $icon = 'fa-store';
                                } elseif(str_contains($cat, 'top up')) {
                                    $badgeClass = 'bg-cyan-100 text-cyan-700 ring-1 ring-cyan-200';
                                    $icon = 'fa-wallet';
                                }
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $badgeClass }}">
                                <i class="fas {{ $icon }} me-1.5"></i> {{ $item->kategori }}
                            </span>
                        </td>

                        {{-- KETERANGAN --}}
                        <td class="px-4 py-3">
                            @if($item->nomor_invoice)
                                <div class="font-mono font-bold text-gray-800 text-xs mb-1 hover:text-blue-600 cursor-pointer" title="Copy Invoice">
                                    {{ $item->nomor_invoice }}
                                </div>
                            @endif
                            <div class="text-xs text-gray-500 leading-snug truncate max-w-[250px]" title="{{ $item->keterangan }}">
                                {{ $item->keterangan ?? '-' }}
                            </div>
                        </td>

                        {{-- OMZET --}}
                        <td class="px-4 py-3 text-right bg-blue-50 group-hover:bg-blue-100 transition border-l border-blue-100">
                            <span class="font-medium text-blue-700">
                                {{ number_format($item->omzet, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- MODAL --}}
                        <td class="px-4 py-3 text-right bg-red-50 group-hover:bg-red-100 transition border-l border-red-100">
                            <span class="font-medium text-red-700">
                                {{ number_format($item->modal, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- PROFIT --}}
                        <td class="px-4 py-3 text-right bg-green-50 group-hover:bg-green-100 transition border-l border-green-100">
                            <span class="font-bold {{ $item->profit >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ number_format($item->profit, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-4 py-3 text-center">
                            {{-- Hanya tampilkan tombol edit/hapus jika data MANUAL --}}
                            @php
                                $isAuto = in_array($item->kategori, ['PPOB', 'Ekspedisi', 'Top Up Saldo', 'Marketplace']);
                            @endphp

                            @if(!$isAuto)
                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <button onclick='editData(@json($item))' class="bg-amber-400 hover:bg-amber-500 text-white px-2 py-1.5 text-xs rounded-l border-r border-amber-500 transition" title="Edit Manual">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <form action="{{ route('admin.keuangan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('PERINGATAN: Yakin hapus data manual ini? Data yang dihapus tidak bisa dikembalikan.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 text-xs rounded-r transition" title="Hapus Manual">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-gray-300 cursor-help" title="Data Otomatis dari Sistem (Tidak bisa diedit manual)">
                                    <i class="fas fa-lock"></i> Auto
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-gray-100 p-4 rounded-full mb-3">
                                    <i class="fas fa-search-dollar text-3xl text-gray-300"></i>
                                </div>
                                <p class="font-medium">Data tidak ditemukan.</p>
                                <p class="text-xs mt-1">Coba ubah filter tanggal atau kata kunci pencarian.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- FOOTER TABLE: SUBTOTAL HALAMAN INI --}}
                <tfoot class="bg-gray-50 border-t-2 border-gray-300 font-bold text-xs">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-gray-600 uppercase tracking-wider">
                            Subtotal (Halaman Ini):
                        </td>
                        <td class="px-4 py-3 text-right text-blue-800 bg-blue-50/50">
                            Rp{{ number_format($transaksi->sum('omzet'), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-red-800 bg-red-50/50">
                            Rp{{ number_format($transaksi->sum('modal'), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-green-800 bg-green-50/50">
                            Rp{{ number_format($transaksi->sum('profit'), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {{-- PAGINATION --}}
        @if($transaksi->hasPages())
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $transaksi->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- =========================================================== --}}
{{-- 5. MODAL TAMBAH DATA (CREATE) --}}
{{-- =========================================================== --}}
<div id="modalCreate" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        {{-- Overlay Gelap --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalCreate')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        {{-- Konten Modal --}}
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full ring-1 ring-black ring-opacity-5">
            <form action="{{ route('admin.keuangan.store') }}" method="POST">
                @csrf
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-5 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center" id="modal-title">
                            <i class="fas fa-plus-circle text-blue-600 me-2 text-xl"></i> Tambah Transaksi Manual
                        </h3>
                        <button type="button" onclick="closeModal('modalCreate')" class="text-gray-400 hover:text-gray-500 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        {{-- Field Tanggal --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Transaksi</label>
                            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                        
                        {{-- Grid Jenis & Kategori --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis</label>
                                <select name="jenis" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm bg-gray-50" required>
                                    <option value="Pemasukan">Pemasukan (+)</option>
                                    <option value="Pengeluaran">Pengeluaran (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                                <select name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm bg-gray-50" required>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Gaji">Gaji Karyawan</option>
                                    <option value="Aset">Pembelian Aset</option>
                                    <option value="Marketing">Marketing / Iklan</option>
                                    <option value="Lainnya">Lainnya</option>
                                    <option disabled>──────────</option>
                                    <option value="Ekspedisi">Ekspedisi (Manual)</option>
                                    <option value="Marketplace">Marketplace (Manual)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Invoice --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">No. Invoice / Referensi (Opsional)</label>
                            <input type="text" name="nomor_invoice" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: INV-MANUAL-001">
                        </div>

                        {{-- Nominal --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nominal (Rp)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                  <span class="text-gray-500 font-bold sm:text-sm">Rp</span>
                                </div>
                                <input type="number" name="jumlah" class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-lg font-bold text-gray-800 placeholder-gray-300" placeholder="0" required>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1 italic">* Masukkan angka saja tanpa titik/koma.</p>
                        </div>

                        {{-- Keterangan --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan / Catatan</label>
                            <textarea name="keterangan" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Tulis detail transaksi disini..."></textarea>
                        </div>
                    </div>
                </div>
                
                {{-- Footer Modal --}}
                <div class="bg-gray-50 px-4 py-4 sm:px-6 flex flex-row-reverse gap-3">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm transition">
                        <i class="fas fa-save me-2 mt-0.5"></i> Simpan Data
                    </button>
                    <button type="button" onclick="closeModal('modalCreate')" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none sm:text-sm transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =========================================================== --}}
{{-- 6. MODAL EDIT DATA (UPDATE) --}}
{{-- =========================================================== --}}
<div id="modalEdit" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" onclick="closeModal('modalEdit')"></div>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full ring-1 ring-black ring-opacity-5">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-5 border-b pb-3">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                            <i class="fas fa-edit text-amber-500 me-2 text-xl"></i> Edit Transaksi Manual
                        </h3>
                        <button type="button" onclick="closeModal('modalEdit')" class="text-gray-400 hover:text-gray-500 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal</label>
                            <input type="date" id="edit_tanggal" name="tanggal" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis</label>
                                <select id="edit_jenis" name="jenis" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                                    <option value="Pemasukan">Pemasukan</option>
                                    <option value="Pengeluaran">Pengeluaran</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                                <select id="edit_kategori" name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" required>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Gaji">Gaji</option>
                                    <option value="Aset">Aset</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Lainnya">Lainnya</option>
                                    <option value="Ekspedisi">Ekspedisi</option>
                                    <option value="Marketplace">Marketplace</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">No. Invoice</label>
                            <input type="text" id="edit_invoice" name="nomor_invoice" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nominal (Rp)</label>
                            <input type="number" id="edit_jumlah" name="jumlah" class="w-full border-gray-300 rounded-lg shadow-sm text-lg font-bold text-gray-800" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan</label>
                            <textarea id="edit_keterangan" name="keterangan" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-4 sm:px-6 flex flex-row-reverse gap-3">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-amber-500 text-white font-medium hover:bg-amber-600 sm:text-sm transition">
                        Update Data
                    </button>
                    <button type="button" onclick="closeModal('modalEdit')" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-gray-700 font-medium hover:bg-gray-100 sm:text-sm transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

{{-- =========================================================== --}}
{{-- JAVASCRIPT & FLATPICKR LOGIC --}}
{{-- =========================================================== --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Inisialisasi Flatpickr (Range Tanggal)
        const dateInput = document.getElementById('date_range_picker');
        const clearBtn = document.getElementById('clearDateBtn');

        if (dateInput) {
            const fp = flatpickr(dateInput, {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                locale: "id",
                theme: "airbnb",
                onReady: function(selectedDates, dateStr, instance) {
                    if (dateStr) clearBtn.classList.remove('hidden');
                },
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                }
            });

            // Tombol Clear Tanggal
            if(clearBtn) {
                clearBtn.addEventListener('click', function() {
                    fp.clear();
                    clearBtn.classList.add('hidden');
                });
            }
        }
    });

    // 2. Logic Modal Open/Close
    function openModal(id) {
        const modal = document.getElementById(id);
        if(modal) {
            modal.classList.remove('hidden');
            // Animasi masuk sederhana (optional)
            const content = modal.querySelector('div.transform');
            if(content) {
                content.classList.remove('opacity-0', 'scale-95');
                content.classList.add('opacity-100', 'scale-100');
            }
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if(modal) {
            modal.classList.add('hidden');
        }
    }

    // 3. Logic Populate Form Edit
    function editData(data) {
        // Ambil Tanggal YYYY-MM-DD
        const rawDate = data.tanggal.substring(0, 10);
        document.getElementById('edit_tanggal').value = rawDate;
        
        document.getElementById('edit_jenis').value = data.jenis;
        document.getElementById('edit_kategori').value = data.kategori;
        document.getElementById('edit_invoice').value = data.nomor_invoice;
        
        // Logika Mengisi Jumlah Uang
        // Di view kita punya 'omzet', 'modal', 'profit'.
        // Jika Jenis = Pemasukan, nilai aslinya ada di kolom 'omzet'
        // Jika Jenis = Pengeluaran, nilai aslinya ada di kolom 'modal'
        let amount = 0;
        if(data.jenis === 'Pemasukan') {
            amount = data.omzet;
        } else if(data.jenis === 'Pengeluaran') {
            amount = data.modal;
        }
        
        document.getElementById('edit_jumlah').value = amount;
        document.getElementById('edit_keterangan').value = data.keterangan;

        // Set URL Action Update
        let url = "{{ route('admin.keuangan.update', ':id') }}";
        url = url.replace(':id', data.id);
        document.getElementById('formEdit').action = url;

        openModal('modalEdit');
    }
</script>
@endpush