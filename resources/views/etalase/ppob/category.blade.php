@extends('layouts.marketplace')

@php
    // ==========================================================
    // 1. LOGIC PHP: PEMETAAN SKU (AGAR AKURAT 100%)
    // ==========================================================
    $urlSlug = request()->segment(4); 
    $pageInfo = $pageInfo ?? [];
    $currentSlug = $pageInfo['slug'] ?? $urlSlug ?? 'pulsa'; 

    // MAPPING: Ubah Slug URL menjadi Kode SKU API yang Benar
    // Ini memastikan saat buka 'bpjs-kesehatan', sistem tahu itu 'bpjs', bukan 'pln'
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
        'pbb'                => 'pbb',       
        'samsat'             => 'samsat',
        'gas-negara'         => 'pgas',
        'pln-nontaglis'      => 'plnnontaglist',
        
        // Kategori Prabayar (Default)
        'pulsa' => 'pulsa',
        'pln-token' => 'pln', 
        'data' => 'data',
    ];

    // Ambil SKU Aktif. Jika tidak ketemu di map, default gunakan 'pln' (jika pascabayar)
    $activeSku = $skuMap[$currentSlug] ?? 'pln';

    // Logika penentuan apakah halaman ini Pascabayar atau Prabayar
    $postpaidKeys = ['pln', 'pdam', 'bpjs', 'bpjstk', 'hp', 'internet', 'tv', 'multifinance', 'pbb', 'samsat', 'pgas', 'plnnontaglist'];
    
    // Cek flag dari controller atau cek apakah slug ada di daftar pascabayar
    $isPostpaid = ($pageInfo['is_postpaid'] ?? false) || in_array($activeSku, $postpaidKeys);
    
    // Judul Halaman
    $pageTitle = $pageInfo['title'] ?? ucfirst(str_replace('-', ' ', $currentSlug));

    // ==========================================================
    // 2. DATA MENU (TAMPILAN)
    // ==========================================================
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

{{-- SECTION BANNER (Dikembalikan seperti semula) --}}
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

{{-- SECTION MENU KATEGORI LENGKAP --}}
<section class="mb-10 space-y-8" data-aos="fade-up">
    
    {{-- BAGIAN 1: PRABAYAR --}}
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

    {{-- BAGIAN 2: PASCABAYAR --}}
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

{{-- MAIN CONTENT AREA --}}
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
            
            {{-- KOLOM KIRI: INPUT NOMOR --}}
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
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">{{ $pageInfo['input_label'] ?? 'Nomor Tujuan' }}</label>
                        <div class="relative group">
                            <input type="text" id="customer_no" 
                                   class="w-full border border-gray-300 rounded-xl px-4 py-4 pl-12 font-bold text-lg text-gray-700 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none shadow-sm transition"
                                   placeholder="{{ $pageInfo['input_place'] ?? 'Contoh: 0812...' }}">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition">
                                <i class="fas fa-phone"></i>
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

            {{-- KOLOM KANAN: HASIL --}}
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

                                        {{-- Data Dinamis --}}
                                        <div>
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider" id="label_power">Keterangan</p>
                                            <p class="font-bold text-gray-700 text-sm" id="bill_power">-</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider">Jml. Lembar</p>
                                            <p class="font-bold text-gray-700 text-sm" id="bill_sheet">-</p>
                                        </div>

                                        {{-- Alamat --}}
                                        <div class="col-span-2">
                                            <p class="text-gray-500 text-[10px] uppercase tracking-wider">Alamat</p>
                                            <p class="font-bold text-gray-700 text-sm break-words" id="bill_address">-</p>
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
                        </div>

                        <div id="bill_empty" class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <div class="bg-gray-100 p-6 rounded-full mb-4"><i class="fas fa-file-invoice-dollar text-4xl text-gray-300"></i></div>
                            <p class="font-medium">Masukkan nomor pelanggan di kolom kiri lalu klik "Cek Tagihan"</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI (PRABAYAR) --}}
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
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm transition">Batal</button>
                <form action="{{ route('ppob.store') }}" method="POST" id="checkoutForm" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="buyer_sku_code" id="form_sku">
                    <input type="hidden" name="customer_no" id="form_no">
                    <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-lg shadow-red-200 px-6 py-3 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none sm:text-sm transition transform hover:scale-[1.02]">Bayar Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // ------------------------------------------------------------------
    // 1. SETUP VARIABEL DARI PHP (CRITICAL)
    // ------------------------------------------------------------------
    // Ini adalah kunci agar JavaScript tahu persis jenis produk apa yang sedang dibuka
    const ACTIVE_SKU = "{{ $activeSku }}"; 

    // Setup Swiper
    var swiper = new Swiper(".heroSwiper", { 
        loop: true, autoplay: { delay: 4000 }, 
        pagination: { el: ".swiper-pagination", clickable: true }, 
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } 
    });

    const inputNo = document.getElementById('customer_no');

    // ------------------------------------------------------------------
    // 2. FORMATTER TANGGAL (CARBON-LIKE + SUPPORT BPJS)
    // ------------------------------------------------------------------
    function formatPeriodeID(periodeStr) {
        if (!periodeStr) return '-';
        let str = periodeStr.toString().trim();

        // BPJS sering kirim "01", artinya 1 Bulan
        if (ACTIVE_SKU.includes('bpjs') && /^\d{1,2}$/.test(str)) {
            return str + " Bulan";
        }

        // Format YYYYMM (Contoh: 202405)
        if (/^\d{6}$/.test(str)) {
            let year = str.substring(0, 4);
            let month = parseInt(str.substring(4, 6));
            const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if (months[month]) return `${months[month]} ${year}`;
        }
        
        // Format YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
             let date = new Date(str);
             return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        return str;
    }

    // ==========================================
    // LOGIKA UNTUK PRABAYAR (TOKEN, PULSA, GAME)
    // ==========================================
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

        function selectProduct(el) {
            const no = inputNo.value;
            if(no.length < 4) { 
                inputNo.classList.add('border-red-500', 'animate-pulse');
                setTimeout(() => inputNo.classList.remove('border-red-500', 'animate-pulse'), 1000);
                inputNo.focus();
                alert("Mohon isi nomor tujuan / ID Pelanggan terlebih dahulu."); return; 
            }
            document.getElementById('modal_no').innerText = no;
            document.getElementById('modal_product').innerText = el.dataset.name;
            document.getElementById('modal_price').innerText = 'Rp ' + parseInt(el.dataset.price).toLocaleString('id-ID');
            document.getElementById('form_sku').value = el.dataset.sku;
            document.getElementById('form_no').value = no;
            document.getElementById('confirmModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('confirmModal').classList.add('hidden'); }

        // Logic Cek Nama PLN Prabayar
        @if($currentSlug == 'pln-token')
        function cekPlnPrabayar() {
            const no = inputNo.value;
            if(no.length < 10) { alert("Masukkan Nomor Meter dengan benar!"); return; }
            const btn = document.getElementById('btn-cek-pln');
            const infoBox = document.getElementById('pln_info');
            const oriText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...'; btn.disabled = true; infoBox.classList.add('hidden');
            
            fetch('{{ route("ppob.check.pln.prabayar") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ customer_no: no })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = oriText; btn.disabled = false;
                if(data.status === 'success') {
                    document.getElementById('pln_name').innerText = data.name;
                    document.getElementById('pln_power').innerText = data.segment_power;
                    infoBox.classList.remove('hidden');
                } else { alert(data.message); }
            })
            .catch(err => {
                btn.innerHTML = oriText; btn.disabled = false; alert("Gagal koneksi server.");
            });
        }
        @endif

    // ==========================================
    // LOGIKA UNTUK PASCABAYAR (FIXED LOGIC)
    // ==========================================
    @else
        let inquiryRefId = null;

        function cekTagihan() {
            const no = inputNo.value;
            if(no.length < 5) { alert("Masukkan ID Pelanggan!"); return; }

            const btn = document.getElementById('btn-cek-tagihan');
            const spinner = document.getElementById('loading-spinner');
            const text = document.getElementById('btn-text');
            const resultDiv = document.getElementById('bill_result');
            const emptyDiv = document.getElementById('bill_empty');

            // Reset UI
            btn.disabled = true; spinner.classList.remove('hidden'); text.innerText = "Mengecek...";
            resultDiv.classList.add('hidden'); emptyDiv.classList.add('hidden');

            // Kirim Request dengan ACTIVE_SKU dari PHP
            fetch('{{ route("ppob.check.bill") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ 
                    customer_no: no, 
                    sku: ACTIVE_SKU,
                    buyer_sku_code: ACTIVE_SKU
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false; spinner.classList.add('hidden'); text.innerText = "Cek Tagihan";
                
                // Cek Response (Bisa dibungkus data atau tidak)
                const d = data.data || data;

                if(d && (d.status === 'Sukses' || d.rc === '00')) {
                    // MAPPING DATA
                    document.getElementById('bill_ref').innerText = d.ref_id || '-';
                    document.getElementById('bill_name').innerText = d.customer_name || d.name || 'Pelanggan';
                    document.getElementById('bill_id').innerText = d.customer_no;
                    
                    let price = d.selling_price || d.price || 0;
                    document.getElementById('bill_amount').innerText = 'Rp ' + parseInt(price).toLocaleString('id-ID');
                    
                    let admin = d.admin || 0;
                    document.getElementById('bill_admin').innerText = 'Rp ' + parseInt(admin).toLocaleString('id-ID');

                    // MAPPING RINCIAN (DESC)
                    if (d.desc) {
                        const desc = d.desc;
                        const detail = (desc.detail && Array.isArray(desc.detail) && desc.detail.length > 0) ? desc.detail[0] : (desc.detail || desc);

                        // A. ALAMAT
                        document.getElementById('bill_address').innerText = desc.alamat || desc.kab_kota || '-';

                        // B. PERIODE
                        let rawPeriode = detail.periode || desc.periode || '-';
                        document.getElementById('bill_period').innerText = formatPeriodeID(rawPeriode);

                        // C. LEMBAR TAGIHAN
                        document.getElementById('bill_sheet').innerText = (desc.lembar_tagihan || '1') + ' Lembar';

                        // D. LOGIKA LABEL DINAMIS (BPJS / PLN / PDAM)
                        let labelEl = document.getElementById('label_power');
                        let valueEl = document.getElementById('bill_power');
                        
                        // Deteksi Tipe Produk
                        if (ACTIVE_SKU.includes('bpjs')) {
                            labelEl.innerText = "JUMLAH PESERTA";
                            let peserta = desc.jumlah_peserta || desc.peserta || '1';
                            valueEl.innerText = peserta + " Orang";
                        } 
                        else if (ACTIVE_SKU.includes('pln') && !ACTIVE_SKU.includes('nontaglis')) {
                            labelEl.innerText = "TARIF / DAYA";
                            valueEl.innerText = (desc.tarif || '-') + ' / ' + (desc.daya || '-') + ' VA';
                        } 
                        else if (ACTIVE_SKU.includes('pdam')) {
                            labelEl.innerText = "GOLONGAN";
                            valueEl.innerText = desc.tarif || desc.golongan || '-';
                        } 
                        else if (ACTIVE_SKU.includes('multifinance')) {
                            labelEl.innerText = "ITEM / TENOR";
                            valueEl.innerText = (desc.item_name || '-') + ' / ' + (desc.tenor || '-') + ' Bln';
                        } 
                        else {
                            labelEl.innerText = "KETERANGAN";
                            valueEl.innerText = desc.item_name || "-";
                        }
                    }

                    inquiryRefId = d.ref_id;
                    resultDiv.classList.remove('hidden');
                } else {
                    alert(d.message || "Tagihan tidak ditemukan (Data Kosong).");
                    emptyDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false; spinner.classList.add('hidden'); text.innerText = "Cek Tagihan";
                alert("Terjadi kesalahan koneksi.");
            });
        }

        function bayarTagihan() {
            if(!inquiryRefId) return;
            window.location.href = "{{ route('login') }}";
        }
    @endif
</script>
@endpush