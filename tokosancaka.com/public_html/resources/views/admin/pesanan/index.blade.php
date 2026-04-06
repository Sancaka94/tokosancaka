{{--
    File: resources/views/admin/pesanan/index.blade.php
    Deskripsi: Halaman Admin untuk manajemen semua pesanan (Full Responsive + Read More Mobile).
--}}

@extends('layouts.admin')

@section('title', 'Data Pesanan Customer')
@section('page-title', 'Data Pesanan Customer')

{{-- =========================================================== --}}
{{-- 1. CSS & STYLE (Disatukan dalam push 'styles')              --}}
{{-- =========================================================== --}}
@push('styles')
    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">

    <style>
        /* === DESKTOP VIEW === */
        @media (min-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            th.sticky-col, td.sticky-col {
                position: -webkit-sticky;
                position: sticky;
                right: 0;
                background-color: white;
                z-index: 10;
                border-left: 1px solid #e5e7eb;
            }
            thead th.sticky-col {
                background-color: #fee2e2; /* Red-100 agar match header */
                z-index: 20;
            }
            tr:hover td.sticky-col {
                background-color: #f9fafb;
            }
        }

        /* === MOBILE VIEW (KARTU & READ MORE) === */
        @media (max-width: 767px) {
            /* Ubah tabel jadi blok (tampilan kartu) */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            /* Sembunyikan Header Tabel Asli */
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            /* Styling Kartu per Pesanan */
            tr {
                margin-bottom: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                background-color: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            /* Styling Isi Kartu */
            td {
                border: none;
                border-bottom: 1px solid #f3f4f6;
                position: relative;
                padding: 0.75rem 1rem !important;
            }
            td:last-child {
                border-bottom: none;
            }

            /* Animasi Transisi Read More */
            .mobile-details {
                transition: all 0.3s ease-in-out;
            }
        }

        /* Animasi untuk tombol Bulk Delete */
        .bulk-action-bar {
            transition: all 0.3s ease-in-out;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
        }
        .bulk-action-bar.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Custom Style untuk Date Picker */
        .flatpickr-calendar { z-index: 9999 !important; }
        .flatpickr-input { background-color: white !important; cursor: pointer !important; }

        /* Animasi Fade In untuk Alert */
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- =========================================================== --}}
    {{-- 2. NOTIFIKASI & ALERT                                       --}}
    {{-- =========================================================== --}}

    {{-- Include Alert Standar (Success/Error) --}}
    @include('layouts.partials.notifications')

    {{-- ALERT KHUSUS WARNING (JADWAL PICKUP DIGESER) --}}
    @if(session('warning'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 px-4 py-4 rounded shadow-sm flex justify-between items-center fade-in">
        <div class="flex items-center">
            <div class="bg-yellow-200 rounded-full p-2 me-3">
                <i class="fas fa-clock text-yellow-700 text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-sm text-yellow-900">Perhatian: Jadwal Pickup Disesuaikan</h4>
                <p class="text-sm font-medium">
                    {!! session('warning') !!}
                </p>
            </div>
        </div>
        <button @click="show = false" class="text-yellow-700 hover:text-yellow-900 transition cursor-pointer">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- 3. KONTEN UTAMA                                             --}}
    {{-- =========================================================== --}}
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">

        {{-- HEADER & SEARCH & FILTER DATE --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">

            {{-- BAGIAN KIRI: FORM PENCARIAN & FILTER --}}
            <div class="w-full lg:w-3/4">
                <form action="{{ route('admin.pesanan.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">

                    {{-- Keep current status --}}
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif

                    {{-- 1. Input Search --}}
                    <div class="relative w-full md:w-1/3">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm transition shadow-sm"
                            placeholder="Cari Resi, Nama, dll...">
                    </div>

                    {{-- 2. Input Tanggal (Flatpickr) --}}
                    <div class="relative w-full md:w-1/3 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="far fa-calendar-alt"></i>
                        </div>

                        <input type="text" id="date_range_picker" name="date_range" value="{{ request('date_range') }}"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm bg-white transition shadow-sm"
                            placeholder="Filter Tanggal..." readonly>

                        {{-- Tombol Clear --}}
                        <button type="button" id="clearDateBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 hidden cursor-pointer" style="z-index: 10;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>

                    {{-- 3. Tombol Filter --}}
                    <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>

                </form>
            </div>

            {{-- BAGIAN KANAN: TOMBOL AKSI --}}
            <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                <button type="button" onclick="openModal('exportModal')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    <i class="fas fa-file-export me-2 text-green-600"></i>Export
                </button>
                <a href="{{ route('admin.pesanan.create') }}" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 shadow-sm transition">
                    <i class="fas fa-plus me-2"></i>Order Baru
                </a>
            </div>
        </div>

        {{-- TAB STATUS --}}
        <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
            @php
                $routeIndex = 'admin.pesanan.index';
                $statuses = [
                    'Menunggu Pickup' => 'Menunggu Pickup',
                    'Diproses'        => 'Diproses',
                    'Terkirim'        => 'Terkirim',
                    'Selesai'         => 'Selesai',
                    'Batal'           => 'Batal',
                    'Gagal Resi'      => 'Pembayaran Lunas (Gagal Auto-Resi)'
                ];
                $currentStatus = request('status');
                $baseQuery = request()->except(['status', 'page']);
            @endphp

            <a href="{{ route($routeIndex, $baseQuery) }}"
               class="px-4 py-2 text-xs font-bold rounded-full border transition
                      {{ !$currentStatus ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                Semua
            </a>

            @foreach($statuses as $label => $value)
                <a href="{{ route($routeIndex, array_merge($baseQuery, ['status' => $value, 'page' => 1])) }}"
                   class="px-4 py-2 text-xs font-bold rounded-full border transition
                          {{ $currentStatus == $value ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- === CARD MONITOR (PENDAPATAN & JUMLAH) === --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            {{-- ROW 1: MONITOR PENDAPATAN (RP) --}}
            <div class="relative overflow-hidden rounded-lg bg-green-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">Rp{{ number_format($incomeSelesai ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Pendapatan Selesai</p>
                    <p class="text-xs opacity-75 mt-0.5">Total nilai pesanan sukses</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-store fa-5x text-white"></i></div>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-cyan-600 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">Rp{{ number_format($incomePickup ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Menunggu Pickup</p>
                    <p class="text-xs opacity-75 mt-0.5">Sudah lunas, belum kirim</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-box-open fa-5x text-white"></i></div>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-blue-600 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">Rp{{ number_format($incomeDikirim ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Sedang Dikirim</p>
                    <p class="text-xs opacity-75 mt-0.5">Sedang dalam perjalanan</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-shipping-fast fa-5x text-white"></i></div>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-red-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">Rp{{ number_format($incomeGagal ?? 0, 0, ',', '.') }}</p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Gagal / Batal</p>
                    <p class="text-xs opacity-75 mt-0.5">Potensi pendapatan hilang</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-arrow-up fa-5x text-white"></i></div>
            </div>

            {{-- ROW 2: MONITOR JUMLAH TRANSAKSI (QTY) --}}
            <div class="relative overflow-hidden rounded-lg bg-green-400 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ number_format($countSelesai ?? 0, 0, ',', '.') }} <span class="text-lg font-normal">Resi</span></p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Jumlah Terkirim</p>
                    <p class="text-xs opacity-75 mt-0.5">Total paket berhasil sampai</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-check-circle fa-5x text-white"></i></div>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-cyan-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ number_format($countPickup ?? 0, 0, ',', '.') }} <span class="text-lg font-normal">Paket</span></p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Jml. Menunggu Pickup</p>
                    <p class="text-xs opacity-75 mt-0.5">Paket siap diambil kurir</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-clock fa-5x text-white"></i></div>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-blue-500 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ number_format($countDikirim ?? 0, 0, ',', '.') }} <span class="text-lg font-normal">Paket</span></p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Jml. Sedang Dikirim</p>
                    <p class="text-xs opacity-75 mt-0.5">Paket dalam perjalanan</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-truck-moving fa-5x text-white"></i></div>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-red-400 p-5 shadow-lg">
                <div class="relative z-10 text-white">
                    <p class="text-3xl font-bold">{{ number_format($countGagal ?? 0, 0, ',', '.') }} <span class="text-lg font-normal">Trx</span></p>
                    <p class="text-sm font-bold uppercase opacity-90 mt-1">Jml. Gagal / Batal</p>
                    <p class="text-xs opacity-75 mt-0.5">Transaksi dibatalkan/retur</p>
                </div>
                <div class="absolute right-0 top-0 -mt-2 -mr-4 h-24 w-24 opacity-20 transform rotate-12"><i class="fas fa-ban fa-5x text-white"></i></div>
            </div>

        </div>

        {{-- === TOMBOL AKSI MASSAL (MUNCUL SAAT ADA YANG DICEKLIST) === --}}
        <div id="bulkActionBar" class="bulk-action-bar bg-red-50 border border-red-200 rounded-lg p-3 mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-sm text-red-800 font-semibold flex items-center gap-2">
                <i class="fas fa-check-square text-red-500 text-lg"></i>
                <span id="selectedCount">0</span> Pesanan Terpilih
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="button" id="btnSelectAll" onclick="toggleSelectAll()" class="flex-1 sm:flex-none bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition">
                    Pilih Semua
                </button>
                <button type="button" onclick="showBulkDeleteModal()" class="flex-1 sm:flex-none bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700 shadow-sm transition flex items-center justify-center gap-2">
                    <i class="fas fa-trash-alt"></i> Hapus Terpilih
                </button>
            </div>
        </div>

        {{-- FORM UNTUK HAPUS MASSAL --}}
        <form id="bulkDeleteForm" action="{{ route('admin.pesanan.bulk_destroy') }}" method="POST">
            @csrf
            @method('DELETE')

            {{-- TABEL DATA --}}
            <div class="table-container">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-red-100">
                        <tr>
                            {{-- TH Checkbox Header --}}
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 w-10">
                                <input type="checkbox" id="checkAllHeader" onclick="toggleSelectAllHeader(this)" class="w-4 h-4 text-red-600 bg-white border-gray-300 rounded focus:ring-red-500 cursor-pointer">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Transaksi</strong></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Alamat</strong></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Ekspedisi & Ongkir</strong></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Isi Paket</strong></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"><strong>Status</strong></th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider sticky-col"><strong>Aksi</strong></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orders as $index => $order)
                        <tr class="group hover:bg-gray-50 row-order">

                            {{-- TD Checkbox --}}
                            <td class="px-4 py-4 align-top border-b-0 pb-0 md:pb-4 md:border-b bg-gray-50 md:bg-transparent">
                                <div class="flex items-center md:block">
                                    <span class="md:hidden font-bold text-gray-400 text-xs mr-2">PILIH:</span>
                                    <input type="checkbox" name="selected_ids[]" value="{{ $order->nomor_invoice }}" data-invoice="{{ $order->nomor_invoice }}" data-resi="{{ $order->resi ?? 'Belum ada resi' }}" onchange="updateBulkActionUI()" class="row-checkbox w-4 h-4 text-red-600 bg-white border-gray-300 rounded focus:ring-red-500 cursor-pointer shadow-sm">
                                </div>
                            </td>

                            {{-- 1. NO --}}
                            <td class="px-4 py-4 align-top text-sm text-gray-500 md:w-12 border-b-0 pb-0 md:pb-4 md:border-b">
                                <div class="flex items-center justify-between md:block">
                                    <div>
                                        <span class="md:hidden font-bold text-gray-400 text-xs mr-2">NO:</span>
                                        {{ $orders->firstItem() + $index }}
                                    </div>
                                </div>
                            </td>

                        {{-- 2. TRANSAKSI --}}
                        <td class="px-4 py-4 align-top text-sm relative">
                            <span class="md:hidden block font-bold text-gray-400 text-xs mb-1">TRANSAKSI:</span>

                            {{-- Metode Pembayaran --}}
                            @if(Str::contains($order->payment_method, 'COD'))
                                <span class="font-bold text-green-600">COD</span><br>
                            @else
                                <span class="font-bold text-blue-600">{{$order->payment_method}}</span><br>
                            @endif

                            {{-- Resi --}}
                            @php
                                $resiValue = $order->resi ?? null;
                                $isResiReady = !empty($resiValue) && strtolower($resiValue) !== 'menunggu resi';
                            @endphp

                            @if($isResiReady)
                                <div class="bg-red-200 border border-red-500 text-gray-800 font-bold mt-1 p-2 rounded flex items-center justify-between whitespace-nowrap">
                                    <span>RESI: <span id="resiNumber-{{$index}}">{{ $resiValue }}</span></span>
                                    <button onclick="copyResiNumber('resiNumber-{{$index}}')" class="text-gray-700 hover:text-gray-900 ml-3" title="Copy">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            @else
                                <div class="font-bold text-red-800 mt-1">RESI: Menunggu Resi</div>
                            @endif

                            {{-- ========================================================================= --}}
                            {{-- 🔥 PERBAIKAN: MENAMPILKAN SHIPPING REF (KODE BOOKING) JIKA ADA 🔥 --}}
                            {{-- ========================================================================= --}}
                            @if(!empty($order->shipping_ref))
                                <div class="bg-blue-100 border border-blue-400 text-blue-800 text-xs font-semibold mt-1 p-1.5 rounded flex items-center gap-1">
                                    <i class="fas fa-barcode"></i> Ref: {{ $order->shipping_ref }}
                                </div>
                            @endif
                            {{-- ========================================================================= --}}

                            <div class="text-xs text-gray-500 mt-1">Invoice: <strong>{{ $order->nomor_invoice }}</strong></div>
                            <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($order->tanggal_pesanan)->format('d M Y, H:i') }}</div>

                            {{-- TOMBOL TRIGGER READ MORE (MOBILE) --}}
                            <div class="md:hidden mt-3">
                                <button type="button"
                                        onclick="toggleDetails({{$index}}, this)"
                                        class="w-full bg-gray-100 text-gray-600 py-2 rounded text-sm font-semibold hover:bg-gray-200 flex items-center justify-center gap-2 transition-colors duration-200">
                                    <span>Lihat Detail Lengkap</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </td>

                        {{-- 3. ALAMAT --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm bg-gray-50 md:bg-white toggle-target-{{$index}}">
                             <span class="md:hidden block font-bold text-gray-500 text-xs mb-1 uppercase tracking-wider border-b pb-1 mt-2">📍 Alamat Pengiriman</span>

                            {{-- NAMA PENGIRIM (Selalu Tampil) --}}
                            <div class="mb-2">
                                <div class="text-xs text-gray-500">Dari:</div>
                                <div class="font-semibold text-blue-700 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user w-4 text-center"></i> {{ $order->sender_name }}
                                    </div>
                                </div>
                            </div>

                            {{-- NAMA PENERIMA (Selalu Tampil) --}}
                            <div class="border-t border-dashed my-2 pt-2 md:border-none md:my-0 md:pt-0">
                                <div class="text-xs text-gray-500 flex items-center gap-1"><i class="fas fa-arrow-right text-gray-400"></i> Ke:</div>
                                <div class="font-semibold text-red-700 space-y-1 mt-1">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user w-4 text-center"></i> {{ $order->receiver_name ?? $order->nama_pembeli }}
                                    </div>
                                </div>
                            </div>

                            {{-- TOMBOL BUKA/TUTUP DETAIL --}}
                            <button type="button" onclick="toggleAddressDetail({{$index}}, this)" class="mt-3 w-full text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 border border-gray-200 py-1.5 px-2 rounded flex items-center justify-center gap-1 transition-colors">
                                <span>Lihat Detail Alamat</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            {{-- BLOK DETAIL ALAMAT (Tersembunyi) --}}
                            <div id="addressDetail-{{$index}}" class="hidden mt-3 pt-3 border-t border-gray-200">
                                {{-- Detail Pengirim Lengkap --}}
                                <div class="mb-3">
                                    <div class="font-semibold text-blue-700 space-y-1">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-phone-alt w-4 text-center"></i> {{ $order->sender_phone }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-600 leading-tight flex items-start gap-2 mt-1">
                                        <i class="fas fa-map-marker-alt mt-1 text-red-500 w-4 text-center"></i>
                                        <div>
                                            {{ $order->sender_address }}<br>
                                            {{ $order->sender_village }}, {{ $order->sender_district }}<br>
                                            {{ $order->sender_regency }}, {{ $order->sender_province }}<br>
                                            <span class="font-semibold">Kode Pos: {{ $order->sender_postal_code }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Detail Penerima Lengkap --}}
                                <div class="border-t border-dashed pt-3 mt-3">
                                    <div class="font-semibold text-red-700 space-y-1">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-phone-alt w-4 text-center"></i> {{ $order->receiver_phone }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-600 leading-tight flex items-start gap-2 mt-1">
                                        <i class="fas fa-map-marker-alt mt-1 text-red-500 w-4 text-center"></i>
                                        <div>
                                            {{ $order->receiver_address }}<br>
                                            {{ $order->receiver_village }}, {{ $order->receiver_district }}<br>
                                            {{ $order->receiver_regency }}, {{ $order->receiver_province }}<br>
                                            <span class="font-semibold">Kode Pos: {{ $order->receiver_postal_code }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- 4. EKSPEDISI --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm bg-gray-50 md:bg-white toggle-target-{{$index}}">
                             <span class="md:hidden block font-bold text-gray-500 text-xs mb-1 uppercase tracking-wider border-b pb-1">🚚 Ekspedisi & Ongkir</span>
                            @php
                                $ship = \App\Helpers\ShippingHelper::parseShippingMethod($order->expedition);
                                $courierName = $ship['courier_name'] ?? 'N/A';
                                $serviceName = $ship['service_name'] ?? 'N/A';
                                $logoUrl = $ship['logo_url'] ?? null;
                            @endphp

                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $courierName }}" class="h-6 mb-1 object-contain">
                            @else
                                <div class="font-bold text-gray-800">{{ $courierName }}</div>
                            @endif

                            <div class="text-xs text-gray-500">{{ $serviceName }}</div>
                            <div class="font-semibold text-green-700 mt-1">
                                Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}
                            </div>
                        </td>

                        {{-- 5. ISI PAKET --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm bg-gray-50 md:bg-white toggle-target-{{$index}}">
                             <span class="md:hidden block font-bold text-gray-500 text-xs mb-1 uppercase tracking-wider border-b pb-1">📦 Detail Paket</span>
                            <div class="font-semibold text-gray-800">Isi: {{ $order->item_description }}</div>
                            <div class="text-xs text-gray-500 mt-1">Dimensi: {{ $order->length ?? '0' }} x {{ $order->width ?? '0' }} x {{ $order->height ?? '0' }}</div>
                            <div class="text-xs text-gray-500 mt-1">Berat: {{ $order->weight }}gr</div>
                            <div class="mt-1 text-xs">Nilai: Rp {{ number_format($order->total_harga_barang ?? $order->item_price ?? 0, 0, ',', '.') }}</div>
                        </td>

                        {{-- 6. STATUS --}}
                        <td class="hidden md:table-cell px-4 py-4 align-top text-sm bg-gray-50 md:bg-white toggle-target-{{$index}}">
                             <span class="md:hidden block font-bold text-gray-500 text-xs mb-1 uppercase tracking-wider border-b pb-1">🔖 Status Pesanan</span>
                            @php
                                $statusText = $order->status_pesanan;
                                $bgClass = match($statusText) {
                                    'Terkirim', 'Selesai', 'Sedang Dikirim' => 'bg-green-100 text-green-800',
                                    'Diproses' => 'bg-blue-100 text-blue-800',
                                    'Menunggu Pickup' => 'bg-yellow-100 text-yellow-800',
                                    'Batal', 'Gagal Bayar', 'Kadaluarsa' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                                if (Str::contains($statusText, 'Gagal Auto-Resi')) $statusText = 'Gagal Resi';
                            @endphp
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded whitespace-nowrap {{ $bgClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>

                        {{-- 7. AKSI --}}
                        <td class="hidden md:table-cell px-4 py-4 align-middle whitespace-nowrap text-sm font-medium sticky-col bg-gray-50 md:bg-white border-t md:border-none toggle-target-{{$index}}">
                             <span class="md:hidden block font-bold text-gray-500 text-xs mb-2 text-center uppercase border-b pb-2">⚙️ Aksi</span>
                            <div class="flex items-center justify-center md:justify-center space-x-3 md:space-x-3 w-full py-2 md:py-0">
                                {{-- Detail --}}
                                <a href="{{ route('admin.pesanan.show', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" class="text-gray-500 hover:text-blue-600 transform hover:scale-110 transition" title="Detail">
                                    <i class="fas fa-eye fa-lg"></i>
                                </a>

                                {{-- Edit --}}
                                <a href="{{ route('admin.pesanan.edit', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" class="text-gray-500 hover:text-blue-600 transform hover:scale-110 transition" title="Edit">
                                    <i class="fas fa-pencil-alt fa-lg"></i>
                                </a>

                                {{-- Cetak & Lacak --}}
                                @if($order->resi)
                                    <a href="{{ route('admin.pesanan.cetak_thermal', ['resi' => $order->resi]) }}" target="_blank" class="text-gray-500 hover:text-gray-800 transform hover:scale-110 transition" title="Cetak Label">
                                        <i class="fas fa-print fa-lg"></i>
                                    </a>
                                    <a href="https://tokosancaka.com/tracking/search?resi={{ $order->resi }}" target="_blank" class="text-gray-500 hover:text-green-600 transform hover:scale-110 transition" title="Lacak Resi">
                                        <i class="fas fa-truck fa-lg"></i>
                                    </a>
                                @endif

                                {{-- Hapus --}}
                                <form action="{{ route('admin.pesanan.destroy', ['resi' => $order->resi ?? $order->nomor_invoice]) }}" method="POST" onsubmit="return confirm('Yakin hapus pesanan ini?');" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-600 transform hover:scale-110 transition" title="Hapus">
                                        <i class="fas fa-trash-alt fa-lg"></i>
                                    </button>
                                </form>

                                {{-- Cancel Order Trigger Button --}}
                                @if(in_array($order->status_pesanan, ['Menunggu Pickup', 'Pesanan Dibuat']) && !empty($order->resi) && !Str::startsWith($order->resi, 'REF-') && !Str::contains($order->resi, 'MOCK'))
                                    <button type="button" onclick="document.getElementById('cancelModal_{{ $order->nomor_invoice }}').classList.remove('hidden')" class="text-gray-500 hover:text-yellow-500 transform hover:scale-110 transition cursor-pointer relative z-50 p-2" title="Batalkan via API">
                                        {{-- pointer-events-none akan memaksa klik tembus ke button --}}
                                        <i class="fas fa-times-circle fa-xl pointer-events-none"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i><br>
                                Data pesanan tidak ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        {{-- Pagination --}}
        @if ($orders->hasPages())
        <div class="mt-4 p-4 border-t border-gray-200">
            {{ $orders->appends(request()->query())->links('vendor.pagination.tailwind') }}
        </div>
        @endif
    </div>

    {{-- ======================================================================= --}}
    {{-- KUMPULAN MODAL CANCEL (MENGGUNAKAN ID INVOICE UNIK & TAILWIND UI)       --}}
    {{-- ======================================================================= --}}
    @foreach ($orders as $order)
        @if(in_array($order->status_pesanan, ['Menunggu Pickup', 'Pesanan Dibuat']) && !empty($order->resi) && !Str::startsWith($order->resi, 'REF-') && !Str::contains($order->resi, 'MOCK'))

        <div id="cancelModal_{{ $order->nomor_invoice }}" class="relative z-[99999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Background Backdrop Gelap --}}
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="document.getElementById('cancelModal_{{ $order->nomor_invoice }}').classList.add('hidden')"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

                    {{-- Kotak Modal --}}
                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200">
                        <form action="{{ route('admin.pesanan.cancel', $order->resi) }}" method="POST">
                            @csrf

                            {{-- Header & Body --}}
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                                    </div>
                                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                        <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">Batalkan Pesanan</h3>
                                        <div class="mt-3">
                                            <p class="text-sm text-gray-600 mb-4">Anda akan membatalkan resi <strong class="bg-red-50 text-red-700 px-2 py-1 rounded border border-red-200">{{ $order->resi }}</strong> di sistem KiriminAja.</p>

                                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Alasan Pembatalan</label>
                                            <textarea name="reason" rows="3" class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm" required minlength="5" maxlength="200" placeholder="Ketik alasan pembatalan di sini..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer / Tombol --}}
                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-200">
                                <button type="submit" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin mr-2\'></i>Memproses...'; this.classList.add('opacity-70','cursor-wait');" class="inline-flex w-full justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto transition">
                                    Ya, Batalkan
                                </button>
                                <button type="button" onclick="document.getElementById('cancelModal_{{ $order->nomor_invoice }}').classList.add('hidden')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-100 sm:mt-0 sm:w-auto transition">
                                    Tutup
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

        @endif
    @endforeach

    {{-- ======================================================================= --}}
    {{-- MODAL KONFIRMASI HAPUS MASSAL (FIXED VISIBILITY)                        --}}
    {{-- ======================================================================= --}}
    <div id="bulkDeleteModal" class="hidden" style="position: fixed; inset: 0px; z-index: 99999;">

        {{-- Backdrop Gelap --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeModal('bulkDeleteModal')"></div>

        {{-- Scrollable Container --}}
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

                {{-- Kotak Modal Utama --}}
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all w-full sm:max-w-2xl border border-gray-200">

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-trash-alt text-red-600 text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">Konfirmasi Hapus Pesanan</h3>
                                <div class="mt-3">
                                    <p class="text-sm text-gray-600 mb-4">
                                        Anda yakin ingin menghapus <strong id="modalSelectedCount" class="text-red-600 text-lg">0</strong> pesanan berikut secara permanen? Data yang dihapus tidak dapat dikembalikan.
                                    </p>

                                    {{-- Daftar List Data yang Akan Dihapus --}}
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-64 overflow-y-auto">
                                        <ul id="deleteItemsList" class="divide-y divide-gray-200 text-sm text-gray-700">
                                            {{-- List akan di-inject via JavaScript (Terbukti sudah berhasil di Log) --}}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Bawah --}}
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-200">
                        <button type="button" onclick="submitBulkDelete()" id="btnConfirmDelete" class="inline-flex w-full justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto transition items-center gap-2">
                            <i class="fas fa-trash"></i> Ya, Hapus Semua
                        </button>
                        <button type="button" onclick="closeModal('bulkDeleteModal')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-100 sm:mt-0 sm:w-auto transition">
                            Batal
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @include('layouts.partials.modals.export', ['excel_route' => route('admin.pesanan.export.excel'), 'pdf_route' => route('admin.pesanan.export.pdf')])

</div>
@endsection

{{-- =========================================================== --}}
{{-- 4. JAVASCRIPT & SCRIPTS (Disatukan dalam push 'scripts')    --}}
{{-- =========================================================== --}}
@push('scripts')
    {{-- Flatpickr JS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>



    <script>
        // Modal Logic
        function openModal(id) {
            const el = document.getElementById(id);
            if(el) el.classList.remove('hidden');
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        }

        // Copy Resi Logic
        function copyResiNumber(elementId) {
            const targetId = elementId || 'resiNumber';
            const textElement = document.getElementById(targetId);

            if(textElement) {
                const text = textElement.innerText;
                navigator.clipboard.writeText(text).then(() => {
                    // Opsional: Bisa pakai Toast notification biar lebih elegan
                    alert('Nomor resi berhasil disalin!');
                }).catch(err => {
                    console.error('Gagal copy: ', err);
                });
            }
        }

        // === LOGIC READ MORE (MOBILE) ===
        function toggleDetails(index, btn) {
            // Ambil semua elemen hidden di baris ini berdasarkan index
            const targets = document.querySelectorAll('.toggle-target-' + index);
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');

            targets.forEach(target => {
                // Kita pakai class 'block' untuk override 'hidden' di mobile
                if (target.classList.contains('hidden')) {
                    target.classList.remove('hidden');
                    target.classList.add('block'); // Paksa tampil sebagai block di mobile
                    target.style.animation = "fadeIn 0.5s";
                } else {
                    target.classList.add('hidden');
                    target.classList.remove('block');
                }
            });

            // Ubah Icon dan Teks Tombol
            if (icon.classList.contains('fa-chevron-down')) {
                // State: Sedang Terbuka
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                textSpan.innerText = "Tutup Detail";
                btn.classList.add('bg-red-50', 'text-red-600');
                btn.classList.remove('bg-gray-100', 'text-gray-600');
            } else {
                // State: Sedang Tertutup
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                textSpan.innerText = "Lihat Detail Lengkap";
                btn.classList.remove('bg-red-50', 'text-red-600');
                btn.classList.add('bg-gray-100', 'text-gray-600');
            }
        }

        // === LOGIC READ MORE ALAMAT ===
        function toggleAddressDetail(index, btn) {
            const detailDiv = document.getElementById('addressDetail-' + index);
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('span');

            if (detailDiv.classList.contains('hidden')) {
                // Buka detail
                detailDiv.classList.remove('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                textSpan.innerText = "Tutup Detail Alamat";
            } else {
                // Tutup detail
                detailDiv.classList.add('hidden');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                textSpan.innerText = "Lihat Detail Alamat";
            }
        }

        // === FLATPICKR LOGIC ===
        (function() {
            var dateInput = document.getElementById('date_range_picker');
            var clearBtn = document.getElementById('clearDateBtn');

            // Cek apakah elemen benar-benar ada sebelum dijalankan
            if (dateInput) {
                var fp = flatpickr(dateInput, {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "j F Y",
                    locale: "id",
                    disableMobile: "true",
                    theme: "airbnb",
                    onReady: function(selectedDates, dateStr, instance) {
                        if (dateStr && clearBtn) clearBtn.classList.remove('hidden');
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (dateStr && clearBtn) {
                            clearBtn.classList.remove('hidden');
                        } else if (clearBtn) {
                            clearBtn.classList.add('hidden');
                        }
                    }
                });

                if(clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        fp.clear();
                        clearBtn.classList.add('hidden');
                    });
                }
            }
        })();

       // ==========================================
        // LOGIKA BULK DELETE (DENGAN DEBUGGING F12)
        // ==========================================
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCountText = document.getElementById('selectedCount');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const checkAllHeader = document.getElementById('checkAllHeader');

        function toggleSelectAllHeader(source) {
            console.log("-> Checkbox Header diklik. Status:", source.checked);
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
            updateBulkActionUI();
        }

        function toggleSelectAll() {
            console.log("-> Tombol 'Pilih Semua' diklik.");
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(cb => cb.checked = !allChecked);
            if(checkAllHeader) checkAllHeader.checked = !allChecked;

            updateBulkActionUI();
        }

        function updateBulkActionUI() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const count = checkedBoxes.length;

            if(selectedCountText) selectedCountText.innerText = count;

            if(checkAllHeader) {
                checkAllHeader.checked = (count === checkboxes.length && count > 0);
            }

            if(btnSelectAll) {
                if (count === checkboxes.length && count > 0) {
                    btnSelectAll.innerText = "Batal Pilih Semua";
                    btnSelectAll.classList.replace('bg-white', 'bg-gray-200');
                } else {
                    btnSelectAll.innerText = "Pilih Semua";
                    btnSelectAll.classList.replace('bg-gray-200', 'bg-white');
                }
            }

            if(bulkActionBar) {
                if (count > 0) {
                    bulkActionBar.classList.add('active');
                    bulkActionBar.style.display = 'flex';
                } else {
                    bulkActionBar.classList.remove('active');
                    // Jika butuh disembunyikan total saat kosong:
                    // bulkActionBar.style.display = 'none';
                }
            }
        }

        // ========================================================
        // DEBUGGING FOKUS DI SINI (TOMBOL HAPUS TERPILIH)
        // ========================================================
        function showBulkDeleteModal() {
            console.log("--- DEBUG: Memulai showBulkDeleteModal() ---");
            try {
                const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
                console.log("1. Jumlah data yang diceklist:", checkedBoxes.length);

                if (checkedBoxes.length === 0) {
                    alert("Pilih minimal satu pesanan untuk dihapus.");
                    return;
                }

                const countEl = document.getElementById('modalSelectedCount');
                if (!countEl) {
                    console.error("❌ ERROR: Elemen dengan ID 'modalSelectedCount' tidak ditemukan di HTML!");
                } else {
                    countEl.innerText = checkedBoxes.length;
                    console.log("2. Angka di modal berhasil diupdate.");
                }

                const listContainer = document.getElementById('deleteItemsList');
                if (!listContainer) {
                    console.error("❌ ERROR: Elemen dengan ID 'deleteItemsList' tidak ditemukan di HTML!");
                } else {
                    listContainer.innerHTML = '';
                    console.log("3. Mempersiapkan list HTML...");

                    checkedBoxes.forEach((cb, index) => {
                        const invoice = cb.getAttribute('data-invoice') || 'N/A';
                        const resi = cb.getAttribute('data-resi') || 'N/A';

                        console.log(`   -> Data ke-${index+1}: Invoice: ${invoice} | Resi: ${resi}`);

                        const li = document.createElement('li');
                        li.className = "py-2 flex justify-between items-center";
                        li.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-400 w-5">${index + 1}.</span>
                                <span class="font-bold text-gray-800">${invoice}</span>
                            </div>
                            <span class="text-xs px-2 py-1 bg-white border border-gray-200 rounded text-gray-600">${resi}</span>
                        `;
                        listContainer.appendChild(li);
                    });
                    console.log("4. List HTML berhasil di-generate.");
                }

                const modal = document.getElementById('bulkDeleteModal');
                if (!modal) {
                    console.error("❌ ERROR: Elemen Modal dengan ID 'bulkDeleteModal' tidak ditemukan!");
                    alert("Sistem gagal menemukan jendela konfirmasi (Modal tidak ditemukan).");
                } else {
                    console.log("5. Membuka Modal...");
                    // Panggil fungsi bawaan Bapak
                    if (typeof openModal === "function") {
                        openModal('bulkDeleteModal');
                        console.log("✅ Modal berhasil dibuka via openModal()");
                    } else {
                        console.warn("⚠️ Peringatan: Fungsi openModal() tidak terdeteksi, mencoba membuka manual...");
                        modal.classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error("❌ ERROR FATAL JS:", error);
                alert("Terjadi error di Javascript: " + error.message);
            }
            console.log("--- DEBUG: Selesai showBulkDeleteModal() ---");
        }

        // Submit Form
        function submitBulkDelete() {
            console.log("--- DEBUG: Memulai submitBulkDelete() ---");
            try {
                const btn = document.getElementById('btnConfirmDelete');
                if(btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
                    btn.classList.add('opacity-70', 'cursor-wait');
                    btn.disabled = true;
                }

                const form = document.getElementById('bulkDeleteForm');
                if(!form) {
                    console.error("❌ ERROR: Form 'bulkDeleteForm' tidak ditemukan!");
                    alert("Gagal menghapus: Form tidak ditemukan.");
                    return;
                }

                console.log("✅ Form ditemukan, submit dijalankan.");
                form.submit();

            } catch (error) {
                console.error("❌ ERROR FATAL JS:", error);
                alert("Gagal mensubmit form: " + error.message);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateBulkActionUI();
        });

    </script>
@endpush
