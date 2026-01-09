@extends('layouts.marketplace')

@php
    // ================================================================
    // 1. LOGIC PHP: PEMETAAN SKU (AGAR TIDAK SALAH DETEKSI PRODUK)
    // ================================================================
    $urlSlug = request()->segment(4); 
    $pageInfo = $pageInfo ?? [];
    $currentSlug = $pageInfo['slug'] ?? $urlSlug ?? 'pulsa'; 

    // MAPPING: Ubah Slug URL menjadi Kode SKU API yang Benar
    $skuMap = [
        // Kategori Pascabayar
        'pln-pascabayar'     => 'pln',
        'pdam'               => 'pdam',
        'bpjs-kesehatan'     => 'bpjs',
        'bpjs-ketenagakerjaan' => 'bpjstk',
        'hp-pascabayar'      => 'hp',
        'internet-pascabayar'=> 'internet',
        'tv-pascabayar'      => 'tv',
        'multifinance'       => 'multifinance',
        'cicilan'            => 'multifinance',
        'pbb'                => 'cimahi',
        'samsat'             => 'samsat',
        'gas-negara'         => 'pgas',
        'pln-nontaglis'      => 'plnnontaglist',
        'e-money'            => 'emoney',
        
        // Kategori Prabayar (Default)
        'pulsa' => 'pulsa',
        'pln-token' => 'pln', 
        'data' => 'data',
    ];

   // 1. DAFTAR KUNCI KHUSUS PASCABAYAR (Tagihan Bulanan)
    // Pastikan HANYA kode di bawah ini yang dianggap Pascabayar.
    // Hapus 'emoney', 'games', 'voucher' jika ada di sini sebelumnya.
    $postpaidKeys = [
        'pln',           // Khusus PLN Tagihan (Akan kita filter di bawah agar Token tidak kena)
        'pdam', 
        'bpjs', 
        'bpjstk', 
        'hp',            // Kartu Halo / Xplor (Pascabayar)
        'internet',      // Indihome / Wifi ID
        'tv',            // TV Kabel Langganan
        'multifinance',  // Cicilan Leasing
        'pbb', 
        'samsat', 
        'pgas', 
        'plnnontaglist'
    ];

    // 2. DAFTAR KUNCI YANG PASTI PRABAYAR (Pengecualian Eksplisit)
    // Ini untuk menjaga-jaga jika ada SKU yang kodenya mirip (seperti pln-token vs pln-pasca)
    $prepaidSlugs = [
        'pln-token',
        'e-money',
        'voucher-game',
        'games',
        'pulsa',
        'data',
        'streaming',
        'tv-prabayar',  // Pastikan TV Prabayar masuk sini
        'voucher',
        'esim'
    ];

    // Ambil SKU Aktif
    $activeSku = $skuMap[$currentSlug] ?? 'pln';

    // 3. LOGIKA FINAL PENENTUAN HALAMAN
    // Halaman dianggap Pascabayar JIKA:
    // (Ada di list postpaidKeys) DAN (URL-nya BUKAN termasuk kategori Prabayar)
    $isPostpaid = (
        ($pageInfo['is_postpaid'] ?? false) || 
        in_array($activeSku, $postpaidKeys)
    ) && !in_array($currentSlug, $prepaidSlugs);
    
    // Judul Halaman & Label Input
    $pageTitle = $pageInfo['title'] ?? ucfirst(str_replace('-', ' ', $currentSlug));
    $inputLabel = "Nomor Pelanggan";
    
    // 1. DEFAULT (Untuk Pulsa/Data/E-Wallet)
    $inputPlace = "Contoh: 08123456789";

    // 2. LOGIKA DETEKSI DINAMIS BERDASARKAN SLUG/SKU
    if (strpos($currentSlug, 'pln') !== false || $activeSku == 'pln') {
        // Khusus PLN (Token & Tagihan)
        $inputPlace = "Contoh: 51234567890 (No. Meter/ID Pel)";
    } elseif ($activeSku == 'pdam') {
        // Khusus PDAM
        $inputPlace = "Contoh: 100223344 (ID Pelanggan)";
    } elseif (strpos($activeSku, 'bpjs') !== false) {
        // Khusus BPJS
        $inputPlace = "Contoh: 888880133445566";
    } elseif ($activeSku == 'internet' || strpos($currentSlug, 'indihome') !== false) {
        // Khusus Internet/Wifi
        $inputPlace = "Contoh: 122333444455";
    } elseif ($activeSku == 'samsat') {
        // Khusus Samsat
        $inputLabel = "Kode Bayar / No. KTP";
        $inputPlace = "Contoh: 32011234..., 8821...";
    } elseif ($activeSku == 'pbb' || $activeSku == 'cimahi') {
        // Khusus PBB
        $inputLabel = "Nomor Objek Pajak (NOP)";
        $inputPlace = "Masukkan Nomor NOP";
    } elseif ($activeSku == 'gas' || strpos($currentSlug, 'gas') !== false) {
        // Khusus Gas
        $inputPlace = "Contoh: 12345678 (ID Pelanggan)";
    }

    // ================================================================
    // 2. DATA MENU (TAMPILAN LENGKAP)
    // ================================================================
    $prepaidMenus = [
        ['slug' => 'pulsa', 'name' => 'Pulsa', 'icon' => 'fa-mobile-alt', 'style' => 'text-red-500 bg-red-50 border-red-200'],
        ['slug' => 'data', 'name' => 'Paket Data', 'icon' => 'fa-wifi', 'style' => 'text-blue-500 bg-blue-50 border-blue-200'],
        ['slug' => 'pln-token', 'name' => 'Token PLN', 'icon' => 'fa-bolt', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-200'],
        ['slug' => 'e-money', 'name' => 'E-Money', 'icon' => 'fa-wallet', 'style' => 'text-purple-500 bg-purple-50 border-purple-200'],
        ['slug' => 'voucher-game', 'name' => 'Games', 'icon' => 'fa-gamepad', 'style' => 'text-indigo-500 bg-indigo-50 border-indigo-200'],
        ['slug' => 'voucher', 'name' => 'Voucher', 'icon' => 'fa-ticket-alt', 'style' => 'text-pink-500 bg-pink-50 border-pink-200'],
        ['slug' => 'paket-sms-telpon', 'name' => 'SMS & Telpon', 'icon' => 'fa-phone-volume', 'style' => 'text-green-500 bg-green-50 border-green-200'],
        ['slug' => 'masa-aktif', 'name' => 'Masa Aktif', 'icon' => 'fa-hourglass-half', 'style' => 'text-orange-500 bg-orange-50 border-orange-200'],
        ['slug' => 'aktivasi-voucher', 'name' => 'Akt. Voucher', 'icon' => 'fa-barcode', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'aktivasi-perdana', 'name' => 'Akt. Perdana', 'icon' => 'fa-sim-card', 'style' => 'text-slate-600 bg-slate-50 border-slate-200'],
        ['slug' => 'streaming', 'name' => 'Streaming', 'icon' => 'fa-play-circle', 'style' => 'text-red-600 bg-red-50 border-red-200'],
        ['slug' => 'tv', 'name' => 'TV Prabayar', 'icon' => 'fa-tv', 'style' => 'text-teal-500 bg-teal-50 border-teal-200'],
        ['slug' => 'china-topup', 'name' => 'China Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-200'],
        ['slug' => 'malaysia-topup', 'name' => 'Malaysia Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-600 bg-blue-50 border-blue-200'],
        ['slug' => 'philippines-topup', 'name' => 'Phil. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-200'],
        ['slug' => 'singapore-topup', 'name' => 'S\'pore Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-600 bg-red-50 border-red-200'],
        ['slug' => 'thailand-topup', 'name' => 'Thai Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-500 bg-blue-50 border-blue-200'],
        ['slug' => 'vietnam-topup', 'name' => 'Vietnam Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-200'],
        ['slug' => 'bundling', 'name' => 'Bundling', 'icon' => 'fa-box-open', 'style' => 'text-cyan-500 bg-cyan-50 border-cyan-200'],
        ['slug' => 'gas', 'name' => 'Gas Token', 'icon' => 'fa-burn', 'style' => 'text-orange-600 bg-orange-50 border-orange-200'],
        ['slug' => 'esim', 'name' => 'eSIM', 'icon' => 'fa-qrcode', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-200'],
        ['slug' => 'media-sosial', 'name' => 'Media Sosial', 'icon' => 'fa-thumbs-up', 'style' => 'text-blue-500 bg-blue-50 border-blue-200'],
        ['slug' => 'telkomsel-omni', 'name' => 'Tsel Omni', 'icon' => 'fa-tower-cell', 'style' => 'text-red-600 bg-red-50 border-red-200'],
        ['slug' => 'indosat-only4u', 'name' => 'Isat Only4u', 'icon' => 'fa-star', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-200'],
        ['slug' => 'tri-cuanmax', 'name' => 'Tri CuanMax', 'icon' => 'fa-percent', 'style' => 'text-purple-600 bg-purple-50 border-purple-200'],
        ['slug' => 'xl-axis-cuanku', 'name' => 'XL Cuanku', 'icon' => 'fa-gift', 'style' => 'text-blue-600 bg-blue-50 border-blue-200'],
        ['slug' => 'by-u', 'name' => 'by.U', 'icon' => 'fa-ghost', 'style' => 'text-orange-500 bg-orange-50 border-orange-200'],
    ];

    $postpaidMenus = [
        ['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca', 'icon' => 'fa-file-invoice-dollar', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-200'],
        ['slug' => 'pdam', 'name' => 'PDAM', 'icon' => 'fa-faucet', 'style' => 'text-cyan-600 bg-cyan-50 border-cyan-200'],
        ['slug' => 'bpjs-kesehatan', 'name' => 'BPJS Kes.', 'icon' => 'fa-heartbeat', 'style' => 'text-green-600 bg-green-50 border-green-200'],
        ['slug' => 'bpjs-ketenagakerjaan', 'name' => 'BPJS TK', 'icon' => 'fa-hard-hat', 'style' => 'text-green-700 bg-green-50 border-green-200'],
        ['slug' => 'hp-pascabayar', 'name' => 'HP Pasca', 'icon' => 'fa-mobile-screen', 'style' => 'text-blue-600 bg-blue-50 border-blue-200'],
        ['slug' => 'internet-pascabayar', 'name' => 'Internet', 'icon' => 'fa-network-wired', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-200'],
        ['slug' => 'tv-pascabayar', 'name' => 'TV Kabel', 'icon' => 'fa-satellite-dish', 'style' => 'text-pink-600 bg-pink-50 border-pink-200'],
        ['slug' => 'multifinance', 'name' => 'Cicilan', 'icon' => 'fa-money-bill-wave', 'style' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
        ['slug' => 'pbb', 'name' => 'Pajak PBB', 'icon' => 'fa-building', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'samsat', 'name' => 'SAMSAT', 'icon' => 'fa-car', 'style' => 'text-blue-700 bg-blue-50 border-blue-200'],
        ['slug' => 'gas-negara', 'name' => 'Gas Negara', 'icon' => 'fa-fire', 'style' => 'text-orange-600 bg-orange-50 border-orange-200'],
        ['slug' => 'pln-nontaglis', 'name' => 'PLN NonTag', 'icon' => 'fa-plug', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-200'],
    ];

    $menus = array_merge($prepaidMenus, $postpaidMenus);
@endphp

@section('title', $pageTitle)

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        .brand-radio:checked + div { border-color: #ef4444; background-color: #fef2f2; color: #b91c1c; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); }
        .product-item:hover { transform: translateY(-4px); border-color: #ef4444; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid #ef4444; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
        .swiper-button-next::after, .swiper-button-prev::after { font-size: 18px; font-weight: bold; }
        .flash-sale-next, .flash-sale-prev, .category-next, .category-prev { width: 35px; height: 35px; background: white; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); color: #333; }
        .flash-sale-next:after, .flash-sale-prev:after { font-size: 14px; }
    </style>
@endpush

@section('content')

@include('layouts.partials.notifications')

{{-- SECTION BANNER --}}
<section class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-4">
    <div class="lg:col-span-2 rounded shadow-sm overflow-hidden h-full sm:h-full md:h-full w-full">
        <div class="swiper heroSwiper w-full h-full">
            <div class="swiper-wrapper">
                @forelse($banners ?? [] as $banner)
                    <div class="swiper-slide">
                        <img src="{{ asset('public/storage/' . $banner->image) }}" class="w-full h-full object-cover" alt="Banner">
                    </div>
                @empty
                    <div class="swiper-slide">
                        <img src="https://placehold.co/800x400/ee4d2d/ffffff?text=Sancaka+Promo" class="w-full h-full object-cover">
                    </div>
                @endforelse
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 gap-2 h-auto lg:h-full">
        <div class="rounded shadow-sm overflow-hidden h-full sm:h-full lg:h-full w-full">
            <img src="{{ isset($settings['banner_2']) ? asset('public/storage/' . $settings['banner_2']) : 'https://placehold.co/400x200/ee4d2d/ffffff?text=Promo+1' }}" class="w-full h-full object-cover">
        </div>
        <div class="rounded shadow-sm overflow-hidden h-full sm:h-full lg:h-full w-full">
            <img src="{{ isset($settings['banner_3']) ? asset('public/storage/' . $settings['banner_3']) : 'https://placehold.co/400x200/d0011b/ffffff?text=Promo+2' }}" class="w-full h-full object-cover">
        </div>
    </div>
</section>

{{-- SECTION MENU KATEGORI --}}
<section class="mb-10 space-y-8" data-aos="fade-up">

    {{-- SEARCH BAR SECTION --}}
    <div class="mb-6 relative" data-aos="fade-up">
        <div class="relative group">
            <input type="text" id="menuSearch" 
                   class="w-full border border-blue-400 rounded-2xl px-5 py-4 pl-12 font-medium text-gray-700 focus:ring-4 focus:ring-red-100 focus:border-red-500 outline-none shadow-sm transition placeholder-gray-400"
                   placeholder="Cari layanan (Contoh: Pulsa, PLN, DANA, BPJS, SAMSAT, DANA, OVO)...">
            
            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition">
                <i class="fas fa-search text-xl"></i>
            </div>

            {{-- Tombol Reset (X) - Muncul saat ada ketikan --}}
            <button id="clearSearch" onclick="document.getElementById('menuSearch').value=''; filterMenu();" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-300 hover:text-red-500 transition hidden">
                <i class="fas fa-times-circle text-xl"></i>
            </button>
        </div>
        
        {{-- Pesan jika tidak ditemukan --}}
        <div id="noResult" class="hidden text-center py-8 text-gray-500 bg-white rounded-xl mt-2 border border-dashed border-gray-300">
            <i class="fas fa-search-minus text-3xl mb-2 text-gray-300"></i>
            <p>Layanan tidak ditemukan.</p>
        </div>
    </div>
    
    {{-- PRABAYAR --}}
    <div id="prabayar-section" class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-red-500">
        <h2 class="text-xl font-bold mb-6 text-gray-800 flex items-center border-b pb-2">
            <i class="fas fa-wallet text-red-500 mr-2"></i> Layanan Top Up & Prabayar
        </h2>
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3 sm:gap-4">
            @foreach($prepaidMenus as $menu)
                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" 
                   class="ppob-icon flex flex-col items-center p-3 sm:p-4 border rounded-xl hover:shadow-lg transition group bg-white hover:border-red-400 {{ $menu['style'] }} border-opacity-50">
                    <i class="fas {{ $menu['icon'] }} text-2xl sm:text-3xl mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="text-[10px] sm:text-xs font-bold text-gray-700 text-center leading-tight">{{ $menu['name'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- PASCABAYAR --}}
    <div id="pascabayar-section" class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-blue-500">
        <h2 class="text-xl font-bold mb-6 text-gray-800 flex items-center border-b pb-2">
            <i class="fas fa-file-invoice text-blue-500 mr-2"></i> Layanan Tagihan & Pascabayar
        </h2>
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3 sm:gap-4">
            @foreach($postpaidMenus as $menu)
                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" 
                   class="ppob-icon flex flex-col items-center p-3 sm:p-4 border rounded-xl hover:shadow-lg transition group bg-white hover:border-blue-400 {{ $menu['style'] }} border-opacity-50">
                    <i class="fas {{ $menu['icon'] }} text-2xl sm:text-3xl mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="text-[10px] sm:text-xs font-bold text-gray-700 text-center leading-tight">{{ $menu['name'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- MAIN CONTENT --}}
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        
        {{-- Breadcrumb --}}
        <div class="text-sm text-gray-500 mb-6 flex items-center gap-2">
            <a href="https://tokosancaka.com/etalase" class="hover:text-red-500 transition">Beranda</a> 
            <i class="fas fa-chevron-right text-xs text-gray-300"></i>
            <a href="{{ url('/etalase/category/e-wallet-pulsa') }}" class="hover:text-red-500 transition">Digital</a>
            <i class="fas fa-chevron-right text-xs text-gray-300"></i>
            <span class="font-bold text-gray-700">{{ $pageTitle }}</span>
        </div>

        {{-- Notifications --}}
        @if(session('success')) 
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div> 
        @endif
        @if(session('error')) 
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            </div> 
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- KOLOM KIRI: INPUT NOMOR & FILTER --}}
            <div class="lg:w-1/3 space-y-6">

                {{-- 💰 WIDGET SALDO USER (ROLE BASED LINK) --}}
                @auth
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between relative overflow-hidden group transition hover:shadow-md">
                    {{-- Hiasan Background --}}
                    <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition duration-500"></div>

                    <div class="flex items-center gap-4 relative z-10">
                        {{-- Icon Dompet --}}
                        <div class="bg-green-100 w-12 h-12 rounded-2xl flex items-center justify-center text-green-600 shadow-sm">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        
                        {{-- Info Saldo --}}
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                {{ Auth::user()->role === 'admin' ? 'Total Saldo Sistem' : 'Sisa Saldo Anda' }}
                            </p>
                            <h3 class="text-xl font-extrabold text-gray-800">
                                Rp {{ number_format(Auth::user()->saldo ?? Auth::user()->balance ?? 0, 0, ',', '.') }}
                            </h3>
                        </div>
                    </div>

                    {{-- LOGIKA LINK TOMBOL --}}
                    @php
                        $userRole = Auth::user()->role ?? 'customer';
                        $topUpLink = 'https://tokosancaka.com/customer/topup/create'; // Default (Customer/Agent/Seller)

                        // Jika Admin, ganti linknya
                        if ($userRole === 'admin') {
                            $topUpLink = 'https://tokosancaka.com/admin/wallet';
                        }
                    @endphp

                    {{-- Tombol Action --}}
                    <a href="{{ $topUpLink }}" class="relative z-10 bg-gray-900 hover:bg-black text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-gray-300 transition transform hover:-translate-y-1 hover:scale-105" title="{{ $userRole === 'admin' ? 'Kelola Wallet' : 'Isi Saldo' }}">
                        <i class="fas {{ $userRole === 'admin' ? 'fa-cog' : 'fa-plus' }}"></i>
                    </a>
                </div>
                @endauth
                
                {{-- Card Input --}}
                <div class="bg-white rounded-2xl shadow-md p-6 border-t-4 border-red-500 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                        <i class="fas {{ $pageInfo['icon'] ?? 'fa-edit' }} text-9xl text-red-500"></i>
                    </div>

                    <h1 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 relative z-10">
                        <span class="bg-red-100 text-red-600 w-10 h-10 flex items-center justify-center rounded-lg shadow-sm">
                            <i class="fas {{ $pageInfo['icon'] ?? 'fa-mobile-alt' }}"></i>
                        </span>
                        {{ $pageTitle }}
                    </h1>

                    <div class="mb-6 relative z-10">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">{{ $inputLabel }}</label>
                        <div class="relative group">
                            <input type="text" id="customer_no" 
                                   class="w-full border border-gray-300 rounded-xl px-4 py-4 pl-12 font-bold text-lg text-gray-700 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none shadow-sm transition"
                                   placeholder="{{ $inputPlace }}">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition">
                                <i class="fas fa-id-card"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 ml-1 flex items-center gap-1">
                            <i class="fas fa-info-circle"></i> Pastikan nomor tujuan benar.
                        </p>
                    </div>

                    {{-- TOMBOL CEK PLN TOKEN (PRABAYAR) --}}
                    @if($currentSlug == 'pln-token')
                        <button onclick="cekPlnPrabayar()" id="btn-cek-pln" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded-xl transition mb-4 shadow-lg shadow-yellow-200/50 flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i> Cek Nama Pelanggan
                        </button>
                        <div id="pln_info" class="hidden bg-yellow-50 p-4 rounded-xl border border-yellow-200 text-sm mb-4 animate-fade-in">
                            <div class="flex justify-between mb-2 border-b border-yellow-200 pb-2">
                                <span class="text-gray-500">Nama:</span>
                                <span class="font-bold text-gray-800" id="pln_name">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tarif/Daya:</span>
                                <span class="font-bold text-gray-800" id="pln_power">-</span>
                            </div>
                        </div>
                    @endif

                    {{-- TOMBOL CEK TAGIHAN (PASCABAYAR) --}}
                    @if($isPostpaid)
                        <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center gap-2 shadow-lg shadow-red-200/50">
                            <span id="btn-text">Cek Tagihan</span>
                            <div id="loading-spinner" class="loader hidden !border-t-white"></div>
                        </button>
                    @endif
                </div>

                {{-- Card Filter Operator (Hanya Prabayar) --}}
                @if(!$isPostpaid)
                <div class="bg-white rounded-2xl shadow-md p-6">
                    <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center justify-between">
                        <span>Pilih Provider / Kategori</span>
                        <span class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Scroll &darr;</span>
                    </h2>
                    <div class="grid grid-cols-3 sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
                        <label class="cursor-pointer">
                            <input type="radio" name="brand_filter" value="all" class="brand-radio hidden" checked onchange="filterProducts('all')">
                            <div class="border border-gray-200 rounded-lg p-3 text-center transition h-full flex items-center justify-center hover:bg-gray-50">
                                <span class="text-xs font-bold text-gray-600">SEMUA</span>
                            </div>
                        </label>
                        @foreach($brands ?? [] as $brand)

                        {{-- TAMBAHKAN LOGIKA FILTER INI --}}
    @php
        // Jika kita sedang di halaman 'pln-token', 
        // tapi nama Brand mengandung kata 'Pascabayar' -> SKIP / Sembunyikan
        if ($currentSlug == 'pln-token' && stripos($brand, 'Pascabayar') !== false) {
            continue;
        }
    @endphp
    {{-- END LOGIKA --}}

                        <label class="cursor-pointer">
                            <input type="radio" name="brand_filter" value="{{ $brand }}" class="brand-radio hidden" onchange="filterProducts('{{ $brand }}')">
                            <div class="border border-gray-200 rounded-lg p-2 text-center transition h-full flex flex-col items-center justify-center hover:bg-gray-50 gap-1">
                                <img src="{{ get_operator_logo($brand) }}" class="h-6 w-auto object-contain mb-1" alt="{{ $brand }}" onerror="this.style.display='none'">
                                <span class="text-[10px] font-bold text-gray-600 uppercase">{{ $brand }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- KOLOM KANAN: DAFTAR PRODUK / HASIL --}}
            <div class="lg:w-2/3">
                <div class="bg-white rounded-2xl shadow-md p-6 min-h-[600px]">
                    
                    {{-- 1. MODE PRABAYAR --}}
                    @if(!$isPostpaid)
                        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-gray-100 gap-4">
                            
                            {{-- Judul Kiri --}}
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 w-full sm:w-auto">
                                <i class="fas fa-list text-gray-400"></i> Daftar Produk
                            </h2>

                            {{-- Form Pencarian Kanan (BARU) --}}
                            <div class="relative w-full sm:w-1/2 md:w-1/3 group">
                                <input type="text" id="productSearch" 
                                       class="w-full border border-gray-200 bg-gray-50 rounded-xl px-4 py-2 pl-10 text-sm font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-red-100 focus:border-red-500 outline-none transition"
                                       placeholder="Cari produk...">
                                <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition">
                                    <i class="fas fa-search"></i>
                                </div>
                                {{-- Tombol X (Clear) --}}
                                <button id="clearProductSearch" onclick="document.getElementById('productSearch').value=''; filterProductsList();" 
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-300 hover:text-red-500 transition hidden">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Pesan "Tidak Ditemukan" (Hidden by default) --}}
                        <div id="productNoResult" class="hidden text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200 mb-4">
                             <i class="fas fa-search-minus text-4xl text-gray-300 mb-2"></i>
                             <p class="text-gray-500 font-medium">Produk tidak ditemukan.</p>
                        </div>

                        {{-- Container Grid Produk (Pastikan ID-nya sama dengan script di bawah) --}}
                        <div id="product_list" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @forelse($products ?? [] as $product)

                            {{-- LOGIKA FILTER TAMBAHAN --}}
    @php
        // 1. Jika halaman ini 'pln-token', tapi nama produk mengandung kata 'Pascabayar' -> SKIP/SEMBUNYIKAN
        if ($currentSlug == 'pln-token' && stripos($product->product_name, 'Pascabayar') !== false) {
            continue;
        }

        // 2. (Opsional) Jika halaman ini 'pln-token', tapi nama produknya 'Cek Tagihan' -> SKIP
        if ($currentSlug == 'pln-token' && stripos($product->product_name, 'Tagihan') !== false) {
            continue;
        }
    @endphp
                            <div class="product-item border border-gray-200 rounded-xl p-4 cursor-pointer relative bg-white transition-all duration-300 group"
                                 data-brand="{{ $product->brand }}"
                                 data-sku="{{ $product->buyer_sku_code }}"
                                 data-name="{{ $product->product_name }}"
                                 data-price="{{ $product->sell_price }}"
                                 onclick="selectProduct(this)">
                                
                                <div class="flex justify-between items-start mb-3">
                                    <div class="h-8 w-auto">
                                        <img src="{{ get_operator_logo($product->brand) }}" 
                                             alt="{{ $product->brand }}" 
                                             class="h-full object-contain"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase hidden">
                                            {{ $product->brand }}
                                        </span>
                                    </div>
                                    @if(($product->stock ?? 0) < 5 && !($product->unlimited_stock ?? false))
                                        <span class="bg-red-50 text-red-600 text-[10px] font-bold px-2 py-1 rounded border border-red-100 animate-pulse">Sisa {{ $product->stock }}</span>
                                    @elseif($product->multi ?? false)
                                        <span class="bg-red-50 text-red-600 text-[10px] font-bold px-2 py-1 rounded border border-red-100">Promo</span>
                                    @endif
                                </div>

                                <h3 class="text-sm font-bold text-gray-800 mb-2 leading-snug group-hover:text-red-600 transition">{{ $product->product_name }}</h3>
                                <p class="text-xs text-gray-400 mb-4 line-clamp-2 h-8">{{ $product->desc }}</p>
                                
                                <div class="flex justify-between items-end border-t border-dashed border-gray-200 pt-3">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Harga</p>
                                        <p class="text-lg font-extrabold text-red-500">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-red-50 text-red-500 w-10 h-10 rounded-full flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition shadow-sm transform group-hover:rotate-12">
                                        <i class="fas fa-shopping-cart text-sm"></i>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-span-full text-center py-20">
                                <div class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-box-open text-gray-300 text-4xl"></i>
                                </div>
                                <h3 class="text-gray-800 font-bold text-lg">Produk Kosong</h3>
                                <p class="text-gray-500 text-sm">Belum ada produk untuk kategori ini.</p>
                            </div>
                            @endforelse
                        </div>
                    
                    {{-- 2. MODE PASCABAYAR --}}
                    @else
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Rincian Tagihan</h2>
                        
                        {{-- STATE: BELUM CEK / KOSONG --}}
                        <div id="bill_empty" class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <div class="bg-gray-100 p-6 rounded-full mb-4"><i class="fas fa-file-invoice-dollar text-4xl text-gray-300"></i></div>
                            <p class="font-medium">Masukkan nomor pelanggan di kolom kiri lalu klik "Cek Tagihan"</p>
                        </div>

                        {{-- STATE: HASIL PENCARIAN (HIDDEN BY DEFAULT) --}}
                        <div id="bill_result" class="hidden">
                            <div class="bg-blue-50 p-6 rounded-2xl mb-6 border border-blue-100 relative overflow-hidden">
                                <div class="absolute -right-6 -top-6 bg-blue-100 w-24 h-24 rounded-full opacity-50"></div>
                                
                                <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    {{-- Data Utama --}}
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Nama Pelanggan</p>
                                        <p class="font-bold text-gray-800 text-lg" id="bill_name">-</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">ID Pelanggan</p>
                                        <p class="font-bold text-gray-800 text-lg" id="bill_id">-</p>
                                    </div>

                                    {{-- Data Rinci Tagihan --}}
                                    <div class="md:col-span-2 grid grid-cols-2 gap-y-4 border-t border-blue-200 mt-4 pt-4">
                                        {{-- Ref ID & Periode --}}
                                        <div>
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider">No. Referensi</p>
                                            <p class="font-bold text-gray-700 text-sm" id="bill_ref">-</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider">Periode</p>
                                            <p class="font-bold text-gray-700 text-sm" id="bill_period">-</p>
                                        </div>

                                        {{-- Data Dinamis 1 --}}
                                        <div>
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider" id="label_info_1">Keterangan</p>
                                            <p class="font-bold text-gray-700 text-sm" id="val_info_1">-</p>
                                        </div>
                                        {{-- Data Dinamis 2 --}}
                                        <div>
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider" id="label_info_2">Lembar</p>
                                            <p class="font-bold text-gray-700 text-sm" id="val_info_2">-</p>
                                        </div>

                                        {{-- Alamat --}}
                                        <div class="col-span-2">
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider" id="label_address">Alamat</p>
                                            <p class="font-bold text-gray-700 text-sm break-words" id="val_address">-</p>
                                        </div>

                                        {{-- Admin --}}
                                        <div class="col-span-2 border-t border-dashed border-gray-200 pt-2">
                                            <div class="flex justify-between items-center">
                                                <p class="text-gray-500 text-xs">Biaya Admin</p>
                                                <p class="font-bold text-gray-700" id="bill_admin">-</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Total Tagihan --}}
                                    <div class="md:col-span-2 border-t border-blue-200 mt-2 pt-2 bg-blue-100/50 p-2 rounded -mx-2">
                                        <p class="text-gray-500 text-xs uppercase text-right">Total Bayar</p>
                                        <p class="font-extrabold text-right text-3xl text-blue-600" id="bill_amount">-</p>
                                    </div>
                                </div>
                            </div>
                            
                            <button onclick="bayarTagihan()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-green-200 transition transform hover:-translate-y-1">
                                <i class="fas fa-check-circle mr-2"></i> Bayar Sekarang
                            </button>

                            {{-- ⚡ FITUR BARU: CONTAINER ARRAY DETAIL (HIDDEN BY DEFAULT) ⚡ --}}
                            <div id="detail_container" class="mt-6 border-t border-dashed border-gray-200 pt-4 hidden">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-list-ul"></i> Rincian Item Tagihan
                                </h4>
                                <div id="detail_list" class="space-y-3">
                                    {{-- ITEM AKAN DIMASUKKAN VIA JS DISINI --}}
                                </div>
                            </div>

                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI (PRABAYAR) - SUDAH DIPERBAIKI --}}
<div id="confirmModal" class="fixed inset-0 z-[999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full animate-bounce-in">
            <div class="bg-white p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Konfirmasi Pembayaran</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition"><i class="fas fa-times"></i></button>
                </div>
                <div class="mt-2">
                    <div class="bg-red-50 p-4 rounded-xl border border-red-100 mb-4">
                        <p class="text-xs text-red-500 uppercase font-bold mb-1">Produk</p>
                        <p class="text-gray-800 font-bold text-lg leading-tight" id="modal_product">-</p>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Nomor Tujuan</span><span class="font-bold text-gray-800 font-mono text-base" id="modal_no">-</span></div>
                        <div class="flex justify-between items-center pt-2"><span class="text-gray-500">Total Harga</span><span class="font-extrabold text-red-600 text-2xl" id="modal_price">-</span></div>
                    </div>
                </div>
            </div>
            {{-- DIV BUTTONS (Form dihapus, diganti Logic Checkout via JS) --}}
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm transition">Batal</button>
                
                {{-- Button Baru yang memanggil processPrepaidCheckout() --}}
                <button type="button" id="btn-confirm-pay" onclick="processPrepaidCheckout()" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-lg shadow-red-200 px-6 py-3 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none sm:text-sm transition transform hover:scale-[1.02]">Bayar Sekarang</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // =================================================================
    // ⚙️ KONFIGURASI GLOBAL
    // =================================================================
    const IS_TESTING = false;
    const ACTIVE_SKU = "{{ $activeSku }}"; 
    
    // Variabel Global
    const inputNo = document.getElementById('customer_no');
    let currentBillData = null; 
    let currentPrepaidData = null;
    let isProcessing = false; // [FIX] Flag untuk mencegah double submit

    // Setup Swiper
    var swiper = new Swiper(".heroSwiper", { 
        loop: true, autoplay: { delay: 4000 }, 
        pagination: { el: ".swiper-pagination", clickable: true }, 
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } 
    });

    // --- HELPER: Generator Ref ID ---
    function generateRefID() {
        let prefix = IS_TESTING ? 'TEST-' : 'INQ-';
        return prefix + Date.now() + '-' + Math.floor(Math.random() * 10000);
    }

    // --- HELPER: Formatter Periode ---
    function formatPeriodeID(periodeStr) {
        if (!periodeStr) return '-';
        let str = periodeStr.toString().trim().toUpperCase();

        // Format Angka (202512)
        if (/^\d{5,6}$/.test(str)) {
            let len = str.length;
            let year = str.substring(len - 4, len);
            let month = parseInt(str.substring(0, len - 4));
            const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if (months[month]) return `${months[month]} ${year}`;
        }
        // Format Teks (DES25)
        if (/^[A-Z]{3}\d{2}$/.test(str)) {
            let code = str.substring(0, 3);
            let year = "20" + str.substring(3, 5);
            const monthMap = { 'JAN': 'Januari', 'FEB': 'Februari', 'MAR': 'Maret', 'APR': 'April', 'MEI': 'Mei', 'JUN': 'Juni', 'JUL': 'Juli', 'AGU': 'Agustus', 'SEP': 'September', 'OKT': 'Oktober', 'NOV': 'November', 'DES': 'Desember' };
            if (monthMap[code]) return `${monthMap[code]} ${year}`;
        }
        return str;
    }

    // --- HELPER: RC Message (LENGKAP) ---
    function getRcMessage(rcCode) {
        const rc = String(rcCode);
        const rcMap = {
            // --- SUKSES & PENDING ---
            '00': { status: 'Sukses', message: 'Transaksi Berhasil.', alertType: 'success' },
            '03': { status: 'Pending', message: 'Transaksi sedang diproses sistem. Mohon tunggu.', alertType: 'warning' },
            '99': { status: 'Pending', message: 'Menunggu respon provider (Router Issue).', alertType: 'warning' },

            // --- GAGAL (USER/INPUT) ---
            '40': { status: 'Gagal', message: 'Format data salah (Payload Error).', alertType: 'error' },
            '41': { status: 'Gagal', message: 'Validasi Signature gagal.', alertType: 'error' },
            '42': { status: 'Gagal', message: 'Konfigurasi akun belum sesuai.', alertType: 'error' },
            '43': { status: 'Gagal', message: 'Produk/SKU sedang Gangguan atau Tidak Aktif.', alertType: 'error' },
            '44': { status: 'Gagal', message: 'Saldo Server Agen tidak mencukupi.', alertType: 'error' },
            '45': { status: 'Gagal', message: 'Akses ditolak (IP Not Allowed).', alertType: 'error' },
            '47': { status: 'Gagal', message: 'Transaksi ganda (Double Request).', alertType: 'error' },
            '49': { status: 'Gagal', message: 'Ref ID sudah pernah digunakan.', alertType: 'error' },

            // --- GAGAL (TUJUAN/NOMOR) ---
            '50': { status: 'Gagal', message: 'Nomor/ID Pelanggan tidak ditemukan atau Jaringan Sibuk.', alertType: 'error' },
            '51': { status: 'Gagal', message: 'Nomor Tujuan Diblokir/Hangus.', alertType: 'error' },
            '52': { status: 'Gagal', message: 'Prefix/Kode Operator tidak sesuai.', alertType: 'error' },
            '53': { status: 'Gagal', message: 'Produk sedang ditutup sementara.', alertType: 'error' },
            '54': { status: 'Gagal', message: 'Nomor Tujuan Salah / Tidak Dikenali.', alertType: 'error' },
            '55': { status: 'Gagal', message: 'Gangguan Provider Pusat.', alertType: 'error' },
            '57': { status: 'Gagal', message: 'Jumlah digit nomor tidak sesuai.', alertType: 'error' },
            '58': { status: 'Gagal', message: 'Sistem sedang Cut Off (Maintenance).', alertType: 'error' },
            '59': { status: 'Gagal', message: 'ID diluar wilayah layanan (Cluster).', alertType: 'error' },

            // --- GAGAL (TAGIHAN) ---
            '60': { status: 'Sukses', message: 'Tagihan Belum Tersedia atau Sudah Lunas.', alertType: 'warning' },
            '63': { status: 'Gagal', message: 'Nominal tidak sesuai / Paket tidak tersedia.', alertType: 'error' },
            '68': { status: 'Gagal', message: 'Stok Voucher Kosong.', alertType: 'error' },
            '70': { status: 'Gagal', message: 'Timeout koneksi ke Biller. Silakan coba lagi.', alertType: 'error' },
            '73': { status: 'Gagal', message: 'Limit KWH terlampaui (Maksimum).', alertType: 'error' },
        };

        return rcMap[rc] || { 
            status: 'Gagal', 
            message: `Gagal memproses transaksi (Kode RC: ${rc}).`, 
            alertType: 'error' 
        };
    }

    // --- HELPER: Notification ---
    function triggerCustomNotification(msg, type) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
             const icon = type === 'success' ? 'success' : type === 'error' ? 'error' : 'warning';
             Swal.fire({ title: type.toUpperCase(), text: msg, icon: icon, confirmButtonText: 'Oke' });
        } else {
            alert(msg);
        }
    }

    // =================================================================
    // 🟢 LOGIKA PRABAYAR (PULSA, TOKEN, DATA)
    // =================================================================
    @if(!$isPostpaid)
        const items = document.querySelectorAll('.product-item');
        
        function filterProducts(brand) {
            items.forEach(item => {
                if (brand === 'all' || item.dataset.brand === brand) item.classList.remove('hidden');
                else item.classList.add('hidden');
            });
        }

        // Auto Detect Operator (Khusus Pulsa & Data)
        @if(in_array($currentSlug, ['pulsa', 'data']))
        inputNo.addEventListener('input', function(e) {
            const val = e.target.value;
            if(val.length >= 4) {
                let operator = null;
                if(/^08(11|12|13|21|22|52|53|51)/.test(val)) operator = 'TELKOMSEL';
                else if(/^08(14|15|16|55|56|57|58)/.test(val)) operator = 'INDOSAT';
                else if(/^08(17|18|19|59|77|78)/.test(val)) operator = 'XL';
                else if(/^08(95|96|97|98|99)/.test(val)) operator = 'TRI';
                else if(/^08(81|82|83|84|85|86|87|88|89)/.test(val)) operator = 'SMARTFREN';
                else if(/^08(31|32|33|38)/.test(val)) operator = 'AXIS';

                if(operator) {
                    const radio = document.querySelector(`input[name="brand_filter"][value="${operator}"]`);
                    if(radio) { radio.checked = true; filterProducts(operator); }
                }
            }
        });
        @endif

        // Modal Pilih Produk
        function selectProduct(el) {
            const no = inputNo.value.replace(/[^0-9]/g, '');
            let minLen = IS_TESTING ? 3 : 4; 
            if(no.length < minLen) { 
                triggerCustomNotification("Mohon isi nomor tujuan yang valid terlebih dahulu.", 'error'); 
                inputNo.focus(); return; 
            }
            currentPrepaidData = {
                sku: el.dataset.sku,
                name: el.dataset.name,
                price: el.dataset.price,
                customer_no: no,
                desc: "Pembelian " + el.dataset.name
            };
            document.getElementById('modal_no').innerText = no;
            document.getElementById('modal_product').innerText = el.dataset.name;
            document.getElementById('modal_price').innerText = 'Rp ' + parseInt(el.dataset.price).toLocaleString('id-ID');
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeModal() { 
            document.getElementById('confirmModal').classList.add('hidden'); 
        }

        // [FIX] Checkout Prabayar (Mencegah Klik Ganda & Syntax Error)
        function processPrepaidCheckout() {
            if(isProcessing) return; // Prevent Double Click
            isProcessing = true; // Set Lock

            if(!currentPrepaidData) { isProcessing = false; return; }

            const btn = document.getElementById('btn-confirm-pay');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            fetch('{{ route("ppob.prepare") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(currentPrepaidData)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    window.location.href = "{{ route('ppob.checkout.index') }}";
                } else {
                    triggerCustomNotification("Gagal: " + data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    isProcessing = false; // Reset Lock
                }
            })
            .catch(err => {
                console.error(err);
                triggerCustomNotification("Terjadi kesalahan sistem.", 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
                isProcessing = false; // Reset Lock
            });
        }
        
        // Cek Nama PLN Prabayar
        @if($currentSlug == 'pln-token')
        function cekPlnPrabayar() {
            const no = inputNo.value.replace(/[^0-9]/g, '');
            if(no.length < 5) { triggerCustomNotification("Nomor Meter tidak valid!", 'error'); return; }
            const btn = document.getElementById('btn-cek-pln');
            const infoBox = document.getElementById('pln_info');
            const oriText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...'; btn.disabled = true; infoBox.classList.add('hidden');
            
            fetch('{{ route("ppob.check.pln.prabayar") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ customer_no: no, testing: IS_TESTING })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = oriText; btn.disabled = false;
                const d = data.data || data;
                if(d.status === 'success' || d.rc === '00' || d.name) {
                    document.getElementById('pln_name').innerText = d.name || d.customer_name || 'Pelanggan';
                    document.getElementById('pln_power').innerText = d.segment_power || '-';
                    infoBox.classList.remove('hidden');
                    triggerCustomNotification("Data pelanggan ditemukan!", 'success');
                } else { triggerCustomNotification(d.message || "Nomor tidak ditemukan.", 'error'); }
            })
            .catch(err => {
                btn.innerHTML = oriText; btn.disabled = false; triggerCustomNotification("Gagal koneksi server.", 'error');
            });
        }
        @endif

    // =================================================================
    // 🔵 LOGIKA PASCABAYAR (PERBAIKAN TOTAL)
    // =================================================================
    @else
    function cekTagihan() {
        const no = inputNo.value.trim();
        let cleanNo = ACTIVE_SKU === 'samsat' ? no : no.replace(/[^0-9]/g, '');

        if (cleanNo.length < 5) {
            triggerCustomNotification("Masukkan Nomor Pelanggan yang valid!", 'error');
            inputNo.focus();
            return;
        }

        const btn = document.getElementById('btn-cek-tagihan');
        const spinner = document.getElementById('loading-spinner');
        const text = document.getElementById('btn-text');
        
        // UI Elements
        const resultDiv = document.getElementById('bill_result');
        const emptyDiv = document.getElementById('bill_empty');
        const detailContainer = document.getElementById('detail_container');
        const detailList = document.getElementById('detail_list');

        // Reset UI
        btn.disabled = true;
        spinner.classList.remove('hidden');
        text.innerText = "Mengecek...";
        resultDiv.classList.add('hidden');
        emptyDiv.classList.add('hidden');
        if (detailContainer) detailContainer.classList.add('hidden');
        currentBillData = null;

        // --- REQUEST API ---
        fetch('{{ route("ppob.check.bill") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ 
                    customer_no: cleanNo, 
                    sku: ACTIVE_SKU,
                    // Pastikan controller juga mengembalikan parameter ini
                    buyer_sku_code: ACTIVE_SKU, 
                    ref_id: generateRefID(),    
                    testing: IS_TESTING 
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                text.innerText = "Cek Tagihan";

                const d = data.data || data;
                const rc = d.rc;
                const rcInfo = getRcMessage(rc);
                let messageToUser = d.message || rcInfo.message;

                // --- JIKA SUKSES ---
                if (d && (d.status === 'success' || d.status === 'Sukses' || rc === '00')) {
                    
                    // 1. Ambil Data Detail
                    const desc = d.desc || {};
                    const detailArray = (desc.detail && Array.isArray(desc.detail)) ? desc.detail : [];
                    const firstDetail = detailArray.length > 0 ? detailArray[0] : {};

                    // 2. Hitung Harga Total
                    let apiSellingPrice = parseInt(d.selling_price || d.total_tagihan || 0);
                    let apiAdmin = parseInt(d.admin || d.admin_fee || d.admin_fee_modal || 0);
                    
                    // Fallback hitung manual jika API return 0 (kasus langka)
                    if (apiSellingPrice === 0 && detailArray.length > 0) {
                         let totalTagihanDetail = 0;
                         let totalAdminDetail = 0;
                         detailArray.forEach(item => {
                             totalTagihanDetail += parseInt(item.nilai_tagihan || 0) + parseInt(item.denda || 0);
                             totalAdminDetail += parseInt(item.admin || 0);
                         });
                         apiSellingPrice = totalTagihanDetail + totalAdminDetail;
                         if (apiAdmin === 0) apiAdmin = totalAdminDetail;
                    }

                    let finalPrice = apiSellingPrice;
                    let finalAdmin = apiAdmin;

                    // 3. Mapping Tampilan
                    let valAddr = desc.alamat || desc.address || d.address || firstDetail.alamat || '-';
                    document.getElementById('bill_ref').innerText = d.ref_id || '-';
                    document.getElementById('bill_name').innerText = d.customer_name || d.name || 'Pelanggan';
                    document.getElementById('bill_id').innerText = d.customer_no;
                    document.getElementById('bill_amount').innerText = 'Rp ' + finalPrice.toLocaleString('id-ID');
                    document.getElementById('bill_admin').innerText = 'Rp ' + finalAdmin.toLocaleString('id-ID');
                    document.getElementById('val_address').innerText = valAddr;

                    // 4. [FIX KRUSIAL] Update Data Checkout dengan SKU Asli API
                    currentBillData = {
                        // Priority: SKU dari API > SKU Request > SKU Default
                        sku: d.buyer_sku_code || ACTIVE_SKU, 
                        // Wajib simpan SKU Asli ini (misal post641597) ke dalam 'desc' agar controller checkout bisa baca
                        buyer_sku_code: d.buyer_sku_code || ACTIVE_SKU,
                        name: "Tagihan " + ACTIVE_SKU.toUpperCase() + " - " + (d.customer_name || d.name),
                        price: finalPrice,
                        ref_id: d.ref_id, // Wajib untuk Payment Pascabayar
                        customer_no: d.customer_no
                    };

                    // 5. Mapping Periode & Detail Dinamis
                    let mainPeriode = firstDetail.periode || desc.periode || d.periode || '-';
                    document.getElementById('bill_period').innerText = formatPeriodeID(mainPeriode);

                    let lbl1 = "Info", val1 = "-", lbl2 = "Lembar", val2 = (desc.lembar_tagihan || '1') + ' Lembar';

                    if (ACTIVE_SKU.includes('pln')) {
                        lbl1 = "Tarif / Daya";
                        let tarifData = desc.tarif || d.segment_power || '-';
                        let dayaData = desc.daya;
                        if (!dayaData && d.segment_power && d.segment_power.includes('/')) {
                             let parts = d.segment_power.split('/');
                             if(parts[1]) dayaData = parts[1].replace(/[^0-9]/g, '');
                        }
                        let displayDaya = (dayaData && dayaData != '-') ? `${dayaData} VA` : '-';
                        val1 = `${tarifData} / ${displayDaya}`;
                    } else if (ACTIVE_SKU.includes('bpjs')) {
                        lbl1 = "Jml. Peserta";
                        val1 = (desc.jumlah_peserta || desc.peserta || '1') + " Orang";
                        lbl2 = "Cabang";
                        val2 = desc.kantor_cabang || '-';
                    } else if (ACTIVE_SKU.includes('pdam')) {
                        lbl1 = "Meteran";
                        val1 = (firstDetail.meter_awal || '-') + ' - ' + (firstDetail.meter_akhir || '-');
                    }

                    if(document.getElementById('label_info_1')) {
                        document.getElementById('label_info_1').innerText = lbl1;
                        document.getElementById('val_info_1').innerText = val1;
                        document.getElementById('label_info_2').innerText = lbl2;
                        document.getElementById('val_info_2').innerText = val2;
                    }

                    // 6. Render List Detail
                    if (detailList && detailArray.length > 0) {
                        detailList.innerHTML = '';
                        detailContainer.classList.remove('hidden');
                        detailArray.forEach((item) => {
                            let itemP = formatPeriodeID(item.periode);
                            let itemN = parseInt(item.nilai_tagihan || 0).toLocaleString('id-ID');
                            let itemAdm = parseInt(item.admin || 0).toLocaleString('id-ID');
                            
                            let htmlItem = `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex justify-between items-start text-sm mb-2">
                                    <div>
                                        <p class="font-bold text-gray-700">Periode: ${itemP}</p>
                                        ${item.meter_awal ? `<div class="text-[10px] text-gray-400 mt-1">Meter: ${item.meter_awal} - ${item.meter_akhir}</div>` : ''}
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-800">Rp ${itemN}</p>
                                        <p class="text-[10px] text-gray-400">Adm: Rp ${itemAdm}</p>
                                    </div>
                                </div>`;
                            detailList.insertAdjacentHTML('beforeend', htmlItem);
                        });
                    } else if(detailContainer) {
                        detailContainer.classList.add('hidden');
                    }

                    triggerCustomNotification("Tagihan ditemukan.", 'success');
                    resultDiv.classList.remove('hidden');

                } else {
                    // Gagal
                    triggerCustomNotification(messageToUser, rcInfo.alertType);
                    resultDiv.classList.add('hidden');
                    emptyDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                spinner.classList.add('hidden');
                text.innerText = "Cek Tagihan";
                triggerCustomNotification("Gagal koneksi server. Periksa jaringan Anda.", 'error');
            });
    }

    // [FIX] Checkout Pascabayar (Mencegah Klik Ganda & Syntax Error)
    function bayarTagihan() {
        if(isProcessing) return; // Lock
        isProcessing = true; // Set Lock

        if(!currentBillData) {
            triggerCustomNotification("Data tagihan tidak valid/kadaluarsa. Silakan cek ulang.", 'error');
            isProcessing = false;
            return;
        }

        const btnBayar = document.querySelector('#bill_result button');
        const oriText = btnBayar.innerHTML;
        btnBayar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        btnBayar.disabled = true;

        fetch('{{ route("ppob.prepare") }}', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            body: JSON.stringify(currentBillData)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.location.href = "{{ route('ppob.checkout.index') }}";
            } else {
                triggerCustomNotification("Gagal memproses pesanan.", 'error');
                btnBayar.innerHTML = oriText;
                btnBayar.disabled = false;
                isProcessing = false; // Release Lock
            }
        })
        .catch(err => {
            console.error(err);
            triggerCustomNotification("Terjadi kesalahan sistem.", 'error');
            btnBayar.innerHTML = oriText;
            btnBayar.disabled = false;
            isProcessing = false; // Release Lock
        });
    }
    @endif

    // ==========================================================
    // 🏃‍♂️ EFEK PLACEHOLDER BERJALAN (MARQUEE)
    // ==========================================================
    document.addEventListener("DOMContentLoaded", function() {
        const inputField = document.getElementById('customer_no');
        
        // Cek apakah input ada
        if (inputField) {
            let originalPlaceholder = inputField.getAttribute('placeholder');
            
            // Tambahkan spasi di depan agar terlihat muncul dari kanan
            // Sesuaikan jumlah spasi untuk mengatur jarak jeda
            let space = "          |          "; 
            let text = space + originalPlaceholder;
            let interval;

            function animatePlaceholder() {
                // Ambil karakter pertama, pindahkan ke paling belakang
                text = text.substring(1) + text.substring(0, 1);
                inputField.setAttribute('placeholder', text);
            }

            // Jalankan animasi setiap 150ms (semakin kecil angka, semakin cepat)
            function startAnimation() {
                if (!interval) {
                    interval = setInterval(animatePlaceholder, 150);
                }
            }

            // Hentikan animasi
            function stopAnimation() {
                clearInterval(interval);
                interval = null;
                // Kembalikan ke teks asli saat user mau ngetik biar enak dilihat
                inputField.setAttribute('placeholder', originalPlaceholder);
            }

            // Mulai animasi pertama kali
            startAnimation();

            // LOGIKA UX:
            // Matikan animasi saat user klik input (Focus)
            inputField.addEventListener('focus', stopAnimation);

            // Nyalakan lagi saat user klik di luar input (Blur) & input kosong
            inputField.addEventListener('blur', function() {
                if (this.value === "") {
                    // Reset teks agar mulus lagi
                    text = space + originalPlaceholder;
                    startAnimation();
                }
            });
        }
    });

</script>

<script>
    // ==========================================================
    // 🔍 FITUR PENCARIAN MENU (LIVE SEARCH)
    // ==========================================================
    const searchInput = document.getElementById('menuSearch');
    const clearBtn = document.getElementById('clearSearch');
    const noResultMsg = document.getElementById('noResult');
    
    // Ambil semua item menu (Prabayar & Pascabayar)
    // Kita target class 'ppob-icon' yang ada di looping menu Anda
    const menuItems = document.querySelectorAll('.ppob-icon');

    // Event Listener saat mengetik
    searchInput.addEventListener('input', function() {
        filterMenu();
    });

    function filterMenu() {
        const query = searchInput.value.toLowerCase().trim();
        let foundCount = 0;

        // Toggle tombol silang (clear)
        if (query.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        menuItems.forEach(item => {
            // Ambil teks nama menu (span di dalam a)
            const menuName = item.querySelector('span').innerText.toLowerCase();
            // Ambil link slug (opsional, buat pencarian lebih luas)
            const menuSlug = item.getAttribute('href').toLowerCase();
            
            // Logika Pencarian: Cek Nama ATAU Link/Slug
            if (menuName.includes(query) || menuSlug.includes(query)) {
                item.classList.remove('hidden'); // Tampilkan
                item.parentElement.classList.remove('hidden'); // Pastikan container grid tidak hidden (jika ada wrapper)
                foundCount++;
            } else {
                item.classList.add('hidden'); // Sembunyikan
            }
        });

        // Cek Logika Section (Sembunyikan Judul Kategori jika semua isinya hidden)
        toggleSectionVisibility('prabayar-section', query); // *Pastikan ID section ditambahkan (lihat langkah 3)
        toggleSectionVisibility('pascabayar-section', query); // *Pastikan ID section ditambahkan (lihat langkah 3)

        // Tampilkan pesan "Tidak Ditemukan" jika semua item hidden
        if (foundCount === 0) {
            noResultMsg.classList.remove('hidden');
        } else {
            noResultMsg.classList.add('hidden');
        }
    }

    // Helper: Sembunyikan satu kotak kategori besar jika tidak ada item yang cocok di dalamnya
    function toggleSectionVisibility(sectionId, query) {
        const section = document.getElementById(sectionId);
        if(!section) return;

        const visibleItems = section.querySelectorAll('.ppob-icon:not(.hidden)');
        if (visibleItems.length === 0 && query !== '') {
            section.classList.add('hidden');
        } else {
            section.classList.remove('hidden');
        }
    }
</script>

<script>
    // ==========================================================
    // 🔍 FITUR PENCARIAN PRODUK (LIVE FILTER)
    // ==========================================================
    document.addEventListener("DOMContentLoaded", function() {
        const pSearchInput = document.getElementById('productSearch');
        const pClearBtn = document.getElementById('clearProductSearch');
        const pNoResult = document.getElementById('productNoResult');
        const pListContainer = document.getElementById('product_list');
        
        // Ambil semua item produk
        const productItems = document.querySelectorAll('.product-item');

        if (pSearchInput) {
            pSearchInput.addEventListener('input', function() {
                filterProductsList();
            });
        }

        window.filterProductsList = function() {
            // Ambil input user & bersihkan spasi kiri kanan
            const rawQuery = pSearchInput.value.toLowerCase().trim();
            
            // Query Versi Bersih (Hapus titik, koma, spasi, dash)
            // Contoh: User ketik "50.000" atau "50 000" -> jadi "50000"
            const cleanQuery = rawQuery.replace(/[\.\,\s\-]/g, ''); 

            let visibleCount = 0;

            // Toggle tombol Clear (X)
            if (rawQuery.length > 0) {
                pClearBtn.classList.remove('hidden');
            } else {
                pClearBtn.classList.add('hidden');
            }

            productItems.forEach(item => {
                // Ambil data asli dari HTML
                const originalName = (item.dataset.name || '').toLowerCase();
                const originalBrand = (item.dataset.brand || '').toLowerCase();
                const originalPrice = (item.dataset.price || '').toLowerCase();
                
                // Buat Versi Bersih dari Data Produk juga
                // Contoh: "PLN 50.000" -> jadi "pln50000"
                const cleanName = originalName.replace(/[\.\,\s\-]/g, '');
                
                // --- LOGIKA PENCARIAN GANDA ---
                
                // 1. Cek Normal (Berdasarkan teks apa adanya)
                // Biar kalau cari "PLN" tetap ketemu
                const matchNormal = originalName.includes(rawQuery) || 
                                    originalBrand.includes(rawQuery);

                // 2. Cek Cerdas (Berdasarkan angka tanpa titik)
                // Biar cari "50000" ketemu "50.000"
                const matchSmart = cleanName.includes(cleanQuery) || 
                                   originalPrice.includes(cleanQuery);

                // Gabungkan kedua logika
                if (rawQuery === '' || matchNormal || matchSmart) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            // Tampilkan pesan kosong jika tidak ada hasil
            if (visibleCount === 0) {
                pNoResult.classList.remove('hidden');
                pListContainer.classList.add('hidden');
            } else {
                pNoResult.classList.add('hidden');
                pListContainer.classList.remove('hidden');
            }
        };
    });
</script>

@endpush