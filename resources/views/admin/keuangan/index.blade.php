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
    {{-- 2. HEADER: FILTER & TOMBOL AKSI --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-end gap-4">

            {{-- Form Filter --}}
            <form action="{{ route('admin.keuangan.index') }}" method="GET" class="w-full md:w-3/4 flex flex-col lg:flex-row gap-4">
                {{-- Pencarian --}}
              
                <div class="w-full lg:w-1/2">
                    <label class="text-xs font-bold text-gray-500 mb-1 block uppercase">Cari Transaksi</label>
                    
                    {{-- x-data: Mengambil nilai awal dari request search --}}
                    <div class="relative" x-data="{ searchQuery: '{{ request('search') }}' }">
                        
                        {{-- Icon Search (Kiri) --}}
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-search"></i>
                        </span>

                        {{-- Input Search --}}
                        <input type="text" 
                            x-ref="searchInput" 
                            name="search" 
                            x-model="searchQuery"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 text-sm placeholder-gray-400" 
                            placeholder="No. Invoice, Resi, atau Keterangan...">

                        {{-- Tombol X (Kanan) --}}
                        <button type="button" 
                                @click="searchQuery = ''; $refs.searchInput.focus()" 
                                x-show="searchQuery.length > 0" 
                                x-transition
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer transition-colors"
                                style="display: none;">
                            <i class="fas fa-times-circle"></i>
                        </button>

                    </div>
                </div>
                {{-- Tanggal --}}
                <div class="w-full lg:w-1/2">
                    <label class="text-xs font-bold text-gray-500 mb-1 block uppercase">Filter Tanggal</label>
                    <div class="relative">
                        {{-- Icon Kalender Kiri --}}
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="far fa-calendar-alt"></i>
                        </span>

                        {{-- Input Date Range (Tambahkan pr-10 agar teks tidak menabrak tombol X) --}}
                        <input type="text" id="date_range_picker" name="date_range"
                            value="{{ request('date_range') }}"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 text-sm placeholder-gray-400"
                            placeholder="Pilih Rentang Tanggal...">

                        {{-- Tombol Clear (X) - Muncul jika ada value --}}
                        <button type="button" id="clearDateBtn"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer transition-colors {{ request('date_range') ? '' : 'hidden' }}"
                            title="Hapus Tanggal">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>

                {{-- Tombol Terapkan --}}
                <div class="lg:w-auto mt-auto">
                    <button type="submit" class="w-full h-[42px] bg-indigo-600 hover:bg-indigo-700 text-white px-6 rounded-lg text-sm font-medium shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>

            {{-- TOMBOL AKSI (EXPORT & MANUAL) --}}
            <div class="w-full md:w-auto flex flex-col md:flex-row gap-2">
                <label class="hidden md:block text-xs font-bold text-transparent mb-1 select-none">Aksi</label>

                {{-- ======================================================= --}}
                {{-- TOMBOL BARU: SINKRONISASI (TAMBAHKAN INI) --}}
                {{-- ======================================================= --}}
                <form action="{{ route('admin.keuangan.sync') }}" method="POST" onsubmit="return confirm('Proses ini akan mengecek pesanan hari ini yang belum masuk laporan keuangan. Lanjutkan?');">
                    @csrf
                    <button type="submit" class="h-[42px] bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg text-sm font-medium shadow-md transition flex items-center justify-center gap-2 w-full md:w-auto whitespace-nowrap" title="Cek ulang pesanan hari ini">
                        <i class="fas fa-sync-alt"></i> Sync
                    </button>
                </form>
                {{-- ======================================================= --}}

                {{-- Export Excel --}}
                <a href="{{ route('admin.keuangan.export_excel', request()->all()) }}" target="_blank" class="h-[42px] bg-green-600 hover:bg-green-700 text-white px-4 rounded-lg text-sm font-medium shadow-md transition flex items-center justify-center gap-2">
                    <i class="fas fa-file-excel"></i> Excel
                </a>

                {{-- Export PDF --}}
                <a href="{{ route('admin.keuangan.export_pdf', request()->all()) }}" target="_blank" class="h-[42px] bg-red-600 hover:bg-red-700 text-white px-4 rounded-lg text-sm font-medium shadow-md transition flex items-center justify-center gap-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>

                {{-- Input Manual --}}
                <button type="button" onclick="openModal('modalCreate')" class="h-[42px] bg-emerald-600 hover:bg-emerald-700 text-white px-5 rounded-lg text-sm font-medium shadow-md transition flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-plus-circle"></i> Manual
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- 2. CARD RINGKASAN GLOBAL (UTAMA) --}}
    {{-- =========================================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-600 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-blue-50 to-transparent opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Omzet (Global)</p>
            <h3 class="text-2xl font-extrabold text-gray-800">
                Rp{{ number_format($summary['omzet'], 0, ',', '.') }}
            </h3>
            <div class="absolute top-6 right-6 text-blue-100 group-hover:text-blue-200 transition transform group-hover:scale-110">
                <i class="fas fa-chart-line text-4xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-red-50 to-transparent opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Modal</p>
            <h3 class="text-2xl font-extrabold text-gray-800">
                Rp{{ number_format($summary['modal'], 0, ',', '.') }}
            </h3>
            <div class="absolute top-6 right-6 text-red-100 group-hover:text-red-200 transition transform group-hover:scale-110">
                <i class="fas fa-wallet text-4xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute right-0 top-0 h-full w-16 bg-gradient-to-l from-green-50 to-transparent opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Profit Bersih</p>
            <h3 class="text-2xl font-extrabold {{ $summary['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                Rp{{ number_format($summary['profit'], 0, ',', '.') }}
            </h3>
            <div class="absolute top-6 right-6 text-green-100 group-hover:text-green-200 transition transform group-hover:scale-110">
                <i class="fas fa-coins text-4xl"></i>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- 3. CARD BREAKDOWN PER KATEGORI (OMZET & COUNT) --}}
    {{-- =========================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        {{-- CARD EKSPEDISI --}}
        <div class="bg-white rounded-lg shadow-sm p-4 border border-yellow-200 flex items-center justify-between hover:shadow-md transition">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Ekspedisi</span>
                </div>
                <h4 class="text-lg font-bold text-gray-800">Rp{{ number_format($summary['ekspedisi']['omzet'], 0, ',', '.') }}</h4>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    <i class="fas fa-receipt me-1"></i> {{ number_format($summary['ekspedisi']['count'], 0, ',', '.') }} Transaksi
                </p>
            </div>
            <div class="bg-yellow-50 p-3 rounded-full text-yellow-600">
                <i class="fas fa-truck-fast text-xl"></i>
            </div>
        </div>

        {{-- CARD PPOB --}}
        <div class="bg-white rounded-lg shadow-sm p-4 border border-purple-200 flex items-center justify-between hover:shadow-md transition">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-purple-100 text-purple-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">PPOB</span>
                </div>
                <h4 class="text-lg font-bold text-gray-800">Rp{{ number_format($summary['ppob']['omzet'], 0, ',', '.') }}</h4>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    <i class="fas fa-receipt me-1"></i> {{ number_format($summary['ppob']['count'], 0, ',', '.') }} Transaksi
                </p>
            </div>
            <div class="bg-purple-50 p-3 rounded-full text-purple-600">
                <i class="fas fa-mobile-screen-button text-xl"></i>
            </div>
        </div>

        {{-- CARD MARKETPLACE --}}
        <div class="bg-white rounded-lg shadow-sm p-4 border border-orange-200 flex items-center justify-between hover:shadow-md transition">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-orange-100 text-orange-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Marketplace</span>
                </div>
                <h4 class="text-lg font-bold text-gray-800">Rp{{ number_format($summary['marketplace']['omzet'], 0, ',', '.') }}</h4>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    <i class="fas fa-receipt me-1"></i> {{ number_format($summary['marketplace']['count'], 0, ',', '.') }} Transaksi
                </p>
            </div>
            <div class="bg-orange-50 p-3 rounded-full text-orange-600">
                <i class="fas fa-store text-xl"></i>
            </div>
        </div>

        {{-- CARD TOP UP --}}
        <div class="bg-white rounded-lg shadow-sm p-4 border border-cyan-200 flex items-center justify-between hover:shadow-md transition">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-cyan-100 text-cyan-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Top Up Saldo</span>
                </div>
                <h4 class="text-lg font-bold text-gray-800">Rp{{ number_format($summary['topup']['omzet'], 0, ',', '.') }}</h4>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    <i class="fas fa-receipt me-1"></i> {{ number_format($summary['topup']['count'], 0, ',', '.') }} Transaksi
                </p>
            </div>
            <div class="bg-cyan-50 p-3 rounded-full text-cyan-600">
                <i class="fas fa-wallet text-xl"></i>
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

                        <th scope="col" class="px-4 py-3 font-extrabold text-center">Unit Usaha</th>

                        {{-- KOLOM BARU: KODE AKUN --}}
                        <th scope="col" class="px-4 py-3 font-extrabold text-center">Kode Akun</th>

                        {{-- DIUBAH: NAMA AKUN / KATEGORI --}}
                        <th scope="col" class="px-4 py-3 font-extrabold">Nama Akun / Kategori</th>

                        <th scope="col" class="px-4 py-3 font-extrabold">Keterangan / Invoice</th>
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

                        {{-- LOGIC MENCARI KODE AKUN & UNIT USAHA --}}
                        @php
                            // Cari data akun di $allAccounts berdasarkan nama kategori/akun
                            // PERHATIKAN: Kita cari 'first' yang cocok. Jika ada duplikat nama, ini bisa ambil yang pertama.
                            // Idealnya controller mengirim 'unit_usaha' di tabel 'keuangans', tapi jika belum ada, kita tebak dari akun.
                            $matchedAccount = $allAccounts->firstWhere('nama_akun', $item->kategori);

                            $kodeAkun = $item->kode_akun ?? ($matchedAccount ? $matchedAccount->kode_akun : '-');
                            // Ambil unit usaha dari tabel transaksi dulu, jika null baru dari master akun
                            $unitUsaha = $item->unit_usaha ?? ($matchedAccount ? $matchedAccount->unit_usaha : 'Umum');
                        @endphp

                        {{-- KOLOM UNIT USAHA (BARU) --}}
                        <td class="px-4 py-3 text-center">
                            @if($unitUsaha == 'Ekspedisi')
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-[10px] font-bold">Ekspedisi</span>
                            @elseif($unitUsaha == 'Percetakan')
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-[10px] font-bold">Percetakan</span>
                            @else
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[10px] font-bold">{{ $unitUsaha }}</span>
                            @endif
                        </td>

                        {{-- LOGIC MENCARI KODE AKUN --}}
                        @php
                            // Cari data akun di $allAccounts berdasarkan nama kategori/akun
                            $matchedAccount = $allAccounts->firstWhere('nama_akun', $item->kategori);
                            $kodeAkun = $matchedAccount ? $matchedAccount->kode_akun : '-';
                        @endphp

                        {{-- KOLOM KODE AKUN --}}
                        <td class="px-4 py-3 text-center">
                            @if($kodeAkun != '-')
                                <span class="font-mono font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded text-xs">
                                    {{ $kodeAkun }}
                                </span>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>

                        {{-- KATEGORI / NAMA AKUN BADGE --}}
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
                        <td class="px-4 py-3 text-center" id="action-cell-{{ $item->id }}">
                            {{-- Logika cek apakah data otomatis --}}
                            @php
                                $isAuto = in_array($item->kategori, ['PPOB', 'Ekspedisi', 'Top Up Saldo', 'Marketplace']);
                            @endphp

                            {{-- KONDISI 1: Data Manual (Langsung tampil tombol Edit/Hapus) --}}
                            @if(!$isAuto)
                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <button onclick='editData(@json($item))' class="bg-amber-400 hover:bg-amber-500 text-white px-2 py-1.5 text-xs rounded-l border-r border-amber-500 transition" title="Edit Manual">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <form action="{{ route('admin.keuangan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('PERINGATAN: Yakin hapus data ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 text-xs rounded-r transition" title="Hapus Manual">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>

                            {{-- KONDISI 2: Data Otomatis (Tampil Gembok yang bisa diklik) --}}
                            @else
                                <button onclick='openPinModal(@json($item))' class="group flex items-center justify-center gap-1 mx-auto text-gray-400 hover:text-red-500 transition px-2 py-1 border border-transparent hover:border-red-200 rounded bg-gray-50 hover:bg-red-50" title="Klik untuk membuka akses edit (Butuh PIN)">
                                    <i class="fas fa-lock group-hover:fa-lock-open transition-all"></i>
                                    <span class="text-[10px] font-bold">Auto</span>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-400">
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
                        <td colspan="5" class="px-4 py-3 text-right text-gray-600 uppercase tracking-wider">
                            Subtotal (Halaman Ini):
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 bg-gray-50">

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
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori / Akun</label>
                                <select id="edit_kategori" name="kategori" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">-- Pilih Akun --</option>

                                    {{-- LOOGIC: Mengelompokkan berdasarkan Unit Usaha agar rapi --}}
                                    @php
                                        $groupedAccounts = $allAccounts->groupBy('unit_usaha');
                                    @endphp

                                    @foreach($groupedAccounts as $unit => $akuns)
                                        <optgroup label="{{ $unit }}">
                                            @foreach($akuns as $akun)
                                                {{-- Value disimpan sebagai Nama Akun agar sesuai dengan data lama --}}
                                                {{-- Tampilan: [1101] Kas --}}
                                                <option value="{{ $akun->nama_akun }}">
                                                    [{{ $akun->kode_akun }}] {{ $akun->nama_akun }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach

                                    {{-- Opsi Fallback jika data lama tidak ada di master akun --}}
                                    <optgroup label="Lainnya">
                                        <option value="Operasional">Operasional</option>
                                        <option value="Gaji">Gaji</option>
                                        <option value="Aset">Aset</option>
                                        <option value="Marketing">Marketing</option>
                                    </optgroup>
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

{{-- =========================================================== --}}
{{-- 7. MODAL PIN SECURITY --}}
{{-- =========================================================== --}}
<div id="modalPin" class="fixed inset-0 z-[110] hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-70 transition-opacity backdrop-blur-sm" onclick="closeModal('modalPin')"></div>

        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <i class="fas fa-user-lock text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg leading-6 font-bold text-gray-900">Keamanan Admin</h3>
                    <p class="text-xs text-gray-500 mt-2">
                        Data ini dibuat otomatis oleh sistem. <br> Masukkan <b>PIN 6 Digit</b> untuk mengubah atau menghapusnya secara paksa.
                    </p>

                    <div class="mt-4">
                        <input type="password" id="input_pin" maxlength="6"
                            class="text-center tracking-[0.5em] text-2xl font-bold w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500"
                            placeholder="••••••" oninput="validateNumeric(this)">
                        <p id="pin_error" class="text-red-600 text-xs mt-2 hidden font-bold"><i class="fas fa-times-circle"></i> PIN Salah!</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 flex gap-2">
                <button type="button" onclick="submitPin()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:text-sm">
                    Buka Gembok
                </button>
                <button type="button" onclick="closeModal('modalPin')" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm">
                    Batal
                </button>
            </div>
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

        // 1. Inisialisasi Flatpickr dengan Tombol Clear
        const dateInput = document.getElementById('date_range_picker');
        const clearBtn = document.getElementById('clearDateBtn');

        if (dateInput) {
            const fp = flatpickr(dateInput, {
                mode: "range",
                dateFormat: "Y-m-d", // Format kirim ke controller
                altInput: true,
                altFormat: "j F Y",  // Format tampil (11 Januari 2026)
                locale: "id",
                theme: "airbnb",
                defaultDate: "{{ request('date_range') }}", // Isi kembali jika ada request

                // Event saat tanggal dipilih
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                },

                // Event saat kalender siap (cek value awal)
                onReady: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        clearBtn.classList.remove('hidden');
                    }
                }
            });

            // Logic Tombol Clear
            if(clearBtn) {
                clearBtn.addEventListener('click', function() {
                    fp.clear(); // Hapus data di flatpickr
                    clearBtn.classList.add('hidden'); // Sembunyikan tombol
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

    // Variabel Global untuk menyimpan data item sementara
    let currentLockedItem = null;
    const ADMIN_PIN = "110622"; // GANTI DENGAN PIN YANG ANDA INGINKAN (Atau gunakan AJAX ke server untuk lebih aman)

    // 1. Fungsi Membuka Modal PIN
    function openPinModal(item) {
        currentLockedItem = item; // Simpan data item yang sedang diklik

        // Reset Input
        document.getElementById('input_pin').value = '';
        document.getElementById('pin_error').classList.add('hidden');

        openModal('modalPin');

        // Auto focus ke input pin setelah modal muncul
        setTimeout(() => {
            document.getElementById('input_pin').focus();
        }, 100);
    }

    // 2. Validasi Hanya Angka
    function validateNumeric(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
    }

    // 3. Submit PIN Logic
    function submitPin() {
        const inputPin = document.getElementById('input_pin').value;
        const errorMsg = document.getElementById('pin_error');

        // Cek PIN
        if (inputPin === ADMIN_PIN) {
            // PIN BENAR
            closeModal('modalPin');
            unlockRowAction(currentLockedItem); // Panggil fungsi ubah tampilan

            // Opsional: Tampilkan notifikasi kecil
            alert('Akses Diberikan. Anda sekarang bisa mengedit data ini.');
        } else {
            // PIN SALAH
            errorMsg.classList.remove('hidden');
            document.getElementById('input_pin').classList.add('border-red-500');

            // Animasi shake (opsional)
            const inputField = document.getElementById('input_pin');
            inputField.classList.add('animate-pulse');
            setTimeout(() => inputField.classList.remove('animate-pulse'), 500);
        }
    }

    function unlockRowAction(item) {
    const cellId = 'action-cell-' + item.id;
    const cell = document.getElementById(cellId);

        if (cell) {
            let deleteUrl = "{{ route('admin.keuangan.destroy', ':id') }}";
            deleteUrl = deleteUrl.replace(':id', item.id);
            
            window['temp_item_' + item.id] = item;

            const newHtml = `
                <div class="inline-flex rounded-md shadow-sm fade-in" role="group">
                    <button onclick='editData(window["temp_item_${item.id}"])' 
                        class="bg-amber-400 hover:bg-amber-500 text-white px-2 py-1.5 text-xs rounded-l border-r border-amber-500 transition" 
                        title="Edit Paksa">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    
                    {{-- PERUBAHAN DISINI: Tambahkan logic untuk menghapus baris TR seketika --}}
                    <form action="${deleteUrl}" method="POST" 
                        onsubmit="if(confirm('Hapus paksa data ini?')) { this.closest('tr').remove(); return true; } else { return false; }" 
                        class="inline">
                        
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 text-xs rounded-r transition" title="Hapus Paksa">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            `;
            cell.innerHTML = newHtml;
        }
    }

    // Tambahkan listener Enter key pada input PIN
    document.getElementById('input_pin').addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            submitPin();
        }
    });

</script>
@endpush
