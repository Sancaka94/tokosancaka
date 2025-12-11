@extends('layouts.marketplace')

@php
    // ================================================================
    // 1. LOGIC PHP: PEMETAAN SKU (AGAR TIDAK SALAH DETEKSI PRODUK)
    // ================================================================
    $urlSlug = request()->segment(4); 
    $pageInf = $pageInfo ?? [];
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

    // Ambil SKU Aktif. Jika tidak ketemu di map, default gunakan 'pln'
    $activeSku = $skuMap[$currentSlug] ?? 'pln';

    // Logika penentuan apakah halaman ini Pascabayar atau Prabayar
    $postpaidKeys = ['pln', 'pdam', 'bpjs', 'bpjstk', 'hp', 'internet', 'tv', 'multifinance', 'cimahi', 'pbb', 'samsat', 'pgas', 'plnnontaglist', 'emoney'];
    $isPostpaid = ($pageInfo['is_postpaid'] ?? false) || in_array($activeSku, $postpaidKeys);
    
    // Judul Halaman & Label Input
    $pageTitle = $pageInfo['title'] ?? ucfirst(str_replace('-', ' ', $currentSlug));
    $inputLabel = "Nomor Pelanggan";
    $inputPlace = "Contoh: 08123456789";
    
    if($activeSku == 'samsat') {
        $inputLabel = "Kode Bayar, No. KTP / Identitas";
        $inputPlace = "Contoh: 8821...,3201...";
    } elseif ($activeSku == 'pbb' || $activeSku == 'cimahi') {
        $inputLabel = "Nomor Objek Pajak (NOP)";
        $inputPlace = "Masukkan NOP";
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
    
    {{-- PRABAYAR --}}
    <div class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-red-500">
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
    <div class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-blue-500">
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
                        <button onclick="cekTagihan()" id="btn-cek-tagihan" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center gap-2 shadow-lg shadow-blue-200/50">
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
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-list text-gray-400"></i> Daftar Produk
                            </h2>
                        </div>

                        <div id="product_list" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @forelse($products ?? [] as $product)
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
                                        <span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-1 rounded border border-blue-100">Promo</span>
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
    let isProcessing = false; // <<< FLAG BARU DITAMBAHKAN

    // Setup Swiper (Unmodified)
    var swiper = new Swiper(".heroSwiper", { 
        loop: true, autoplay: { delay: 4000 }, 
        pagination: { el: ".swiper-pagination", clickable: true }, 
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } 
    });

    // --- HELPER: Generator Ref ID (Unmodified) ---
    function generateRefID() {
        let prefix = IS_TESTING ? 'TEST-' : 'INQ-';
        return prefix + Date.now() + '-' + Math.floor(Math.random() * 10000);
    }

    // --- HELPER: Formatter Tanggal/Periode (Update Versi Lengkap) ---
function formatPeriodeID(periodeStr) {
    if (!periodeStr) return '-';
    let str = periodeStr.toString().trim().toUpperCase();

    // 1. Cek Format Angka "MMYYYY" (Contoh: 122025 -> Desember 2025)
    if (/^\d{5,6}$/.test(str)) {
        // Asumsi format 6 digit: MMYYYY (122025) atau 5 digit: MYYYY (12025 - jarang tapi mungkin)
        let len = str.length;
        let year = str.substring(len - 4, len);
        let month = parseInt(str.substring(0, len - 4)); // Ambil sisa digit depan sebagai bulan
        
        const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (months[month]) return `${months[month]} ${year}`;
    }

    // 2. Cek Format Teks "MMMYY" (Contoh: DES25 -> Desember 2025)
    if (/^[A-Z]{3}\d{2}$/.test(str)) {
        let code = str.substring(0, 3);
        let year = "20" + str.substring(3, 5);
        
        const monthMap = {
            'JAN': 'Januari', 'FEB': 'Februari', 'MAR': 'Maret', 'APR': 'April',
            'MEI': 'Mei', 'JUN': 'Juni', 'JUL': 'Juli', 'AGU': 'Agustus',
            'SEP': 'September', 'OKT': 'Oktober', 'NOV': 'November', 'DES': 'Desember'
        };
        
        if (monthMap[code]) return `${monthMap[code]} ${year}`;
    }

    // Fallback: Jika format tidak dikenali, kembalikan apa adanya
    return str;
}


function getRcMessage(rcCode) {
    // Pastikan rcCode diubah menjadi string
    const rc = String(rcCode);

    // Peta (Map) Response Code ke Objek Pesan
    const rcMap = {
        // ------------------------------------
        // --- SUKSES (1 Kode) ---
        // ------------------------------------
        // 1. RC 00
        '00': { status: 'Sukses', message: 'Transaksi Sukses.', alertType: 'success' },

        // ------------------------------------
        // --- PENDING (3 Kode) ---
        // ------------------------------------
        // 2. RC 03
        '03': { status: 'Pending', message: 'Transaksi Pending. Mohon tunggu status update.', alertType: 'warning' },
        // 3. RC 55
        '55': { status: 'Pending', message: 'Produk Sedang Gangguan. Silakan coba sebentar lagi.', alertType: 'warning' },
        // 4. RC 99
        '99': { status: 'Pending', message: 'DF Router Issue / Saldo API bermasalah. Silakan isi deposit.', alertType: 'warning' },
        
        // ------------------------------------
        // --- GAGAL (42 Kode) ---
        // ------------------------------------
        // 5. RC 01
        '01': { status: 'Gagal', message: 'Timeout. Transaksi Gagal.', alertType: 'error' },
        // 6. RC 02
        '02': { status: 'Gagal', message: 'Transaksi Gagal. Terjadi kesalahan sistem.', alertType: 'error' },
        // 7. RC 40
        '40': { status: 'Gagal', message: 'Payload Error. Tipe data atau parameter tidak sesuai.', alertType: 'error' },
        // 8. RC 41
        '41': { status: 'Gagal', message: 'Signature tidak valid. Perhatikan kembali formula signature dan apiKey Anda.', alertType: 'error' },
        // 9. RC 42
        '42': { status: 'Gagal', message: 'Gagal memproses API Buyer. Username belum sesuai.', alertType: 'error' },
        // 10. RC 43
        '43': { status: 'Gagal', message: 'SKU tidak ditemukan atau Non-Aktif. Periksa konfigurasi produk.', alertType: 'error' },
        // 11. RC 44
        '44': { status: 'Gagal', message: 'Saldo tidak cukup. Mohon isi deposit API Anda.', alertType: 'error' },
        // 12. RC 45
        '45': { status: 'Gagal', message: 'IP Anda tidak kami kenali. Silahkan whitelist IP Anda.', alertType: 'error' },
        // 13. RC 47
        '47': { status: 'Gagal', message: 'Transaksi sudah terjadi di buyer lain.', alertType: 'error' },
        // 14. RC 49
        '49': { status: 'Gagal', message: 'Ref ID tidak unik.', alertType: 'error' },
        // 15. RC 50
        '50': { status: 'Gagal', message: 'Transaksi Tidak Ditemukan.', alertType: 'error' },
        // 16. RC 51
        '51': { status: 'Gagal', message: 'Nomor Tujuan Diblokir.', alertType: 'error' },
        // 17. RC 52
        '52': { status: 'Gagal', message: 'Prefix Tidak Sesuai Dengan Operator.', alertType: 'error' },
        // 18. RC 53
        '53': { status: 'Gagal', message: 'Produk Seller Sedang Tidak Tersedia.', alertType: 'error' },
        // 19. RC 54
        '54': { status: 'Gagal', message: 'Nomor Tujuan Salah. Mohon periksa kembali nomor pelanggan.', alertType: 'error' },
        // 20. RC 56
        '56': { status: 'Gagal', message: 'Limit saldo seller (Deprecated).', alertType: 'error' },
        // 21. RC 57
        '57': { status: 'Gagal', message: 'Jumlah Digit Kurang Atau Lebih dari standar.', alertType: 'error' },
        // 22. RC 58
        '58': { status: 'Gagal', message: 'Sedang Cut Off. Transaksi dibatalkan.', alertType: 'error' },
        // 23. RC 59
        '59': { status: 'Gagal', message: 'Tujuan di Luar Wilayah/Cluster layanan.', alertType: 'error' },
        // 24. RC 60
        '60': { status: 'Gagal', message: 'Tagihan belum tersedia (Belum Terbit/Sudah Lunas).', alertType: 'error' },
        // 25. RC 61
        '61': { status: 'Gagal', message: 'Akun API belum pernah melakukan deposit (Saldo Nol).', alertType: 'error' },
        // 26. RC 62
        '62': { status: 'Gagal', message: 'Seller sedang mengalami gangguan teknis.', alertType: 'error' },
        // 27. RC 63
        '63': { status: 'Gagal', message: 'Tidak support transaksi multi.', alertType: 'error' },
        // 28. RC 64
        '64': { status: 'Gagal', message: 'Tarik tiket gagal, coba nominal lain atau hubungi admin.', alertType: 'error' },
        // 29. RC 65
        '65': { status: 'Gagal', message: 'Limit transaksi multi (Deprecated).', alertType: 'error' },
        // 30. RC 66
        '66': { status: 'Gagal', message: 'Cut Off (Perbaikan Sistem Seller).', alertType: 'error' },
        // 31. RC 67
        '67': { status: 'Gagal', message: 'Seller belum ter-verfikasi.', alertType: 'error' },
        // 32. RC 68
        '68': { status: 'Gagal', message: 'Stok habis.', alertType: 'error' },
        // 33. RC 69
        '69': { status: 'Gagal', message: 'Harga seller lebih besar dari ketentuan harga Buyer.', alertType: 'error' },
        // 34. RC 70
        '70': { status: 'Gagal', message: 'Timeout Dari Biller. Coba lagi.', alertType: 'error' },
        // 35. RC 71
        '71': { status: 'Gagal', message: 'Produk Sedang Tidak Stabil. Coba sebentar lagi.', alertType: 'error' },
        // 36. RC 72
        '72': { status: 'Gagal', message: 'Lakukan Unreg Paket Dahulu.', alertType: 'error' },
        // 37. RC 73
        '73': { status: 'Gagal', message: 'Kwh Melebihi Batas.', alertType: 'error' },
        // 38. RC 74
        '74': { status: 'Gagal', message: 'Transaksi Refund.', alertType: 'error' },
        // 39. RC 80
        '80': { status: 'Gagal', message: 'Akun Anda telah diblokir oleh Seller.', alertType: 'error' },
        // 40. RC 81
        '81': { status: 'Gagal', message: 'Seller ini telah diblokir oleh Anda.', alertType: 'error' },
        // 41. RC 82
        '82': { status: 'Gagal', message: 'Akun Anda belum ter-verfikasi.', alertType: 'error' },
        // 42. RC 83
        '83': { status: 'Gagal', message: 'Limitasi pengecekan pricelist terlampaui. Silahkan coba beberapa saat lagi.', alertType: 'error' },
        // 43. RC 84
        '84': { status: 'Gagal', message: 'Nominal tidak valid.', alertType: 'error' },
        // 44. RC 85
        '85': { status: 'Gagal', message: 'Limitasi transaksi terlampaui. Silahkan coba 1 menit lagi.', alertType: 'error' },
        // 45. RC 86
        '86': { status: 'Gagal', message: 'Limitasi pengecekan nomor PLN terlampaui. Silahkan coba beberapa saat lagi.', alertType: 'error' },
        // 46. RC 87
        '87': { status: 'Gagal', message: 'Transaksi E-money wajib kelipatan Rp 1.000.', alertType: 'error' },
    };

    // Kembalikan pesan yang sesuai atau pesan default jika RC tidak terdefinisi
    return rcMap[rc] || { 
        status: 'Gagal', 
        message: `Gagal (RC: ${rc}). Kesalahan tidak terdefinisi.`, 
        alertType: 'error' 
    };
}

    // --- NEW HELPER: Trigger Custom Notification ---
    function triggerCustomNotification(msg, type) {
        // Karena kita tidak bisa mengakses DOM Blade penuh atau Session Laravel dari sini,
        // kita akan menggunakan SweetAlert2 (library JS populer) jika tersedia, atau
        // Console.log sebagai fallback untuk menghindari alert() yang mengganggu.
        
        // Cek jika SweetAlert2 tersedia (Asumsi: Anda mungkin menggunakannya)
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
             const icon = type === 'success' ? 'success' : type === 'error' ? 'error' : 'warning';
             Swal.fire({
                title: type.toUpperCase(),
                text: msg,
                icon: icon,
                confirmButtonText: 'Oke'
            });
        } else {
            // Fallback: Console.log (untuk debugging tanpa mengganggu UI)
            const logType = type === 'error' ? console.error : type === 'warning' ? console.warn : console.log;
            logType(`[STATUS ${type.toUpperCase()}] ${msg}`);
            
            // Opsional: Untuk memastikan user melihat pesan, kita tambahkan DIV sementara
            // Ini membutuhkan styling, tapi ini adalah simulasi perbaikan tampilan error.
            
            /*
            const notificationDiv = document.createElement('div');
            notificationDiv.innerHTML = `<div class="bg-${type === 'success' ? 'green' : 'red'}-100 border-l-4 border-${type === 'success' ? 'green' : 'red'}-500 text-${type === 'success' ? 'green' : 'red'}-700 p-4 rounded shadow-sm mb-6 flex items-center" role="alert"><i class="fas fa-exclamation-circle mr-2"></i> ${msg}</div>`;
            const container = document.querySelector('.container.mx-auto.px-4'); // Container setelah Breadcrumb
            if (container) {
                 container.insertBefore(notificationDiv, container.firstChild);
                 setTimeout(() => notificationDiv.remove(), 6000); // Hapus setelah 6 detik
            }
            */
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

        // --- FUNGSI PILIH PRODUK (POPUP MODAL) ---
        function selectProduct(el) {
            const no = inputNo.value.replace(/[^0-9]/g, '');
            let minLen = IS_TESTING ? 3 : 4; 
            if(no.length < minLen) { 
                triggerCustomNotification("Mohon isi nomor tujuan yang valid terlebih dahulu.", 'error'); inputNo.focus(); return; 
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

        // --- FUNGSI CHECKOUT PRABAYAR (AJAX) ---
        function processPrepaidCheckout() {
            if(isProcessing) return; // JANGAN LANJUTKAN jika sedang memproses
            if(!currentPrepaidData) return;
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
            // SUCCESS: Tetap disabled dan REDIRECT
            window.location.href = "{{ route('ppob.checkout.index') }}";
        } else {
            // GAGAL: Kembalikan state
            triggerCustomNotification("Gagal menambahkan ke keranjang: " + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
            isProcessing = false; // <<< PENTING: Reset flag hanya jika GAGAL
        }
    })
    .catch(err => {
        // ERROR: Kembalikan state
        console.error(err);
        triggerCustomNotification("Terjadi kesalahan sistem.", 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
        isProcessing = false; // <<< PENTING: Reset flag hanya jika GAGAL
    });
}
        
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
                    document.getElementById('pln_name').innerText = d.name || d.customer_name || 'Pelanggan Test';
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
    // 🔵 LOGIKA PASCABAYAR (LENGKAP)
    // =================================================================
    @else
    // =================================================================
    // 🔵 LOGIKA PASCABAYAR (PERBAIKAN TOTAL)
    // =================================================================
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

        // Elemen HTML Hasil
        const resultDiv = document.getElementById('bill_result');
        const emptyDiv = document.getElementById('bill_empty');
        const detailContainer = document.getElementById('detail_container');
        const detailList = document.getElementById('detail_list');

        // Reset UI State
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
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    customer_no: cleanNo,
                    sku: ACTIVE_SKU,
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

                // Deteksi wrapper data
                const d = data.data || data;
                const rc = d.rc;
                const rcInfo = getRcMessage(rc);
                let messageToUser = d.message || rcInfo.message;

                // Validasi Status Sukses (RC 00)
                if (d && (d.status === 'success' || d.status === 'Sukses' || rc === '00')) {

                    // 1. DEFINISI VARIABEL (PENTING: JANGAN DIHAPUS)
                    // Kita definisikan 'desc' agar kode rincian di bawah tidak error
                    const desc = d.desc || {};
                    const detailArray = (desc.detail && Array.isArray(desc.detail)) ? desc.detail : [];
                    const firstDetail = detailArray.length > 0 ? detailArray[0] : {};

                    // 2. LOGIC HARGA & ADMIN
                    // Ambil harga jual API (Priority)
                    let apiSellingPrice = parseInt(d.selling_price || 0);
                    let apiAdmin = parseInt(d.admin || d.admin_fee || 0);
                    
                    // Jika selling_price 0, coba hitung manual dari rincian
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

                    // Total Bayar Akhir
                    let finalPrice = apiSellingPrice;
                    let finalAdmin = apiAdmin;

                    // 3. LOGIC ALAMAT (SAFE MODE)
                    let valAddr = desc.alamat || desc.address || d.address || firstDetail.alamat || '-';

                    // 4. MAPPING KE TAMPILAN
                    document.getElementById('bill_ref').innerText = d.ref_id || '-';
                    document.getElementById('bill_name').innerText = d.customer_name || d.name || 'Pelanggan';
                    document.getElementById('bill_id').innerText = d.customer_no;

                    // Tampilkan Harga
                    document.getElementById('bill_amount').innerText = 'Rp ' + finalPrice.toLocaleString('id-ID');
                    document.getElementById('bill_admin').innerText = 'Rp ' + finalAdmin.toLocaleString('id-ID');
                    document.getElementById('val_address').innerText = valAddr;

                    // 5. UPDATE VARIABLE GLOBAL (PENTING UNTUK CHECKOUT)
                    currentBillData = {
                        sku: ACTIVE_SKU,
                        name: "Tagihan " + ACTIVE_SKU.toUpperCase() + " - " + (d.customer_name || d.name),
                        price: finalPrice,
                        ref_id: d.ref_id,
                        customer_no: d.customer_no
                    };

                    // 6. MAPPING RINCIAN (PERIODE & LABEL DINAMIS)
                    let mainPeriode = firstDetail.periode || desc.periode || d.periode || '-';
                    document.getElementById('bill_period').innerText = formatPeriodeID(mainPeriode);

                    // Label Dinamis (PLN, BPJS, PDAM, dll)
                    let lbl1 = "Info", val1 = "-", lbl2 = "Lembar", val2 = (desc.lembar_tagihan || '1') + ' Lembar';

                    if (ACTIVE_SKU.includes('pln')) {
                        lbl1 = "Tarif / Daya";
                        
                        // Ambil Tarif (Prioritas: desc.tarif -> d.segment_power -> -)
                        let tarifData = desc.tarif || d.segment_power || '-';
                        
                        // Ambil Daya (Prioritas: desc.daya -> parsed dari segment_power -> -)
                        let dayaData = desc.daya;
                        
                        // Jika daya kosong tapi ada segment_power (misal: R1M/900), coba ambil angkanya
                        if (!dayaData && d.segment_power && d.segment_power.includes('/')) {
                             let parts = d.segment_power.split('/');
                             if(parts[1]) dayaData = parts[1].replace(/[^0-9]/g, '');
                        }
                        
                        // Format Akhir: "R1M / 900 VA"
                        // Pastikan dayaData ada isinya sebelum ditambah " VA"
                        let displayDaya = (dayaData && dayaData != '-') ? `${dayaData} VA` : '-';
                        val1 = `${tarifData} / ${displayDaya}`;

                    } else if (ACTIVE_SKU.includes('bpjs')) {
                        // ... (Logika BPJS tetap sama)
                        lbl1 = "Jml. Peserta";
                        val1 = (desc.jumlah_peserta || desc.peserta || '1') + " Orang";
                        lbl2 = "Cabang";
                        val2 = desc.kantor_cabang || '-';
                        
                    } else if (ACTIVE_SKU.includes('pdam')) {
                        lbl1 = "Meteran";
                        val1 = (firstDetail.meter_awal || '-') + ' - ' + (firstDetail.meter_akhir || '-');
                    }

                    // Terapkan Label Dinamis (Cek element ada atau tidak untuk menghindari error)
                    if(document.getElementById('label_info_1')) {
                        document.getElementById('label_info_1').innerText = lbl1;
                        document.getElementById('val_info_1').innerText = val1;
                        document.getElementById('label_info_2').innerText = lbl2;
                        document.getElementById('val_info_2').innerText = val2;
                    }

                    // 7. RENDER LIST DETAIL (JIKA ADA)
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

                    // Tampilkan Hasil Akhir
                    triggerCustomNotification("Data ditemukan.", 'success');
                    resultDiv.classList.remove('hidden');

                } else {
                    // ERROR DARI API (RC BUKAN 00)
                    triggerCustomNotification(messageToUser, rcInfo.alertType);
                    resultDiv.classList.add('hidden');
                    emptyDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                // ERROR JARINGAN / SYNTAX JS
                console.error(err);
                btn.disabled = false;
                spinner.classList.add('hidden');
                text.innerText = "Cek Tagihan";
                triggerCustomNotification("Gagal koneksi server. Periksa jaringan Anda.", 'error');
            });
    }

    // --- FUNGSI BAYAR (VERSI BARU: DIRECT CHECKOUT) ---
    function bayarTagihan() {
        if(isProcessing) return; // JANGAN LANJUTKAN jika sedang memproses
        if(!currentBillData) {
            triggerCustomNotification("Data tagihan tidak valid/kadaluarsa. Silakan cek ulang.", 'error');
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
            // SUCCESS: Tetap disabled dan REDIRECT
            window.location.href = "{{ route('ppob.checkout.index') }}";
        } else {
            // GAGAL: Kembalikan state
            triggerCustomNotification("Gagal memproses pesanan.", 'error');
            btnBayar.innerHTML = oriText;
            btnBayar.disabled = false;
            isProcessing = false; // <<< PENTING: Reset flag hanya jika GAGAL
        }
    })
    .catch(err => {
        // ERROR: Kembalikan state
        console.error(err);
        triggerCustomNotification("Terjadi kesalahan sistem.", 'error');
        btnBayar.innerHTML = oriText;
        btnBayar.disabled = false;
        isProcessing = false; // <<< PENTING: Reset flag hanya jika GAGAL
    });
}
    @endif
</script>
@endpush