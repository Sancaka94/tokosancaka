@extends('layouts.marketplace')

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
    
    <section class="mb-10" data-aos="fade-up">
        <div class="bg-white p-6 rounded-2xl shadow-md border-t-4 border-red-500">
            <h2 class="text-xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="fas fa-mobile-alt text-blue-500 mr-2"></i> Layanan Top Up & Tagihan
            </h2>
            
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                {{-- MENU PULSA --}}
                <a href="{{ url('/etalase/ppob/digital/pulsa') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-blue-500 transition bg-blue-50">
                    <i class="fas fa-mobile-screen-button text-3xl text-blue-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Pulsa</span>
                </a>

                {{-- MENU DATA --}}
                <a href="{{ url('/etalase/ppob/digital/data') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-green-500 transition bg-green-50">
                    <i class="fas fa-wifi text-3xl text-green-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Paket Data</span>
                </a>

                {{-- MENU PLN TOKEN --}}
                <a href="{{ url('/etalase/ppob/digital/pln-token') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-yellow-500 transition bg-yellow-50">
                    <i class="fas fa-bolt text-3xl text-yellow-500 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Token PLN</span>
                </a>

                {{-- MENU PLN PASCABAYAR --}}
                <a href="{{ url('/etalase/ppob/digital/pln-pascabayar') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-orange-500 transition bg-orange-50">
                    <i class="fas fa-file-invoice-dollar text-3xl text-orange-500 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">PLN Pasca</span>
                </a>

                {{-- MENU PDAM --}}
                <a href="{{ url('/etalase/ppob/digital/pdam') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-cyan-500 transition bg-cyan-50">
                    <i class="fas fa-faucet text-3xl text-cyan-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">PDAM</span>
                </a>

                {{-- MENU E-MONEY --}}
                <a href="{{ url('/etalase/ppob/digital/e-money') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-purple-500 transition bg-purple-50">
                    <i class="fas fa-wallet text-3xl text-purple-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">E-Wallet</span>
                </a>

                {{-- MENU GAMES --}}
                <a href="{{ url('/etalase/ppob/digital/voucher-game') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-red-500 transition bg-red-50">
                    <i class="fas fa-gamepad text-3xl text-red-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">Voucher Game</span>
                </a>

                {{-- MENU TV / STREAMING --}}
                <a href="{{ url('/etalase/ppob/digital/streaming') }}" class="ppob-icon flex flex-col items-center p-4 border rounded-xl hover:shadow-lg hover:border-pink-500 transition bg-pink-50">
                    <i class="fas fa-tv text-3xl text-pink-600 mb-2"></i>
                    <span class="text-sm font-bold text-gray-700 text-center">TV Kabel</span>
                </a>
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
</script>
@endpush