@extends('layouts.marketplace')

@php
    // --- 1. SETUP DATA KATEGORI (Sesuai List Kamu) ---
    $urlSlug = request()->segment(4); 
    $pageInfo = $pageInfo ?? [];
    $currentSlug = $pageInfo['slug'] ?? $urlSlug ?? 'pulsa'; 

    // Daftar Slug Pascabayar untuk deteksi otomatis halaman
    $postpaidSlugs = [
        'pln-pascabayar', 'pdam', 'bpjs-kesehatan', 'bpjs-ketenagakerjaan', 
        'hp-pascabayar', 'internet-pascabayar', 'tv-pascabayar', 
        'multifinance', 'pbb', 'samsat', 'gas-negara', 'pln-nontaglis'
    ];
    
    $isPostpaid = ($pageInfo['is_postpaid'] ?? false) || in_array($currentSlug, $postpaidSlugs);
    $pageTitle = $pageInfo['title'] ?? ucwords(str_replace('-', ' ', $currentSlug));

    // --- ARRAY MENU PRABAYAR (LENGKAP) ---
    $prepaidMenus = [
        ['slug' => 'pulsa', 'name' => 'Pulsa', 'icon' => 'fa-mobile-alt', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'data', 'name' => 'Paket Data', 'icon' => 'fa-wifi', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'pln-token', 'name' => 'Token PLN', 'icon' => 'fa-bolt', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'e-money', 'name' => 'E-Money', 'icon' => 'fa-wallet', 'style' => 'text-purple-500 bg-purple-50 border-purple-100'],
        ['slug' => 'voucher-game', 'name' => 'Games', 'icon' => 'fa-gamepad', 'style' => 'text-indigo-500 bg-indigo-50 border-indigo-100'],
        ['slug' => 'voucher', 'name' => 'Voucher', 'icon' => 'fa-ticket-alt', 'style' => 'text-pink-500 bg-pink-50 border-pink-100'],
        ['slug' => 'paket-sms-telpon', 'name' => 'SMS & Telpon', 'icon' => 'fa-phone-volume', 'style' => 'text-green-500 bg-green-50 border-green-100'],
        ['slug' => 'masa-aktif', 'name' => 'Masa Aktif', 'icon' => 'fa-hourglass-half', 'style' => 'text-orange-500 bg-orange-50 border-orange-100'],
        ['slug' => 'aktivasi-voucher', 'name' => 'Akt. Voucher', 'icon' => 'fa-barcode', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'aktivasi-perdana', 'name' => 'Akt. Perdana', 'icon' => 'fa-sim-card', 'style' => 'text-slate-600 bg-slate-50 border-slate-200'],
        ['slug' => 'streaming', 'name' => 'Streaming', 'icon' => 'fa-play-circle', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'tv', 'name' => 'TV Prabayar', 'icon' => 'fa-tv', 'style' => 'text-teal-500 bg-teal-50 border-teal-100'],
        ['slug' => 'china-topup', 'name' => 'China Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'malaysia-topup', 'name' => 'Mly. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'philippines-topup', 'name' => 'Phil. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'singapore-topup', 'name' => 'S\'pore Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'thailand-topup', 'name' => 'Thai Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'vietnam-topup', 'name' => 'Viet. Topup', 'icon' => 'fa-globe-asia', 'style' => 'text-red-500 bg-red-50 border-red-100'],
        ['slug' => 'bundling', 'name' => 'Bundling', 'icon' => 'fa-box-open', 'style' => 'text-cyan-500 bg-cyan-50 border-cyan-100'],
        ['slug' => 'gas', 'name' => 'Gas Token', 'icon' => 'fa-burn', 'style' => 'text-orange-600 bg-orange-50 border-orange-100'],
        ['slug' => 'esim', 'name' => 'eSIM', 'icon' => 'fa-qrcode', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-100'],
        ['slug' => 'media-sosial', 'name' => 'Media Sosial', 'icon' => 'fa-thumbs-up', 'style' => 'text-blue-500 bg-blue-50 border-blue-100'],
        ['slug' => 'telkomsel-omni', 'name' => 'Tsel Omni', 'icon' => 'fa-tower-cell', 'style' => 'text-red-600 bg-red-50 border-red-100'],
        ['slug' => 'indosat-only4u', 'name' => 'Isat Only4u', 'icon' => 'fa-star', 'style' => 'text-yellow-500 bg-yellow-50 border-yellow-100'],
        ['slug' => 'tri-cuanmax', 'name' => 'Tri CuanMax', 'icon' => 'fa-percent', 'style' => 'text-purple-600 bg-purple-50 border-purple-100'],
        ['slug' => 'xl-axis-cuanku', 'name' => 'XL Cuanku', 'icon' => 'fa-gift', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'by-u', 'name' => 'by.U', 'icon' => 'fa-ghost', 'style' => 'text-orange-500 bg-orange-50 border-orange-100'],
    ];

    // --- ARRAY MENU PASCABAYAR ---
    $postpaidMenus = [
        ['slug' => 'pln-pascabayar', 'name' => 'PLN Pasca', 'icon' => 'fa-file-invoice-dollar', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-100'],
        ['slug' => 'pdam', 'name' => 'PDAM', 'icon' => 'fa-faucet', 'style' => 'text-cyan-600 bg-cyan-50 border-cyan-100'],
        ['slug' => 'bpjs-kesehatan', 'name' => 'BPJS Kes.', 'icon' => 'fa-heartbeat', 'style' => 'text-green-600 bg-green-50 border-green-100'],
        ['slug' => 'bpjs-ketenagakerjaan', 'name' => 'BPJS TK', 'icon' => 'fa-hard-hat', 'style' => 'text-green-700 bg-green-50 border-green-100'],
        ['slug' => 'hp-pascabayar', 'name' => 'HP Pasca', 'icon' => 'fa-mobile-screen', 'style' => 'text-blue-600 bg-blue-50 border-blue-100'],
        ['slug' => 'internet-pascabayar', 'name' => 'Internet', 'icon' => 'fa-network-wired', 'style' => 'text-indigo-600 bg-indigo-50 border-indigo-100'],
        ['slug' => 'tv-pascabayar', 'name' => 'TV Kabel', 'icon' => 'fa-satellite-dish', 'style' => 'text-pink-600 bg-pink-50 border-pink-100'],
        ['slug' => 'multifinance', 'name' => 'Cicilan', 'icon' => 'fa-money-bill-wave', 'style' => 'text-emerald-600 bg-emerald-50 border-emerald-100'],
        ['slug' => 'pbb', 'name' => 'Pajak PBB', 'icon' => 'fa-building', 'style' => 'text-gray-600 bg-gray-50 border-gray-200'],
        ['slug' => 'samsat', 'name' => 'SAMSAT', 'icon' => 'fa-car', 'style' => 'text-blue-700 bg-blue-50 border-blue-100'],
        ['slug' => 'gas-negara', 'name' => 'Gas Negara', 'icon' => 'fa-fire', 'style' => 'text-orange-600 bg-orange-50 border-orange-100'],
        ['slug' => 'pln-nontaglis', 'name' => 'PLN NonTag', 'icon' => 'fa-plug', 'style' => 'text-yellow-600 bg-yellow-50 border-yellow-100'],
    ];

    // --- LOGIKA LABEL INPUT DINAMIS ---
    $dynamicLabel = 'Nomor Pelanggan';
    $dynamicPlaceholder = 'Masukkan Nomor...';
    
    if (str_contains($currentSlug, 'game')) {
        $dynamicLabel = 'User ID / Zone ID';
        $dynamicPlaceholder = '12345678 (1234)';
    } elseif (str_contains($currentSlug, 'pulsa') || str_contains($currentSlug, 'data') || str_contains($currentSlug, 'paket') || str_contains($currentSlug, 'hp')) {
        $dynamicLabel = 'Nomor HP';
        $dynamicPlaceholder = '08xxxxxxxxxx';
    } elseif (str_contains($currentSlug, 'pln')) {
        $dynamicLabel = 'No. Meter / ID Pelanggan';
        $dynamicPlaceholder = '5123xxxxxxxx';
    }
@endphp

@section('title', 'Sancaka Marketplace')

@push('styles')
<style>
    :root {
        --primary-red: #d0011b;
        --bg-gray: #f5f5f5;
    }

    /* Card Produk Padat */
    .product-card {
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 280px; /* <--- TAMBAHKAN ATAU SESUAIKAN INI */
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        position: relative;
    }

    /* Hover Effect Card */
    .product-card:hover {
        border-color: var(--primary-red);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 2;
    }

    /* Container Gambar: Wajib Kotak Persegi */
    .product-img-container {
        width: 100%;
        padding-top: 100%; /* Ini trik CSS biar selalu kotak 1:1 (Square) */
        position: relative;
        background-color: #f9fafb; /* Abu-abu sangat muda */
        overflow: hidden;
        border-bottom: 1px solid #f0f0f0; /* Batas bawah biar rapi */
    }

    /* Gambar: Wajib Pas di Tengah & Tidak Kepotong */
    .product-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain; /* <--- INI KUNCINYA (Gambar dipaksa masuk kotak) */
        padding: 10px; /* <--- INI JARAKNYA (Biar gambar ga nempel pinggir) */
        background-color: transparent;
        transition: transform 0.3s ease;
    }

    /* Efek Zoom Sedikit pas Hover (Opsional, biar manis dikit) */
    .product-card:hover .product-img {
        transform: scale(1.05);
    }

    /* Judul Produk */
    .product-title {
        font-size: 12px; /* Perkecil sedikit untuk mobile */
        line-height: 1.4; /* Beri napas antar baris */
        height: 34px; /* Fixed height 2 baris (12px * 1.4 * 2 ~= 33.6px) */
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        color: #212121;
        margin-bottom: 4px;
        font-weight: 500; /* Jangan terlalu bold biar ga makan tempat */
    }

    /* Di layar Desktop (MD ke atas), besarkan lagi */
    @media (min-width: 768px) {
        .product-title {
            font-size: 14px;
            height: 40px;
        }
    }
    
    /* Judul Section (Flash Sale, Kategori, dll) */
    h2 {
        font-size: 16px; /* Default mobile */
    }
    @media (min-width: 768px) {
        h2 {
            font-size: 20px; /* Desktop lebih besar */
        }
    }
    
    /* Hover Kategori Merah */
    .category-item:hover .category-icon {
        color: var(--primary-red) !important;
        border-color: var(--primary-red) !important;
        background-color: #fff5f5;
    }
    .category-item:hover .category-text {
        color: var(--primary-red) !important;
    }
    
    /* --- TAMBAHAN KHUSUS PAGINATION SWIPER --- */
    
    /* Memberi ruang di bawah slider agar titik-titik tidak menutupi produk */
    .flashSaleSwiper, .bestSellerSwiper, .categoriesSwiper {
        padding-bottom: 40px !important;
    }

    /* Styling Titik Pagination */
    .swiper-pagination-bullet {
        width: 8px;
        height: 8px;
        background: #d1d5db; /* Abu-abu */
        opacity: 1;
        transition: all 0.3s;
    }

    /* Styling Titik Aktif (Merah Panjang) */
    .swiper-pagination-bullet-active {
        background-color: var(--primary-red) !important;
        width: 24px; /* Memanjang */
        border-radius: 4px;
    }

    /* Posisi Pagination */
    .swiper-horizontal > .swiper-pagination-bullets, 
    .swiper-pagination-bullets.swiper-pagination-horizontal {
        bottom: 0px !important;
    }

    /* Navigasi & Pagination */
    .swiper-pagination-bullet-active { background-color: var(--primary-red) !important; }
    
    /* Tombol Keranjang */
    .btn-cart {
        background-color: var(--primary-red);
        color: white;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 0;
        border-radius: 4px;
        width: 100%;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: background-color 0.2s;
        margin-top: 8px; /* Jarak dari harga */
    }
    .btn-cart:hover {
        background-color: #d0011b; /* Merah lebih gelap saat hover */
    }
    
    
</style>
@endpush

@section('content')
<div class="container mx-auto py-4 px-2 md:px-4 max-w-7xl">

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-4">
        
        <div class="lg:col-span-2 rounded shadow-sm overflow-hidden h-full sm:h-full md:h-full w-full">
            <div class="swiper heroSwiper w-full h-full">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
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

    <section class="bg-white p-4 rounded shadow-sm mb-4 border-b border-gray-200">
        <h2 class="text-sm font-bold text-gray-700 mb-4 uppercase">KATEGORI</h2>
        
        <div class="swiper categoriesSwiper relative pb-8">
            <div class="swiper-wrapper">
                @php $categoryChunks = $categories->chunk(10); @endphp
                
                @forelse ($categoryChunks as $chunk)
                <div class="swiper-slide">
                    <div class="grid grid-cols-5 gap-y-4">
                        @foreach ($chunk as $category)
                        <a href="{{ url('/etalase/category/' . $category->slug) }}" class="category-item flex flex-col items-center group transition-all duration-200">
                            {{-- Icon Container --}}
                            <div class="category-icon w-10 h-10 md:w-12 md:h-12 border border-gray-200 rounded-lg flex items-center justify-center mb-2 bg-white text-gray-500 transition-colors duration-200">
                                @if(isset($category->image) && $category->image)
                                    <img src="{{ asset('public/storage/' . $category->image) }}" class="w-7 h-7 object-contain">
                                @else
                                    {{-- Icon FontAwesome --}}
                                    <i class="fas {{ $category->icon ?? 'fa-box' }} text-lg md:text-xl"></i>
                                @endif
                            </div>
                            {{-- Nama Kategori --}}
                            <span class="category-text text-[10px] text-center text-gray-600 leading-tight px-1 line-clamp-2 h-7 flex items-center justify-center transition-colors duration-200 font-medium">
                                {{ $category->name }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @empty
                    <p class="text-center text-gray-400 text-xs py-4">Kategori kosong.</p>
                @endforelse
            </div>
            <div class="swiper-pagination !bottom-0"></div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- ⚡ UPDATE: MENU PPOB TAMPIL PUBLIC (TANPA SYARAT) ⚡ --}}
    {{-- ============================================================ --}}
    
    {{-- === KATEGORI MENU SECTION (REVISI SESUAI GAMBAR) === --}}
        <section class="mb-8" data-aos="fade-up">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                
                {{-- 1. TAB HEADER (Prabayar vs Pascabayar) --}}
                <div class="flex border-b border-gray-200">
                    <button onclick="switchTab('prepaid')" id="tab-prepaid" class="flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-white hover:text-white transition bg-red-600 hover:bg-red-700 flex items-center justify-center gap-2">
                        <i class="fas fa-mobile-alt"></i> Prabayar / Topup
                    </button>
                    <button onclick="switchTab('postpaid')" id="tab-postpaid" class="flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-grey-600 hover:text-white transition hover:bg-red-600 flex items-center justify-center gap-2">
                        <i class="fas fa-file-invoice"></i> Pascabayar / Tagihan
                    </button>
                </div>

                <div class="p-6 md:p-8">
                    {{-- 2. KONTEN PRABAYAR (GRID 8 KOLOM) --}}
                    <div id="content-prepaid" class="animate-fade">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-y-8 gap-x-4" id="grid-prepaid-wrapper">
                            @foreach($prepaidMenus as $index => $menu)
                                {{-- Item Menu --}}
                                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" 
                                   class="menu-item-prepaid group flex flex-col items-center gap-3 {{ $index >= 16 ? 'hidden' : '' }}">
                                    
                                    {{-- Icon Container (Rounded) --}}
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center transition border {{ $menu['style'] }} {{ $currentSlug == $menu['slug'] ? 'ring-2 ring-offset-2 ring-blue-400' : 'group-hover:scale-105' }}">
                                        <i class="fas {{ $menu['icon'] }} text-xl md:text-2xl"></i>
                                    </div>
                                    
                                    {{-- Text Label --}}
                                    <span class="text-[11px] md:text-xs font-medium text-gray-600 text-center leading-tight px-1">{{ $menu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                        
                        {{-- Tombol Lihat Semua (Accordion) --}}
                        @if(count($prepaidMenus) > 16)
                            <div class="mt-8 text-center">
                                <button onclick="toggleExpand('prepaid')" id="btn-expand-prepaid" class="inline-flex items-center gap-2 px-6 py-2 bg-red-600 text-white text-xs font-bold rounded-full hover:bg-red-700 transition">
                                    <span>Lihat Semua Layanan</span> 
                                    <i class="fas fa-chevron-down text-[10px]"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- 3. KONTEN PASCABAYAR (GRID 8 KOLOM) --}}
                    <div id="content-postpaid" class="hidden animate-fade">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-y-8 gap-x-4">
                            @foreach($postpaidMenus as $menu)
                                <a href="{{ url('/etalase/ppob/digital/' . $menu['slug']) }}" class="group flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center transition border {{ $menu['style'] }} {{ $currentSlug == $menu['slug'] ? 'ring-2 ring-offset-2 ring-blue-400' : 'group-hover:scale-105' }}">
                                        <i class="fas {{ $menu['icon'] }} text-xl md:text-2xl"></i>
                                    </div>
                                    <span class="text-[11px] md:text-xs font-medium text-gray-600 text-center leading-tight px-1">{{ $menu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </section>

  {{-- === FLASH SALE SECTION (REVISI MOBILE) === --}}
    @if($flashSaleProducts->isNotEmpty())
    <section class="mb-6 bg-white shadow-sm rounded overflow-hidden border border-gray-100">
        
        {{-- Header Merah --}}
        <div class="bg-[#d0011b] p-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="text-lg md:text-xl font-bold text-white italic flex items-center gap-1">
                    <i class="fas fa-bolt text-yellow-300"></i> FLASH SALE
                </h2>
                {{-- Timer --}}
                <div class="flex items-center gap-1 font-bold text-[#ee4d2d] text-xs md:text-sm" id="flashSaleTimer">
                    <span id="fs-hours" class="bg-white px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center">23</span>:
                    <span id="fs-minutes" class="bg-white px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center">59</span>:
                    <span id="fs-seconds" class="bg-white px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center">59</span>
                </div>
            </div>
            <a href="#" class="text-white text-xs font-semibold hover:opacity-80 transition">Lihat Semua ></a>
        </div>
        
        {{-- Konten Slider --}}
        <div class="p-3">
            <div class="swiper flashSaleSwiper">
                <div class="swiper-wrapper pb-1">
                    @foreach ($flashSaleProducts as $product)
                        @php
                            $hasDiscount = ($product->original_price && $product->original_price > $product->price);
                            $discountPercent = $hasDiscount ? round((($product->original_price - $product->price) / $product->original_price) * 100) : 0;
                            $imgSrc = $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/300x300?text=No+Image';
                            $soldPercentage = 85; // Simulasi terjual
                        @endphp

                        <div class="swiper-slide h-auto">
                            {{-- KARTU PRODUK (Struktur Best Seller) --}}
                            <div class="product-card group h-full flex flex-col hover:border-[#ee4d2d] transition-colors">
                                
                                {{-- 1. Link Gambar --}}
                                <a href="{{ url('/products/' . $product->slug) }}" class="block relative">
                                    <div class="product-img-container">
                                        <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="product-img" loading="lazy">
                                        
                                        {{-- Badge Petir (Kiri) --}}
                                        <div class="absolute top-0 left-0 bg-[#ee4d2d] text-white w-6 h-6 flex items-center justify-center rounded-br-lg shadow-sm z-10">
                                            <i class="fas fa-bolt text-[10px]"></i>
                                        </div>

                                        {{-- Badge Diskon (Kanan) --}}
                                        @if($hasDiscount)
                                            <div class="absolute top-0 right-0 bg-yellow-400 text-[#ee4d2d] text-[10px] font-bold px-2 py-1 rounded-bl-lg z-10 shadow-sm">
                                                {{ $discountPercent }}%
                                            </div>
                                        @endif
                                    </div>
                                </a>

                                {{-- 2. Info Produk --}}
                                <div class="p-2 flex flex-col flex-grow justify-between bg-white">
                                    
                                    {{-- Judul --}}
                                    <a href="{{ url('/products/' . $product->slug) }}" class="block">
                                        <h3 class="product-title" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </h3>
                                    </a>

                                    {{-- Area Harga & Progress --}}
                                    <div class="mt-1">
                                        {{-- Harga Coret --}}
                                        @if($hasDiscount)
                                            <div class="text-gray-400 text-[10px] line-through h-3 leading-3 overflow-hidden">
                                                Rp{{ number_format($product->original_price, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <div class="h-3"></div>
                                        @endif

                                        {{-- Harga Utama --}}
                                        <div class="text-[#ee4d2d] font-bold text-sm md:text-base mb-2 truncate">
                                            <span class="text-xs font-normal">Rp</span>{{ number_format($product->price, 0, ',', '.') }}
                                        </div>
                                        
                                        {{-- Progress Bar Flash Sale --}}
                                        <div class="relative w-full h-3 bg-red-100 rounded-full overflow-hidden">
                                            <div class="absolute top-0 left-0 h-full bg-[#d0011b]" style="width: {{ $soldPercentage }}%"></div>
                                            <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                                <span class="text-[8px] text-white font-bold tracking-wider drop-shadow-md">SEGERA HABIS</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
    @endif
    
    {{-- === FILTER BEST SELLER (PRODUK TERLARIS) === --}}
    @php
        $bestSellerItems = $products->where('is_bestseller', true);
    @endphp

    @if($bestSellerItems->isNotEmpty())
    <section class="mb-6 bg-white shadow-sm rounded overflow-hidden border border-gray-100">
        {{-- Header --}}
        <div class="p-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg md:text-xl font-bold text-[#d0011b] uppercase tracking-wide flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-400"></i> PRODUK TERLARIS
            </h2>
            <a href="#" class="text-[#ee4d2d] text-xs font-semibold hover:underline">Lihat Semua ></a>
        </div>
        
        {{-- Content --}}
        <div class="p-3">
            <div class="swiper bestSellerSwiper">
                <div class="swiper-wrapper pb-1">
                    @foreach ($bestSellerItems as $product)
                        @php
                            $hasDiscount = ($product->original_price && $product->original_price > $product->price);
                            $discountPercent = $hasDiscount ? round((($product->original_price - $product->price) / $product->original_price) * 100) : 0;
                            $imgSrc = $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/300x300?text=No+Image';
                        @endphp
                        
                        {{-- Slide: h-auto penting agar tinggi kartu rata --}}
                        <div class="swiper-slide h-auto">
                            
                            {{-- Wrapper Kartu: h-full agar mengisi tinggi slide --}}
                            <div class="product-card group h-full flex flex-col">
                                
                                {{-- 1. Gambar (Square) --}}
                                <a href="{{ url('/products/' . $product->slug) }}" class="block relative">
                                    <div class="product-img-container">
                                        <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="product-img" loading="lazy">
                                        
                                        {{-- Badge Mahkota --}}
                                        <div class="absolute top-0 left-0 bg-[#ee4d2d] text-white w-7 h-7 flex items-center justify-center rounded-br-lg shadow-sm z-10 border-b border-r border-white/20">
                                            <i class="fas fa-crown text-xs"></i>
                                        </div>

                                        {{-- Badge Diskon --}}
                                        @if($hasDiscount)
                                            <div class="absolute top-0 right-0 bg-yellow-400 text-[#ee4d2d] text-[10px] font-bold px-2 py-1 rounded-bl-lg z-10 shadow-sm">
                                                {{ $discountPercent }}%
                                            </div>
                                        @endif
                                    </div>
                                </a>

                                {{-- 2. Info Produk (Flex Grow untuk mendorong harga ke bawah) --}}
                                <div class="p-2 flex flex-col flex-grow justify-between bg-white">
                                    
                                    {{-- Judul --}}
                                    <div>
                                        <a href="{{ url('/products/' . $product->slug) }}" class="block">
                                            <h3 class="product-title" title="{{ $product->name }}">
                                                {{ $product->name }}
                                            </h3>
                                        </a>
                                        
                                        {{-- Label Ongkir --}}
                                        <div class="flex flex-wrap gap-1 mb-2 h-4 overflow-hidden">
                                            @if($product->is_free_shipping)
                                                <span class="bg-[#00bfa5] text-white text-[9px] px-1 font-bold rounded-[2px]">Gratis Ongkir</span>
                                            @endif
                                            @if($product->is_shipping_discount)
                                                <span class="border border-[#ee4d2d] text-[#ee4d2d] text-[9px] px-1 rounded-[2px]">COD</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Area Harga & Terjual --}}
                                    <div class="mt-1">
                                        {{-- Harga Coret --}}
                                        @if($hasDiscount)
                                            <div class="text-gray-400 text-[10px] line-through h-3 leading-3">
                                                Rp{{ number_format($product->original_price, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <div class="h-3"></div>
                                        @endif

                                        {{-- Harga Utama & Terjual --}}
                                        <div class="flex items-end justify-between mt-1">
                                            <span class="text-[#ee4d2d] text-sm font-bold truncate">
                                                <span class="text-xs">Rp</span>{{ number_format($product->price, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[10px] text-gray-500 truncate ml-1">
                                                {{ $product->sold_count > 0 ? format_sold($product->sold_count).' Terjual' : '' }}
                                            </span>
                                        </div>
                                    </div>

                                </div> {{-- End P-2 --}}
                            </div> {{-- End Product Card --}}
                        </div> {{-- End Slide --}}
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
    @endif

    <section class="mb-10">
        <div class="bg-[#d0011b] py-3 mb-2 z-30 shadow-md">
    <h2 class="text-center text-white font-bold text-base uppercase tracking-widest">REKOMENDASI</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 md:gap-3">
            @forelse ($products as $product)
    @php
        $hasDiscount = ($product->original_price && $product->original_price > $product->price);
        $discountPercent = $hasDiscount ? round((($product->original_price - $product->price) / $product->original_price) * 100) : 0;
        $imgSrc = $product->image_url ? asset('public/storage/' . $product->image_url) : 'https://placehold.co/300x300?text=No+Image';
    @endphp

    {{-- WRAPPER UTAMA GANTI JADI DIV (Bukan A lagi) --}}
    <div class="product-card group">
        
        {{-- 1. Link Gambar --}}
        <a href="{{ url('/products/' . $product->slug) }}" class="block">
            <div class="product-img-container">
                <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="product-img" loading="lazy">

                {{-- Badge Diskon --}}
                @if($hasDiscount)
                    <div class="absolute top-0 right-0 bg-yellow-400 text-[#ee4d2d] text-[10px] font-bold px-2 py-1 rounded-bl-lg z-10 shadow-sm">
                        {{ $discountPercent }}%
                    </div>
                @endif

                {{-- Label --}}
                @if($product->is_promo)
                    <div class="absolute bottom-0 left-0 bg-[#ee4d2d] text-white text-[9px] font-bold px-1.5 py-0.5 z-10">Star+</div>
                @elseif($product->is_bestseller)
                    <div class="absolute bottom-0 left-0 bg-[#ee4d2d] text-white text-[9px] font-bold px-1.5 py-0.5 z-10">Terlaris</div>
                @endif
            </div>
        </a>

        {{-- 2. Info Produk --}}
        <div class="p-2 flex flex-col flex-grow justify-between bg-white">
            
            {{-- Link Judul --}}
            <a href="{{ url('/products/' . $product->slug) }}" class="block">
                <h3 class="product-title" title="{{ $product->name }}">
                    {{ $product->name }}
                </h3>
                
                <div class="flex flex-wrap gap-1 mb-1 h-4 overflow-hidden">
                    @if($product->is_free_shipping)
                        <span class="bg-[#00bfa5] text-white text-[9px] px-1 font-bold rounded-[2px]">Gratis Ongkir</span>
                    @endif
                    @if($product->is_shipping_discount)
                        <span class="border border-[#ee4d2d] text-[#ee4d2d] text-[9px] px-1 rounded-[2px]">COD</span>
                    @endif
                </div>
            </a>

            <div class="mt-1">
                {{-- Harga Coret --}}
                @if($hasDiscount)
                    <div class="text-gray-400 text-[10px] line-through h-3 leading-3">
                        Rp{{ number_format($product->original_price, 0, ',', '.') }}
                    </div>
                @else
                    <div class="h-3"></div>
                @endif

                {{-- Harga Utama & Terjual --}}
                <div class="flex items-end justify-between mt-1">
                    <span class="text-[#ee4d2d] text-sm font-bold truncate">
                        <span class="text-xs">Rp</span>{{ number_format($product->price, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-gray-500 truncate ml-1">
                        {{ $product->sold_count > 0 ? $product->sold_count.' Terjual' : '' }}
                    </span>
                </div>

                {{-- TOMBOL KERANJANG (BARU) --}}
                <form action="{{ route('cart.add') }}" method="POST" class="mt-2">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    
                    <button type="submit" class="btn-cart">
                        <i class="fas fa-cart-plus"></i> + Keranjang
                    </button>
                </form>

            </div>
        </div>
    </div>
@empty
    <div class="col-span-full py-10 text-center text-gray-400 bg-white rounded">
        <p>Belum ada produk.</p>
    </div>
@endforelse
        </div>

        <div class="mt-6">
            {{ $products->links('pagination::tailwind') }}
        </div>
    </section>
    
    <div class="h-16 md:h-0"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // --- LOGIKA COUNTDOWN 24 JAM ---
    function startFlashSaleCountdown() {
        // Durasi 24 Jam dalam milidetik
        const duration = 24 * 60 * 60 * 1000; 
        
        // Cek apakah sudah ada waktu akhir yang tersimpan
        let storedEndTime = localStorage.getItem('sancakaFlashSaleEnd');
        let endTime;

        // Jika tidak ada atau waktu sudah lewat, setel ulang 24 jam dari SEKARANG
        if (!storedEndTime || new Date().getTime() > storedEndTime) {
            endTime = new Date().getTime() + duration;
            localStorage.setItem('sancakaFlashSaleEnd', endTime);
        } else {
            endTime = parseInt(storedEndTime);
        }

        // Fungsi update setiap detik
        const timerInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = endTime - now;

            // Jika waktu habis, reset lagi ke 24 jam (Looping)
            if (distance < 0) {
                endTime = new Date().getTime() + duration;
                localStorage.setItem('sancakaFlashSaleEnd', endTime);
                return;
            }

            // Hitung Jam, Menit, Detik
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Tampilkan ke elemen HTML (tambah angka 0 di depan jika satuan)
            const elHours = document.getElementById('fs-hours');
            const elMinutes = document.getElementById('fs-minutes');
            const elSeconds = document.getElementById('fs-seconds');

            if(elHours) elHours.innerText = hours < 10 ? "0" + hours : hours;
            if(elMinutes) elMinutes.innerText = minutes < 10 ? "0" + minutes : minutes;
            if(elSeconds) elSeconds.innerText = seconds < 10 ? "0" + seconds : seconds;

        }, 1000);
    }

    // Jalankan fungsi
    startFlashSaleCountdown();
    
    // ... script swiper flash sale yang sudah ada ...

    // Best Seller Swiper (Baru)
    new Swiper(".bestSellerSwiper", { 
        slidesPerView: 2.2, // Mobile
        spaceBetween: 10, 
        navigation: { 
            nextEl: ".bestseller-next", // Class tombol unique
            prevEl: ".bestseller-prev" 
        }, 
        breakpoints: { 
            640: { slidesPerView: 3, spaceBetween: 10 }, 
            768: { slidesPerView: 4, spaceBetween: 15 }, 
            1024: { slidesPerView: 6, spaceBetween: 15 } 
        } 
    });
    
    // Hero Slider
    new Swiper(".heroSwiper", { 
        loop: true, 
        effect: "fade", 
        autoplay: { delay: 4000, disableOnInteraction: false }, 
        pagination: { el: ".swiper-pagination", clickable: true }, 
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } 
    });

   // Flash Sale Swiper (UPDATE INI)
    new Swiper(".flashSaleSwiper", { 
        slidesPerView: 2.2, // <--- INI YANG BIKIN GAMBAR BESAR DI HP
        spaceBetween: 10, 
        pagination: { 
            el: ".swiper-pagination", 
            clickable: true 
        },
        breakpoints: { 
            640: { slidesPerView: 3, spaceBetween: 10 }, // Tablet
            768: { slidesPerView: 4, spaceBetween: 15 }, // Laptop Kecil
            1024: { slidesPerView: 6, spaceBetween: 15 } // Desktop Besar
        } 
    });

    // Categories
    new Swiper(".categoriesSwiper", { 
        loop: false, 
        slidesPerView: 1, 
        pagination: { el: ".swiper-pagination", clickable: true },
    });
});

 // 2. Logic Tabs (Prabayar vs Pascabayar) - SESUAI DESAIN BARU
    function switchTab(type) {
        const btnPre = document.getElementById('tab-prepaid');
        const btnPost = document.getElementById('tab-postpaid');
        const conPre = document.getElementById('content-prepaid');
        const conPost = document.getElementById('content-postpaid');

        if(type === 'prepaid') {
            btnPre.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            btnPost.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            
            conPre.classList.remove('hidden');
            conPost.classList.add('hidden');
        } else {
            btnPost.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-blue-600 text-blue-600 transition hover:bg-gray-50 flex items-center justify-center gap-2";
            btnPre.className = "flex-1 py-4 text-center font-bold text-sm md:text-base border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition hover:bg-gray-50 flex items-center justify-center gap-2";

            conPost.classList.remove('hidden');
            conPre.classList.add('hidden');
        }
    }

    // 3. Logic "Lihat Semua" (Expand Grid)
    function toggleExpand(type) {
        const wrapper = document.getElementById('grid-' + type + '-wrapper');
        const btn = document.getElementById('btn-expand-' + type);
        const items = wrapper.querySelectorAll('.menu-item-' + type);
        const isExpanded = btn.getAttribute('data-expanded') === 'true';

        items.forEach((item, index) => {
            if (index >= 16) {
                if (isExpanded) item.classList.add('hidden');
                else item.classList.remove('hidden');
            }
        });

        if (isExpanded) {
            btn.innerHTML = '<span>Lihat Semua Layanan</span> <i class="fas fa-chevron-down text-[10px]"></i>';
            btn.setAttribute('data-expanded', 'false');
        } else {
            btn.innerHTML = '<span>Sembunyikan</span> <i class="fas fa-chevron-up text-[10px]"></i>';
            btn.setAttribute('data-expanded', 'true');
        }
    }

    // Default: Set active tab berdasarkan halaman (Jika halaman pascabayar, buka tab pascabayar)
    @if($isPostpaid) switchTab('postpaid'); @endif

</script>
@endpush